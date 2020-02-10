<?php

namespace WP2Static;

use ZipArchive;
use WP_Error;
use WP_CLI;
use WP_Post;

class Controller {
    const WP2STATIC_VERSION = '7.0-build-10';

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

        // prepare DB tables
        CoreOptions::init();
        CrawlCache::createTable();
        CrawlQueue::createTable();
        WsLog::createTable();
        DeployQueue::createTable();
        DeployCache::createTable();
        JobQueue::createTable();

        ConfigHelper::set_max_execution_time();

        return $plugin_instance;
    }

    /**
     * Adjusts position of dashboard menu icons
     *
     * @param string[] $menu_order list of menu items
     * @return string[] list of menu items
     */
    public function set_menu_order( array $menu_order ) : array {
        $order = [];
        $file  = plugin_basename( __FILE__ );

        foreach ( $menu_order as $index => $item ) {
            if ( $item === 'index.php' ) {
                $order[] = $item;
            }
        }

        $order = array(
            'index.php',
            'wp2static',
        );

        return $order;
    }

    public static function uninstall() : void {
        error_log('Uninstalled WP2Static for single site');

        WPCron::clearRecurringEvent();
    }

    public static function deactivate_for_single_site() : void {
        error_log('Deactivated WP2Static for single site');

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
        WsLog::l( "Activated WP2Static for single site");
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

    public function registerOptionsPage() : void {
        add_menu_page(
            'WP2Static',
            'WP2Static',
            'manage_options',
            'wp2static',
            [ 'WP2Static\ViewRenderer', 'renderOptionsPage' ],
            'dashicons-shield-alt');

        $submenu_pages = [
            'options',
            'jobs',
            'caches',
            'diagnostics',
            'logs',
        ];

        foreach ($submenu_pages as $page) {
            $menu_slug =
                $page === 'options' ? 'wp2static' : 'wp2static-' . $page;

            add_submenu_page(
                'wp2static',
                'WP2Static ' . ucfirst($page),
                ucfirst($page),
                'manage_options',
                $menu_slug,
                [ 'WP2Static\ViewRenderer', 'render' . ucfirst($page) . 'Page' ]);
        }
    }

    public function crawlSite() : void {
        $crawler = new Crawler();
        $static_site = new StaticSite('/tmp/teststaticsite');

        // TODO: if WordPressSite methods are static and we only need detectURLs
        // here, pass in iterable to URLs here?
        $crawler->crawlSite($static_site);

        // TOOD: legacy AssetDownloader implementation
        //     $ch = curl_init();
        //     $asset_downloader = new AssetDownloader( $ch );
        //     $site_crawler = new SiteCrawler( $asset_downloader );
        //     $site_crawler->crawl();
    }

    /**
     * Check whether a PHP function is enabled
     *
     * @param string $function_name list of menu items
     */
    public function isEnabled( string $function_name ) : bool {
        $disable_functions = ini_get( 'disable_functions' );

        if ( ! is_string( $disable_functions ) ) {
            return false;
        }

        $is_enabled =
            is_callable( $function_name ) &&
            false === stripos( $disable_functions, $function_name );

        return $is_enabled;
    }

    /**
     * Check whether site it publicly accessible
     */
    public function check_local_dns_resolution() : string {
        if ( $this->isEnabled( 'shell_exec' ) ) {
            $site_host = parse_url( $this->site_url, PHP_URL_HOST );

            $output =
                shell_exec( "/usr/sbin/traceroute $site_host" );

            if ( ! is_string( $output ) ) {
                return 'Unknown';
            }

            $hops_in_route = substr_count( $output, PHP_EOL );

            $resolves_to_local_ip4 =
                ( strpos( $output, '127.0.0.1' ) !== false );
            $resolves_to_local_ip6 = ( strpos( $output, '::1' ) !== false );

            $resolves_locally =
                $resolves_to_local_ip4 || $resolves_to_local_ip6;

            if ( $resolves_locally && $hops_in_route < 2 ) {
                return 'Yes';
            } else {
                return 'No';
            }
        } else {
            WsLog::l( "no shell_exec available for local DNS checking");
            return 'Unknown';
        }
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

    public function post_process_archive_dir() : void {
        $processor = new ArchiveProcessor();

        $processor->createNetlifySpecialFiles();
        // NOTE: renameWP Directories also doing same server publish
        $processor->renameArchiveDirectories();
        $processor->removeWPCruft();
        $processor->create_zip();

        $via_ui = filter_input( INPUT_POST, 'ajax_action' );

        if ( is_string( $via_ui ) ) {
            echo 'SUCCESS';
        }
    }

    public function delete_deploy_cache() : void {
        DeployCache::truncate();

        $via_ui = filter_input( INPUT_POST, 'ajax_action' );

        if ( is_string( $via_ui ) ) {
            echo 'SUCCESS';
        }
    }

    public function wp2static_ui_save_options() : void {
        CoreOptions::savePosted('core');

        do_action('wp2static_addon_ui_save_options');

        check_admin_referer( 'wp2static-ui-options' );

        wp_redirect(admin_url('admin.php?page=wp2static'));
        exit;
    }

    public function wp2static_ui_save_job_options() : void {
        CoreOptions::savePosted('jobs');

        do_action('wp2static_addon_ui_save_job_options');

        check_admin_referer( 'wp2static-ui-job-options' );

        wp_redirect(admin_url('admin.php?page=wp2static-jobs'));
        exit;
    }

    public function wp2static_save_post_handler( $post_id ) : void {
        if ( get_post_status( $post_id ) !== 'publish') {
            return;
        }

        self::wp2static_enqueue_jobs( $post_id );
    }

    public function wp2static_trashed_post_handler( $post_id ) : void {
        self::wp2static_enqueue_jobs( $post_id );
    }

    public function wp2static_enqueue_jobs( $post_id ) : void {
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

    public function wp2static_manually_enqueue_jobs() : void {
        check_admin_referer( 'wp2static-manually-enqueue-jobs' );

        // TODO: consider using a transient based notifications system to
        // persist through wp_redirect calls
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

        wp_redirect(admin_url('admin.php?page=wp2static-jobs'));
        exit;
    }

    /*
        Should only process at most 4 jobs here (1 per type), with
        earlier jobs of the same type having been "squashed" first
    */
    public function wp2static_process_queue() : void {
        // skip any earlier jobs of same type still in 'waiting' status
        JobQueue::squashQueue();

        if ( JobQueue::jobsInProgress() ) {
            WsLog::l( "Job in progress when attempting to process queue. No new jobs will be processed until current in progress is complete.");
            return;
        }

        // get all with status 'waiting' in order of oldest to newest
        $jobs = JobQueue::getProcessableJobs();

        foreach ($jobs as $job) {
            JobQueue::setStatus($job->id, 'processing');

            switch ( $job->job_type ) {
                case 'detect':
                    WsLog::l( "Starting URL detection");
                    $detected_count = URLDetector::detectURLs();
                    WsLog::l( "URL detection completed ($detected_count URLs detected)");
                    break;
                case 'crawl':
                    WsLog::l( "Starting crawling");
                    $crawler = new Crawler();
                    $crawler->crawlSite( StaticSite::getPath());
                    WsLog::l( "Crawling completed");
                    break;
                case 'post_process':
                    WsLog::l( "Starting post-processing");
                    $post_processor = new PostProcessor();
                    $processed_site_dir =
                        SiteInfo::getPath( 'uploads') . 'wp2static-processed-site';
                    $processed_site = new ProcessedSite( $processed_site_dir );
                    $post_processor->processStaticSite( StaticSite::getPath(), $processed_site);
                    WsLog::l( "Post-processing completed");
                    break;
                case 'deploy':
                    WsLog::l( "Starting deployment");
                    do_action('wp2static_deploy', ProcessedSite::getPath());
                    WsLog::l( "Deployment complete");
                    break;
                default:
                    WsLog::l('Trying to process unknown job type');
            }

            JobQueue::setStatus($job->id, 'completed');
        }
    }

    public function wp2static_ui_admin_notices() : void {
        $notices = [
            'errors' => [],
            'successes' => [],
            'warnings' => [],
            'infos' => [],
        ];

        $notices = apply_filters( 'wp2static_add_ui_admin_notices', $notices );

        foreach($notices['errors'] as $errors) {
            echo '<div class="notice notice-error">';
                echo '<p>something</p>';
            echo '</div>';
        }

        foreach($notices['successes'] as $successes) {
            echo '<div class="notice notice-success is-dismissible">';
                echo '<p>some success</p>';
            echo '</div>';
        }
    }

    public function wp2static_headless() : void {
        WsLog::l( "Running WP2Static\Controller::wp2static_headless()");
        WsLog::l( "Starting URL detection");
        $detected_count = URLDetector::detectURLs();
        WsLog::l( "URL detection completed ($detected_count URLs detected)");

        WsLog::l( "Starting crawling");
        $crawler = new Crawler();
        $crawler->crawlSite( StaticSite::getPath());
        WsLog::l( "Crawling completed");

        WsLog::l( "Starting post-processing");
        $post_processor = new PostProcessor();
        $processed_site_dir =
            SiteInfo::getPath( 'uploads') . 'wp2static-processed-site';
        $processed_site = new ProcessedSite( $processed_site_dir );
        $post_processor->processStaticSite( StaticSite::getPath(), $processed_site);
        WsLog::l( "Post-processing completed");

        WsLog::l( "Starting deployment");
        do_action('wp2static_deploy', ProcessedSite::getPath());
        WsLog::l( "Deployment complete");
    }

    public function invalidate_single_url_cache(
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
}

