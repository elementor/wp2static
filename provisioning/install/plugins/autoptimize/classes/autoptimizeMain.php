<?php
/**
 * Wraps base plugin logic/hooks and handles activation/deactivation/uninstall.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class autoptimizeMain
{
    const INIT_EARLIER_PRIORITY = -1;
    const DEFAULT_HOOK_PRIORITY = 2;

    /**
     * Version string.
     *
     * @var string
     */
    protected $version = null;

    /**
     * Main plugin filepath.
     * Used for activation/deactivation/uninstall hooks.
     *
     * @var string
     */
    protected $filepath = null;

    /**
     * Constructor.
     *
     * @param string $version Version.
     * @param string $filepath Filepath. Needed for activation/deactivation/uninstall hooks.
     */
    public function __construct( $version, $filepath )
    {
        $this->version  = $version;
        $this->filepath = $filepath;
    }

    public function run()
    {
        $this->add_hooks();

        // Runs cache size checker.
        $checker = new autoptimizeCacheChecker();
        $checker->run();
    }

    protected function add_hooks()
    {
        add_action( 'plugins_loaded', array( $this, 'setup' ) );

        add_action( 'autoptimize_setup_done', array( $this, 'version_upgrades_check' ) );
        add_action( 'autoptimize_setup_done', array( $this, 'check_cache_and_run' ) );
        add_action( 'autoptimize_setup_done', array( $this, 'maybe_run_ao_extra' ) );
        add_action( 'autoptimize_setup_done', array( $this, 'maybe_run_partners_tab' ) );

        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'plugins_loaded', array( $this, 'hook_page_cache_purge' ) );
        add_action( 'admin_init', array( 'PAnD', 'init' ) );

        register_activation_hook( $this->filepath, array( $this, 'on_activate' ) );
    }

    public function on_activate()
    {
        register_uninstall_hook( $this->filepath, 'autoptimizeMain::on_uninstall' );
    }

    public function load_textdomain()
    {
        load_plugin_textdomain( 'autoptimize' );
    }

    public function setup()
    {
        // Do we gzip in php when caching or is the webserver doing it?
        define( 'AUTOPTIMIZE_CACHE_NOGZIP', (bool) get_option( 'autoptimize_cache_nogzip' ) );

        // These can be overridden by specifying them in wp-config.php or such.
        if ( ! defined( 'AUTOPTIMIZE_WP_CONTENT_NAME' ) ) {
            define( 'AUTOPTIMIZE_WP_CONTENT_NAME', '/' . wp_basename( WP_CONTENT_DIR ) );
        }
        if ( ! defined( 'AUTOPTIMIZE_CACHE_CHILD_DIR' ) ) {
            define( 'AUTOPTIMIZE_CACHE_CHILD_DIR', '/cache/autoptimize/' );
        }
        if ( ! defined( 'AUTOPTIMIZE_CACHEFILE_PREFIX' ) ) {
            define( 'AUTOPTIMIZE_CACHEFILE_PREFIX', 'autoptimize_' );
        }
        // Note: trailing slash is not optional!
        if ( ! defined( 'AUTOPTIMIZE_CACHE_DIR' ) ) {
            define( 'AUTOPTIMIZE_CACHE_DIR', autoptimizeCache::get_pathname() );
        }

        define( 'WP_ROOT_DIR', substr( WP_CONTENT_DIR, 0, strlen( WP_CONTENT_DIR ) - strlen( AUTOPTIMIZE_WP_CONTENT_NAME ) ) );

        if ( ! defined( 'AUTOPTIMIZE_WP_SITE_URL' ) ) {
            if ( function_exists( 'domain_mapping_siteurl' ) ) {
                define( 'AUTOPTIMIZE_WP_SITE_URL', domain_mapping_siteurl( get_current_blog_id() ) );
            } else {
                define( 'AUTOPTIMIZE_WP_SITE_URL', site_url() );
            }
        }
        if ( ! defined( 'AUTOPTIMIZE_WP_CONTENT_URL' ) ) {
            if ( function_exists( 'domain_mapping_siteurl' ) ) {
                define( 'AUTOPTIMIZE_WP_CONTENT_URL', str_replace( get_original_url( AUTOPTIMIZE_WP_SITE_URL ), AUTOPTIMIZE_WP_SITE_URL, content_url() ) );
            } else {
                define( 'AUTOPTIMIZE_WP_CONTENT_URL', content_url() );
            }
        }
        if ( ! defined( 'AUTOPTIMIZE_CACHE_URL' ) ) {
            if ( is_multisite() && apply_filters( 'autoptimize_separate_blog_caches', true ) ) {
                $blog_id = get_current_blog_id();
                define( 'AUTOPTIMIZE_CACHE_URL', AUTOPTIMIZE_WP_CONTENT_URL . AUTOPTIMIZE_CACHE_CHILD_DIR . $blog_id . '/' );
            } else {
                define( 'AUTOPTIMIZE_CACHE_URL', AUTOPTIMIZE_WP_CONTENT_URL . AUTOPTIMIZE_CACHE_CHILD_DIR );
            }
        }
        if ( ! defined( 'AUTOPTIMIZE_WP_ROOT_URL' ) ) {
            define( 'AUTOPTIMIZE_WP_ROOT_URL', str_replace( AUTOPTIMIZE_WP_CONTENT_NAME, '', AUTOPTIMIZE_WP_CONTENT_URL ) );
        }
        if ( ! defined( 'AUTOPTIMIZE_HASH' ) ) {
            define( 'AUTOPTIMIZE_HASH', wp_hash( AUTOPTIMIZE_CACHE_URL ) );
        }
        if ( ! defined( 'AUTOPTIMIZE_SITE_DOMAIN' ) ) {
            define( 'AUTOPTIMIZE_SITE_DOMAIN', parse_url( AUTOPTIMIZE_WP_SITE_URL, PHP_URL_HOST ) );
        }
        do_action( 'autoptimize_setup_done' );
    }

    /**
     * Checks if there's a need to upgrade/update options and whatnot,
     * in which case we might need to do stuff and flush the cache
     * to avoid old versions of aggregated files lingering around.
     */
    public function version_upgrades_check()
    {
        autoptimizeVersionUpdatesHandler::check_installed_and_update( $this->version );
    }

    public function check_cache_and_run()
    {
        if ( autoptimizeCache::cacheavail() ) {
            $conf = autoptimizeConfig::instance();
            if ( $conf->get( 'autoptimize_html' ) || $conf->get( 'autoptimize_js' ) || $conf->get( 'autoptimize_css' ) ) {
                // Hook into WordPress frontend.
                if ( defined( 'AUTOPTIMIZE_INIT_EARLIER' ) ) {
                    add_action(
                        'init',
                        array( $this, 'start_buffering' ),
                        self::INIT_EARLIER_PRIORITY
                    );
                } else {
                    if ( ! defined( 'AUTOPTIMIZE_HOOK_INTO' ) ) {
                        define( 'AUTOPTIMIZE_HOOK_INTO', 'template_redirect' );
                    }
                    add_action(
                        constant( 'AUTOPTIMIZE_HOOK_INTO' ),
                        array( $this, 'start_buffering' ),
                        self::DEFAULT_HOOK_PRIORITY
                    );
                }
            }
        } else {
            add_action( 'admin_notices', 'autoptimizeMain::notice_cache_unavailable' );
        }
    }

    public function maybe_run_ao_extra()
    {
        if ( apply_filters( 'autoptimize_filter_extra_activate', true ) ) {
            $ao_extra = new autoptimizeExtra();
            $ao_extra->run();
        }
    }

    public function maybe_run_partners_tab()
    {
        // Loads partners tab code if in admin (and not in admin-ajax.php)!
        if ( autoptimizeConfig::is_admin_and_not_ajax() ) {
            new autoptimizePartners();
        }
    }

    public function hook_page_cache_purge()
    {
        // hook into a collection of page cache purge actions if filter allows.
        if ( apply_filters( 'autoptimize_filter_main_hookpagecachepurge', true ) ) {
            $page_cache_purge_actions = array(
                'after_rocket_clean_domain', // exists.
                'hyper_cache_purged', // Stefano confirmed this will be added.
                'w3tc_flush_posts', // exits.
                'w3tc_flush_all', // exists.
                'ce_action_cache_cleared', // Sven confirmed this will be added.
                'comet_cache_wipe_cache', // still to be confirmed by Raam.
                'wp_cache_cleared', // cfr. https://github.com/Automattic/wp-super-cache/pull/537.
                'wpfc_delete_cache', // Emre confirmed this will be added this.
                'swift_performance_after_clear_all_cache', // swift perf. yeah!
            );
            $page_cache_purge_actions = apply_filters( 'autoptimize_filter_main_pagecachepurgeactions', $page_cache_purge_actions );
            foreach ( $page_cache_purge_actions as $purge_action ) {
                add_action( $purge_action, 'autoptimizeCache::clearall_actionless' );
            }
        }
    }

    /**
     * Setup output buffering if needed.
     *
     * @return void
     */
    public function start_buffering()
    {
        if ( $this->should_buffer() ) {

            // Load speedupper conditionally (true by default).
            if ( apply_filters( 'autoptimize_filter_speedupper', true ) ) {
                $ao_speedupper = new autoptimizeSpeedupper();
            }

            $conf = autoptimizeConfig::instance();

            if ( $conf->get( 'autoptimize_js' ) ) {
                if ( ! defined( 'CONCATENATE_SCRIPTS' ) ) {
                    define( 'CONCATENATE_SCRIPTS', false );
                }
                if ( ! defined( 'COMPRESS_SCRIPTS' ) ) {
                    define( 'COMPRESS_SCRIPTS', false );
                }
            }

            if ( $conf->get( 'autoptimize_css' ) ) {
                if ( ! defined( 'COMPRESS_CSS' ) ) {
                    define( 'COMPRESS_CSS', false );
                }
            }

            if ( apply_filters( 'autoptimize_filter_obkiller', false ) ) {
                while ( ob_get_level() > 0 ) {
                    ob_end_clean();
                }
            }

            // Now, start the real thing!
            ob_start( array( $this, 'end_buffering' ) );
        }
    }

    /**
     * Returns true if all the conditions to start output buffering are satisfied.
     *
     * @param bool $doing_tests Allows overriding the optimization of only
     *                          deciding once per request (for use in tests).
     * @return bool
     */
    public function should_buffer( $doing_tests = false )
    {
        static $do_buffering = null;

        // Only check once in case we're called multiple times by others but
        // still allows multiple calls when doing tests.
        if ( null === $do_buffering || $doing_tests ) {

            $ao_noptimize = false;

            // Checking for DONOTMINIFY constant as used by e.g. WooCommerce POS.
            if ( defined( 'DONOTMINIFY' ) && ( constant( 'DONOTMINIFY' ) === true || constant( 'DONOTMINIFY' ) === 'true' ) ) {
                $ao_noptimize = true;
            }

            // Skip checking query strings if they're disabled.
            if ( apply_filters( 'autoptimize_filter_honor_qs_noptimize', true ) ) {
                // Check for `ao_noptimize` (and other) keys in the query string
                // to get non-optimized page for debugging.
                $keys = array(
                    'ao_noptimize',
                    'ao_noptirocket',
                );
                foreach ( $keys as $key ) {
                    if ( array_key_exists( $key, $_GET ) && '1' === $_GET[ $key ] ) {
                        $ao_noptimize = true;
                        break;
                    }
                }
            }

            // If setting says not to optimize logged in user and user is logged in...
            if ( 'on' !== get_option( 'autoptimize_optimize_logged', 'on' ) && is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
                $ao_noptimize = true;
            }

            // If setting says not to optimize cart/checkout.
            if ( 'on' !== get_option( 'autoptimize_optimize_checkout', 'on' ) ) {
                // Checking for woocommerce, easy digital downloads and wp ecommerce...
                foreach ( array( 'is_checkout', 'is_cart', 'edd_is_checkout', 'wpsc_is_cart', 'wpsc_is_checkout' ) as $func ) {
                    if ( function_exists( $func ) && $func() ) {
                        $ao_noptimize = true;
                        break;
                    }
                }
            }

            // Allows blocking of autoptimization on your own terms regardless of above decisions.
            $ao_noptimize = (bool) apply_filters( 'autoptimize_filter_noptimize', $ao_noptimize );

            // Check for site being previewed in the Customizer (available since WP 4.0).
            $is_customize_preview = false;
            if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
                $is_customize_preview = is_customize_preview();
            }

            /**
             * We only buffer the frontend requests (and then only if not a feed
             * and not turned off explicitly and not when being previewed in Customizer)!
             * NOTE: Tests throw a notice here due to is_feed() being called
             * while the main query hasn't been ran yet. Thats why we use
             * AUTOPTIMIZE_INIT_EARLIER in tests.
             */
            $do_buffering = ( ! is_admin() && ! is_feed() && ! $ao_noptimize && ! $is_customize_preview );
        }

        return $do_buffering;
    }

    /**
     * Returns true if given markup is considered valid/processable/optimizable.
     *
     * @param string $content Markup.
     *
     * @return bool
     */
    public function is_valid_buffer( $content )
    {
        // Defaults to true.
        $valid = true;

        $has_no_html_tag    = ( false === stripos( $content, '<html' ) );
        $has_xsl_stylesheet = ( false !== stripos( $content, '<xsl:stylesheet' ) );
        $has_html5_doctype  = ( preg_match( '/^<!DOCTYPE.+html>/i', $content ) > 0 );

        if ( $has_no_html_tag ) {
            // Can't be valid amp markup without an html tag preceding it.
            $is_amp_markup = false;
        } else {
            $is_amp_markup = self::is_amp_markup( $content );
        }

        // If it's not html, or if it's amp or contains xsl stylesheets we don't touch it.
        if ( $has_no_html_tag && ! $has_html5_doctype || $is_amp_markup || $has_xsl_stylesheet ) {
            $valid = false;
        }

        return $valid;
    }

    /**
     * Returns true if given $content is considered to be AMP markup.
     * This is far from actual validation against AMP spec, but it'll do for now.
     *
     * @param string $content Markup to check.
     *
     * @return bool
     */
    public static function is_amp_markup( $content )
    {
        $is_amp_markup = preg_match( '/<html[^>]*(?:amp|⚡)/i', $content );

        return (bool) $is_amp_markup;
    }

    /**
     * Processes/optimizes the output-buffered content and returns it.
     * If the content is not processable, it is returned unmodified.
     *
     * @param string $content Buffered content.
     *
     * @return string
     */
    public function end_buffering( $content )
    {
        // Bail early without modifying anything if we can't handle the content.
        if ( ! $this->is_valid_buffer( $content ) ) {
            return $content;
        }

        $conf = autoptimizeConfig::instance();

        // Determine what needs to be ran.
        $classes = array();
        if ( $conf->get( 'autoptimize_js' ) ) {
            $classes[] = 'autoptimizeScripts';
        }
        if ( $conf->get( 'autoptimize_css' ) ) {
            $classes[] = 'autoptimizeStyles';
        }
        if ( $conf->get( 'autoptimize_html' ) ) {
            $classes[] = 'autoptimizeHTML';
        }

        $classoptions = array(
            'autoptimizeScripts' => array(
                'aggregate'      => $conf->get( 'autoptimize_js_aggregate' ),
                'justhead'       => $conf->get( 'autoptimize_js_justhead' ),
                'forcehead'      => $conf->get( 'autoptimize_js_forcehead' ),
                'trycatch'       => $conf->get( 'autoptimize_js_trycatch' ),
                'js_exclude'     => $conf->get( 'autoptimize_js_exclude' ),
                'cdn_url'        => $conf->get( 'autoptimize_cdn_url' ),
                'include_inline' => $conf->get( 'autoptimize_js_include_inline' ),
            ),
            'autoptimizeStyles'  => array(
                'aggregate'      => $conf->get( 'autoptimize_css_aggregate' ),
                'justhead'       => $conf->get( 'autoptimize_css_justhead' ),
                'datauris'       => $conf->get( 'autoptimize_css_datauris' ),
                'defer'          => $conf->get( 'autoptimize_css_defer' ),
                'defer_inline'   => $conf->get( 'autoptimize_css_defer_inline' ),
                'inline'         => $conf->get( 'autoptimize_css_inline' ),
                'css_exclude'    => $conf->get( 'autoptimize_css_exclude' ),
                'cdn_url'        => $conf->get( 'autoptimize_cdn_url' ),
                'include_inline' => $conf->get( 'autoptimize_css_include_inline' ),
                'nogooglefont'   => $conf->get( 'autoptimize_css_nogooglefont' ),
            ),
            'autoptimizeHTML'    => array(
                'keepcomments' => $conf->get( 'autoptimize_html_keepcomments' ),
            ),
        );

        $content = apply_filters( 'autoptimize_filter_html_before_minify', $content );

        // Run the classes!
        foreach ( $classes as $name ) {
            $instance = new $name( $content );
            if ( $instance->read( $classoptions[ $name ] ) ) {
                $instance->minify();
                $instance->cache();
                $content = $instance->getcontent();
            }
            unset( $instance );
        }

        $content = apply_filters( 'autoptimize_html_after_minify', $content );

        return $content;
    }

    public static function on_uninstall()
    {
        autoptimizeCache::clearall();

        $delete_options = array(
            'autoptimize_cache_clean',
            'autoptimize_cache_nogzip',
            'autoptimize_css',
            'autoptimize_css_aggregate',
            'autoptimize_css_datauris',
            'autoptimize_css_justhead',
            'autoptimize_css_defer',
            'autoptimize_css_defer_inline',
            'autoptimize_css_inline',
            'autoptimize_css_exclude',
            'autoptimize_html',
            'autoptimize_html_keepcomments',
            'autoptimize_js',
            'autoptimize_js_aggregate',
            'autoptimize_js_exclude',
            'autoptimize_js_forcehead',
            'autoptimize_js_justhead',
            'autoptimize_js_trycatch',
            'autoptimize_version',
            'autoptimize_show_adv',
            'autoptimize_cdn_url',
            'autoptimize_cachesize_notice',
            'autoptimize_css_include_inline',
            'autoptimize_js_include_inline',
            'autoptimize_optimize_logged',
            'autoptimize_optimize_checkout',
            'autoptimize_extra_settings',
            'autoptimize_service_availablity',
            'autoptimize_imgopt_provider_stat',
            'autoptimize_imgopt_launched',
        );

        if ( ! is_multisite() ) {
            foreach ( $delete_options as $del_opt ) {
                delete_option( $del_opt );
            }
        } else {
            global $wpdb;
            $blog_ids         = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
            $original_blog_id = get_current_blog_id();
            foreach ( $blog_ids as $blog_id ) {
                switch_to_blog( $blog_id );
                foreach ( $delete_options as $del_opt ) {
                    delete_option( $del_opt );
                }
            }
            switch_to_blog( $original_blog_id );
        }

        if ( wp_get_schedule( 'ao_cachechecker' ) ) {
            wp_clear_scheduled_hook( 'ao_cachechecker' );
        }
    }

    public static function notice_cache_unavailable()
    {
        echo '<div class="error"><p>';
        // Translators: %s is the cache directory location.
        printf( __( 'Autoptimize cannot write to the cache directory (%s), please fix to enable CSS/ JS optimization!', 'autoptimize' ), AUTOPTIMIZE_CACHE_DIR );
        echo '</p></div>';
    }

    public static function notice_installed()
    {
        echo '<div class="updated"><p>';
        _e( 'Thank you for installing and activating Autoptimize. Please configure it under "Settings" -> "Autoptimize" to start improving your site\'s performance.', 'autoptimize' );
        echo '</p></div>';
    }

    public static function notice_updated()
    {
        echo '<div class="updated"><p>';
        _e( 'Autoptimize has just been updated. Please <strong>test your site now</strong> and adapt Autoptimize config if needed.', 'autoptimize' );
        echo '</p></div>';
    }

}
