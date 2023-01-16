<?php
/*
    AdminNotices

    Displays admin notices in WP2Static screens according to rules
*/

namespace WP2Static;

class AdminNotices {

    /**
     * Displays WP2Static admin notices
     */
    public function showAdminNotices() : void {
        if ( ! self::userAllowedToSeeNotices() ) {
            return;
        }

        $class = 'notice notice-error wp2static-admin-notice';
        $title =
            'Super charge your static website using Strattic by Elementor!';
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $message = 'Enjoy blindingly fast, secure and simple WordPress static hosting, with dozens of dynamic features. Get 14 days for free. No credit card required!';
        $primary_button_title = 'Start my trial';
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $primary_button_url = 'https://www.strattic.com/pricing/?utm_campaign=start-trial&utm_source=wp2static&utm_medium=wp-dash&utm_term=general&utm_content=wp-notification-banner';
        $secondary_button_title = 'Learn more';
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $secondary_button_url = 'https://www.strattic.com/static-tools/?utm_campaign=learn-more&utm_source=wp2static&utm_medium=wp-dash&utm_term=general&utm_content=wp-notification-banner';

        printf(
            '<div class="%1$s"><b>%2$s</b><p>%3$s</p>' .
            // phpcs:disable Generic.Files.LineLength.TooLong
            '<a href="%4$s" target="_blank"><button class="button button-primary">%5$s</button></a>' .
            // phpcs:disable Generic.Files.LineLength.TooLong
            '<a href="%6$s" target="_blank"><button class="button button-secondary">%7$s</button></a>' .
            '<a href="#" class="wp2static-admin-notice-dismiss" id="wp2static-admin-notice-dismiss-generic">Don\'t show this again</a>' .
            '<div id="wp2static-admin-notice-nonce">' .
            wp_create_nonce( 'wp2static-admin-notice' ) . '</div>' .
            '</div>',
            esc_attr( $class ),
            esc_html( $title ),
            esc_html( $message ),
            esc_html( $primary_button_url ),
            esc_html( $primary_button_title ),
            esc_html( $secondary_button_url ),
            esc_html( $secondary_button_title )
        );
        $run_nonce = wp_create_nonce( 'wp2static-run-page' );
    }

    /**
     * Determine if a user should user see any notices
     */
    public function userAllowedToSeeNotices() : bool {
        return current_user_can( 'upload_plugins' );
    }

    public function handleDismissedNotice( string $dismissed_notice ) : void {
        check_ajax_referer( 'wp2static-admin-notice', 'security' );

        error_log( filter_input( INPUT_POST, 'dismissedNotice' ) );

        wp_die();
    }
}

