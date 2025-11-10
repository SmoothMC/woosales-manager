<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class WSM_Updater {
    private $plugin_file; private $plugin_basename; private $update_url;
    public function __construct( $plugin_file ){
        $this->plugin_file = $plugin_file;
        $this->plugin_basename = plugin_basename( $plugin_file );
        $settings = get_option('wsm_settings', array());
        $this->update_url = $settings['update_json_url'] ?? '';
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugins_api' ), 10, 3 );
        add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );
    }
    private function fetch_update_data(){
        if ( empty( $this->update_url ) ) return null;
        $resp = wp_remote_get( $this->update_url, array('timeout'=>10) );
        if ( is_wp_error($resp) ) return null;
        if ( 200 !== (int) wp_remote_retrieve_response_code($resp) ) return null;
        $data = json_decode( wp_remote_retrieve_body($resp) );
        return is_object($data) ? $data : null;
    }
    public function check_for_update( $transient ){
        if ( empty( $transient->checked ) ) return $transient;
        $data = $this->fetch_update_data(); if(!$data) return $transient;
        $plugin_data = get_file_data( $this->plugin_file, array( 'Version' => 'Version' ), 'plugin' );
        $current = $plugin_data['Version'] ?? '0.0.0';
        if ( version_compare( $data->version ?? '0.0.0', $current, '>' ) ) {
            $obj = new stdClass();
            $obj->slug = dirname( $this->plugin_basename );
            $obj->plugin = $this->plugin_basename;
            $obj->new_version = $data->version ?? '';
            $obj->url = $data->homepage ?? '';
            $obj->tested = $data->tested ?? '';
            $obj->requires = $data->requires ?? '';
            $obj->package = $data->download_url ?? '';
            $transient->response[ $this->plugin_basename ] = $obj;
        }
        return $transient;
    }
    public function plugins_api( $result, $action, $args ){
        if ( 'plugin_information' !== $action ) return $result;
        if ( empty( $args->slug ) || $args->slug !== dirname( $this->plugin_basename ) ) return $result;
        $data = $this->fetch_update_data(); if ( ! $data ) return $result;
        $res = new stdClass();
        $res->name = $data->name ?? 'WooSales Manager';
        $res->slug = dirname( $this->plugin_basename );
        $res->version = $data->version ?? '';
        $res->author = $data->author ?? '';
        $res->homepage = $data->homepage ?? '';
        $res->requires = $data->requires ?? '6.0';
        $res->tested = $data->tested ?? '';
        $res->last_updated = $data->last_updated ?? '';
        $res->download_link = $data->download_url ?? '';
        $res->sections = array(
            'description' => $data->sections->description ?? '',
            'changelog'   => $data->sections->changelog ?? '',
        );
        return $res;
    }
    public function after_install( $res, $hook_extra, $result ){
        if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_basename ) return $res;
        if ( is_plugin_active( $this->plugin_basename ) ) {
            activate_plugin( $this->plugin_basename );
        }
        return $res;
    }
}
