<?php

namespace WP2Static;

use ZipArchive;
use WP_Error;
use WP_CLI;
use WP_Post;

class Controller {
    const WP2STATIC_VERSION = '7.0-alpha-007';

    /**
     * @var string
     */
    public $bootstrap_file;

    /**
     * Main controller of WP2Static
     *
     * @var \WP2Static\Controller Instance.
     */
    protected static $plugin_instance = null;

    protected function __construct() {}

    /**
     * Returns instance of WP2Static Controller
     *
     * @return \WP2Static\Controller Instance of self.
     */
    public static function getInstance() : Controller {
        if ( null === self::$plugin_instance ) {
            self::$plugin_instance = new self();
        }

        return self::$plugin_instance;
    }

    public static function init( string $bootstrap_file ) : Controller {
        $plugin_instance = self::getInstance();

        WordPressAdmin::registerHooks( $bootstrap_file );
        WordPressAdmin::addAdminUIElements();

        Utils::set_max_execution_time();

        return $plugin_instance;
    }

    /**
     * Adjusts position of dashboard menu icons
     *
     * @param string[] $menu_order list of menu items
     * @return string[] list of menu items
     */
    public static function set_menu_order( array $menu_order ) : array {
        $order = [];
        $file  = plugin_basename( __FILE__ );

        foreach ( $menu_order as $index => $item ) {
            if ( $item === 'index.php' ) {
                $order[] = $item;
            }
        }

        $order = [
            'index.php',
            'wp2static',
            'statichtmloutput',
        ];

        return $order;
    }

    public static function deactivate_for_single_site() : void {
        WPCron::clearRecurringEvent();
    }

    public static function deactivate( bool $network_wide = null ) : void {
        if ( $network_wide ) {
            global $wpdb;

            $query = 'SELECT blog_id FROM %s WHERE site_id = %d;';

            $site_ids = $wpdb->get_col(
                sprintf(
                    $query,
                    $wpdb->blogs,
                    $wpdb->siteid
                )
            );

            foreach ( $site_ids as $site_id ) {
                switch_to_blog( $site_id );
                self::deactivate_for_single_site();
            }

            restore_current_blog();
        } else {
            self::deactivate_for_single_site();
        }
    }

    public static function activate_for_single_site() : void {
        // prepare DB tables
        WsLog::createTable();
        CoreOptions::init();
        CrawlCache::createTable();
        CrawlQueue::createTable();
        DeployCache::createTable();
        JobQueue::createTable();
        Addons::createTable();
    }

    public static function activate( bool $network_wide = null ) : void {
        if ( $network_wide ) {
            global $wpdb;

            $query = 'SELECT blog_id FROM %s WHERE site_id = %d;';

            $site_ids = $wpdb->get_col(
                sprintf(
                    $query,
                    $wpdb->blogs,
                    $wpdb->siteid
                )
            );

            foreach ( $site_ids as $site_id ) {
                switch_to_blog( $site_id );
                self::activate_for_single_site();
            }

            restore_current_blog();
        } else {
            self::activate_for_single_site();
        }
    }

    public static function registerOptionsPage() : void {
        add_menu_page(
            'WP2Static',
            'WP2Static',
            'manage_options',
            'wp2static',
            [ 'WP2Static\ViewRenderer', 'renderRunPage' ],
            'dashicons-shield-alt'
        );

        $submenu_pages = [
            'run' => [ 'WP2Static\ViewRenderer', 'renderRunPage' ],
            'options' => [ 'WP2Static\ViewRenderer', 'renderOptionsPage' ],
            'jobs' => [ 'WP2Static\ViewRenderer', 'renderJobsPage' ],
            'caches' => [ 'WP2Static\ViewRenderer', 'renderCachesPage' ],
            'diagnostics' => [ 'WP2Static\ViewRenderer', 'renderDiagnosticsPage' ],
            'logs' => [ 'WP2Static\ViewRenderer', 'renderLogsPage' ],
            'addons' => [ 'WP2Static\ViewRenderer', 'renderAddonsPage' ],
        ];

        foreach ( $submenu_pages as $slug => $method ) {
            $menu_slug =
                $slug === 'run' ? 'wp2static' : 'wp2static-' . $slug;

            $title = ucfirst( $slug );

            // @phpstan-ignore-next-line
            add_submenu_page(
                'wp2static',
                'WP2Static ' . ucfirst( $slug ),
                $title,
                'manage_options',
                $menu_slug,
                $method
            );
        }

        add_submenu_page(
            '',
            'WP2Static Crawl Queue',
            'Crawl Queue',
            'manage_options',
            'wp2static-crawl-queue',
            [ 'WP2Static\ViewRenderer', 'renderCrawlQueue' ]
        );

        add_submenu_page(
            '',
            'WP2Static Crawl Cache',
            'Crawl Cache',
            'manage_options',
            'wp2static-crawl-cache',
            [ 'WP2Static\ViewRenderer', 'renderCrawlCache' ]
        );

        add_submenu_page(
            '',
            'WP2Static Deploy Cache',
            'Deploy Cache',
            'manage_options',
            'wp2static-deploy-cache',
            [ 'WP2Static\ViewRenderer', 'renderDeployCache' ]
        );

        add_submenu_page(
            '',
            'WP2Static Static Site',
            'Static Site',
            'manage_options',
            'wp2static-static-site',
            [ 'WP2Static\ViewRenderer', 'renderStaticSitePaths' ]
        );

        add_submenu_page(
            '',
            'WP2Static Post Processed Site',
            'Post Processed Site',
            'manage_options',
            'wp2static-post-processed-site',
            [ 'WP2Static\ViewRenderer', 'renderPostProcessedSitePaths' ]
        );
    }

    public function crawlSite() : void {
        $crawler = new Crawler();

        // TODO: if WordPressSite methods are static and we only need detectURLs
        // here, pass in iterable to URLs here?
        $crawler->crawlSite( StaticSite::getPath() );
    }

    // TODO: why is this here? Move to CrawlQueue if still needed
    public function delete_crawl_cache() : void {
        // we now have modified file list in DB
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_crawl_cache';

        $wpdb->query( "TRUNCATE TABLE $table_name" );

        $sql =
            "SELECT count(*) FROM $table_name";

        $count = $wpdb->get_var( $sql );

        if ( $count === '0' ) {
            http_response_code( 200 );

            echo 'SUCCESS';
        } else {
            http_response_code( 500 );
        }
    }

    public function userIsAllowed() : bool {
        if ( defined( 'WP_CLI' ) ) {
            return true;
        }

        $referred_by_admin = check_admin_referer( 'wp2static-options' );
        $user_can_manage_options = current_user_can( 'manage_options' );

        return $referred_by_admin && $user_can_manage_options;
    }

    public function reset_default_settings() : void {
        CoreOptions::seedOptions();
    }

    public function delete_deploy_cache() : void {
        DeployCache::truncate();
    }

    public static function wp2static_ui_save_options() : void {
        CoreOptions::savePosted( 'core' );

        do_action( 'wp2static_addon_ui_save_options' );

        check_admin_referer( 'wp2static-ui-options' );

        wp_safe_redirect( admin_url( 'admin.php?page=wp2static-options' ) );
        exit;
    }

    public static function wp2static_crawl_queue_delete() : void {
        check_admin_referer( 'wp2static-caches-page' );

        CrawlQueue::truncate();

        wp_safe_redirect( admin_url( 'admin.php?page=wp2static-caches' ) );
        exit;
    }

    public static function wp2static_crawl_queue_show() : void {
        check_admin_referer( 'wp2static-caches-page' );

        wp_safe_redirect( admin_url( 'admin.php?page=wp2static-crawl-queue' ) );
        exit;
    }

    public static function wp2static_delete_jobs_queue() : void {
        check_admin_referer( 'wp2static-ui-job-options' );

        JobQueue::truncate();

        wp_safe_redirect( admin_url( 'admin.php?page=wp2static-jobs' ) );
        exit;
    }

    public static function wp2static_delete_all_caches() : void {
        check_admin_referer( 'wp2static-caches-page' );

        self::delete_all_caches();

        wp_safe_redirect( admin_url( 'admin.php?page=wp2static-caches' ) );
        exit;
    }

    public static function delete_all_caches() : void {
        CrawlQueue::truncate();
        CrawlCache::truncate();
        StaticSite::delete();
        ProcessedSite::delete();
        DeployCache::truncate();
    }

    public static function wp2static_process_jobs_queue() : void {
        check_admin_referer( 'wp2static-ui-job-options' );

        WsLog::l( 'Manually processing JobQueue' );

        self::wp2static_process_queue();

        wp_safe_redirect( admin_url( 'admin.php?page=wp2static-jobs' ) );
        exit;
    }

    public static function wp2static_deploy_cache_delete() : void {
        check_admin_referer( 'wp2static-caches-page' );

        if ( isset( $_POST['deploy_namespace'] ) ) {
            DeployCache::truncate( $_POST['deploy_namespace'] );
        } else {
            DeployCache::truncate();
        }

        wp_safe_redirect( admin_url( 'admin.php?page=wp2static-caches' ) );
        exit;
    }

    public static function wp2static_deploy_cache_show() : void {
        check_admin_referer( 'wp2static-caches-page' );

        if ( isset( $_POST['deploy_namespace'] ) ) {
            wp_safe_redirect( admin_url('admin.php?page=wp2static-deploy-cache&deploy_namespace=' . urlencode($_POST['deploy_namespace'])) );
        } else {
            wp_safe_redirect( admin_url( 'admin.php?page=wp2static-deploy-cache' ) );
        }
        
        exit;
    }

    public static function wp2static_crawl_cache_delete() : void {
        check_admin_referer( 'wp2static-caches-page' );

        CrawlCache::truncate();

        wp_safe_redirect( admin_url( 'admin.php?page=wp2static-caches' ) );
        exit;
    }

    public static function wp2static_crawl_cache_show() : void {
        check_admin_referer( 'wp2static-caches-page' );

        wp_safe_redirect( admin_url( 'admin.php?page=wp2static-crawl-cache' ) );
        exit;
    }

    public static function wp2static_post_processed_site_delete() : void {
        check_admin_referer( 'wp2static-caches-page' );

        ProcessedSite::delete();

        wp_safe_redirect( admin_url( 'admin.php?page=wp2static-caches' ) );
        exit;
    }

    public static function wp2static_post_processed_site_show() : void {
        check_admin_referer( 'wp2static-caches-page' );

        wp_safe_redirect( admin_url( 'admin.php?page=wp2static-post-processed-site' ) );
        exit;
    }

    public static function wp2static_log_delete() : void {
        check_admin_referer( 'wp2static-log-page' );

        WsLog::truncate();

        wp_safe_redirect( admin_url( 'admin.php?page=wp2static-logs' ) );
        exit;
    }

    public static function wp2static_static_site_delete() : void {
        check_admin_referer( 'wp2static-caches-page' );

        StaticSite::delete();

        wp_safe_redirect( admin_url( 'admin.php?page=wp2static-caches' ) );
        exit;
    }

    public static function wp2static_static_site_show() : void {
        check_admin_referer( 'wp2static-caches-page' );

        wp_safe_redirect( admin_url( 'admin.php?page=wp2static-static-site' ) );
        exit;
    }

    public static function wp2static_ui_save_job_options() : void {
        CoreOptions::savePosted( 'jobs' );

        do_action( 'wp2static_addon_ui_save_job_options' );

        check_admin_referer( 'wp2static-ui-job-options' );

        wp_safe_redirect( admin_url( 'admin.php?page=wp2static-jobs' ) );
        exit;
    }

    public static function wp2static_save_post_handler( int $post_id ) : void {
        if ( CoreOptions::getValue( 'queueJobOnPostSave' ) &&
             get_post_status( $post_id ) === 'publish' ) {
            self::wp2static_enqueue_jobs();
        }
    }

    public static function wp2static_trashed_post_handler() : void {
        if ( CoreOptions::getValue( 'queueJobOnPostDelete' ) ) {
            self::wp2static_enqueue_jobs();
        }
    }

    public static function wp2static_enqueue_jobs() : void {
        // check each of these in order we want to enqueue
        $job_types = [
            'autoJobQueueDetection' => 'detect',
            'autoJobQueueCrawling' => 'crawl',
            'autoJobQueuePostProcessing' => 'post_process',
            'autoJobQueueDeployment' => 'deploy',
        ];

        foreach ( $job_types as $key => $job_type ) {
            if ( (int) CoreOptions::getValue( $key ) === 1 ) {
                JobQueue::addJob( $job_type );
            }
        }
    }

    public static function wp2static_toggle_addon() : void {
        check_admin_referer( 'wp2static-addons-page' );

        $addon_slug = sanitize_text_field( $_POST['addon_slug'] );

        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_addons';

        // get target addon's current state
        $addon =
            $wpdb->get_row( "SELECT enabled, type FROM $table_name WHERE slug = '$addon_slug'" );

        // if deploy type, disable others when enabling this one
        if ( $addon->type === 'deploy' ) {
            $wpdb->update(
                $table_name,
                [ 'enabled' => 0 ],
                [ 'enabled' => 1 ]
            );
        }

        // toggle the target addon's state
        $wpdb->update(
            $table_name,
            [ 'enabled' => ! $addon->enabled ],
            [ 'slug' => $addon_slug ]
        );

        wp_safe_redirect( admin_url( 'admin.php?page=wp2static-addons' ) );
        exit;
    }

    public static function wp2static_manually_enqueue_jobs() : void {
        check_admin_referer( 'wp2static-manually-enqueue-jobs' );

        // TODO: consider using a transient based notifications system to
        // persist through wp_safe_redirect calls
        // ie, https://github.com/wpscholar/wp-transient-admin-notices/blob/master/TransientAdminNotices.php

        // check each of these in order we want to enqueue
        $job_types = [
            'autoJobQueueDetection' => 'detect',
            'autoJobQueueCrawling' => 'crawl',
            'autoJobQueuePostProcessing' => 'post_process',
            'autoJobQueueDeployment' => 'deploy',
        ];

        foreach ( $job_types as $key => $job_type ) {
            if ( (int) CoreOptions::getValue( $key ) === 1 ) {
                JobQueue::addJob( $job_type );
            }
        }

        wp_safe_redirect( admin_url( 'admin.php?page=wp2static-jobs' ) );
        exit;
    }

    /*
        Should only process at most 4 jobs here (1 per type), with
        earlier jobs of the same type having been "squashed" first
    */
    public static function wp2static_process_queue() : void {
        // skip any earlier jobs of same type still in 'waiting' status
        JobQueue::squashQueue();

        if ( JobQueue::jobsInProgress() ) {
            WsLog::l(
                'Job in progress when attempting to process queue.
                  No new jobs will be processed until current in progress is complete.'
            );

            return;
        }

        // get all with status 'waiting' in order of oldest to newest
        $jobs = JobQueue::getProcessableJobs();

        foreach ( $jobs as $job ) {
            JobQueue::setStatus( $job->id, 'processing' );

            switch ( $job->job_type ) {
                case 'detect':
                    WsLog::l( 'Starting URL detection' );
                    $detected_count = URLDetector::detectURLs();
                    WsLog::l( "URL detection completed ($detected_count URLs detected)" );
                    break;
                case 'crawl':
                    WsLog::l( 'Starting crawling' );
                    $crawler = new Crawler();
                    $crawler->crawlSite( StaticSite::getPath() );
                    WsLog::l( 'Crawling completed' );
                    break;
                case 'post_process':
                    WsLog::l( 'Starting post-processing' );
                    $post_processor = new PostProcessor();
                    $processed_site_dir =
                        SiteInfo::getPath( 'uploads' ) . 'wp2static-processed-site';
                    $processed_site = new ProcessedSite();
                    $post_processor->processStaticSite( StaticSite::getPath() );
                    WsLog::l( 'Post-processing completed' );
                    break;
                case 'deploy':
                    if ( Addons::getDeployer() === 'no-enabled-deployment-addons' ) {
                        WsLog::l( 'No deployment add-ons are enabled, skipping deployment.' );
                    } else {
                        WsLog::l( 'Starting deployment' );
                        do_action(
                            'wp2static_deploy',
                            ProcessedSite::getPath(),
                            Addons::getDeployer()
                        );
                        do_action( 'wp2static_post_deploy_trigger', Addons::getDeployer() );
                    }

                    break;
                default:
                    WsLog::l( 'Trying to process unknown job type' );
            }

            JobQueue::setStatus( $job->id, 'completed' );
        }
    }

    public static function wp2static_headless() : void {
        WsLog::l( 'Running WP2Static\Controller::wp2static_headless()' );
        WsLog::l( 'Starting URL detection' );
        $detected_count = URLDetector::detectURLs();
        WsLog::l( "URL detection completed ($detected_count URLs detected)" );

        WsLog::l( 'Starting crawling' );
        $crawler = new Crawler();
        $crawler->crawlSite( StaticSite::getPath() );
        WsLog::l( 'Crawling completed' );

        WsLog::l( 'Starting post-processing' );
        $post_processor = new PostProcessor();
        $processed_site_dir =
            SiteInfo::getPath( 'uploads' ) . 'wp2static-processed-site';
        $processed_site = new ProcessedSite();
        $post_processor->processStaticSite( StaticSite::getPath() );
        WsLog::l( 'Post-processing completed' );

        if ( Addons::getDeployer() === 'no-enabled-deployment-addons' ) {
            WsLog::l( 'No deployment add-ons are enabled, skipping deployment.' );
        } else {
            WsLog::l( 'Starting deployment' );
            do_action( 'wp2static_deploy', ProcessedSite::getPath(), Addons::getDeployer() );
            do_action( 'wp2static_post_deploy_trigger', Addons::getDeployer() );
        }
    }

    public static function invalidate_single_url_cache(
        int $post_id = 0,
        WP_Post $post = null
    ) : void {
        if ( ! $post ) {
            return;
        }

        $permalink = get_permalink(
            $post->ID
        );

        $site_url = SiteInfo::getUrl( 'site' );

        if ( ! is_string( $permalink ) || ! is_string( $site_url ) ) {
            return;
        }

        $url = str_replace(
            $site_url,
            '/',
            $permalink
        );

        CrawlCache::rmUrl( $url );
    }

    public static function emailDeployNotification() : void {
        if ( empty( CoreOptions::getValue( 'completionEmail' ) ) ) {
            return;
        }

        WsLog::l( 'Sending deployment notification email...' );

        $to = CoreOptions::getValue( 'completionEmail' );
        $subject = 'WP2Static deployment complete on site: ' .
            $site_title = get_bloginfo( 'name' );
        $body = 'WP2Static deployment complete!';
        $headers = [];

        if ( wp_mail( $to, $subject, $body, $headers ) ) {
            WsLog::l( 'Deployment notification email sent without error.' );
        } else {
            WsLog::l( 'Failed to send deployment notificaiton email.' );
        }
    }

    public static function webhookDeployNotification() : void {
        $webhook_url = CoreOptions::getValue( 'completionWebhook' );

        if ( empty( $webhook_url ) ) {
            return;
        }

        WsLog::l( 'Sending deployment notification webhook...' );

        $http_method = CoreOptions::getValue( 'completionWebhookMethod' );

        $body = $http_method === 'POST' ? 'WP2Static deployment complete!' :
            [ 'message' => 'WP2Static deployment complete!' ];

        $webhook_response = wp_remote_request(
            $webhook_url,
            [
                'method' => CoreOptions::getValue( 'completionWebhookMethod' ),
                'timeout' => 30,
                'user-agent' => 'WP2Static.com',
                'body' => $body,
            ]
        );

        WsLog::l(
            'Webhook response code: ' . wp_remote_retrieve_response_code( $webhook_response )
        );
    }

    public static function wp2static_run() : void {
        check_ajax_referer( 'wp2static-run-page', 'security' );

        WsLog::l( 'Running full workflow from UI' );

        self::wp2static_headless();

        wp_die();
    }

    /**
     * Give logs to UI
     */
    public static function wp2static_poll_log() : void {
        check_ajax_referer( 'wp2static-run-page', 'security' );

        $logs = WsLog::poll();

        echo $logs;

        wp_die();
    }
}

