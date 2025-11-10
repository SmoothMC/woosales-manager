<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Woo_Sales_Manager_DB {
    public $table_agents;
    public $table_commissions;
    public function __construct(){
        global $wpdb;
        $this->table_agents = $wpdb->prefix . 'wsm_agents';
        $this->table_commissions = $wpdb->prefix . 'wsm_commissions';
    }
}
