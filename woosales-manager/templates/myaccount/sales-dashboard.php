<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<style>
.wsm-sales-wrap {
    padding: 24px;
    border-radius: 8px;
}
.wsm-sales-wrap table {
    width: 100%;
    border-collapse: collapse;
}
.wsm-sales-wrap th {
    text-align: left;
    border-bottom: 1px solid #222;
    padding-bottom: 8px;
    color: #00D6B9;
}
.wsm-sales-wrap td {
    padding: 8px 0;
    border-bottom: 1px solid #1b1b1b;
}
.wsm-sales-total {
    margin-bottom: 20px;
    font-size: 18px;
}
</style>

<div class="wsm-sales-wrap">
    <h3><?php esc_html_e('My Sales (Net)','woo-sales-manager'); ?></h3>

    <?php
    $total = 0;
    foreach($items as $row){
        $total += $row->amount; // Netto based
    }
    ?>

    <p class="wsm-sales-total">
        <strong><?php esc_html_e('Total Commission:','woo-sales-manager'); ?></strong>
        <?php echo wc_price($total); ?>
    </p>

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
        <?php foreach($items as $row): ?>
            <tr>
                <td><a href="<?php echo esc_url( wc_get_endpoint_url('view-order', $row->order_id, wc_get_page_permalink('myaccount')) ); ?>">
                    #<?php echo esc_html($row->order_id); ?></a></td>
                <td><?php echo wc_price($row->taxable_base); ?></td>
                <td><?php echo wc_price($row->amount); ?></td>
                <td><?php echo ucfirst(esc_html($row->status)); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
