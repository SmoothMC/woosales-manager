<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Woo_Sales_Manager_Commissions {
    private $db; private $agents;
    public function __construct( $db, $agents ){
        $this->db = $db; $this->agents = $agents;
        add_action('woocommerce_order_status_completed', array($this,'maybe_create_for_order'));
        add_action('woocommerce_order_status_cancelled', array($this,'reject_for_order'));
        add_action('woocommerce_order_refunded', array($this,'reject_for_order'));
    }
    private function settings(){
        $opt = get_option('wsm_settings', array());
        $defaults = array('default_base'=>'net','allow_multi_agents'=>'no','assignment_mode'=>'all_agents');
        return wp_parse_args($opt,$defaults);
    }
    private function order_base_amount( WC_Order $order ){
        $s = $this->settings();
        if($s['default_base']==='gross') return (float)$order->get_total();
        return (float)$order->get_total() - (float)$order->get_total_tax();
    }
    public function maybe_create_for_order( $order_id ){
        $order = wc_get_order($order_id); if(!$order) return;
        $settings = $this->settings();
        $currency = $order->get_currency();
        $base = $this->order_base_amount($order);
        $order_total = (float)$order->get_total();
        $agents = $settings['assignment_mode']==='all_agents' ? $this->agents->all_active() : array();
        if( empty($agents) ) return;
        global $wpdb;
        foreach($agents as $a){
            $exists = $wpdb->get_var( $wpdb->prepare("SELECT id FROM {$this->db->table_commissions} WHERE order_id=%d AND agent_id=%d", $order_id, $a->id) );
            if($exists){
                $wpdb->update( $this->db->table_commissions, array('status'=>'approved','updated_at'=>current_time('mysql')), array('id'=>$exists) );
                continue;
            }
            $rate = (float)$a->rate;
            $amount = round( $base * $rate, wc_get_price_decimals() );
            $wpdb->insert( $this->db->table_commissions, array(
                'order_id'=>$order_id,'agent_id'=>$a->id,'order_total'=>$order_total,'taxable_base'=>$base,'rate'=>$rate,'amount'=>$amount,
                'status'=>'approved','currency'=>$currency,'created_at'=>current_time('mysql'),'updated_at'=>current_time('mysql')
            ));
        }
    }
    public function reject_for_order( $order_id ){
        global $wpdb;
        $wpdb->query( $wpdb->prepare("UPDATE {$this->db->table_commissions} SET status='rejected', updated_at=%s WHERE order_id=%d AND status IN ('pending','approved')", current_time('mysql'), $order_id) );
    }
    public function list( $args = array() ){
        global $wpdb;
        $a = wp_parse_args($args, array('agent_id'=>0,'status'=>'','date_from'=>'','date_to'=>'','paged'=>1,'per_page'=>50));
        $where="WHERE 1=1"; $p=array();
        if($a['agent_id']){ $where.=" AND agent_id=%d"; $p[]=$a['agent_id']; }
        if($a['status']){ $where.=" AND status=%s"; $p[]=$a['status']; }
        if($a['date_from']){ $where.=" AND created_at >= %s"; $p[]=$a['date_from']; }
        if($a['date_to']){ $where.=" AND created_at <= %s"; $p[]=$a['date_to']; }
        $offset = (max(1,(int)$a['paged'])-1) * (int)$a['per_page'];
        $sql = "SELECT * FROM {$this->db->table_commissions} $where ORDER BY id DESC LIMIT %d OFFSET %d";
        $rows = $wpdb->get_results( $wpdb->prepare($sql, array_merge($p,[(int)$a['per_page'], (int)$offset])) );
        $total = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$this->db->table_commissions} $where", $p) );
        return array('rows'=>$rows,'total'=>$total);
    }
    public function totals( $args = array() ){
        global $wpdb; $a = wp_parse_args($args, array('agent_id'=>0,'status'=>'approved','date_from'=>'','date_to'=>''));
        $where="WHERE 1=1"; $p=array();
        if($a['agent_id']){ $where.=" AND agent_id=%d"; $p[]=$a['agent_id']; }
        if($a['status']){ $where.=" AND status=%s"; $p[]=$a['status']; }
        if($a['date_from']){ $where.=" AND created_at >= %s"; $p[]=$a['date_from']; }
        if($a['date_to']){ $where.=" AND created_at <= %s"; $p[]=$a['date_to']; }
        return (float)$wpdb->get_var( $wpdb->prepare("SELECT SUM(amount) FROM {$this->db->table_commissions} $where", $p) );
    }
}
