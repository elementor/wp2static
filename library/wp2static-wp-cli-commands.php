<?php

/**
 * Just a few sample commands to learn how WP-CLI works
 */
class WP2Static_CLI extends WP_CLI_Command {
    /**
     * Display system information and health check
     */
    public function diagnostics() {
        WP_CLI::line(
            PHP_EOL . 'WP2Static' . PHP_EOL
        );

        $environmental_info = array(
            array(
                'key' => 'PLUGIN VERSION',
                'value' => StaticHtmlOutput_Controller::VERSION,
            ),
            array(
                'key' => 'PHP_VERSION',
                'value' => phpversion(),
            ),
            array(
                'key' => 'PHP MAX EXECUTION TIME',
                'value' => ini_get( 'max_execution_time' ),
            ),
            array(
                'key' => 'OS VERSION',
                'value' => php_uname(),
            ),
            array(
                'key' => 'WP VERSION',
                'value' => get_bloginfo( 'version' ),
            ),
            array(
                'key' => 'WP URL',
                'value' => get_bloginfo( 'url' ),
            ),
            array(
                'key' => 'WP SITEURL',
                'value' => get_option( 'siteurl' ),
            ),
            array(
                'key' => 'WP HOME',
                'value' => get_option( 'home' ),
            ),
            array(
                'key' => 'WP ADDRESS',
                'value' => get_bloginfo( 'wpurl' ),
            ),
        );

        WP_CLI\Utils\format_items(
            'table',
            $environmental_info,
            array( 'key', 'value' )
        );

        $active_plugins = get_option( 'active_plugins' );

        WP_CLI::line( PHP_EOL . 'Active plugins:' . PHP_EOL );

        foreach ( $active_plugins as $active_plugin ) {
            WP_CLI::line( $active_plugin );
        }

        WP_CLI::line( PHP_EOL );

        WP_CLI::line(
            'There are a total of ' . count( $active_plugins ) .
            ' active plugins on this site.' . PHP_EOL
        );

    }

    /**
     * Get / set plugin settings.
     * ## OPTIONS
     *
     * [--mask-passwords]
     * : Don't show passwords in plain text
     */
    public function options( $args, $assoc_args ) {
        if ( ! empty( $assoc_args['get'] ) ) {
            WP_CLI::line( 'getting wp2static options' );
        } elseif ( ! empty( $assoc_args['set'] ) ) {
            WP_CLI::line( 'setting wp2static options' );
        } elseif ( ! empty( $assoc_args['reset'] ) ) {
            WP_CLI::line( 'resetting to default options' );
        } else {
            WP_CLI::line( 'show help about cmd here' );
        }
    }
}

WP_CLI::add_command( 'wp2static', 'wp2static_cli' );
