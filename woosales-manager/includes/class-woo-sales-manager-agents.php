<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Woo_Sales_Manager_Agents {

    private $db;

    public function __construct( $db ){
        $this->db = $db;

        add_action('admin_post_wsm_save_agent', array($this,'handle_save'));
        add_action('admin_post_wsm_delete_agent', array($this,'handle_delete'));
    }

    /**
     * Get all active agents
     */
    public function all_active(){
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->db->table_agents} WHERE is_active = 1 ORDER BY id ASC");
    }

    /**
     * Get agent by ID
     */
    public function get( $id ){
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->db->table_agents} WHERE id=%d",
            $id
        ));
    }

    /**
     * Get agent by linked WP user account
     */
    public function get_by_user( $user_id ){
        global $wpdb;
        if (! $user_id) return null;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->db->table_agents} WHERE user_id=%d AND is_active=1 LIMIT 1",
            $user_id
        ));
    }

    /**
     * Save agent handler
     */
    public function handle_save(){
        if ( ! current_user_can('manage_woocommerce') || ! check_admin_referer('wsm_save_agent') ) {
            wp_die('Not allowed');
        }

        global $wpdb;

        $id = absint($_POST['id'] ?? 0);

        $data = array(
            'name'       => sanitize_text_field($_POST['name'] ?? ''),
            'email'      => sanitize_email($_POST['email'] ?? ''),
            'rate'       => floatval($_POST['rate'] ?? 0) / 100,
            'is_active'  => isset($_POST['is_active']) ? 1 : 0,
            'user_id'    => absint($_POST['user_id'] ?? 0), // ✅ NEW FIELD
            'updated_at' => current_time('mysql'),
        );

        if ($id) {
            $wpdb->update($this->db->table_agents, $data, array('id'=>$id));
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($this->db->table_agents, $data);
        }

        wp_redirect(admin_url('admin.php?page=wsm-sales&tab=agents&updated=1'));
        exit;
    }

    /**
     * Delete agent
     */
    public function handle_delete(){
        if ( ! current_user_can('manage_woocommerce') || ! check_admin_referer('wsm_delete_agent') ) {
            wp_die('Not allowed');
        }

        global $wpdb;
        $id = absint($_GET['id'] ?? 0);

        if($id){
            $wpdb->delete($this->db->table_agents, array('id'=>$id));
        }

        wp_redirect(admin_url('admin.php?page=wsm-sales&tab=agents&deleted=1'));
        exit;
    }
}
