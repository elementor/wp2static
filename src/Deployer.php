<?php

namespace WP2Static;

use WP_CLI;

class Deployer {

    private $settings;

    public function __construct() {
        $plugin = Controller::getInstance();
        $this->settings = $plugin->options->getSettings( true );
    }

    /*
        TODO: refactor all deploy code for CLI/unattended usage

              None of this expected to work currently
    */
    public function deploy( bool $test = false ) : void {
        $method = $this->settings['selected_deployment_option'];

        if ( defined( 'WP_CLI' ) ) {
            WP_CLI::log( 'Deploying static site via: ' . $method );
        } else {
            $msg = 'Starting headless deployment of ' . $method . ' method';
            WsLog::l( $msg );
        }

        $start_time = microtime( true );

        // give the selected_deployment_option to Add-ons to determine if
        // they should do their deployment actions
        do_action(
            'wp2static_addon_trigger_deploy',
            $method
        );

        $end_time = microtime( true );

        $duration = $end_time - $start_time;

        $via_ui = filter_input( INPUT_POST, 'ajax_action' );

        // Note when running via UI, we save all options
        if ( ! is_string( $via_ui ) ) {
            $msg = 'Deployed to: ' . $method . ' in ' .
                date( 'H:i:s', (int) $duration );
            if ( defined( 'WP_CLI' ) ) {
                WP_CLI::success( $msg );
            } else {
                WsLog::l( $msg );
            }
        }

        $this->finalizeDeployment();
    }

    public function finalizeDeployment() : void {
        $this->emailDeployNotification();
        $this->triggerPostDeployHooks();
    }

    public function emailDeployNotification() : void {
        if ( ! isset( $this->settings['completionEmail'] ) ) {
            return;
        }

        if ( defined( 'WP_CLI' ) ) {
            WP_CLI::line( 'Sending confirmation email...' );
        }

        $current_user = wp_get_current_user();
        $to = $current_user->user_email;
        $subject = 'Static site deployment: ' .
            $site_title = get_bloginfo( 'name' );
        $body = 'Your WordPress site has been automatically deployed.';
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        wp_mail( $to, $subject, $body, $headers );
    }

    public function triggerPostDeployHooks() : void {
        $archive_path = SiteInfo::getPath( 'uploads' ) .
                'wp2static-exported-site/';

        do_action( 'wp2static_post_deploy_trigger', $archive_path );
    }
}
