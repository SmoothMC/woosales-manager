<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$agent_id = absint($_GET['agent_id'] ?? 0);

// status: approved|pending|rejected|paid|all
$status = sanitize_key($_GET['status'] ?? 'approved');
if (!in_array($status, ['approved','pending','rejected','paid','all'], true)) {
    $status = 'approved';
}

// period_mode: month|quarter
$period_mode = sanitize_key($_GET['period_mode'] ?? 'month');
if (!in_array($period_mode, ['month','quarter'], true)) $period_mode = 'month';

$months = $this->commissions->get_available_months();

$month = sanitize_text_field($_GET['month'] ?? date('Y-m'));
if ($months && !in_array($month, $months, true)) {
    $month = $months[0];
}

// Quarter: YYYY-Qn
$quarter = sanitize_text_field($_GET['quarter'] ?? '');
if (!preg_match('/^\d{4}-Q[1-4]$/', $quarter)) {
    $y = date('Y');
    $q = (int)ceil((int)date('n')/3);
    $quarter = $y . '-Q' . $q;
}

// Quarters from months
$quarters = [];
foreach ($months as $m) {
    $y = substr($m, 0, 4);
    $mon = (int)substr($m, 5, 2);
    $q = (int)ceil($mon / 3);
    $quarters[$y . '-Q' . $q] = true;
}
$quarters = array_keys($quarters);
rsort($quarters);

// Quarter -> month range
$month_from = '';
$month_to   = '';
if ($period_mode === 'quarter') {
    $y = substr($quarter, 0, 4);
    $q = (int)substr($quarter, 6, 1);
    $start_m = str_pad((($q-1)*3)+1, 2, '0', STR_PAD_LEFT);
    $end_m   = str_pad((($q-1)*3)+3, 2, '0', STR_PAD_LEFT);
    $month_from = $y . '-' . $start_m;
    $month_to   = $y . '-' . $end_m;
}

// Build list args
$args = [
    'agent_id'  => $agent_id,
    'status'    => ($status === 'all') ? '' : $status,
    'per_page'  => 50,
    'paged'     => absint($_GET['paged'] ?? 1),
];

if ($period_mode === 'month') {
    $args['month'] = $month;
} else {
    $args['month_from'] = $month_from;
    $args['month_to']   = $month_to;
}

$list = $this->commissions->list($args);

$total_amount = $this->commissions->totals([
    'agent_id'  => $agent_id,
    'status'    => ($status === 'all') ? '' : $status,
    'month'     => ($period_mode === 'month') ? $month : '',
    'month_from'=> ($period_mode === 'quarter') ? $month_from : '',
    'month_to'  => ($period_mode === 'quarter') ? $month_to : '',
]);

$agents = $this->agents->all_active();
?>

<form method="get">
    <input type="hidden" name="page" value="wsm-sales" />
    <input type="hidden" name="tab" value="dashboard" />

    <p class="search-box">

        <select name="period_mode" onchange="this.form.submit()">
            <option value="month" <?php selected($period_mode,'month'); ?>>
                <?php esc_html_e('Month','woo-sales-manager'); ?>
            </option>
            <option value="quarter" <?php selected($period_mode,'quarter'); ?>>
                <?php esc_html_e('Quarter','woo-sales-manager'); ?>
            </option>
        </select>

        <?php if ($period_mode === 'month'): ?>
            <select name="month">
                <?php foreach($months as $m): ?>
                    <option value="<?php echo esc_attr($m); ?>" <?php selected($month, $m); ?>>
                        <?php echo esc_html($m); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php else: ?>
            <select name="quarter">
                <?php foreach($quarters as $q): ?>
                    <option value="<?php echo esc_attr($q); ?>" <?php selected($quarter, $q); ?>>
                        <?php echo esc_html($q); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <select name="agent_id">
            <option value="0"><?php esc_html_e('All agents','woo-sales-manager'); ?></option>
            <?php foreach($agents as $a): ?>
                <option value="<?php echo esc_attr($a->id); ?>" <?php selected($agent_id,$a->id); ?>>
                    <?php echo esc_html($a->name); ?> (<?php echo esc_html($a->rate*100); ?>%)
                </option>
            <?php endforeach; ?>
        </select>

        <select name="status">
            <option value="all" <?php selected($status,'all'); ?>><?php esc_html_e('All','woo-sales-manager'); ?></option>
            <option value="approved" <?php selected($status,'approved'); ?>><?php esc_html_e('Approved','woo-sales-manager'); ?></option>
            <option value="pending" <?php selected($status,'pending'); ?>><?php esc_html_e('Pending','woo-sales-manager'); ?></option>
            <option value="rejected" <?php selected($status,'rejected'); ?>><?php esc_html_e('Rejected','woo-sales-manager'); ?></option>
            <option value="paid" <?php selected($status,'paid'); ?>><?php esc_html_e('Paid','woo-sales-manager'); ?></option>
        </select>

        <button class="button"><?php esc_html_e('Filter','woo-sales-manager'); ?></button>
    </p>
</form>

<p><strong><?php esc_html_e('Total amount','woo-sales-manager'); ?>:</strong> <?php echo wc_price($total_amount); ?></p>
<?php
// ✅ Human label for period
$period_label = '';
if ($period_mode === 'month') {
    $period_label = $month;
} else {
    $period_label = $quarter;
}

$status_label = ($status === 'all') ? __('All', 'woo-sales-manager') : ucfirst($status);
$agent_label  = __('All agents', 'woo-sales-manager');
if ($agent_id) {
    $agent_obj = $this->agents->get($agent_id);
    $agent_label = $agent_obj ? $agent_obj->name : ('#'.$agent_id);
}

$count = isset($list['total']) ? (int)$list['total'] : (is_array($list['rows']) ? count($list['rows']) : 0);
?>

<div class="notice notice-info" style="margin:12px 0;">
    <p style="margin:8px 0;">
        <strong><?php echo esc_html($count); ?></strong>
        <?php esc_html_e('commissions filtered', 'woo-sales-manager'); ?>
        —
        <?php esc_html_e('Period', 'woo-sales-manager'); ?>: <strong><?php echo esc_html($period_label); ?></strong>,
        <?php esc_html_e('Status', 'woo-sales-manager'); ?>: <strong><?php echo esc_html($status_label); ?></strong>,
        <?php esc_html_e('Agent', 'woo-sales-manager'); ?>: <strong><?php echo esc_html($agent_label); ?></strong>
        —
        <?php esc_html_e('Total', 'woo-sales-manager'); ?>: <strong><?php echo wp_kses_post(wc_price($total_amount)); ?></strong>
    </p>

    <?php if ($count === 0): ?>
        <p style="margin:0 0 8px;"><?php esc_html_e('No results found for this filter.', 'woo-sales-manager'); ?></p>
    <?php endif; ?>
</div>

<table class="widefat striped">
    <thead>
        <tr>
            <th><?php esc_html_e('ID','woo-sales-manager'); ?></th>
            <th><?php esc_html_e('Order','woo-sales-manager'); ?></th>
            <th><?php esc_html_e('Agent','woo-sales-manager'); ?></th>
            <th><?php esc_html_e('Base','woo-sales-manager'); ?></th>
            <th><?php esc_html_e('Rate','woo-sales-manager'); ?></th>
            <th><?php esc_html_e('Amount','woo-sales-manager'); ?></th>
            <th><?php esc_html_e('Status','woo-sales-manager'); ?></th>
            <th><?php esc_html_e('Created','woo-sales-manager'); ?></th>
						<th><?php esc_html_e('Paid at','woo-sales-manager'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($list['rows'] as $r): $agent = $this->agents->get($r->agent_id); ?>
            <tr>
                <td>
                  <a href="<?php echo esc_url(
                      admin_url('admin.php?page=wsm-sales&commission_id=' . $r->id)
                  ); ?>">
                      #<?php echo esc_html($r->id); ?>
                  </a>
                </td>
                <td><a href="<?php echo esc_url(get_edit_post_link($r->order_id)); ?>">#<?php echo esc_html($r->order_id); ?></a></td>
                <td><?php echo esc_html($agent ? $agent->name : ('#'.$r->agent_id)); ?></td>
                <td><?php echo wc_price( $r->taxable_base ); ?></td>
                <td><?php echo esc_html( ($r->rate*100) . '%' ); ?></td>
                <td><?php echo wc_price( $r->amount ); ?></td>
                <td><?php echo esc_html( ucfirst($r->status) ); ?></td>
                <td><?php echo esc_html( $r->created_at ); ?></td>
								<td><?php echo $r->paid_at ? esc_html(date_i18n('d.m.Y H:i', strtotime($r->paid_at))) : ''; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
