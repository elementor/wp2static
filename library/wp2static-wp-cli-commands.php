<?php

/**
 * Generate a static copy of your website & publish remotely
 */
class WP2Static_CLI {
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
     * Read / write plugin options
     *
     * ## OPTIONS
     *
     * <list> [--reveal-sensitive-values]
     *
     * Get all option names and values (explicitly reveal sensitive values)
     *
     * <get> <option-name>
     *
     * Get or set a specific option via name
     *
     * <set> <option-name> <value>
     *
     * Set a specific option via name
     *
     *
     * ## EXAMPLES
     *
     * List all options
     *
     *     wp wp2static options list
     *
     * List all options (revealing sensitive values)
     *
     *     wp wp2static options list --reveal_sensitive_values
     *
     * Get option
     *
     *     wp wp2static options get selected_deployment_option
     *
     * Set option
     *
     *     wp wp2static options set baseUrl 'https://mystaticsite.com'
     */
    public function options( $args, $assoc_args ) {
        $action = $args[0];
        $option_name = $args[1];
        $value = $args[2];
        $reveal_sensitive_values = false;

        if ( empty( $action ) ) {
            WP_CLI::error( 'Missing required argument: <get|set>>' );
        }

        $plugin = StaticHtmlOutput_Controller::getInstance();

        if ( $action === 'get' ) {
            if ( empty( $option_name ) ) {
                WP_CLI::error( 'Missing required argument: <option-name>' );
            }

            if ( ! $plugin->options->optionExists( $option_name ) ) {
                WP_CLI::error( 'Invalid option name' );
            } else {
                $option_value =
                    $plugin->options->getOption( $option_name );

                WP_CLI::line( $option_value );
            }
        }

        if ( $action === 'set' ) {
            if ( empty( $option_name ) ) {
                WP_CLI::error( 'Missing required argument: <option-name>' );
            }

            if ( empty( $value ) ) {
                WP_CLI::error( 'Missing required argument: <value>' );
            }

            if ( ! $plugin->options->optionExists( $option_name ) ) {
                WP_CLI::error( 'Invalid option name' );
            } else {
                $plugin->options->setOption( $option_name, $value );
                $plugin->options->save();

                $result = $plugin->options->getOption( $option_name );

                if ( ! $result === $value ) {
                    WP_CLI::error( 'Option not able to be updated' );
                }
            }
        }

        if ( $action === 'list' ) {
            if ( isset( $assoc_args['reveal-sensitive-values'] ) ) {
                $reveal_sensitive_values = true;
            }

            $options =
                $plugin->options->getAllOptions( $reveal_sensitive_values );

            WP_CLI\Utils\format_items(
                'table',
                $options,
                array( 'Option name', 'Value' )
            );
        }
    }

    public function microtime_diff( $start, $end = null ) {
        if ( ! $end ) {
            $end = microtime();
        }

        list( $start_usec, $start_sec ) = explode( ' ', $start );
        list( $end_usec, $end_sec ) = explode( ' ', $end );

        $diff_sec = intval( $end_sec ) - intval( $start_sec );
        $diff_usec = floatval( $end_usec ) - floatval( $start_usec );

        return floatval( $diff_sec ) + $diff_usec;
    }

    /**
     * Generate a static copy of your WordPress site.
     */
    public function generate() {
        $start_time = microtime();

        $plugin = StaticHtmlOutput_Controller::getInstance();
        $plugin->generate_filelist_preview();
        $plugin->prepare_for_export();

        require_once dirname( __FILE__ ) .
            '/StaticHtmlOutput/SiteCrawler.php';

        $site_crawler->crawl_site();
        $site_crawler->crawl_discovered_links();
        $plugin->post_process_archive_dir();

        $end_time = microtime();

        $duration = $this->microtime_diff( $start_time, $end_time );

        WP_CLI::success(
            "Generated static site archive in $duration seconds"
        );
    }

    /**
     * Deploy the generated static site.
     * ## OPTIONS
     *
     * [--test]
     * : Validate the connection settings without deploying
     *
     * [--selected_deployment_option]
     * : Override the deployment option
     */
    public function deploy( $args, $assoc_args ) {
        $test = false;

        if ( ! empty( $assoc_args['test'] ) ) {
            $test = true;
        }

        if ( ! empty( $assoc_args['selected_deployment_option'] ) ) {
            switch ( $assoc_args['selected_deployment_option'] ) {
                case 'zip':
                    break;
            }
        }

        require_once dirname( __FILE__ ) . '/StaticHtmlOutput/Deployer.php';

        $deployer = new Deployer();

        $deployer->deploy( $test );
    }
}

WP_CLI::add_command( 'wp2static', 'wp2static_cli' );

/*
TODO:

WP_CLI\Utils\launch_editor_for_input() – Launch system’s $EDITOR f
r the user to edit some text.

use that for inputting things like additional URLs, Netlify _redirects, etc

TODO: use WP error for things like permalinks. Run on every command?
no, just diagnostics

*/
