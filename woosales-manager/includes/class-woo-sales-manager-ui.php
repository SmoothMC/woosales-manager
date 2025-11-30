<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Woo_Sales_Manager_UI {

    private $db;
    private $agents;
    private $commissions;
    private $payouts;

    public function __construct( $db, $agents, $commissions, $payouts ){
        $this->db = $db;
        $this->agents = $agents;
        $this->commissions = $commissions;
        $this->payouts = $payouts;

        // Admin UI
        add_action('admin_menu', array($this,'menu'), 99);
        add_action('admin_enqueue_scripts', array($this,'assets'));

        add_filter('woocommerce_screen_ids', function($ids){
            $screen = function_exists('get_current_screen') ? get_current_screen() : null;
            if($screen && str_starts_with($screen->id,'woocommerce_page_wsm-sales')){
                $ids[] = $screen->id;
            }
            return $ids;
        });

        // ✅ Frontend: My Account -> My Sales
        add_action('init', array($this,'add_account_endpoint'));
        add_filter('query_vars', array($this,'add_query_var'));
        add_filter('woocommerce_account_menu_items', array($this,'add_my_account_menu_item'));
        add_action('woocommerce_account_my-sales_endpoint', array($this,'render_my_account_sales'));
        add_action('add_meta_boxes', [$this,'add_order_agents_metabox']);
        add_action('save_post_shop_order', [$this,'save_order_agents_metabox'], 20, 2);
    }

    public function assets(){}

    public function add_order_agents_metabox(){
        add_meta_box(
            'wsm_order_agents',
            __('Sales Agents','woo-sales-manager'),
            [$this,'render_order_agents_metabox'],
            'shop_order',
            'side',
            'default'
        );
    }

    public function render_order_agents_metabox($post){
    
        wp_nonce_field('wsm_assign_agents','wsm_assign_agents_nonce');
    
        $agents = $this->agents->all_active();
    
        // ✅ Zuerst: gespeichertes Post-Meta auslesen
        $assigned = get_post_meta($post->ID,'_wsm_agents', true);
        $assigned = is_array($assigned) ? $assigned : [];
    
        // ✅ Fallback: Wenn kein Meta existiert → aus bestehenden Commissions lesen
        if ( empty($assigned) ) {
            global $wpdb;
    
            $assigned = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT DISTINCT agent_id 
                     FROM {$this->db->table_commissions} 
                     WHERE order_id = %d",
                    $post->ID
                )
            );
    
            $assigned = array_map('intval', $assigned);
        }
    
        echo '<p>'.__('Assign sales agents to this order','woo-sales-manager').'</p>';
    
        foreach($agents as $a){
    
            $checked = in_array( (int)$a->id, $assigned, true ) ? 'checked' : '';
    
            echo '<label style="display:block;margin-bottom:4px">';
            echo '<input type="checkbox" name="wsm_agents[]" value="'.esc_attr($a->id).'" '.$checked.'> ';
            echo esc_html($a->name).' ('.($a->rate*100).'%)';
            echo '</label>';
    
        }
    }


    public function save_order_agents_metabox($post_id, $post){
    
        if(
            defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ||
            !isset($_POST['wsm_assign_agents_nonce']) ||
            !wp_verify_nonce($_POST['wsm_assign_agents_nonce'],'wsm_assign_agents') ||
            !current_user_can('manage_woocommerce')
        ){
            return;
        }
    
        $agents = array_map('absint', $_POST['wsm_agents'] ?? []);
    
        update_post_meta($post_id,'_wsm_agents',$agents);
    
        // Generiere / aktualisiere Commissions
        $this->commissions->create_for_manual_assignment($post_id, $agents);
    }


    public function menu(){
        $title = esc_html__( 'Sales', 'woo-sales-manager' );
        add_submenu_page(
            'woocommerce',
            $title,
            $title,
            'manage_woocommerce',
            'wsm-sales',
            array($this,'render_page'),
            56
        );
    }

    public function render_page(){
    
        // ✅ Einzel-Commission öffnen
        if ( isset($_GET['commission_id']) && absint($_GET['commission_id']) ) {
            $commission_id = absint($_GET['commission_id']);
            include WSM_PATH . 'admin/views/edit-commission.php';
            return;
        }
        
        $tab = sanitize_key($_GET['tab'] ?? 'dashboard');

        echo '<div class="wrap"><h1>'.esc_html__('WooSales Manager','woo-sales-manager').'</h1>';
        echo '<h2 class="nav-tab-wrapper">';
        $tabs = array(
            'dashboard'=>__('Dashboard','woo-sales-manager'),
            'agents'   =>__('Agents','woo-sales-manager'),
            'payouts'  =>__('Payouts','woo-sales-manager'),
            'settings' =>__('Settings','woo-sales-manager')
        );

        foreach($tabs as $t => $label){
            $active = ($tab === $t) ? ' nav-tab-active' : '';
            echo '<a class="nav-tab'.$active.'" href="'.esc_url(admin_url('admin.php?page=wsm-sales&tab='.$t)).'">'.esc_html($label).'</a>';
        }

        echo '</h2>';

        switch($tab){
            case 'agents':   include WSM_PATH.'admin/views/agents.php'; break;
            case 'payouts':  include WSM_PATH.'admin/views/payouts.php'; break;
            case 'settings': include WSM_PATH.'admin/views/settings.php'; break;
            default:         include WSM_PATH.'admin/views/dashboard.php';
        }

        echo '</div>';
    }

    /* --------------------------------------------------------
     * Frontend: My Account → "My Sales"
     * -------------------------------------------------------- */

    public function add_account_endpoint(){
        add_rewrite_endpoint('my-sales', EP_ROOT | EP_PAGES);
    }

    public function add_query_var($vars){
        $vars[] = 'my-sales';
        return $vars;
    }

    public function add_my_account_menu_item($items){
        $agent = $this->agents->get_by_user(get_current_user_id());
        if ($agent) {
            $new = [];
            foreach($items as $key => $label){
                $new[$key] = $label;
                if($key === 'dashboard'){
                    $new['my-sales'] = __('My Sales','woo-sales-manager');
                }
            }
            return $new;
        }
        return $items;
    }

    public function render_my_account_sales(){
        $user_id = get_current_user_id();
        $agent = $this->agents->get_by_user($user_id);

        if (! $agent){
            echo '<p>'.esc_html__('No sales assigned.','woo-sales-manager').'</p>';
            return;
        }

        $items = $this->commissions->by_agent($agent->id);

        wc_get_template(
            'myaccount/sales-dashboard.php',
            ['agent'=>$agent, 'items'=>$items],
            '',
            WSM_PATH . 'templates/'
        );
    }
}
