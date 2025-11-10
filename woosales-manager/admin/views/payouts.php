<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$agents = $this->agents->all_active();
$agent_id = absint($_GET['agent_id'] ?? 0);
$date_from = sanitize_text_field($_GET['date_from'] ?? '');
$date_to = sanitize_text_field($_GET['date_to'] ?? '');
?>
<h2><?php esc_html_e('Payouts','woo-sales-manager'); ?></h2>
<form method="get" style="margin-bottom:1em;">
    <input type="hidden" name="page" value="wsm-sales" />
    <input type="hidden" name="tab" value="payouts" />
    <select name="agent_id">
        <option value="0"><?php esc_html_e('All agents','woo-sales-manager'); ?></option>
        <?php foreach($agents as $a): ?>
            <option value="<?php echo esc_attr($a->id); ?>" <?php selected($agent_id,$a->id); ?>>
                <?php echo esc_html($a->name); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" />
    <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" />
    <button class="button"><?php esc_html_e('Filter','woo-sales-manager'); ?></button>
    <a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg(array('action'=>'wsm_export_csv'), admin_url('admin-post.php') ) . '&agent_id='.$agent_id.'&date_from='.$date_from.'&date_to='.$date_to, 'wsm_export_csv' ) ); ?>"><?php esc_html_e('Export CSV','woo-sales-manager'); ?></a>
</form>
<form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
    <?php wp_nonce_field('wsm_mark_paid'); ?>
    <input type="hidden" name="action" value="wsm_mark_paid" />
    <input type="hidden" name="agent_id" value="<?php echo esc_attr($agent_id); ?>" />
    <input type="hidden" name="date_from" value="<?php echo esc_attr($date_from); ?>" />
    <input type="hidden" name="date_to" value="<?php echo esc_attr($date_to); ?>" />
    <button class="button button-primary"><?php esc_html_e('Mark filtered as Paid','woo-sales-manager'); ?></button>
</form>
