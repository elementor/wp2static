<?php

namespace WP2Static;

use ZipArchive;
use WP_Error;
use WP_CLI;
use WP_Post;

class Controller {
    const WP2STATIC_VERSION = '7.0-build-10';
    const HOOK = 'wp2static';

    public $options;
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

        // load Settings once into singleton
        $plugin_instance->options = new Options( 'wp2static-options');

        // TODO: change ExportSettings to ExportOptions
        ExportSettings::loadSettingsFromDBOptions(
            $plugin_instance->options->getSettings( true ));
        ExportSettings::setDestinationURL( 'https://example.com' );
        ExportSettings::loadRewriteRules();

        // create DB table for crawl caching
        CrawlCache::createTable();
        CrawlQueue::createTable();
        ExportLog::createTable();
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

    // TODO: adjust to add-on's seedOptions() style
    public function setDefaultOptions() : void {
        if ( null === self::$plugin_instance->options->getOption( 'version' ) ) {
            self::$plugin_instance->options
            ->setOption( 'version', self::WP2STATIC_VERSION )
            ->setOption( 'static_export_settings', self::WP2STATIC_VERSION )
            // set default options
            ->save();
        }
    }

    public static function activate_for_single_site() : void {
        self::setDefaultOptions();
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
            self::HOOK,
            [ self::$plugin_instance, 'renderOptionsPage' ],
            'dashicons-shield-alt');

        add_submenu_page(
            self::HOOK,
            'WP2Static Options',
            'Options',
            'manage_options',
            'wp2static',
            [ self::$plugin_instance, 'renderOptionsPage' ]);

        add_submenu_page(
            self::HOOK,
            'WP2Static Jobs',
            'Jobs',
            'manage_options',
            'wp2static-jobs',
            [ self::$plugin_instance, 'renderJobsPage' ]);

        add_submenu_page(
            self::HOOK,
            'WP2Static Caches',
            'Caches',
            'manage_options',
            'wp2static-caches',
            [ self::$plugin_instance, 'renderCachesPage' ]);

        add_submenu_page(
            self::HOOK,
            'WP2Static Diagnostics',
            'Diagnostics',
            'manage_options',
            'wp2static-diagnostics',
            [ self::$plugin_instance, 'renderDiagnosticsPage' ]);

    }


    // NOTE: wrapper for UI to echo success response
    public function finalize_deployment() : void {
        $deployer = new Deployer();
        $deployer->finalizeDeployment();

        echo 'SUCCESS';
    }

    /**
     * Generate ZIP of export log and print URL to UI
     *
     * @throws WP2StaticException
     */
    public function download_export_log() : void {
        $export_log = SiteInfo::getPath( 'uploads' ) .
            'wp2static-working-files/EXPORT-LOG.txt';

        if ( is_file( $export_log ) ) {
            // create zip of export log in tmp file
            $export_log_zip = SiteInfo::getPath( 'uploads' ) .
                'wp2static-working-files/EXPORT-LOG.zip';

            $zip_archive = new ZipArchive();
            $zip_opened =
                $zip_archive->open( $export_log_zip, ZipArchive::CREATE );

            if ( $zip_opened !== true ) {
                throw new WP2StaticException(
                    'Could not create archive'
                );
            }

            $real_filepath = realpath( $export_log );

            if ( ! $real_filepath ) {
                $err = 'Trying to add unknown file to Zip: ' . $export_log;
                WsLog::l( $err );
                throw new WP2StaticException( $err );
            }

            if ( ! $zip_archive->addFile(
                $real_filepath,
                'EXPORT-LOG.txt'
            )
            ) {
                throw new WP2StaticException(
                    'Could not add Export Log to zip'
                );
            }

            $zip_archive->close();

            echo SiteInfo::getUrl( 'uploads' ) .
                'wp2static-working-files/EXPORT-LOG.zip';
        } else {
            throw new WP2StaticException(
                'Unable to find Export Log to create ZIP'
            );
        }
    }

    // TODO: legacy, see about AssetDownloader
    // public function crawl_site() : void {
    //     $ch = curl_init();

    //     $asset_downloader = new AssetDownloader( $ch );

    //     $site_crawler = new SiteCrawler( $asset_downloader );

    //     $site_crawler->crawl();
    // }

    public function crawlSite() : void {
        $crawler = new Crawler();

        $static_site = new StaticSite('/tmp/teststaticsite');

        // TODO: if WordPressSite methods are static and we only need detectURLs
        // here, pass in iterable to URLs here?
        $crawler->crawlSite($static_site);

    }

    /**
     * Detect URLs within WordPress site and echo number of files to UI
     *
     * @throws WP2StaticException
     */
    public function detectURLs() : void {
        // TODO: move DeployQueue truncation somewhere...
        // DeployQueue::truncate();

        $detected_url_count = WordPressSite::detectURLs();

        if ( $detected_url_count < 1 ) {
            $err = 'Initial file list unable to be generated';
            http_response_code( 500 );
            echo $err;
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        $via_ui = filter_input( INPUT_POST, 'ajax_action' );

        if ( is_string( $via_ui ) ) {
            echo $detected_url_count;
        }
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
            error_log( 'no shell_exec' );
            return 'Unknown';
        }
    }

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

    public function load_wp2static_admin_js( string $hook ) : void {
        if ( $hook !== 'toplevel_page_wp2static' ) {
            return;
        }

        $plugin = self::getInstance();

        wp_register_script(
            'wp2static_admin_js',
            SiteInfo::getUrl( 'plugins' ) .
                'static-html-output-plugin/' . // TODO: rm hardcoding slug
                'admin/wp2static-admin.js',
            array( 'jquery' ),
            self::WP2STATIC_VERSION,
            false
        );

        $options = json_encode(
            $plugin->options->wp2static_options,
            JSON_FORCE_OBJECT | JSON_UNESCAPED_SLASHES
        );

        // TODO: move site info to diagnostics
        $site_info = SiteInfo::getAllInfo();
        $site_info['phpOutOfDate'] = PHP_VERSION < 7.2;
        $site_info['uploadsWritable'] = SiteInfo::isUploadsWritable();
        $site_info['maxExecutionTime'] = ini_get( 'max_execution_time' );
        $site_info['curlSupported'] = SiteInfo::hasCURLSupport();
        $site_info['permalinksDefined'] = SiteInfo::permalinksAreDefined();
        $site_info['domDocumentAvailable'] = class_exists( 'DOMDocument' );

        $plugin->site_info = $site_info;

        $site_info = json_encode(
            $site_info,
            JSON_FORCE_OBJECT | JSON_UNESCAPED_SLASHES
        );


    }

    public function renderOptionsPage() : void {
        $view = [];
        $view['nonce_action'] = self::HOOK . '-ui-options';
        $view['options_templates'] = [
            __DIR__ . '/../views/core-detection-options.php',
            __DIR__ . '/../views/core-crawling-options.php',
            __DIR__ . '/../views/core-post-processing-options.php',
            __DIR__ . '/../views/core-deployment-options.php',
            __DIR__ . '/../views/tab_forms.php',
            __DIR__ . '/../views/tab_advanced.php',
            __DIR__ . '/../views/tab_caching.php',
            __DIR__ . '/../views/tab_automation.php',
        ];

        $plugin = self::getInstance();

        $view['crawlingOptions'] = 
            array_merge(
                $plugin->options->getAllOptions(false, 'basicAuthUser'),
                $plugin->options->getAllOptions(false, 'basicAuthPassword')
            );

        $view['detectionOptions'] =
            $plugin->options->getAllOptions(false, 'detect');

        $view['postProcessingOptions'] = 
            array_merge(
                $plugin->options->getAllOptions(false, 'basicAuthUser'),
                $plugin->options->getAllOptions(false, 'basicAuthPassword')
            );

        $view = apply_filters( 'wp2static_render_options_page_vars', $view );


        require_once WP2STATIC_PATH . 'views/options-page.php';
    }

    public function renderDiagnosticsPage() : void {
        $view = [];
        // TODO: kill all vars in PHP templates
        $view['publiclyAccessible'] = self::check_local_dns_resolution();

        require_once WP2STATIC_PATH . 'views/diagnostics-page.php';
    }

    public function renderJobsPage() : void {
        $view = [];
        $view['jobs'] = JobQueue::getJobs();

        require_once WP2STATIC_PATH . 'views/jobs-page.php';
    }


    public function renderCachesPage() : void {
        $view = [];

        // performance check vs map 
        $diskSpace = 0;

        $exportedSiteDir = SiteInfo::getPath( 'uploads' ) . 'wp2static-exported-site/';
        if (is_dir($exportedSiteDir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $exportedSiteDir));

            foreach ($files as $file) {
                $diskSpace += $file->getSize();
            }
        }

        $view['exportedSiteDiskSpace'] = sprintf("%4.2f MB", $diskSpace / 1048576);
        // end check

        if ( is_dir( $exportedSiteDir ) ) {
            $view['exportedSiteFileCount'] = iterator_count(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($exportedSiteDir, \FilesystemIterator::SKIP_DOTS)
                )
            );
        } else {
            $view['exportedSiteFileCount'] = 0;
        }


        // performance check vs map 
        $diskSpace = 0;
        $processedSiteDir = SiteInfo::getPath( 'uploads' ) . 'wp2static-processed-site/';

        if (is_dir($processedSiteDir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $processedSiteDir));

            foreach ($files as $file) {
                $diskSpace += $file->getSize();
            }
        }


        $view['processedSiteDiskSpace'] = sprintf("%4.2f MB", $diskSpace / 1048576);
        // end check

        if ( is_dir( $processedSiteDir ) ) {
            $view['processedSiteFileCount'] = iterator_count(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($processedSiteDir, \FilesystemIterator::SKIP_DOTS)
                )
            );
        } else {
            $view['processedSiteFileCount'] = 0;
        }

        $view['crawlQueueTotalURLs'] = CrawlQueue::getTotal();
        $view['crawlCacheTotalURLs'] = CrawlCache::getTotal();
        $view['deployCacheTotalURLs'] = DeployCache::getTotal();

        require_once WP2STATIC_PATH . 'views/caches-page.php';
    }

    public function userIsAllowed() : bool {
        if ( defined( 'WP_CLI' ) ) {
            return true;
        }

        $referred_by_admin = check_admin_referer( self::HOOK . '-options' );
        $user_can_manage_options = current_user_can( 'manage_options' );

        return $referred_by_admin && $user_can_manage_options;
    }

    public function save_options() : void {
        $via_ui = filter_input( INPUT_POST, 'ajax_action' );

        // Note when running via UI, we save all options
        if ( is_string( $via_ui ) ) {
            if ( ! $this->userIsAllowed() ) {
                exit( 'Not allowed to change plugin options.' );
            }

            $this->options->saveAllOptions();
        }
    }

    public function prepare_for_export() : void {
        $this->save_options();
        $exporter = new Exporter();
        $exporter->pre_export_cleanup();

        FilesHelper::create_export_directory(
            SiteInfo::getPath( 'uploads' ) . 'wp2static-exported-site/');

        EnvironmentalInfo::log(
            self::WP2STATIC_VERSION,
            $this->options->getAllOptions( false ));

        $exporter->generateModifiedFileList();
        $via_ui = filter_input( INPUT_POST, 'ajax_action' );

        if ( is_string( $via_ui ) ) {
            echo 'SUCCESS';
        }
    }

    public function reset_default_settings() : void {
        if ( ! delete_option( 'wp2static-options' ) ) {
            $err = 'Couldn\'t reset plugin to default settings';
            WsLog::l( $err );
        }

        $this->options = new Options( 'wp2static-options' );
        $this->setDefaultOptions();
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
        error_log('handling options page saving');

        error_log(print_r($_POST, true));
        do_action('wp2static_addon_ui_save_options');

        check_admin_referer( self::HOOK . '-ui-options' );

        wp_redirect(admin_url('admin.php?page=wp2static'));
        exit; // required after wp_redirect
    }

    public function wp2static_ui_admin_notices() : void {
        error_log('add admin notices');

        // types notice-erorr, notice-warning, notice-info, notice-success

        $notices = [
            'errors' => [],
            'successes' => [],
            'warnings' => [],
            'infos' => [],
        ];

        $notices = apply_filters( 'wp2static_add_ui_admin_notices', $notices );

        error_log(print_r($notices, true));

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
        $start_time = microtime();

        $plugin = self::getInstance();
        $plugin->generate_filelist_preview();
        $plugin->prepare_for_export();
        $plugin->crawl_site();
        $plugin->post_process_archive_dir();

        $end_time = microtime();

        $duration = $Utils::microtime_diff( $start_time, $end_time );

        WsLog::l( "Generated static site archive in $duration seconds" );

        $deployer = new Deployer();
        $deployer->deploy();
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

