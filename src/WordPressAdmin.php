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
            'save_post',
            [ Controller::class, 'wp2staticSavePostHandler' ],
            0
        );

        add_action(
            'trashed_post',
            [ Controller::class, 'wp2staticTrashedPostHandler' ],
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
        $method = $_SERVER['REQUEST_METHOD'];
        if ( 'POST' !== $method ) {
            $msg = "Invalid method in request to admin-post.php (wp2static_process_queue): $method";
        }

        $nonce = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : false;
        $nonce_valid = $nonce && wp_verify_nonce( $nonce, 'wp2static_process_queue' );
        if ( ! $nonce_valid ) {
            $msg = 'Invalid nonce in request to admin-post.php (wpstatic_process_queue)';
        }

        if ( isset( $msg ) ) {
            WsLog::l( $msg );
            throw new \RuntimeException( $msg );
        }

        Controller::wp2staticProcessQueue();
    }
}

