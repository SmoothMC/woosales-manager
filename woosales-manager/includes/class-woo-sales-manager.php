<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Woo_Sales_Manager {
    private static $instance;
    public $db; public $agents; public $commissions; public $payouts; public $ui;

    public static function instance(){
        if(!self::$instance) self::$instance = new self();
        return self::$instance;
    }
    private function __construct(){
        require_once __DIR__ . '/class-woo-sales-manager-db.php';
        require_once __DIR__ . '/class-woo-sales-manager-agents.php';
        require_once __DIR__ . '/class-woo-sales-manager-commissions.php';
        require_once __DIR__ . '/class-woo-sales-manager-payouts.php';
        require_once __DIR__ . '/class-woo-sales-manager-ui.php';

        $this->db = new Woo_Sales_Manager_DB();
        $this->agents = new Woo_Sales_Manager_Agents( $this->db );
        $this->commissions = new Woo_Sales_Manager_Commissions( $this->db, $this->agents );
        $this->payouts = new Woo_Sales_Manager_Payouts( $this->db, $this->agents, $this->commissions );
        $this->ui = new Woo_Sales_Manager_UI( $this->db, $this->agents, $this->commissions, $this->payouts );
    }
}
