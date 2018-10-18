<?php
/**
 * Handles version updates and should only be instantiated in autoptimize.php if/when needed.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class autoptimizeVersionUpdatesHandler
{
    /**
     * The current major version string.
     *
     * @var string
     */
    protected $current_major_version = null;

    public function __construct( $current_version )
    {
        $this->current_major_version = substr( $current_version, 0, 3 );
    }

    /**
     * Runs all needed upgrade procedures (depending on the
     * current major version specified during class instantiation)
     */
    public function run_needed_major_upgrades()
    {
        $major_update = false;

        switch ( $this->current_major_version ) {
            case '1.6':
                $this->upgrade_from_1_6();
                $major_update = true;
                // No break, intentionally, so all upgrades are ran during a single request...
            case '1.7':
                $this->upgrade_from_1_7();
                $major_update = true;
                // No break, intentionally, so all upgrades are ran during a single request...
            case '1.9':
                $this->upgrade_from_1_9();
                $major_update = true;
                // No break, intentionally, so all upgrades are ran during a single request...
            case '2.2':
                $this->upgrade_from_2_2();
                $major_update = true;
                // No break, intentionally, so all upgrades are ran during a single request...
        }

        if ( true === $major_update ) {
            $this->on_major_version_update();
        }
    }

    /**
     * Checks specified version against the one stored in the database under `autoptimize_version` and performs
     * any major upgrade routines if needed.
     * Updates the database version to the specified $target if it's different to the one currently stored there.
     *
     * @param string $target Target version to check against (ie., the currently running one).
     */
    public static function check_installed_and_update( $target )
    {
        $db_version = get_option( 'autoptimize_version', 'none' );
        if ( $db_version !== $target ) {
            if ( 'none' === $db_version ) {
                add_action( 'admin_notices', 'autoptimizeMain::notice_installed' );
            } else {
                $updater = new self( $db_version );
                $updater->run_needed_major_upgrades();
            }

            // Versions differed, upgrades happened if needed, store the new version.
            update_option( 'autoptimize_version', $target );
        }
    }

    /**
     * Called after any major version update (and it's accompanying upgrade procedure)
     * has happened. Clears cache and sets an admin notice.
     */
    protected function on_major_version_update()
    {
        // The transients guard here prevents stale object caches from busting the cache on every request.
        if ( false == get_transient( 'autoptimize_stale_option_buster' ) ) {
            set_transient( 'autoptimize_stale_option_buster', 'Mamsie & Liessie zehhe: ZWIJH!', HOUR_IN_SECONDS );
            autoptimizeCache::clearall();
            add_action( 'admin_notices', 'autoptimizeMain::notice_updated' );
        }
    }

    /**
     * From back in the days when I did not yet consider multisite.
     */
    private function upgrade_from_1_6()
    {
        // If user was on version 1.6.x, force advanced options to be shown by default.
        update_option( 'autoptimize_show_adv', '1' );

        // And remove old options.
        $to_delete_options = array(
            'autoptimize_cdn_css',
            'autoptimize_cdn_css_url',
            'autoptimize_cdn_js',
            'autoptimize_cdn_js_url',
            'autoptimize_cdn_img',
            'autoptimize_cdn_img_url',
            'autoptimize_css_yui',
        );
        foreach ( $to_delete_options as $del_opt ) {
            delete_option( $del_opt );
        }
    }

    /**
     * Forces WP 3.8 dashicons in CSS exclude options when upgrading from 1.7 to 1.8
     *
     * @global $wpdb
     */
    private function upgrade_from_1_7()
    {
        if ( ! is_multisite() ) {
            $css_exclude = get_option( 'autoptimize_css_exclude' );
            if ( empty( $css_exclude ) ) {
                $css_exclude = 'admin-bar.min.css, dashicons.min.css';
            } elseif ( false === strpos( $css_exclude, 'dashicons.min.css' ) ) {
                $css_exclude .= ', dashicons.min.css';
            }
            update_option( 'autoptimize_css_exclude', $css_exclude );
        } else {
            global $wpdb;
            $blog_ids         = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
            $original_blog_id = get_current_blog_id();
            foreach ( $blog_ids as $blog_id ) {
                switch_to_blog( $blog_id );
                $css_exclude = get_option( 'autoptimize_css_exclude' );
                if ( empty( $css_exclude ) ) {
                    $css_exclude = 'admin-bar.min.css, dashicons.min.css';
                } elseif ( false === strpos( $css_exclude, 'dashicons.min.css' ) ) {
                    $css_exclude .= ', dashicons.min.css';
                }
                update_option( 'autoptimize_css_exclude', $css_exclude );
            }
            switch_to_blog( $original_blog_id );
        }
    }

    /**
     * 2.0 will not aggregate inline CSS/JS by default, but we want users
     * upgrading from 1.9 to keep their inline code aggregated by default.
     *
     * @global $wpdb
     */
    private function upgrade_from_1_9()
    {
        if ( ! is_multisite() ) {
            update_option( 'autoptimize_css_include_inline', 'on' );
            update_option( 'autoptimize_js_include_inline', 'on' );
        } else {
            global $wpdb;
            $blog_ids         = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
            $original_blog_id = get_current_blog_id();
            foreach ( $blog_ids as $blog_id ) {
                switch_to_blog( $blog_id );
                update_option( 'autoptimize_css_include_inline', 'on' );
                update_option( 'autoptimize_js_include_inline', 'on' );
            }
            switch_to_blog( $original_blog_id );
        }
    }

    /**
     * 2.3 has no "remove google fonts" in main screen, moved to "extra"
     *
     * @global $wpdb
     */
    private function upgrade_from_2_2()
    {
        if ( ! is_multisite() ) {
            $this->do_2_2_settings_update();
        } else {
            global $wpdb;
            $blog_ids         = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
            $original_blog_id = get_current_blog_id();
            foreach ( $blog_ids as $blog_id ) {
                switch_to_blog( $blog_id );
                $this->do_2_2_settings_update();
            }
            switch_to_blog( $original_blog_id );
        }
    }

    /**
     * Helper for 2.2 autoptimize_extra_settings upgrade to avoid duplicate code
     */
    private function do_2_2_settings_update()
    {
        $nogooglefont    = get_option( 'autoptimize_css_nogooglefont', '' );
        $ao_extrasetting = get_option( 'autoptimize_extra_settings', '' );
        if ( ( $nogooglefont ) && ( empty( $ao_extrasetting ) ) ) {
            update_option( 'autoptimize_extra_settings', autoptimizeConfig::get_ao_extra_default_options() );
        }
        delete_option( 'autoptimize_css_nogooglefont' );
    }
}
