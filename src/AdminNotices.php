<?php
/*
    AdminNotices

    Displays admin notices in WP2Static screens according to rules 
*/

namespace WP2Static;

class AdminNotices {

    /**
     * AdminNotices constructor
     */
    public function __construct() {

    }

    /**
     * Displays WP2Static admin notices
     */
    public static function showAdminNotices() : void {
        $class = 'notice notice-error is-dismissable wp2static-admin-notice';
        $title = 'Super charge your static website using Strattic by Elementor!';
        $message = "Enjoy blindingly fast, secure and simple WordPress static hosting, with dozens of dynamic features. Get 14 days for free. No credit card required!";
        $primary_button_title = 'Start my trial';
        $primary_button_url = 'https://www.strattic.com/pricing/?utm_campaign=start-trial&utm_source=wp2static&utm_medium=wp-dash&utm_term=general&utm_content=wp-notification-banner';
        $secondary_button_title = 'Learn more';
        $secondary_button_url = 'https://www.strattic.com/static-tools/?utm_campaign=learn-more&utm_source=wp2static&utm_medium=wp-dash&utm_term=general&utm_content=wp-notification-banner';

        printf(
            '<div class="%1$s"><b>%2$s</b><p>%3$s</p>' .
            '<a href="%4$s" target="_blank"><button class="button button-primary">%5$s</button></a>' .
            '<a href="%6$s" target="_blank"><button class="button button-secondary">%7$s</button></a>' .
            '</div>',
            esc_attr( $class ),
            esc_html( $title ),
            esc_html( $message ),
            esc_html( $primary_button_url ),
            esc_html( $primary_button_title ),
            esc_html( $secondary_button_url ),
            esc_html( $secondary_button_title )
        );
    }
}

