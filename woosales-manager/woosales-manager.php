<?php
/**
 * Plugin Name: WooSales Manager
 * Description: Global sales commissions for WooCommerce, with JSON-based self-updater.
 * Version: 1.2.1
 * Author: Mikka | zzzooo Studio
 * Text Domain: woo-sales-manager
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WSM_VERSION', '1.2.1' );
define( 'WSM_PATH', plugin_dir_path( __FILE__ ) );
define( 'WSM_URL', plugin_dir_url( __FILE__ ) );
define( 'WSM_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Activation: only run once
 */
register_activation_hook( __FILE__, function() {
    require_once __DIR__ . '/includes/class-woo-sales-manager-installer.php';
    Woo_Sales_Manager_Installer::activate();
});

/**
 * Init
 */
add_action('plugins_loaded', function(){

    load_plugin_textdomain('woo-sales-manager', false, dirname(WSM_BASENAME) . '/languages');

    if ( ! class_exists('WooCommerce') ) {
        add_action('admin_notices', function(){
            echo '<div class="notice notice-error"><p>' .
                esc_html__( 'WooSales Manager requires WooCommerce.', 'woo-sales-manager' ) .
            '</p></div>';
        });
        return;
    }

    // ✅ Load Installer + Updater FIRST
    require_once __DIR__ . '/includes/class-woo-sales-manager-installer.php';
    require_once __DIR__ . '/includes/class-woo-sales-manager-updater.php';

    // ✅ Load main plugin core (this loads DB / Agents / UI etc.)
    require_once __DIR__ . '/includes/class-woo-sales-manager.php';

    // ✅ Create core plugin instance
    $core = Woo_Sales_Manager::instance();

    // ✅ Perform DB schema updates (run every load)
    Woo_Sales_Manager_Installer::upgrade();

    // ✅ Enable updater
    new Woo_Sales_Manager_Updater();
});
