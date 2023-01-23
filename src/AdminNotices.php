<?php
/*
    AdminNotices

    Displays admin notices in WP2Static screens according to rules
*/

namespace WP2Static;

class AdminNotices {

    public static function createTable() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_notices';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            user_id bigint(20) NOT NULL,
            action VARCHAR(191) NOT NULL,
            timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Displays WP2Static admin notices
     */
    public function showAdminNotices() : void {
        if ( ! self::userAllowedToSeeNotices() ) {
            return;
        }

        $notice_to_display = self::getNoticeBasedOnRules();

        if ( ! $notice_to_display ) {
            return;
        }

        self::logNoticeAction( $notice_to_display['name'], 'displayed' );

        printf(
            '<div class="%1$s"><b>%2$s</b><p>%3$s</p>' .
            // phpcs:disable Generic.Files.LineLength.TooLong
            '<a href="%4$s" target="_blank"><button class="button button-primary">%5$s</button></a>' .
            // phpcs:disable Generic.Files.LineLength.TooLong
            '<a href="%6$s" target="_blank"><button class="button button-secondary">%7$s</button></a>' .
            // phpcs:disable Generic.Files.LineLength.MaxExceeded
            '<a href="#" class="wp2static-admin-notice-dismiss" id="wp2static-admin-notice-dismiss-' .
            $notice_to_display['name'] . '">Don\'t show this again</a>' .
            '<div id="wp2static-admin-notice-nonce">' .
            wp_create_nonce( 'wp2static-admin-notice' ) . '</div>' .
            '<div id="wp2static-admin-notice-user-id">' .
            get_current_user_id() . '</div>' .
            '</div>',
            esc_attr( $notice_to_display['class'] ),
            esc_html( $notice_to_display['title'] ),
            esc_html( $notice_to_display['message'] ),
            esc_html( $notice_to_display['primary_button_url'] ),
            esc_html( $notice_to_display['primary_button_title'] ),
            esc_html( $notice_to_display['secondary_button_url'] ),
            esc_html( $notice_to_display['secondary_button_title'] )
        );
    }

    /**
     * Determine if a user should user see any notices
     */
    public function userAllowedToSeeNotices() : bool {
        return current_user_can( 'manage_options' );
    }

    public function handleDismissedNotice() : void {
        check_ajax_referer( 'wp2static-admin-notice', 'security' );

        $dismissed_notice = strval( filter_input( INPUT_POST, 'dismissedNotice' ) );

        self::logNoticeAction( $dismissed_notice, 'dismissed' );

        wp_die();
    }

    public function logNoticeAction( string $notice_name, string $action ) : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_notices';

        $current_user_id = get_current_user_id();

        $wpdb->insert(
            $table_name,
            [
                'name' => $notice_name,
                'user_id' => $current_user_id,
                'action' => $action,
            ]
        );
    }

    /**
     * Apply rules to determine which, if any, notice to show
     *
     * @return string[]|null
     */
    public function getNoticeBasedOnRules() {
        // don't show if no publish history (empty CrawlCache)
        if ( CrawlCache::getTotal() === 0 ) {
            return null;
        }

        // common notice properties
        $notice = [
            'name' => 'generic',
            'class' => 'notice notice-error wp2static-admin-notice',
            'primary_button_title' => 'Start my trial',
            'secondary_button_title' => 'Learn more',
        ];

        // show notice if Gravity Forms or Contact Form 7 active and hasn't dismissed notice
        // if both are active, prioritise showing message for Gravity Forms
        if ( ! self::noticeAlreadyDismissed( 'forms-plugin-activated' ) &&
            ( is_plugin_active( 'gravityforms/gravityforms.php' ) ||
                is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) )
        ) {
            $notice['name'] = 'forms-plugin-activated';

            if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
                $notice = array_merge( self::getNoticeContents( 'gravity-forms' ), $notice );
            } else {
                $notice = array_merge( self::getNoticeContents( 'contact-form-7' ), $notice );
            }
            // if both are active, prioritise showing message for WPML
        } elseif ( ! self::noticeAlreadyDismissed( 'multilingual-plugin-activated' ) &&
            ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ||
                is_plugin_active( 'polylang/polylang.php' ) )
        ) {
            $notice['name'] = 'multilingual-plugin-activated';

            if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
                $notice = array_merge( self::getNoticeContents( 'wpml' ), $notice );
            } else {
                $notice = array_merge( self::getNoticeContents( 'polylang' ), $notice );
            }
        } elseif ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            // if at least one WooCommerce order exists, don't show any notices
            global $wpdb;
            $table_name = $wpdb->prefix . 'wc_order_stats';
            $woocommerce_orders = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT count(*) FROM $table_name"
                )
            );

            if ( $woocommerce_orders ) {
                return null;
            }

            $notice = array_merge( self::getNoticeContents( 'generic' ), $notice );
        } else {
            // if no other notices to be shown, fall back to this generic one
            $notice = array_merge( self::getNoticeContents( 'generic' ), $notice );
        }

        if ( self::noticeAlreadyDismissed( $notice['name'] ) ) {
            return null;
        }

        return $notice;
    }

    public function noticeAlreadyDismissed( string $notice_name ) : bool {
        // don't show if same notice has been dismissed by this user
        $current_user_id = get_current_user_id();

        // query for notice matching name, user id and 'dismissed' action
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_notices';

        $sql = $wpdb->prepare(
            "SELECT count(*) FROM $table_name WHERE" .
            ' name = %s AND user_id = %s AND action = "dismissed"',
            $notice_name,
            $current_user_id
        );

        $already_dismissed = $wpdb->get_var( $sql );

        return $already_dismissed != 0;
    }

    /**
     * Get notice contents for a specified notice
     *
     * @return string[]
     */
    public function getNoticeContents( string $notice_name ) {
        $notice_contents = [];
        switch ( $notice_name ) {
            case 'generic':
                $notice_contents = [
                    'title' =>
                        'Super charge your static website using Strattic by Elementor!',
                    // phpcs:disable Generic.Files.LineLength.MaxExceeded
                    'message' => 'Enjoy blindingly fast, secure and simple WordPress static hosting, with dozens of dynamic features. Get 14 days for free. No credit card required!',
                    // phpcs:disable Generic.Files.LineLength.MaxExceeded
                    'primary_button_url' => 'https://www.strattic.com/pricing/?utm_campaign=start-trial&utm_source=wp2static&utm_medium=wp-dash&utm_term=general&utm_content=wp-notification-banner',
                    // phpcs:disable Generic.Files.LineLength.MaxExceeded
                    'secondary_button_url' => 'https://www.strattic.com/static-tools/?utm_campaign=learn-more&utm_source=wp2static&utm_medium=wp-dash&utm_term=general&utm_content=wp-notification-banner',
                ];
                break;
            case 'contact-form-7':
                $notice_contents = [
                    'title' =>
                        'Want integrated forms on your static site, out-of-the-box?',
                    // phpcs:disable Generic.Files.LineLength.MaxExceeded
                    'message' => 'Strattic by Elementor lets you use Contact Form 7 on your static site, without implementing any extra configurations. Get it, plus secure and simple WordPress static hosting. Start a 14 days free trial today, no credit card required!',
                    // phpcs:disable Generic.Files.LineLength.MaxExceeded
                    'primary_button_url' => 'https://www.strattic.com/static-tools/?utm_campaign=start-trial&utm_source=wp2static&utm_medium=wp-dash&utm_term=plugin-form&utm_content=wp-notification-banner',
                    // phpcs:disable Generic.Files.LineLength.MaxExceeded
                    'secondary_button_url' => 'https://www.strattic.com/static-tools/?utm_campaign=learn-more&utm_source=wp2static&utm_medium=wp-dash&utm_term=plugin-form&utm_content=wp-notification-banner',
                ];
                break;
            case 'gravity-forms':
                $notice_contents = [
                    'title' =>
                        'Want integrated forms on your static site, out-of-the-box?',
                    // phpcs:disable Generic.Files.LineLength.MaxExceeded
                    'message' => 'Strattic by Elementor lets you use Gravity Forms on your static site, without implementing any extra configurations. Get it, plus secure and simple WordPress static hosting. Start a 14 days free trial today, no credit card required!',
                    // phpcs:disable Generic.Files.LineLength.MaxExceeded
                    'primary_button_url' => 'https://www.strattic.com/static-tools/?utm_campaign=start-trial&utm_source=wp2static&utm_medium=wp-dash&utm_term=plugin-form&utm_content=wp-notification-banner',
                    // phpcs:disable Generic.Files.LineLength.MaxExceeded
                    'secondary_button_url' => 'https://www.strattic.com/static-tools/?utm_campaign=learn-more&utm_source=wp2static&utm_medium=wp-dash&utm_term=plugin-form&utm_content=wp-notification-banner',
                ];
                break;
            case 'wpml':
                $notice_contents = [
                    'title' =>
                        'Create dynamic, multi-lingual static websites using Strattic by Elementor!',
                    // phpcs:disable Generic.Files.LineLength.MaxExceeded
                    'message' => 'Strattic by Elementor lets you use WPML on your static site, without implementing any extra configurations. Get 14 days for free. No credit card required!',
                    // phpcs:disable Generic.Files.LineLength.MaxExceeded
                    'primary_button_url' => 'https://www.strattic.com/pricing/?utm_campaign=start-trial&utm_source=wp2static&utm_medium=wp-dash&utm_term=multilingual&utm_content=wp-notification-banner',
                    // phpcs:disable Generic.Files.LineLength.MaxExceeded
                    'secondary_button_url' => 'https://www.strattic.com/static-tools/?utm_campaign=learn-more&utm_source=wp2static&utm_medium=wp-dash&utm_term=multilingual&utm_content=wp-notification-banner',
                ];
                break;
            case 'polylang':
                $notice_contents = [
                    'title' =>
                        'Create dynamic, multi-lingual static websites using Strattic by Elementor!',
                    // phpcs:disable Generic.Files.LineLength.MaxExceeded
                    'message' => 'Strattic by Elementor lets you use Polylang on your static site, without implementing any extra configurations. Get 14 days for free. No credit card required!',
                    // phpcs:disable Generic.Files.LineLength.MaxExceeded
                    'primary_button_url' => 'https://www.strattic.com/pricing/?utm_campaign=start-trial&utm_source=wp2static&utm_medium=wp-dash&utm_term=multilingual&utm_content=wp-notification-banner',
                    // phpcs:disable Generic.Files.LineLength.MaxExceeded
                    'secondary_button_url' => 'https://www.strattic.com/static-tools/?utm_campaign=learn-more&utm_source=wp2static&utm_medium=wp-dash&utm_term=multilingual&utm_content=wp-notification-banner',
                ];
                break;
        }

        return $notice_contents;
    }
}

