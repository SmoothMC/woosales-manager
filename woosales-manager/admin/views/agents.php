<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$agents = $this->agents->all_active();
?>
<h2><?php esc_html_e('Agents','woo-sales-manager'); ?></h2>
<table class="widefat striped">
    <thead>
        <tr>
            <th><?php esc_html_e('ID','woo-sales-manager'); ?></th>
            <th><?php esc_html_e('Name','woo-sales-manager'); ?></th>
            <th><?php esc_html_e('Email','woo-sales-manager'); ?></th>
            <th><?php esc_html_e('Rate %','woo-sales-manager'); ?></th>
            <th><?php esc_html_e('Active','woo-sales-manager'); ?></th>
            <th><?php esc_html_e('Actions','woo-sales-manager'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach( $agents as $a ): ?>
            <tr>
                <td><?php echo esc_html($a->id); ?></td>
                <td><?php echo esc_html($a->name); ?></td>
                <td><?php echo esc_html($a->email); ?></td>
                <td><?php echo esc_html($a->rate*100); ?></td>
                <td><?php echo $a->is_active ? '✓' : '✗'; ?></td>
                <td>
                    <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url('admin.php?page=wsm-sales&tab=agents&edit='.$a->id), 'wsm_edit_agent' ) ); ?>"><?php esc_html_e('Edit','woo-sales-manager'); ?></a>
                    <a class="button button-link-delete" href="<?php echo esc_url( wp_nonce_url( admin_url('admin-post.php?action=wsm_delete_agent&id='.$a->id), 'wsm_delete_agent' ) ); ?>"><?php esc_html_e('Delete','woo-sales-manager'); ?></a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<hr/>
<?php $edit_id = absint($_GET['edit'] ?? 0); $edit = $edit_id ? $this->agents->get($edit_id) : null; ?>
<h3><?php echo $edit ? esc_html__('Edit Agent','woo-sales-manager') : esc_html__('Add Agent','woo-sales-manager'); ?></h3>
<form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
    <?php wp_nonce_field('wsm_save_agent'); ?>
    <input type="hidden" name="action" value="wsm_save_agent" />
    <input type="hidden" name="id" value="<?php echo esc_attr($edit->id ?? 0); ?>" />
    <table class="form-table">
        <tr><th><label><?php esc_html_e('Name','woo-sales-manager'); ?></label></th><td><input name="name" type="text" class="regular-text" value="<?php echo esc_attr($edit->name ?? ''); ?>"></td></tr>
        <tr><th><label><?php esc_html_e('Email','woo-sales-manager'); ?></label></th><td><input name="email" type="email" class="regular-text" value="<?php echo esc_attr($edit->email ?? ''); ?>"></td></tr>
        <tr><th><label><?php esc_html_e('Rate (%)','woo-sales-manager'); ?></label></th><td><input name="rate" type="number" min="0" max="100" step="0.0001" value="<?php echo esc_attr( isset($edit) ? ($edit->rate*100) : 5 ); ?>"> <span class="description"><?php esc_html_e('Percentage for this agent.','woo-sales-manager'); ?></span></td></tr>
        <tr><th><label><?php esc_html_e('Active','woo-sales-manager'); ?></label></th><td><label><input name="is_active" type="checkbox" <?php checked( $edit ? (bool)$edit->is_active : true ); ?>> <?php esc_html_e('Active','woo-sales-manager'); ?></label></td></tr>
    </table>
    <p><button class="button button-primary"><?php esc_html_e('Save Agent','woo-sales-manager'); ?></button></p>
</form>
