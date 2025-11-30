<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$agent_id = absint($_GET['agent_id'] ?? 0);
$status = sanitize_text_field($_GET['status'] ?? 'approved');
$date_from = sanitize_text_field($_GET['date_from'] ?? '');
$date_to = sanitize_text_field($_GET['date_to'] ?? '');
$list = $this->commissions->list(array('agent_id'=>$agent_id,'status'=>$status,'date_from'=>$date_from,'date_to'=>$date_to,'per_page'=>50,'paged'=>absint($_GET['paged'] ?? 1)));
$total_amount = $this->commissions->totals(array('agent_id'=>$agent_id,'status'=>$status,'date_from'=>$date_from,'date_to'=>$date_to));
$agents = $this->agents->all_active();
?>
<form method="get">
    <input type="hidden" name="page" value="wsm-sales" />
    <input type="hidden" name="tab" value="dashboard" />
    <p class="search-box">
        <select name="agent_id">
            <option value="0"><?php esc_html_e('All agents','woo-sales-manager'); ?></option>
            <?php foreach($agents as $a): ?>
                <option value="<?php echo esc_attr($a->id); ?>" <?php selected($agent_id,$a->id); ?>>
                    <?php echo esc_html($a->name); ?> (<?php echo esc_html($a->rate*100); ?>%)
                </option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="approved" <?php selected($status,'approved'); ?>><?php esc_html_e('Approved','woo-sales-manager'); ?></option>
            <option value="pending" <?php selected($status,'pending'); ?>><?php esc_html_e('Pending','woo-sales-manager'); ?></option>
            <option value="rejected" <?php selected($status,'rejected'); ?>><?php esc_html_e('Rejected','woo-sales-manager'); ?></option>
            <option value="paid" <?php selected($status,'paid'); ?>><?php esc_html_e('Paid','woo-sales-manager'); ?></option>
        </select>
        <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" />
        <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" />
        <button class="button"><?php esc_html_e('Filter','woo-sales-manager'); ?></button>
    </p>
</form>
<p><strong><?php esc_html_e('Total amount','woo-sales-manager'); ?>:</strong> <?php echo wc_price($total_amount); ?></p>
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
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
