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
     * Get / set plugin settings.
     * ## OPTIONS
     *
     * <get|set> [option-name]
     * : Get all option names and values (get singular with --option-name)
     *
     * [--option-name]
     * : Get stored value for singular option
     *
     * [--mask-passwords]
     * : Don't show passwords in plain text
     *
     * ## EXAMPLES
     *
     * Get all options
     *
     * wp wp2static options get --option-name="selected_deployment_option"
     */
    public function options( $args, $assoc_args ) {
        error_log(print_r($args, true));
        error_log(print_r($assoc_args, true));

        $action = $arg[0];
        $option_name = $arg[1];

        if ( ! empty( $assoc_args['get'] ) ) {
            if ( ! empty( $assoc_args['option-name'] ) ) {
                $option_name = $assoc_args['option-name'];


                $plugin = StaticHtmlOutput_Controller::getInstance();

                if ( ! $plugin->options->optionExists( $option_name ) ) {
                    WP_CLI::error( 'Invalid option name' );
                } else {
                    $option_value = $plugin->options->getOption($option_name);

                    WP_CLI::line($option_value);
                }
            } else {
                WP_CLI::line( 'returning all option key:values' );
            }
        } elseif ( ! empty( $assoc_args['set'] ) ) {
            WP_CLI::line( 'setting wp2static options' );
        } elseif ( ! empty( $assoc_args['reset'] ) ) {
            WP_CLI::line( 'resetting to default options' );
        } else {
            WP_CLI::line( 'show help about cmd here' );
        }
    }

    /**
     * Generate a static copy of your WordPress site.
     */
    public function generate() {
        $plugin = StaticHtmlOutput_Controller::getInstance();

        // TODO: reimplement diff-based deploys
        // $plugin->capture_last_deployment();

        $plugin->prepare_for_export();

        require_once dirname( __FILE__ ) .
            '/StaticHtmlOutput/SiteCrawler.php';

        $site_crawler->crawl_site();
        $site_crawler->crawl_discovered_links();
        $plugin->post_process_archive_dir();
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
            switch( $assoc_args['selected_deployment_option'] ) {
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
Note: Historically, WP-CLI provided a base WP_CLI_Command class to extend, however extending this class is not required and will not change how your command behaves.

All commands can be registered to their own top-level namespace (e.g. wp foo), or as subcommands to an existing namespace (e.g. wp core foo). For the latter, simply include the existing namespace as a part of the command definition.

class Foo_Command {
    public function __invoke( $args ) {
        WP_CLI::success( $args[0] );
    }
}

WP_CLI::add_command( 'core foo', 'Foo_Command' );

https://make.wordpress.org/cli/handbook/commands-cookbook/

// 1. Command is a function
function foo_command( $args ) {
    WP_CLI::success( $args[0] );
}
WP_CLI::add_command( 'foo', 'foo_command' );

// 2. Command is a closure
$foo_command = function( $args ) {
    WP_CLI::success( $args[0] );
}
WP_CLI::add_command( 'foo', $foo_command );

// 3. Command is a method on a class
class Foo_Command {
    public function __invoke( $args ) {
        WP_CLI::success( $args[0] );
    }
}
WP_CLI::add_command( 'foo', 'Foo_Command' );

// 4. Command is a method on a class with constructor arguments
class Foo_Command {
    protected $bar;
    public function __construct( $bar ) {
        $this->bar = $bar;
    }
    public function __invoke( $args ) {
        WP_CLI::success( $this->bar . ':' . $args[0] );
    }
}
$instance = new Foo_Command( 'Some text' );
WP_CLI::add_command( 'foo', $instance );


TODO:

WP_CLI\Utils\launch_editor_for_input() – Launch system’s $EDITOR for the user to edit some text.

use that for inputting things like additional URLs, Netlify _redirects, etc

TODO: use WP error for things like permalinks. Run on every command? no, just diagnostics

*/

WP_CLI::add_command( 'wp2static', 'WP2Static_CLI' );
