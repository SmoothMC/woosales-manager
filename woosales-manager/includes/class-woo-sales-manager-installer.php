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
                email VARCHAR(191),
                rate DECIMAL(6,4) NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                user_id BIGINT UNSIGNED NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY user_idx (user_id)
                ) {$charset};";

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
            commission_month CHAR(7) NOT NULL,
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

    public static function upgrade() {
    
        global $wpdb;
    
        // Agents: user_id
        $agents = $wpdb->prefix . 'wsm_agents';
    
        $has_user_id = $wpdb->get_var("SHOW COLUMNS FROM $agents LIKE 'user_id'");
        if (!$has_user_id) {
            $wpdb->query("ALTER TABLE $agents ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER is_active;");
            $wpdb->query("ALTER TABLE $agents ADD INDEX user_idx (user_id);");
        }
    
        // ✅ Commissions: commission_month
        $comms = $wpdb->prefix . 'wsm_commissions';
    
        $has_month = $wpdb->get_var("SHOW COLUMNS FROM $comms LIKE 'commission_month'");
        if (!$has_month) {
            $wpdb->query("ALTER TABLE $comms ADD commission_month CHAR(7) NOT NULL AFTER updated_at;");
        }
    }

}
