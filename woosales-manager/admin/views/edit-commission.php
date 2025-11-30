<?php
if (! defined('ABSPATH')) exit;

global $wpdb;

$id = absint($_GET['commission_id'] ?? 0);

$row = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$this->db->table_commissions} WHERE id=%d",
        $id
    )
);

if (!$row) {
    echo '<div class="wrap"><p>Commission not found.</p></div>';
    return;
}

$agents   = $this->agents->all_active();
$statuses = ['pending','approved','rejected','paid'];

// Rate für Anzeige von Dezimal → Prozent
$rate_percent = round($row->rate * 100, 4);
?>

<div class="wrap">

<h1>
    <?php printf(__('Commission #%d','woo-sales-manager'), $row->id); ?>
</h1>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
<?php wp_nonce_field('wsm_update_commission'); ?>

<input type="hidden" name="action" value="wsm_update_commission">
<input type="hidden" name="id" value="<?php echo esc_attr($row->id); ?>">

<table class="form-table">

<tr>
    <th><?php _e('Order','woo-sales-manager'); ?></th>
    <td>
        <a href="<?php echo esc_url(get_edit_post_link($row->order_id)); ?>">
            #<?php echo esc_html($row->order_id); ?>
        </a>
    </td>
</tr>

<tr>
    <th><?php _e('Agent','woo-sales-manager'); ?></th>
    <td>
        <select name="agent_id">
            <?php foreach($agents as $a): ?>
                <option value="<?php echo esc_attr($a->id); ?>"
                    <?php selected($row->agent_id, $a->id); ?>>
                    <?php echo esc_html($a->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </td>
</tr>

<tr>
    <th><?php _e('Status','woo-sales-manager'); ?></th>
    <td>
        <select name="status">
            <?php foreach($statuses as $st): ?>
                <option value="<?php echo esc_attr($st); ?>"
                    <?php selected($row->status, $st); ?>>
                    <?php echo esc_html(ucfirst($st)); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </td>
</tr>

<tr>
    <th><?php _e('Rate (%)','woo-sales-manager'); ?></th>
    <td>
        <input type="number"
          step="0.1"
          min="0"
          max="100"
          name="rate"
          value="<?php echo esc_attr($row->rate * 100); ?>">
        <p class="description">
            <?php _e('Enter percentage (e.g. 3 for 3%). Amount is recalculated automatically.','woo-sales-manager'); ?>
        </p>
    </td>
</tr>

<tr>
    <th><?php _e('Amount','woo-sales-manager'); ?></th>
    <td>
        <strong id="wsm-preview-amount"></strong>
        <p class="description">
            <?php _e('Calculated automatically from base × rate.','woo-sales-manager'); ?>
        </p>
    </td>
</tr>

</table>

<p class="submit">
    <button class="button button-primary">
        <?php _e('Save Commission','woo-sales-manager'); ?>
    </button>

    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=wsm-sales')); ?>">
        <?php _e('Back to Dashboard','woo-sales-manager'); ?>
    </a>
</p>

</form>
<script>
document.addEventListener('DOMContentLoaded', function(){

    const rateInput = document.querySelector('[name="rate"]');
    const preview   = document.getElementById('wsm-preview-amount');

    if(!rateInput || !preview) return;

    const base = <?php echo json_encode((float) $row->taxable_base); ?>;

    function recalc() {
        const ratePercent = parseFloat(rateInput.value.replace(',', '.')) || 0;
        const amount = (base * (ratePercent / 100)).toFixed(2);
        preview.innerText = amount.replace('.', ',') + ' €';
    }

    rateInput.addEventListener('input', recalc);

    // Beim ersten Laden einmal rechnen
    recalc();

});
</script>
</div>
