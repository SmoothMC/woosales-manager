<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Woo_Sales_Manager_Updater {

    private $update_url = 'https://cdn.zzzooo.studio/wp-plugins/woosales-manager/update.json';
    private $plugin_file = 'woosales-manager/woosales-manager.php';

    public function __construct() {
        add_filter( 'site_transient_update_plugins', [ $this, 'check_for_update' ] );
        add_filter( 'plugins_api', [ $this, 'plugins_api' ], 10, 3 );
        add_filter( 'auto_update_plugin', [ $this, 'enable_auto_update' ], 10, 2 );
    }

    public function check_for_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $response = wp_remote_get( $this->update_url );
        if ( is_wp_error( $response ) ) return $transient;

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! $data ) return $transient;

        if ( version_compare( $data['version'], $transient->checked[ $this->plugin_file ], '>' ) ) {
            $transient->response[ $this->plugin_file ] = (object) [
                'slug'         => 'woosales-manager',
                'plugin'       => $this->plugin_file,
                'new_version'  => $data['version'],
                'url'          => $data['url'],
                'package'      => $data['download_url'],
                'tested'       => $data['tested'] ?? '',
                'requires'     => $data['requires'] ?? '',
                'requires_php' => '7.4',
            ];
        }

        return $transient;
    }

    public function plugins_api( $result, $action, $args ) {
        if ( $action !== 'plugin_information' || $args->slug !== 'woosales-manager' ) {
            return $result;
        }

        $response = wp_remote_get( $this->update_url );
        if ( is_wp_error( $response ) ) return $result;

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! $data ) return $result;

        return (object)[
            'name'          => $data['name'],
            'slug'          => 'woosales-manager',
            'version'       => $data['version'],
            'author'        => '<a href="https://zzzooo.studio/">SmoothMC | zzzooo Studio</a>',
            'homepage'      => $data['url'],
            'download_link' => $data['download_url'],
            'requires'      => $data['requires'],
            'tested'        => $data['tested'],
            'sections'      => [
                'description' => $data['description'],
                'changelog'   => nl2br($data['changelog']),
            ],
        ];
    }

    public function enable_auto_update( $update, $item ) {
        return ( isset($item->slug) && $item->slug === 'woosales-manager' )
            ? true
            : $update;
    }
}
