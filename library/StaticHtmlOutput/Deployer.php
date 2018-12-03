<?php


class Deployer {

    public function __construct() {

        switch ( $this->selected_deployment_option ) {
            case 'folder':
                $this->copyStaticSiteToPublicFolder();
                break;

            case 'zip':
                $this->create_zip();
                break;
        }

        // TODO: email upon successful cron deploy
        // $this->emailDeployNotification();
        error_log( 'scheduled deploy complete' );
    }

    public function emailDeployNotification() {
        $current_user = wp_get_current_user();
        $to = $current_user->user_email;
        $subject = 'Static site deployment: ' .
            $site_title = get_bloginfo( 'name' );
        $body = 'Your WordPress site has been automatically deployed.';
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        wp_mail( $to, $subject, $body, $headers );
    }
}
