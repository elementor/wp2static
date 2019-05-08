<?php

namespace WP2Static;

use WP_CLI;

class Deployer extends Base {

    public function __construct() {
        $this->loadSettings(
            array(
                'advanced',
            )
        );
    }

    /*
        TODO: refactor all deploy code for CLI/unattended usage

              None of this expected to work currently
    */
    public function deploy( $test = false ) {
        $method = $this->settings['selected_deployment_option'];

        WP_CLI::log( 'Deploying static site via: ' . $method );

        $start_time = microtime( true );

        // TODO: reimplement CLI based deployment for add-ons / local

        $end_time = microtime( true );

        $duration = $end_time - $start_time;

        WP_CLI::success(
            'Deployed to: ' . $method . ' in ' .
            date( 'H:i:s', (int) $duration )
        );

        $this->finalizeDeployment();
    }

    public function finalizeDeployment() {
        $this->emailDeployNotification();
        $this->triggerPostDeployHooks();
    }

    public function emailDeployNotification() {
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

    public function triggerPostDeployHooks() {
        $this->archive = new Archive();

        do_action( 'wp2static_post_deploy_trigger', $this->archive );
    }
}
