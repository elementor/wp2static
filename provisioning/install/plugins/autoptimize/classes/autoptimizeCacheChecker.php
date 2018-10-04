<?php
/**
 * CacheChecker - new in AO 2.0
 *
 * Daily cronned job (filter to change freq. + filter to disable).
 * Checks if cachesize is > 0.5GB (size is filterable), if so, an option is set which controls showing an admin notice.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class autoptimizeCacheChecker
{
    const SCHEDULE_HOOK = 'ao_cachechecker';

    public function __construct()
    {
    }

    public function run()
    {
        $this->add_hooks();
    }

    public function add_hooks()
    {
        if ( is_admin() ) {
            add_action( 'plugins_loaded', array( $this, 'setup' ) );
        }
        add_action( self::SCHEDULE_HOOK, array( $this, 'cronjob' ) );
        add_action( 'admin_notices', array( $this, 'show_admin_notice' ) );
    }

    public function setup()
    {
        $do_cache_check = (bool) apply_filters( 'autoptimize_filter_cachecheck_do', true );
        $schedule       = wp_get_schedule( self::SCHEDULE_HOOK );
        $frequency      = apply_filters( 'autoptimize_filter_cachecheck_frequency', 'daily' );
        if ( ! in_array( $frequency, array( 'hourly', 'daily', 'weekly', 'monthly' ) ) ) {
            $frequency = 'daily';
        }
        if ( $do_cache_check && ( ! $schedule || $schedule !== $frequency ) ) {
            wp_schedule_event( time(), $frequency, self::SCHEDULE_HOOK );
        } elseif ( $schedule && ! $do_cache_check ) {
            wp_clear_scheduled_hook( self::SCHEDULE_HOOK );
        }
    }

    public function cronjob()
    {
        // Check cachesize and act accordingly.
        $max_size       = (int) apply_filters( 'autoptimize_filter_cachecheck_maxsize', 536870912 );
        $do_cache_check = (bool) apply_filters( 'autoptimize_filter_cachecheck_do', true );
        $stat_array     = autoptimizeCache::stats();
        $cache_size     = round( $stat_array[1] );
        if ( ( $cache_size > $max_size ) && ( $do_cache_check ) ) {
            update_option( 'autoptimize_cachesize_notice', true );
            if ( apply_filters( 'autoptimize_filter_cachecheck_sendmail', true ) ) {
                $site_url  = esc_url( site_url() );
                $ao_mailto = apply_filters( 'autoptimize_filter_cachecheck_mailto', get_option( 'admin_email', '' ) );

                $ao_mailsubject = __( 'Autoptimize cache size warning', 'autoptimize' ) . ' (' . $site_url . ')';
                $ao_mailbody    = __( 'Autoptimize\'s cache size is getting big, consider purging the cache. Have a look at https://wordpress.org/plugins/autoptimize/faq/ to see how you can keep the cache size under control.', 'autoptimize' ) . ' (site: ' . $site_url . ')';

                if ( ! empty( $ao_mailto ) ) {
                    $ao_mailresult = wp_mail( $ao_mailto, $ao_mailsubject, $ao_mailbody );
                    if ( ! $ao_mailresult ) {
                        error_log( 'Autoptimize could not send cache size warning mail.' );
                    }
                }
            }
        }

        // Check if 3rd party services (e.g. image proxy) are up.
        autoptimizeUtils::check_service_availability();

        // Check image optimization stats.
        autoptimizeExtra::get_img_provider_stats();

        // Nukes advanced cache clearing artifacts if they exists...
        autoptimizeCache::delete_advanced_cache_clear_artifacts();
    }

    public function show_admin_notice()
    {
        // fixme: make notices dismissable.
        if ( (bool) get_option( 'autoptimize_cachesize_notice', false ) ) {
            echo '<div class="notice notice-warning"><p>';
            _e( '<strong>Autoptimize\'s cache size is getting big</strong>, consider purging the cache. Have a look at <a href="https://wordpress.org/plugins/autoptimize/faq/" target="_blank" rel="noopener noreferrer">the Autoptimize FAQ</a> to see how you can keep the cache size under control.', 'autoptimize' );
            echo '</p></div>';
            update_option( 'autoptimize_cachesize_notice', false );
        }

        // Notice for image proxy usage.
        $_imgopt_notice = autoptimizeExtra::get_imgopt_status_notice_wrapper();
        if ( is_array( $_imgopt_notice ) && array_key_exists( 'status', $_imgopt_notice ) && in_array( $_imgopt_notice['status'], array( 1, -1 ) ) ) {
            $_dismissible = 'ao-img-opt-notice-';
            $_hide_notice = '7';

            if ( -1 == $_imgopt_notice['status'] ) {
                $_hide_notice = '1';
            }

            $_imgopt_notice_dismissible = apply_filters( 'autoptimize_filter_imgopt_notice_dismissable', $_dismissible . $_hide_notice );

            if ( $_imgopt_notice && PAnD::is_admin_notice_active( $_imgopt_notice_dismissible ) ) {
                echo '<div class="notice notice-warning is-dismissible" data-dismissible="' . $_imgopt_notice_dismissible . '"><p>' . $_imgopt_notice['notice'] . '</p></div>';
            }
        }
    }
}
