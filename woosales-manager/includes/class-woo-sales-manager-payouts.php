<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Woo_Sales_Manager_Payouts {

    private $db;
    private $agents;
    private $commissions;

    public function __construct( $db, $agents, $commissions ){
        $this->db = $db;
        $this->agents = $agents;
        $this->commissions = $commissions;

        add_action('admin_post_wsm_mark_paid', array($this,'mark_paid'));
        add_action('admin_post_wsm_export_csv', array($this,'export_csv'));

        // Optional: PDF (Druckansicht)
        add_action('admin_post_wsm_export_pdf', array($this,'export_pdf'));
    }

    private function get_period_from_request( array $src ) : array {

        $mode = sanitize_key($src['mode'] ?? 'month');
        if (!in_array($mode, ['month','quarter'], true)) $mode = 'month';

        $month = sanitize_text_field($src['month'] ?? '');
        $quarter = sanitize_text_field($src['quarter'] ?? '');

        if ($mode === 'month' && !$month) {
            $month = date('Y-m');
        }

        $range = null;

        if ($mode === 'quarter') {

            if (!preg_match('/^\d{4}-Q[1-4]$/', $quarter)) {
                $y = date('Y');
                $q = (int)ceil((int)date('n')/3);
                $quarter = $y . '-Q' . $q;
            }

            $y = substr($quarter, 0, 4);
            $q = (int)substr($quarter, 6, 1);

            $start_m = str_pad((($q-1)*3)+1, 2, '0', STR_PAD_LEFT);
            $end_m   = str_pad((($q-1)*3)+3, 2, '0', STR_PAD_LEFT);

            $range = [
                'start' => $y . '-' . $start_m,
                'end'   => $y . '-' . $end_m,
                'label' => $quarter,
            ];
        }

        return [
            'mode'   => $mode,
            'month'  => $month,
            'quarter'=> $quarter,
            'range'  => $range,
        ];
    }

    public function mark_paid(){

        if( ! current_user_can('manage_woocommerce') || ! check_admin_referer('wsm_mark_paid') ) {
            wp_die('Not allowed');
        }

        global $wpdb;

        $agent_id = absint($_POST['agent_id'] ?? 0);
        $period = $this->get_period_from_request($_POST);

        $where = "status='approved'";
        $p = [];

        if ($agent_id) {
            $where .= " AND agent_id=%d";
            $p[] = $agent_id;
        }

        if ($period['mode'] === 'month') {
            $where .= " AND commission_month=%s";
            $p[] = $period['month'];
        } else {
            $where .= " AND commission_month >= %s AND commission_month <= %s";
            $p[] = $period['range']['start'];
            $p[] = $period['range']['end'];
        }

        $sql = "UPDATE {$this->db->table_commissions}
                SET status='paid', updated_at=%s, paid_at=%s
                WHERE $where";

        array_unshift($p, current_time('mysql'), current_time('mysql'));

        $wpdb->query( $wpdb->prepare($sql, $p) );

        $args = [
            'page' => 'wsm-sales',
            'tab'  => 'payouts',
            'paid' => 1,
            'agent_id' => $agent_id,
            'mode' => $period['mode'],
        ];
        if ($period['mode'] === 'month') $args['month'] = $period['month'];
        else $args['quarter'] = $period['quarter'];

        wp_redirect( admin_url('admin.php?' . http_build_query($args)) );
        exit;
    }

    /**
     * CSV export filtered by billing period (commission_month)
     * ✅ paid_at column only if exporting paid status
     */
    public function export_csv(){

        if( ! current_user_can('manage_woocommerce') || ! check_admin_referer('wsm_export_csv') ) {
            wp_die('Not allowed');
        }

        $agent_id = absint($_GET['agent_id'] ?? 0);
        $period = $this->get_period_from_request($_GET);

        // ✅ Allow status selection for export
        $status = sanitize_key($_GET['status'] ?? 'approved');
        if (!in_array($status, ['approved','paid','all'], true)) {
            $status = 'approved';
        }

        $include_paid_at = ($status === 'paid');

        global $wpdb;

        $where = "WHERE 1=1";
        $p = [];

        // status filter
        if ($status !== 'all') {
            $where .= " AND c.status=%s";
            $p[] = $status;
        } else {
            // typical payouts export: approved + paid
            $where .= " AND c.status IN ('approved','paid')";
        }

        if ($agent_id) {
            $where .= " AND c.agent_id=%d";
            $p[] = $agent_id;
        }

        if ($period['mode'] === 'month') {
            $where .= " AND c.commission_month=%s";
            $p[] = $period['month'];
            $label = $period['month'];
        } else {
            $where .= " AND c.commission_month >= %s AND c.commission_month <= %s";
            $p[] = $period['range']['start'];
            $p[] = $period['range']['end'];
            $label = $period['range']['label'];
        }

        $sql = "SELECT c.id, c.order_id, c.agent_id, a.name AS agent_name,
                       c.taxable_base, c.rate, c.amount, c.currency, c.status,
                       c.commission_month, c.created_at, c.paid_at
                FROM {$this->db->table_commissions} c
                LEFT JOIN {$this->db->table_agents} a ON c.agent_id = a.id
                $where
                ORDER BY a.name, c.order_id";

        $rows = $wpdb->get_results( $wpdb->prepare($sql, $p) );

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=wsm-payouts-'.$label.'-'.$status.'.csv');

        $out = fopen('php://output', 'w');

        // ✅ Header dynamic
        $headers = array('Period','Commission ID','Order','Agent','Base','Rate %','Commission','Currency','Status','Created');
        if ($include_paid_at) $headers[] = 'Paid at';
        fputcsv($out, $headers);

        $sum = 0.0;
        foreach($rows as $r){

            $sum += (float)$r->amount;

            $line = array(
                $period['mode'] === 'month' ? $r->commission_month : $label,
                $r->id,
                $r->order_id,
                $r->agent_name ?: $r->agent_id,
                wc_format_decimal($r->taxable_base),
                (float)$r->rate * 100,
                wc_format_decimal($r->amount),
                $r->currency,
                $r->status,
                $r->created_at,
            );

            if ($include_paid_at) {
                // only meaningful when status=paid export
                $line[] = ($r->status === 'paid' && !empty($r->paid_at)) ? $r->paid_at : '';
            }

            fputcsv($out, $line);
        }

        fputcsv($out, []);
        // TOTAL row – keep alignment
        $total_row = array('TOTAL','','','','','', wc_format_decimal($sum),'','','');
        if ($include_paid_at) $total_row[] = '';
        fputcsv($out, $total_row);

        fclose($out);
        exit;
    }

    /**
     * "PDF" export as print-friendly HTML (no external PDF lib).
     * ✅ paid_at column only if exporting paid status
     */
    public function export_pdf(){

        if( ! current_user_can('manage_woocommerce') || ! check_admin_referer('wsm_export_pdf') ) {
            wp_die('Not allowed');
        }

        $agent_id = absint($_GET['agent_id'] ?? 0);
        $period = $this->get_period_from_request($_GET);

        // ✅ Allow status selection for export
        $status = sanitize_key($_GET['status'] ?? 'approved');
        if (!in_array($status, ['approved','paid','all'], true)) {
            $status = 'approved';
        }

        $include_paid_at = ($status === 'paid');

        global $wpdb;

        $where = "WHERE 1=1";
        $p = [];

        if ($status !== 'all') {
            $where .= " AND c.status=%s";
            $p[] = $status;
        } else {
            $where .= " AND c.status IN ('approved','paid')";
        }

        if ($agent_id) { $where .= " AND c.agent_id=%d"; $p[] = $agent_id; }

        if ($period['mode'] === 'month') {
            $where .= " AND c.commission_month=%s";
            $p[] = $period['month'];
            $label = $period['month'];
        } else {
            $where .= " AND c.commission_month >= %s AND c.commission_month <= %s";
            $p[] = $period['range']['start'];
            $p[] = $period['range']['end'];
            $label = $period['range']['label'];
        }

				$sort_sql = "a.name ASC, c.order_id ASC";
				
				// Wenn paid Export: nach paid_at sortieren (falls gesetzt)
				if ($status === 'paid') {
						$sort_sql = "a.name ASC, c.paid_at ASC, c.order_id ASC";
				} else {
						// sonst nach Bestelldatum sortieren (created date aus wp_posts)
						$sort_sql = "a.name ASC, o.post_date ASC, c.order_id ASC";
				}
				
				$sql = "SELECT c.order_id, c.taxable_base, c.rate, c.amount, c.currency, c.status,
											c.commission_month, c.paid_at, a.name AS agent_name,
											o.post_date AS order_date
								FROM {$this->db->table_commissions} c
								LEFT JOIN {$this->db->table_agents} a ON c.agent_id = a.id
								LEFT JOIN {$wpdb->posts} o ON o.ID = c.order_id
								$where
								ORDER BY $sort_sql";

        $rows = $wpdb->get_results( $wpdb->prepare($sql, $p) );

        $sum = 0.0;
        foreach ($rows as $r) $sum += (float)$r->amount;

        $agent_label = 'All agents';
        if ($agent_id) {
            $agent_label = $wpdb->get_var( $wpdb->prepare("SELECT name FROM {$this->db->table_agents} WHERE id=%d", $agent_id) ) ?: ('#'.$agent_id);
        }

        echo '<!doctype html><html><head><meta charset="utf-8">';
        echo '<title>WooSales Payout '.$label.'</title>';
        echo '<style>
            body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,sans-serif;padding:24px}
            h1{margin:0 0 8px}
            .meta{color:#555;margin-bottom:16px}
            table{width:100%;border-collapse:collapse;margin-top:12px}
            th,td{border-bottom:1px solid #ddd;padding:8px;text-align:left;font-size:13px}
            th{background:#f6f6f6}
            .right{text-align:right}
            .total{margin-top:14px;font-size:16px;font-weight:700}
            @media print{button{display:none}}
        </style></head><body>';

        echo '<button onclick="window.print()">Print / Save as PDF</button>';
        echo '<h1>Payout Report</h1>';
        echo '<div class="meta">Period: <strong>'.esc_html($label).'</strong> &nbsp;|&nbsp; Agent: <strong>'.esc_html($agent_label).'</strong> &nbsp;|&nbsp; Status: <strong>'.esc_html($status).'</strong> &nbsp;|&nbsp; Total: <strong>'.wp_kses_post(wc_price($sum)).'</strong></div>';

        echo '<table><thead><tr>
            <th>Month</th><th>Order date</th><th>Order</th><th class="right">Base</th><th class="right">Rate</th><th class="right">Commission</th><th>Status</th>';

        if ($include_paid_at) {
            echo '<th>Paid at</th>';
        }

        echo '</tr></thead><tbody>';

        foreach ($rows as $r){
            echo '<tr>';
            echo '<td>'.esc_html($r->commission_month).'</td>';
						echo '<td>'.esc_html(date_i18n('d.m.Y H:i', strtotime($r->order_date))).'</td>';
            echo '<td>#'.esc_html($r->order_id).'</td>';
            echo '<td class="right">'.wp_kses_post(wc_price($r->taxable_base)).'</td>';
            echo '<td class="right">'.esc_html(($r->rate*100).' %').'</td>';
            echo '<td class="right">'.wp_kses_post(wc_price($r->amount)).'</td>';
            echo '<td>'.esc_html(ucfirst($r->status)).'</td>';

            if ($include_paid_at) {
                echo '<td>'.esc_html(($r->status === 'paid' && !empty($r->paid_at)) ? $r->paid_at : '').'</td>';
            }

            echo '</tr>';
        }

        echo '</tbody></table>';

        echo '</body></html>';
        exit;
    }
}
