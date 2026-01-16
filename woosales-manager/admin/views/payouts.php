<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$agents   = $this->agents->all_active();
$agent_id = absint($_GET['agent_id'] ?? 0);

$mode = sanitize_key($_GET['mode'] ?? 'month');
if (!in_array($mode, ['month','quarter'], true)) $mode = 'month';

$months = $this->commissions->get_available_months();
$month  = sanitize_text_field($_GET['month'] ?? date('Y-m'));
if ($months && !in_array($month, $months, true)) $month = $months[0];

// Quarter dropdown from months
$quarters = [];
foreach ($months as $m) {
    $y = substr($m, 0, 4);
    $mon = (int)substr($m, 5, 2);
    $q = (int)ceil($mon / 3);
    $quarters[$y . '-Q' . $q] = true;
}
$quarters = array_keys($quarters);
rsort($quarters);

$quarter = sanitize_text_field($_GET['quarter'] ?? '');
if (!preg_match('/^\d{4}-Q[1-4]$/', $quarter)) {
    $y = date('Y');
    $q = (int)ceil((int)date('n')/3);
    $quarter = $y . '-Q' . $q;
}

// status for payout reporting (approved/paid/all)
$status = sanitize_key($_GET['status'] ?? 'approved');
if (!in_array($status, ['approved','paid','all'], true)) $status = 'approved';

// Build list args using commission_month
$args = [
    'agent_id' => $agent_id,
    'status'   => ($status === 'all') ? '' : $status,
    'per_page' => 999999,
    'paged'    => 1,
];

$label = $month;

if ($mode === 'month') {
    $args['month'] = $month;
    $label = $month;
} else {
    $y = substr($quarter, 0, 4);
    $q = (int)substr($quarter, 6, 1);
    $start_m = str_pad((($q-1)*3)+1, 2, '0', STR_PAD_LEFT);
    $end_m   = str_pad((($q-1)*3)+3, 2, '0', STR_PAD_LEFT);
    $args['month_from'] = $y . '-' . $start_m;
    $args['month_to']   = $y . '-' . $end_m;
    $label = $quarter;
}

$list = $this->commissions->list($args);
$total_amount = $this->commissions->totals([
    'agent_id'   => $agent_id,
    'status'     => ($status === 'all') ? '' : $status,
    'month'      => ($mode === 'month') ? $month : '',
    'month_from' => ($mode === 'quarter') ? $args['month_from'] : '',
    'month_to'   => ($mode === 'quarter') ? $args['month_to'] : '',
]);

$count = isset($list['total']) ? (int)$list['total'] : (is_array($list['rows']) ? count($list['rows']) : 0);
$agent_label = __('All agents','woo-sales-manager');
if ($agent_id) {
    foreach ($agents as $a) {
        if ((int)$a->id === (int)$agent_id) { $agent_label = $a->name; break; }
    }
}
?>
<h2><?php esc_html_e('Payouts','woo-sales-manager'); ?></h2>

<form method="get" style="margin-bottom:1em;display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
    <input type="hidden" name="page" value="wsm-sales" />
    <input type="hidden" name="tab" value="payouts" />

    <label>
        <?php esc_html_e('Mode','woo-sales-manager'); ?><br>
        <select name="mode" onchange="this.form.submit()">
            <option value="month" <?php selected($mode,'month'); ?>><?php esc_html_e('Month','woo-sales-manager'); ?></option>
            <option value="quarter" <?php selected($mode,'quarter'); ?>><?php esc_html_e('Quarter','woo-sales-manager'); ?></option>
        </select>
    </label>

    <?php if ($mode === 'month'): ?>
        <label>
            <?php esc_html_e('Month','woo-sales-manager'); ?><br>
            <select name="month">
                <?php foreach($months as $m): ?>
                    <option value="<?php echo esc_attr($m); ?>" <?php selected($month,$m); ?>>
                        <?php echo esc_html($m); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    <?php else: ?>
        <label>
            <?php esc_html_e('Quarter','woo-sales-manager'); ?><br>
            <select name="quarter">
                <?php foreach($quarters as $q): ?>
                    <option value="<?php echo esc_attr($q); ?>" <?php selected($quarter,$q); ?>>
                        <?php echo esc_html($q); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    <?php endif; ?>

    <label>
        <?php esc_html_e('Agent','woo-sales-manager'); ?><br>
        <select name="agent_id">
            <option value="0"><?php esc_html_e('All agents','woo-sales-manager'); ?></option>
            <?php foreach($agents as $a): ?>
                <option value="<?php echo esc_attr($a->id); ?>" <?php selected($agent_id,$a->id); ?>>
                    <?php echo esc_html($a->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>
        <?php esc_html_e('Status','woo-sales-manager'); ?><br>
        <select name="status">
            <option value="approved" <?php selected($status,'approved'); ?>><?php esc_html_e('Approved','woo-sales-manager'); ?></option>
            <option value="paid" <?php selected($status,'paid'); ?>><?php esc_html_e('Paid','woo-sales-manager'); ?></option>
            <option value="all" <?php selected($status,'all'); ?>><?php esc_html_e('All (Approved + Paid)','woo-sales-manager'); ?></option>
        </select>
    </label>

    <button class="button"><?php esc_html_e('Filter','woo-sales-manager'); ?></button>

    <?php
    // Export URLs include same filters (+ status)
    $export_args = [
        'action'   => 'wsm_export_csv',
        'agent_id' => $agent_id,
        'mode'     => $mode,
        'status'   => $status,
    ];
    if ($mode === 'month') $export_args['month'] = $month;
    else $export_args['quarter'] = $quarter;

    $csv_url = wp_nonce_url(add_query_arg($export_args, admin_url('admin-post.php')), 'wsm_export_csv');

    $export_args['action'] = 'wsm_export_pdf';
    $pdf_url = wp_nonce_url(add_query_arg($export_args, admin_url('admin-post.php')), 'wsm_export_pdf');
    ?>

    <a class="button" href="<?php echo esc_url($csv_url); ?>"><?php esc_html_e('Export CSV','woo-sales-manager'); ?></a>
    <a class="button" href="<?php echo esc_url($pdf_url); ?>"><?php esc_html_e('Export PDF','woo-sales-manager'); ?></a>
</form>

<div class="notice notice-info" style="margin:12px 0;">
    <p style="margin:8px 0;">
        <strong><?php echo esc_html($count); ?></strong>
        <?php esc_html_e('commissions filtered', 'woo-sales-manager'); ?>
        — <?php esc_html_e('Period', 'woo-sales-manager'); ?>: <strong><?php echo esc_html($label); ?></strong>,
        <?php esc_html_e('Status', 'woo-sales-manager'); ?>: <strong><?php echo esc_html($status); ?></strong>,
        <?php esc_html_e('Agent', 'woo-sales-manager'); ?>: <strong><?php echo esc_html($agent_label); ?></strong>
        — <?php esc_html_e('Total', 'woo-sales-manager'); ?>: <strong><?php echo wp_kses_post(wc_price($total_amount)); ?></strong>
    </p>
</div>

