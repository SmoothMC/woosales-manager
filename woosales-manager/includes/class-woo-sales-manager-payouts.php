<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class Woo_Sales_Manager_Payouts {
    private $db; private $agents; private $commissions;
    public function __construct( $db, $agents, $commissions ){
        $this->db=$db; $this->agents=$agents; $this->commissions=$commissions;
        add_action('admin_post_wsm_mark_paid', array($this,'mark_paid'));
        add_action('admin_post_wsm_export_csv', array($this,'export_csv'));
    }
    public function mark_paid(){
        if( ! current_user_can('manage_woocommerce') || ! check_admin_referer('wsm_mark_paid') ) wp_die('Not allowed');
        global $wpdb;
        $agent_id = absint($_POST['agent_id'] ?? 0);
        $date_from = sanitize_text_field($_POST['date_from'] ?? '');
        $date_to = sanitize_text_field($_POST['date_to'] ?? '');
        $where = "status='approved'"; $p = array();
        if($agent_id){ $where.=" AND agent_id=%d"; $p[]=$agent_id; }
        if($date_from){ $where.=" AND created_at >= %s"; $p[]=$date_from; }
        if($date_to){ $where.=" AND created_at <= %s"; $p[]=$date_to; }
        $sql = "UPDATE {$this->db->table_commissions} SET status='paid', updated_at=%s WHERE $where";
        array_unshift($p, current_time('mysql'));
        $wpdb->query( $wpdb->prepare($sql, $p) );
        wp_redirect( admin_url('admin.php?page=wsm-sales&tab=payouts&paid=1') ); exit;
    }
    public function export_csv(){
        if( ! current_user_can('manage_woocommerce') || ! check_admin_referer('wsm_export_csv') ) wp_die('Not allowed');
        $agent_id = absint($_GET['agent_id'] ?? 0);
        $date_from = sanitize_text_field($_GET['date_from'] ?? '');
        $date_to = sanitize_text_field($_GET['date_to'] ?? '');
        $list = $this->commissions->list(array('agent_id'=>$agent_id,'status'=>'approved','date_from'=>$date_from,'date_to'=>$date_to,'per_page'=>100000,'paged'=>1));
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=wsm-payouts.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, array('ID','Order','Agent','Amount','Rate','Currency','Status','Created'));
        foreach($list['rows'] as $r){
            fputcsv($out, array($r->id,$r->order_id,$r->agent_id,$r->amount,$r->rate,$r->currency,$r->status,$r->created_at));
        }
        fclose($out); exit;
    }
}
