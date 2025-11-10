<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Woo_Sales_Manager_Installer {
    public static function activate(){
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $agents = $wpdb->prefix . 'wsm_agents';
        $comms  = $wpdb->prefix . 'wsm_commissions';

        $sql_agents = "CREATE TABLE $agents (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            email VARCHAR(191) DEFAULT '' NOT NULL,
            rate DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY is_active (is_active)
        ) $charset;";

        $sql_commissions = "CREATE TABLE $comms (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT UNSIGNED NOT NULL,
            agent_id BIGINT UNSIGNED NOT NULL,
            order_total DECIMAL(20,6) NOT NULL DEFAULT 0,
            taxable_base DECIMAL(20,6) NOT NULL DEFAULT 0,
            rate DECIMAL(7,4) NOT NULL DEFAULT 0.0000,
            amount DECIMAL(20,6) NOT NULL DEFAULT 0,
            status ENUM('pending','approved','rejected','paid') NOT NULL DEFAULT 'pending',
            currency VARCHAR(10) NOT NULL DEFAULT 'EUR',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_order_agent (order_id, agent_id),
            PRIMARY KEY (id),
            KEY agent_id (agent_id),
            KEY status (status)
        ) $charset;";

        dbDelta( $sql_agents );
        dbDelta( $sql_commissions );

        add_option( 'wsm_settings', array(
            'default_base' => 'net',
            'allow_multi_agents' => 'no',
            'assignment_mode' => 'all_agents',
            'payout_period_default' => 'month',
            'menu_title' => 'Sales',
            'update_json_url' => '',
        ) );

        $wpdb->insert( $agents, array(
            'name' => 'Default Agent',
            'email' => '',
            'rate' => 0.0500,
            'is_active' => 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ));
    }
}
