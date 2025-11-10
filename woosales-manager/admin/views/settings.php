<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$settings = get_option('wsm_settings', array());
$settings = wp_parse_args($settings, array(
    'default_base'          => 'net',
    'allow_multi_agents'    => 'no',
    'assignment_mode'       => 'all_agents',
    'payout_period_default' => 'month'
));

// Load allowed roles for user-linking
$allowed_roles = (array) get_option('wsm_link_roles', []);

// Save
if( isset($_POST['wsm_save_settings']) && check_admin_referer('wsm_save_settings') && current_user_can('manage_woocommerce') ){

    $settings['default_base'] = in_array($_POST['default_base'], array('net','gross'), true) ? $_POST['default_base'] : 'net';
    $settings['allow_multi_agents'] = ($_POST['allow_multi_agents'] ?? 'no') === 'yes' ? 'yes':'no';
    $settings['assignment_mode'] = 'all_agents';
    $settings['payout_period_default'] = in_array($_POST['payout_period_default'], array('month','quarter','year','custom'), true) ? $_POST['payout_period_default'] : 'month';

    update_option('wsm_settings', $settings);

    // Save allowed roles for agent linking
    if ( isset($_POST['wsm_link_roles']) ) {
        update_option('wsm_link_roles', array_map('sanitize_text_field', (array)$_POST['wsm_link_roles']));
    } else {
        delete_option('wsm_link_roles'); // empty = allow all
    }

    echo '<div class="updated notice"><p>'.esc_html__('Settings saved','woo-sales-manager').'</p></div>';
}

$all_roles = wp_roles()->roles;
?>

<form method="post">
    <?php wp_nonce_field('wsm_save_settings'); ?>
    <input type="hidden" name="wsm_save_settings" value="1"/>

    <table class="form-table">
        <tr>
            <th><?php esc_html_e('Commission base','woo-sales-manager'); ?></th>
            <td>
                <label><input type="radio" name="default_base" value="net" <?php checked($settings['default_base'],'net'); ?>> <?php esc_html_e('Net (default)','woo-sales-manager'); ?></label><br/>
                <label><input type="radio" name="default_base" value="gross" <?php checked($settings['default_base'],'gross'); ?>> <?php esc_html_e('Gross','woo-sales-manager'); ?></label>
            </td>
        </tr>

        <tr>
            <th><?php esc_html_e('Allow multiple agents per order','woo-sales-manager'); ?></th>
            <td>
                <label><input type="checkbox" name="allow_multi_agents" value="yes" <?php checked($settings['allow_multi_agents'],'yes'); ?>> <?php esc_html_e('Yes','woo-sales-manager'); ?></label>
            </td>
        </tr>

        <tr>
            <th><?php esc_html_e('Assignment mode','woo-sales-manager'); ?></th>
            <td><em><?php esc_html_e('Global: All active agents receive commission on every Completed order.','woo-sales-manager'); ?></em></td>
        </tr>

        <tr>
            <th><?php esc_html_e('Default payout period','woo-sales-manager'); ?></th>
            <td>
                <select name="payout_period_default">
                    <option value="month"   <?php selected($settings['payout_period_default'],'month'); ?>><?php esc_html_e('Month','woo-sales-manager'); ?></option>
                    <option value="quarter" <?php selected($settings['payout_period_default'],'quarter'); ?>><?php esc_html_e('Quarter','woo-sales-manager'); ?></option>
                    <option value="year"    <?php selected($settings['payout_period_default'],'year'); ?>><?php esc_html_e('Year','woo-sales-manager'); ?></option>
                    <option value="custom"  <?php selected($settings['payout_period_default'],'custom'); ?>><?php esc_html_e('Custom','woo-sales-manager'); ?></option>
                </select>
            </td>
        </tr>

        <tr>
            <th><?php esc_html_e('Allow linking agents to WP users with roles','woo-sales-manager'); ?></th>
            <td>
                <select name="wsm_link_roles[]" multiple style="min-width:240px;height:8em;">
                    <?php foreach ($all_roles as $role_key => $role_info): ?>
                        <option value="<?php echo esc_attr($role_key); ?>" <?php selected(in_array($role_key,$allowed_roles,true)); ?>>
                            <?php echo esc_html(translate_user_role($role_info['name'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e('Used in Agent → Linked WP User dropdown. Leave empty = allow all users.','woo-sales-manager'); ?></p>
            </td>
        </tr>
    </table>

    <p><button class="button button-primary"><?php esc_html_e('Save settings','woo-sales-manager'); ?></button></p>
</form>
