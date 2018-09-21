<?php
// @see https://github.com/FacetWP/github-updater-lite
defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'GHU_Core' ) ) {
    class GHU_Core
    {
        public $update_data = array();
        public $active_plugins = array();


        function __construct() {
            add_action( 'admin_init', array( $this, 'admin_init' ) );
            add_filter( 'plugins_api', array( $this, 'plugins_api' ), 10, 3 );
            add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'set_update_data' ) );
            add_filter( 'upgrader_source_selection', array( $this, 'upgrader_source_selection' ), 10, 4 );
            add_filter( 'extra_plugin_headers', array( $this, 'extra_plugin_headers' ) );
        }


        function admin_init() {
            $now = strtotime( 'now' );
            $last_checked = (int) get_option( 'ghu_last_checked' );
            $check_interval = apply_filters( 'ghu_check_interval', ( 60 * 60 * 12 ) );
            $this->update_data = (array) get_option( 'ghu_update_data' );
            $active = (array) get_option( 'active_plugins' );

            foreach ( $active as $slug ) {
                $this->active_plugins[ $slug ] = true;
            }

            // transient expiration
            if ( ( $now - $last_checked ) > $check_interval ) {
                $this->update_data = $this->get_github_updates();

                update_option( 'ghu_update_data', $this->update_data );
                update_option( 'ghu_last_checked', $now );
            }
        }


        /**
         * Fetch the latest GitHub tags and build the plugin data array
         */
        function get_github_updates() {
            $plugin_data = array();
            $plugins = get_plugins();
            foreach ( $plugins as $slug => $info ) {
                if ( isset( $this->active_plugins[ $slug ] ) && ! empty( $info['GitHub URI'] ) ) {
                    $temp = array(
                        'plugin'            => $slug,
                        'slug'              => trim( dirname( $slug ), '/' ),
                        'name'              => $info['Name'],
                        'github_repo'       => $info['GitHub URI'],
                        'description'       => $info['Description'],
                    );

                    // get plugin tags
                    list( $owner, $repo ) = explode( '/', $temp['github_repo'] );
                    $request = wp_remote_get( "https://api.github.com/repos/$owner/$repo/tags" );

                    // WP error or rate limit exceeded
                    if ( is_wp_error( $request ) || 200 != wp_remote_retrieve_response_code( $request ) ) {
                        break;
                    }

                    $json = json_decode( $request['body'], true );

                    if ( is_array( $json ) && ! empty( $json ) ) {
                        $latest_tag = $json[0];
                        $temp['new_version'] = $latest_tag['name'];
                        $temp['url'] = "https://github.com/$owner/$repo/";
                        $temp['package'] = $latest_tag['zipball_url'];
                        $plugin_data[ $slug ] = $temp;
                    }
                }
            }

            return $plugin_data;
        }


        /**
         * Get plugin info for the "View Details" popup
         */
        function plugins_api( $default = false, $action, $args ) {
            if ( 'plugin_information' == $action ) {
                if ( isset( $this->update_data[ $args->slug ] ) ) {
                    $plugin = $this->update_data[ $args->slug ];

                    return (object) array(
                        'name'          => $plugin['name'],
                        'slug'          => $plugin['plugin'],
                        'version'       => $plugin['new_version'],
                        'requires'      => '4.4',
                        'tested'        => get_bloginfo( 'version' ),
                        'last_updated'  => date( 'Y-m-d' ),
                        'sections' => array(
                            'description' => $plugin['description']
                        )
                    );
                }
            }

            return $default;
        }


        function set_update_data( $transient ) {
            if ( empty( $transient->checked ) ) {
                return $transient;
            }

            foreach ( $this->update_data as $plugin => $info ) {
                if ( isset( $this->active_plugins[ $plugin ] ) ) {
                    $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
                    $version = $plugin_data['Version'];

                    if ( version_compare( $version, $info['new_version'], '<' ) ) {
                        $transient->response[ $plugin ] = (object) $info;
                    }
                }
            }

            return $transient;
        }


        /**
         * Rename the plugin folder
         */
        function upgrader_source_selection( $source, $remote_source, $upgrader, $hook_extra = null ) {
            global $wp_filesystem;

            $plugin = isset( $hook_extra['plugin'] ) ? $hook_extra['plugin'] : false;
            if ( isset( $this->update_data[ $plugin ] ) && $plugin ) {
                $new_source = trailingslashit( $remote_source ) . dirname( $plugin );
                $wp_filesystem->move( $source, $new_source );
                return trailingslashit( $new_source );
            }

            return $source;
        }


        /**
         * Parse the "GitHub URI" config too
         */
        function extra_plugin_headers( $headers ) {
            $headers[] = 'GitHub URI';
            return $headers;
        }
    }

    new GHU_Core();
}
