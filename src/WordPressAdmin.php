<?php
/*
    WordPressAdmin

    WP2Static's interface to WordPress Admin functions

    Used for registering hooks, Admin UI components, ...
*/

namespace WP2Static;

class WordPressAdmin {

    /**
     * WordPressAdmin constructor
     */
    public function __construct() {

    }

    /**
     * Register hooks for WordPress and WP2Static actions
     *
     * @param string $bootstrap_file main plugin filepath
     */
    public static function registerHooks( string $bootstrap_file ) : void {
        register_activation_hook(
            $bootstrap_file,
            [ Controller::class, 'activate' ]
        );

        register_deactivation_hook(
            $bootstrap_file,
            [ Controller::class, 'deactivate' ]
        );

        add_filter(
            // phpcs:ignore WordPress.WP.CronInterval -- namespaces not yet fully supported
            'cron_schedules',
            [ WPCron::class, 'wp2static_custom_cron_schedules' ]
        );

        add_filter(
            'wp2static_list_redirects',
            [ CrawlCache::class, 'wp2static_list_redirects' ]
        );

        add_filter(
            'cron_request',
            [ WPCron::class, 'wp2static_cron_with_http_basic_auth' ]
        );

        add_action(
            'wp_ajax_wp2static_run',
            [ Controller::class, 'wp2staticRun' ],
            10,
            0
        );

        add_action(
            'wp_ajax_wp2static_poll_log',
            [ Controller::class, 'wp2staticPollLog' ],
            10,
            0
        );

        add_action(
            'admin_post_wp2static_ui_save_options',
            [ Controller::class, 'wp2staticUISaveOptions' ],
            10,
            0
        );

        add_action(
            'wp2static_register_addon',
            [ Addons::class, 'registerAddon' ],
            10,
            5
        );

        add_action(
            'wp2static_post_deploy_trigger',
            [ Controller::class, 'emailDeployNotification' ],
            10,
            0
        );

        add_action(
            'wp2static_post_deploy_trigger',
            [ Controller::class, 'webhookDeployNotification' ],
            10,
            0
        );

        add_action(
            'admin_post_wp2static_post_processed_site_delete',
            [ Controller::class, 'wp2staticPostProcessedSiteDelete' ],
            10,
            0
        );

        add_action(
            'admin_post_wp2static_post_processed_site_show',
            [ Controller::class, 'wp2staticPostProcessedSiteShow' ],
            10,
            0
        );

        add_action(
            'admin_post_wp2static_log_delete',
            [ Controller::class, 'wp2staticLogDelete' ],
            10,
            0
        );

        add_action(
            'admin_post_wp2static_delete_all_caches',
            [ Controller::class, 'wp2staticDeleteAllCaches' ],
            10,
            0
        );

        add_action(
            'admin_post_wp2static_delete_jobs_queue',
            [ Controller::class, 'wp2staticDeleteJobsQueue' ],
            10,
            0
        );

        add_action(
            'admin_post_wp2staticProcessJobsQueue',
            [ Controller::class, 'wp2staticProcessJobsQueue' ],
            10,
            0
        );

        add_action(
            'admin_post_wp2static_process_queue',
            [ self::class, 'adminPostProcessQueue' ],
            10,
            0
        );

        add_action(
            'admin_post_wp2static_crawl_queue_delete',
            [ Controller::class, 'wp2staticCrawlQueueDelete' ],
            10,
            0
        );

        add_action(
            'admin_post_wp2static_crawl_queue_show',
            [ Controller::class, 'wp2staticCrawlQueueShow' ],
            10,
            0
        );

        add_action(
            'admin_post_wp2static_deploy_cache_delete',
            [ Controller::class, 'wp2staticDeployCacheDelete' ],
            10,
            0
        );

        add_action(
            'admin_post_wp2static_deploy_cache_show',
            [ Controller::class, 'wp2staticDeployCacheShow' ],
            10,
            0
        );

        add_action(
            'admin_post_wp2static_crawl_cache_delete',
            [ Controller::class, 'wp2staticCrawlCacheDelete' ],
            10,
            0
        );

        add_action(
            'admin_post_wp2static_crawl_cache_show',
            [ Controller::class, 'wp2staticCrawlCacheShow' ],
            10,
            0
        );

        add_action(
            'admin_post_wp2static_static_site_delete',
            [ Controller::class, 'wp2staticStaticSiteDelete' ],
            10,
            0
        );

        add_action(
            'admin_post_wp2static_static_site_show',
            [ Controller::class, 'wp2staticStaticSiteShow' ],
            10,
            0
        );

        add_action(
            'admin_post_wp2static_ui_save_job_options',
            [ Controller::class, 'wp2staticUISaveJobOptions' ],
            10,
            0
        );

        add_action(
            'admin_post_wp2static_ui_save_advanced_options',
            [ Controller::class, 'wp2staticUISaveAdvancedOptions' ],
            10,
            0
        );

        add_action(
            'admin_post_wp2static_manually_enqueue_jobs',
            [ Controller::class, 'wp2staticManuallyEnqueueJobs' ],
            10,
            0
        );

        add_action(
            'admin_post_wp2static_toggle_addon',
            [ Controller::class, 'wp2staticToggleAddon' ],
            10,
            0
        );

        add_action(
            'wp2static_process_queue',
            [ Controller::class, 'wp2staticProcessQueue' ],
            10,
            0
        );

        add_action(
            'wp2static_headless_hook',
            [ Controller::class, 'wp2staticHeadless' ],
            10,
            0
        );

        add_action(
            'wp2static_crawl',
            [ Crawler::class, 'wp2staticCrawl' ],
            10,
            2
        );

        add_action(
            'wp2static_process_html',
            [ SimpleRewriter::class, 'rewrite' ],
            10,
            1
        );

        add_action(
            'wp2static_process_css',
            [ SimpleRewriter::class, 'rewrite' ],
            10,
            1
        );

        add_action(
            'wp2static_process_js',
            [ SimpleRewriter::class, 'rewrite' ],
            10,
            1
        );

        add_action(
            'wp2static_process_robots_txt',
            [ SimpleRewriter::class, 'rewrite' ],
            10,
            1
        );

        add_action(
            'wp2static_process_xml',
            [ SimpleRewriter::class, 'rewrite' ],
            10,
            1
        );

        add_action(
            'wp2static_process_xsl',
            [ SimpleRewriter::class, 'rewrite' ],
            10,
            1
        );

        add_action(
            'save_post',
            [ Controller::class, 'wp2staticSavePostHandler' ],
            0
        );

        add_action(
            'trashed_post',
            [ Controller::class, 'wp2staticTrashedPostHandler' ],
            0
        );

        add_action(
            'admin_enqueue_scripts',
            [ self::class, 'wp2staticAdminStyles' ],
            0
        );

        add_action(
            'admin_enqueue_scripts',
            [ self::class, 'wp2staticAdminScripts' ],
            0
        );

        /*
         * Register actions for when we should invalidate cache for
         * a URL(s) or whole site
         *
         */
        $single_url_invalidation_events = [
            'save_post',
            'deleted_post',
        ];

        $full_site_invalidation_events = [
            'switch_theme',
        ];

        foreach ( $single_url_invalidation_events as $invalidation_events ) {
            add_action(
                $invalidation_events,
                [ Controller::class, 'invalidateSingleURLCache' ],
                10,
                2
            );
        }

        add_filter(
            'plugin_row_meta',
            [ self::class, 'wp2staticPluginMetaLinks' ],
            10,
            2
        );

        add_action(
            'wp_ajax_wp2static_admin_notice_dismissal',
            [ AdminNotices::class, 'handleDismissedNotice' ],
            10,
            1
        );

        // show admin notices on WP2Static pages if rules are met
        if ( str_contains( URLHelper::getCurrent(), 'page=wp2static' ) ) {
            add_action(
                'admin_notices',
                [ AdminNotices::class, 'showAdminNotices' ],
                0
            );
        }
    }

    /**
     * Add WP2Static elements to WordPress Admin UI
     */
    public static function addAdminUIElements() : void {
        if ( is_admin() ) {
            add_action(
                'admin_menu',
                [ Controller::class, 'registerOptionsPage' ]
            );
            add_filter( 'custom_menu_order', '__return_true' );
            add_filter( 'menu_order', [ Controller::class, 'setMenuOrder' ] );
        }
    }

    /*
     * Do security checks before calling Controller::wp2staticProcessQueue
     */
    public static function adminPostProcessQueue() : void {
        $method = filter_input( INPUT_SERVER, 'REQUEST_METHOD' );
        if ( ! $method ) {
            $msg = 'Empty method in request to admin-post.php (wp2static_process_queue)';
        } elseif ( 'POST' !== $method ) {
            $method = strval( $method );
            $msg = "Invalid method in request to admin-post.php (wp2static_process_queue): $method";
        }
        $nonce = filter_input( INPUT_POST, '_wpnonce' );
        $nonce_valid = $nonce && wp_verify_nonce( strval( $nonce ), 'wp2static_process_queue' );
        if ( ! $nonce_valid ) {
            $msg = 'Invalid nonce in request to admin-post.php (wpstatic_process_queue)';
        }

        if ( isset( $msg ) ) {
            WsLog::l( $msg );
            throw new \RuntimeException( $msg );
        }

        Controller::wp2staticProcessQueue();
    }

    public static function wp2staticAdminStyles() : void {
        wp_register_style(
            'wp2static_admin_styles',
            plugins_url( '../css/admin/style.css', __FILE__ ),
            [],
            WP2STATIC_VERSION
        );
        wp_enqueue_style( 'wp2static_admin_styles' );
    }

    public static function wp2staticAdminScripts() : void {
        wp_register_script(
            'wp2static_admin_scripts',
            plugins_url( '../js/admin/override-menu-style.js', __FILE__ ),
            [],
            WP2STATIC_VERSION,
            false
        );
        wp_enqueue_script( 'wp2static_admin_scripts' );
    }

    /**
     * Add extra link to WP2Static's Plugins page entry
     *
     * @param mixed[] $links plugin meta links
     * @param string $file path to the plugin's entrypoint
     * @return mixed[] $links plugin meta links
     */
    public static function wp2staticPluginMetaLinks( $links, $file ) {
        if ( $file === 'wp2static/wp2static.php' ) {
            // phpcs:ignore Generic.Files.LineLength.MaxExceeded
            $links[] = '<a id="wp2static-try-1-click-publish-plugin-screen" target="_blank" href="https://www.strattic.com/pricing/?utm_campaign=start-trial&utm_source=wp2static&utm_medium=wp-dash&utm_term=try-strattic&utm_content=plugins">Try 1-Click Publish</a>';
        }

        return $links;
    }
}

