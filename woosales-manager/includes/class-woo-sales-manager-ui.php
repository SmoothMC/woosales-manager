<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Woo_Sales_Manager_UI {
    private $db; private $agents; private $commissions; private $payouts;
    public function __construct( $db, $agents, $commissions, $payouts ){
        $this->db=$db; $this->agents=$agents; $this->commissions=$commissions; $this->payouts=$payouts;
        add_action('admin_menu', array($this,'menu'), 99);
        add_action('admin_enqueue_scripts', array($this,'assets'));
        add_filter('woocommerce_screen_ids', function($ids){
            $screen = function_exists('get_current_screen') ? get_current_screen() : null;
            if($screen && str_starts_with($screen->id,'woocommerce_page_wsm-sales')){ $ids[]=$screen->id; }
            return $ids;
        });
    }
    public function assets(){}
    public function menu(){
        $title = esc_html__( 'Sales', 'woo-sales-manager' );
        add_submenu_page('woocommerce', $title, $title, 'manage_woocommerce', 'wsm-sales', array($this,'render_page'), 56);
    }
    public function render_page(){
        $tab = sanitize_key($_GET['tab'] ?? 'dashboard');
        echo '<div class="wrap"><h1>'.esc_html__('WooSales Manager','woo-sales-manager').'</h1>';
        echo '<h2 class="nav-tab-wrapper">';
        $tabs = array('dashboard'=>__('Dashboard','woo-sales-manager'),'agents'=>__('Agents','woo-sales-manager'),'payouts'=>__('Payouts','woo-sales-manager'),'settings'=>__('Settings','woo-sales-manager'));
        foreach($tabs as $t=>$label){
            $active = $tab===$t ? ' nav-tab-active' : '';
            echo '<a class="nav-tab'.$active.'" href="'.esc_url(admin_url('admin.php?page=wsm-sales&tab='.$t)).'">'.esc_html($label).'</a>';
        }
        echo '</h2>';
        switch($tab){
            case 'agents': include WSM_PATH.'admin/views/agents.php'; break;
            case 'payouts': include WSM_PATH.'admin/views/payouts.php'; break;
            case 'settings': include WSM_PATH.'admin/views/settings.php'; break;
            default: include WSM_PATH.'admin/views/dashboard.php';
        }
        echo '</div>';
    }
}
