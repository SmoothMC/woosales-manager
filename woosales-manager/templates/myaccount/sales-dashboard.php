<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<style>
.wsm-sales-wrap { padding: 24px; border-radius: 8px; }
@media (width <= 768px) {
  .wsm-sales-wrap { padding: 0 }  attributes
}
.wsm-sales-wrap table { width: 100%; border-collapse: collapse; }
.wsm-sales-wrap th { text-align: left; border-bottom: 1px solid #222; padding-bottom: 8px; }
.wsm-sales-wrap td { padding: 8px 0; border-bottom: 1px solid #1b1b1b; }
.wsm-sales-total { margin-bottom: 20px; font-size: 18px; }
.wsm-sales-filters { margin-bottom: 16px; display:flex; gap:16px; flex-wrap:wrap; align-items:flex-end; }
.wsm-sales-filters select { margin-right: 8px; min-width: 160px; }
.wsm-sales-filters label {margin-bottom: 0}
.wsm-status-btns { display:flex; gap:8px; align-items:center; }
.wsm-status-btn {
  border: 1px solid #ccc; background: #fff; color: #333;
  border-radius: 9999px; padding: 6px 14px; cursor: pointer;
  transition: all .2s ease;
}
.wsm-status-btn.active { background: #0073aa; color: #fff; border-color: #0073aa; }
.wsm-status-btn:hover { background: #e5f2ff; }
.wsm-status-btn.active:hover {color: #333}
.wsm-nav-btn {border-radius: 9999px; cursor: pointer;}
</style>

<div class="wsm-sales-wrap">
  <h3><?php esc_html_e('My Sales (Net)','woo-sales-manager'); ?></h3>

  <?php
  // ---- INPUT ----
  $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'month'; // default: This Month
	$offset = isset($_GET['period_offset']) ? intval($_GET['period_offset']) : 0;
  $status_param = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'approved'; // csv
  $status_filter = array_filter(array_map('strtolower', array_map('trim', explode(',', $status_param))));
  if (empty($status_filter)) { $status_filter = array('approved'); }

  $allowed_statuses = array('approved','pending','rejected','paid');

  // ---- FILTERING ----
  $filtered = array();
  $now = current_time('timestamp');
	$ref = strtotime("$offset month", $now); // Standard-Offset in Monaten
	
  foreach($items as $row){
    $created = strtotime($row->created_at);
    $include = true;

    // Zeitraumfilter basierend auf Offset-Referenz ($ref)
    switch($period){
        case 'month':
            $include = (date('Y-m', $created) === date('Y-m', $ref));
            break;
        case 'quarter':
            $include = (ceil(date('n', $created)/3) === ceil(date('n', $ref)/3)
                        && date('Y', $created) === date('Y', $ref));
            break;
        case 'year':
            $include = (date('Y', $created) === date('Y', $ref));
            break;
        case 'all':
        default:
            $include = true;
    }

      // Status (Mehrfachauswahl)
      if ($include) {
          $row_status = strtolower($row->status);
          if (!in_array($row_status, $status_filter, true)) {
              $include = false;
          }
      }

      if ($include) $filtered[] = $row;
  }

  // ---- TOTAL (gefiltert) ----
  $total = 0;
  foreach($filtered as $row){ $total += (float) $row->amount; }

  // Hilfsfunktion für active-State
  $isActive = function($st) use ($status_filter){ return in_array($st, $status_filter, true) ? 'active' : ''; };
  ?>

  <form method="get" class="wsm-sales-filters" id="wsm-sales-filters">
    <div class="wsm-status-btns" data-default="approved">
      <?php foreach($allowed_statuses as $st): ?>
        <button type="button"
                class="wsm-status-btn <?php echo esc_attr($isActive($st)); ?>"
                data-status="<?php echo esc_attr($st); ?>">
          <?php echo esc_html(ucfirst($st)); ?>
        </button>
      <?php endforeach; ?>
    </div>

    <input type="hidden" name="wsm-sales" value="" />
    <!-- Status als CSV in hidden input, wird per JS aktualisiert -->
    <input type="hidden" name="status" id="wsm-status-input" value="<?php echo esc_attr(implode(',', $status_filter)); ?>"/>

    <label>
      <?php esc_html_e('Period:','woo-sales-manager'); ?>
      <select name="period" id="wsm-period" onchange="this.form.submit()">
        <option value="all" <?php selected($period,'all'); ?>><?php esc_html_e('All','woo-sales-manager'); ?></option>
        <option value="month" <?php selected($period,'month'); ?>><?php esc_html_e('This Month','woo-sales-manager'); ?></option>
        <option value="quarter" <?php selected($period,'quarter'); ?>><?php esc_html_e('This Quarter','woo-sales-manager'); ?></option>
        <option value="year" <?php selected($period,'year'); ?>><?php esc_html_e('This Year','woo-sales-manager'); ?></option>
      </select>
    </label>
  </form>

  <p class="wsm-sales-total">
    <strong><?php esc_html_e('Total Commission (Filtered):','woo-sales-manager'); ?></strong>
    <?php echo wc_price($total); ?>
  </p>
	
	<?php
		$label = '';
		switch($period){
			case 'month':   $label = date_i18n('F Y', $ref); break;
			case 'quarter': $label = sprintf(__('Q%d %s','woo-sales-manager'),
												ceil(date('n',$ref)/3), date('Y',$ref)); break;
			case 'year':    $label = date('Y',$ref); break;
		}
		if ($label) echo '<p style="margin-top:8px;font-weight:600;">'.esc_html($label).'</p>';
	?>


  <table>
    <thead>
      <tr>
        <th><?php esc_html_e('Order','woo-sales-manager'); ?></th>
        <th><?php esc_html_e('Net Amount','woo-sales-manager'); ?></th>
        <th><?php esc_html_e('Commission','woo-sales-manager'); ?></th>
        <th><?php esc_html_e('Status','woo-sales-manager'); ?></th>
      </tr>
    </thead>
    <tbody>
    <?php if(empty($filtered)): ?>
      <tr><td colspan="4"><?php esc_html_e('No results found for this filter.','woo-sales-manager'); ?></td></tr>
    <?php else: ?>
      <?php foreach($filtered as $row): ?>
        <tr>
          <td>
            <a href="<?php echo esc_url( wc_get_endpoint_url('view-order', $row->order_id, wc_get_page_permalink('myaccount')) ); ?>">
              #<?php echo esc_html($row->order_id); ?>
            </a>
          </td>
          <td><?php echo wc_price($row->taxable_base); ?></td>
          <td><?php echo wc_price($row->amount); ?></td>
          <td><?php echo ucfirst(esc_html($row->status)); ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
	<?php if (in_array($period, ['month','quarter','year'], true)) : ?>
  <div class="wsm-period-nav" style="margin-top:16px;display:flex;justify-content:center;gap:16px;">
    <?php
      $prev = $offset - 1;
      $next = $offset + 1;
// Basis-URL mit allen aktiven Parametern (period, status usw.)
$base_url = add_query_arg(array(
    'wsm-sales' => '',
    'period' => $period,
    'status' => implode(',', $status_filter)
));

// Nur period_offset austauschen
$prev_url = add_query_arg('period_offset', $prev, $base_url);
$next_url = add_query_arg('period_offset', $next, $base_url);

// "Next" deaktivieren, wenn in Zukunft
$next_disabled = ($ref > $now);
    ?>
    <a type="button" class="button wsm-nav-btn" href="<?php echo esc_url($prev_url); ?>"><?php esc_html_e('Previous','woo-sales-manager'); ?></a>
		<a class="button button wsm-nav-btn <?php echo $next_disabled ? 'disabled' : ''; ?>"
   href="<?php echo esc_url($next_url); ?>"
   <?php echo $next_disabled ? 'style="opacity:0.5;pointer-events:none;"' : ''; ?>>
   <?php esc_html_e('Next','woo-sales-manager'); ?></a>
  </div>
<?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const form = document.getElementById('wsm-sales-filters');
  const hidden = document.getElementById('wsm-status-input');
  const btns = Array.from(document.querySelectorAll('.wsm-status-btn'));

  function submitWithStatuses() {
    // Falls nichts aktiv -> default "approved"
    let actives = btns.filter(b => b.classList.contains('active'))
                      .map(b => b.dataset.status);
    if (actives.length === 0) actives = [form.querySelector('.wsm-status-btns').dataset.default || 'approved'];
    hidden.value = actives.join(',');
    form.submit();
  }

  btns.forEach(btn => {
    btn.addEventListener('click', () => {
      btn.classList.toggle('active');
      submitWithStatuses();
    });
  });
});
</script>
