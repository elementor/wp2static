<?php

namespace WP2Static;

use ZipArchive;
use WP_Error;
use WP_CLI;
use WP_Post;

class Controller {
    const VERSION = '7.0-build0005';
    const OPTIONS_KEY = 'wp2static-options';
    const HOOK = 'wp2static';

    public $options;
    public $settings;
    public $site_url;
    public $site_url_host;
    public $destination_url;
    public $rewrite_rules;
    public $version;
    public $exporter;
    private $remove_wp_meta;
    private $remove_conditional_head_comments;
    private $remove_wp_links;
    private $remove_canonical_links;
    private $create_empty_favicon;
    private $remove_html_comments;

    /**
     * Main controller of WP2Static
     *
     * @var \WP2Static\Controller Instance.
     */
    protected static $instance = null;

    protected function __construct() {}

    /**
     * Returns instance of WP2Static Controller
     *
     * @return \WP2Static\Controller Instance of self.
     */
    public static function getInstance() : Controller {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->options = new Options(
                self::OPTIONS_KEY
            );
        }

        return self::$instance;
    }

    public static function init( string $bootstrap_file ) : Controller {
        $instance = self::getInstance();

        $ajax_action = filter_input( INPUT_POST, 'ajax_action' );

        // TODO: non-AJAX methods to use different signature
        if ( $ajax_action === 'reset_default_settings' ) {
            $instance->reset_default_settings();
        }

        register_activation_hook(
            $bootstrap_file,
            array( $instance, 'activate' )
        );

        if ( is_admin() ) {
            add_action(
                'admin_menu',
                array(
                    $instance,
                    'registerOptionsPage',
                )
            );
            add_filter( 'custom_menu_order', '__return_true' );
            add_filter( 'menu_order', array( $instance, 'set_menu_order' ) );
        }

        $instance->settings = $instance->options->getSettings( true );
        $instance->site_url = SiteInfo::getUrl( 'site' );
        $instance->remove_wp_meta = $instance->settings['removeWPMeta'];
        $instance->remove_conditional_head_comments =
            $instance->settings['removeConditionalHeadComments'];
        $instance->remove_wp_links =
            $instance->settings['removeWPLinks'];
        $instance->remove_canonical_links =
            $instance->settings['removeCanonical'];
        $instance->create_empty_favicon =
            $instance->settings['createEmptyFavicon'];
        $instance->remove_html_comments =
            $instance->settings['removeHTMLComments'];

        // create DB table for crawl caching
        CrawlCache::createTable();
        CrawlQueue::createTable();
        ExportLog::createTable();
        DeployQueue::createTable();
        DeployCache::createTable();

        // capture URL hosts for use in detecting internal links
        $instance->site_url_host =
            parse_url( $instance->site_url, PHP_URL_HOST );

        $instance->destination_url = $instance->settings['baseUrl'];

        if ( ! is_string( $instance->destination_url ) ) {
            $err = 'Destination URL not defined';
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        $instance->loadRewriteRules();

        // override max_execution_time to unlimited
        if ( $instance->set_max_execution_time() ) {
            set_time_limit( 0 );
        }

        add_action(
            'wp2static_headless_hook',
            [ 'WP2Static\Controller', 'wp2static_headless' ],
            10,
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
                [ 'WP2Static\Controller', 'invalidate_single_url_cache' ],
                0
            );
        }

        if ( isset( $instance->settings['redeployOnPostUpdates'] ) ) {
            add_action(
                'save_post',
                [ 'WP2Static\Controller', 'wp2static_headless' ],
                0
            );
        }

        if ( isset( $instance->settings['displayDashboardWidget'] ) ) {
            add_action(
                'wp_dashboard_setup',
                [ 'WP2Static\Controller', 'wp2static_add_dashboard_widgets' ],
                0
            );
        }

        add_action(
            'admin_enqueue_scripts',
            [ 'WP2Static\Controller', 'load_wp2static_admin_js' ]
        );

        return $instance;
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

    public function setDefaultOptions() : void {
        if ( null === $this->options->getOption( 'version' ) ) {
            $this->options
            ->setOption( 'version', self::VERSION )
            ->setOption( 'static_export_settings', self::VERSION )
            // set default options
            ->setOption( 'rewriteWPPaths', '1' )
            ->setOption( 'currentDeploymentMethod', 'folder' )
            ->setOption( 'currentDeploymentMethodProduction', 'folder' )
            ->setOption( 'removeConditionalHeadComments', '1' )
            ->setOption( 'removeWPMeta', '1' )
            ->setOption( 'dontUseCrawlCaching', '1' )
            ->setOption( 'removeWPLinks', '1' )
            ->setOption( 'removeHTMLComments', '1' )
            ->setOption( 'parseCSS', '0' )
            ->save();
        }
    }

    public function activate_for_single_site() : void {
        $this->setDefaultOptions();
    }

    public function activate( bool $network_wide = null ) : void {
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
                $this->activate_for_single_site();
            }

            restore_current_blog();
        } else {
            $this->activate_for_single_site();
        }
    }

    public function registerOptionsPage() : void {
        $plugins_url = plugin_dir_url( dirname( __FILE__ ) );
        $page = add_menu_page(
            __( 'WP2Static', 'static-html-output-plugin' ),
            __( 'WP2Static', 'static-html-output-plugin' ),
            'manage_options',
            self::HOOK,
            array( self::$instance, 'renderOptionsPage' ),
            'dashicons-shield-alt'
        );

        add_action(
            'admin_print_styles-' . $page,
            array(
                $this,
                'enqueueAdminStyles',
            )
        );
    }

    public function enqueueAdminStyles() : void {
        $plugins_url = plugin_dir_url( dirname( __FILE__ ) );

        wp_enqueue_style(
            self::HOOK . '-admin',
            $plugins_url . 'admin/wp2static.css?cache-buster=wp2static',
            array(),
            $this::VERSION
        );
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

    /**
     * Load user rewrite rules
     *
     * @throws WP2StaticException
     */
    public function loadRewriteRules() : void {
        // get user rewrite rules, use regular and escaped versions of them
        $this->rewrite_rules =
            RewriteRules::generate(
                $this->site_url,
                $this->destination_url
            );

        if ( ! $this->rewrite_rules ) {
            $err = 'No URL rewrite rules defined';
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }
    }

    public function crawl_site() : void {
        // TODO: check where all cURL handles being set, should
        // just be in SiteCrawler?

        $ch = curl_init();
        $site_url = SiteInfo::getUrl( 'site' );

        // add filter to allow user to specify extra downloadable extensions
        $crawlable_filetypes = [];
        $crawlable_filetypes['img'] = 1;
        $crawlable_filetypes['jpeg'] = 1;
        $crawlable_filetypes['jpg'] = 1;
        $crawlable_filetypes['png'] = 1;
        $crawlable_filetypes['webp'] = 1;
        $crawlable_filetypes['gif'] = 1;
        $crawlable_filetypes['svg'] = 1;

        $asset_downloader = new AssetDownloader(
            $ch,
            $site_url,
            $crawlable_filetypes,
            $this->settings
        );

        $site_crawler = new SiteCrawler(
            (bool) $this->settings['allowOfflineUsage'],
            (bool) $this->remove_wp_meta,
            (bool) $this->remove_conditional_head_comments,
            (bool) $this->remove_wp_links,
            (bool) $this->remove_canonical_links,
            (bool) $this->create_empty_favicon,
            (bool) $this->remove_html_comments,
            $this->site_url,
            $this->site_url_host,
            $this->destination_url,
            $this->rewrite_rules,
            $this->settings,
            $asset_downloader
        );

        $site_crawler->crawl();
    }

    public function test_folder() : void {
        $archive_processor = new ArchiveProcessor();

        $target_folder = $this->settings['targetFolder'];

        $has_safety_file =
            $archive_processor->dir_has_safety_file( $target_folder );
        $is_empty =
            $archive_processor->dir_is_empty( $target_folder );

        if ( $has_safety_file || $is_empty ) {
            wp_die( 'SUCCESS', '', 200 );
        }

        wp_die(
            'Not permitted to write to target directory',
            '',
            500
        );
    }

    /**
     * Generate initial crawl list and echo number of files to UI
     *
     * @throws WP2StaticException
     */
    public function generate_filelist_preview() : void {
        CrawlQueue::truncate();
        DeployQueue::truncate();

        $initial_file_list_count =
            FilesHelper::buildInitialFileList(
                true,
                SiteInfo::getPath( 'uploads' ),
                $this->settings
            );

        if ( $initial_file_list_count < 1 ) {
            $err = 'Initial file list unable to be generated';
            http_response_code( 500 );
            echo $err;
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        $via_ui = filter_input( INPUT_POST, 'ajax_action' );

        if ( is_string( $via_ui ) ) {
            echo $initial_file_list_count;
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
            $plugin->version,
            false
        );

        $options = json_encode(
            $plugin->options->wp2static_options,
            JSON_FORCE_OBJECT | JSON_UNESCAPED_SLASHES
        );

        $site_info = SiteInfo::getAllInfo();
        $site_info['phpOutOfDate'] = PHP_VERSION < 7.2;
        $site_info['uploadsWritable'] = SiteInfo::isUploadsWritable();
        $site_info['curlSupported'] = SiteInfo::hasCURLSupport();
        $site_info['permalinksDefined'] = SiteInfo::permalinksAreDefined();
        $site_info['domDocumentAvailable'] = class_exists( 'DOMDocument' );

        $site_info = json_encode(
            $site_info,
            JSON_FORCE_OBJECT | JSON_UNESCAPED_SLASHES
        );

        $data = array(
            // TODO: pass translatable strings
            'someString' => __( 'Some string to translate', 'plugin-domain' ),
            'options' => $options,
            'siteInfo' => $site_info,
            'onceAction' => self::HOOK . '-options',
        );

        wp_localize_script( 'wp2static_admin_js', 'wp2staticString', $data );
        wp_enqueue_script( 'wp2static_admin_js' );
    }

    public function renderOptionsPage() : void {
        $view = [];
        // TODO: kill all vars in PHP templates
        $view['onceAction'] = self::HOOK . '-options';

        require_once WP2STATIC_PATH . 'views/options-page.php';
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

    /**
     * Create export dir
     *
     * @throws WP2StaticException
     */
    public function create_export_directory() : void {
        $archive_path = SiteInfo::getPath( 'uploads' ) .
                'wp2static-exported-site/';

        if ( ! wp_mkdir_p( $archive_path ) ) {
            $err = "Couldn't create archive directory:" . $archive_path;
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }
    }

    public function prepare_for_export() : void {
        $this->save_options();

        $this->exporter = new Exporter();

        $this->exporter->pre_export_cleanup();

        $this->create_export_directory();

        $this->logEnvironmentalInfo();

        $this->exporter->generateModifiedFileList();

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

        $this->options = new Options( self::OPTIONS_KEY );
        $this->setDefaultOptions();
    }

    public function post_process_archive_dir() : void {
        $processor = new ArchiveProcessor();

        $processor->createNetlifySpecialFiles();
        // NOTE: renameWP Directories also doing same server publish
        $processor->renameArchiveDirectories();
        $processor->removeWPCruft();
        $processor->copyStaticSiteToPublicFolder();
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

    public function logEnvironmentalInfo() : void {
        $info = array(
            'EXPORT START: ' . date( 'Y-m-d h:i:s' ),
            'PLUGIN VERSION: ' . $this::VERSION,
            'PHP VERSION: ' . phpversion(),
            'OS VERSION: ' . php_uname(),
            'PHP MEMORY LIMIT: ' . ini_get( 'memory_limit' ),
            'WP VERSION: ' . get_bloginfo( 'version' ),
            'WP URL: ' . get_bloginfo( 'url' ),
            'WP SITEURL: ' . get_option( 'siteurl' ),
            'WP HOME: ' . get_option( 'home' ),
            'WP ADDRESS: ' . get_bloginfo( 'wpurl' ),
            defined( 'WP_CLI' ) ? 'WP-CLI: YES' : 'WP-CLI: NO',
            'STATIC EXPORT URL: ' . $this->settings['baseUrl'],
            'PERMALINK STRUCTURE: ' . get_option( 'permalink_structure' ),
        );

        if ( isset( $_SERVER['SERVER_SOFTWARE'] ) ) {
            $info[] = 'SERVER SOFTWARE: ' . $_SERVER['SERVER_SOFTWARE'];
        }

        $info[] = 'ACTIVE PLUGINS: ';

        $active_plugins = get_option( 'active_plugins' );

        foreach ( $active_plugins as $active_plugin ) {
            $info[] = $active_plugin;
        }

        $info[] = 'ACTIVE THEME: ';

        $theme = wp_get_theme();

        $info[] = $theme->get( 'Name' ) . ' is version ' .
            $theme->get( 'Version' );

        $info[] = 'WP2STATIC OPTIONS: ';

        $options = $this->options->getAllOptions( false );

        foreach ( $options as $key[] => $value ) {
            $info[] = "{$value['Option name']}: {$value['Value']}";
        }

        $info[] = 'SITE URL PATTERNS: ' .
            $this->rewrite_rules['site_url_patterns'];

        $info[] = 'DESTINATION URL PATTERNS: ' .
            $this->rewrite_rules['destination_url_patterns'];

        $extensions = get_loaded_extensions();

        $info[] = 'INSTALLED EXTENSIONS: ' .
            join( PHP_EOL, $extensions );

        WsLog::lines( $info );
    }

    public function wp2static_headless() : void {
        $start_time = microtime();

        $plugin = self::getInstance();
        $plugin->generate_filelist_preview();
        $plugin->prepare_for_export();
        $plugin->crawl_site();
        $plugin->post_process_archive_dir();

        $end_time = microtime();

        $duration = $plugin->microtime_diff( $start_time, $end_time );

        WsLog::l( "Generated static site archive in $duration seconds" );

        $deployer = new Deployer();
        $deployer->deploy();
    }

    public function microtime_diff(
        string $start,
        string $end = null
    ) : float {
        if ( ! $end ) {
            $end = microtime();
        }

        list( $start_usec, $start_sec ) = explode( ' ', $start );
        list( $end_usec, $end_sec ) = explode( ' ', $end );

        $diff_sec = intval( $end_sec ) - intval( $start_sec );
        $diff_usec = floatval( $end_usec ) - floatval( $start_usec );

        return floatval( $diff_sec ) + $diff_usec;
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

    public function wp2static_add_dashboard_widgets() : void {
        wp_add_dashboard_widget(
            'wp2static__dashboard_widget',
            'WP2Static',
            [ 'WP2Static\Controller', 'wp2static_dashboard_widget_function' ]
        );
    }

    public function wp2static_dashboard_widget_function() : void {
        echo '<p>Publish whole site as static HTML</p>';
        echo "<button class='button button-primary'>Publish whole site" .
            '</button>';
    }

    public function set_max_execution_time() : bool {
        if (
            ! function_exists( 'set_time_limit' ) ||
            ! function_exists( 'ini_get' )
        ) {
            return false;
        }

        $current_max_execution_time  = ini_get( 'max_execution_time' );
        $proposed_max_execution_time =
            ( $current_max_execution_time == 30 ) ? 31 : 30;
        set_time_limit( $proposed_max_execution_time );
        $current_max_execution_time = ini_get( 'max_execution_time' );

        return $proposed_max_execution_time == $current_max_execution_time;
    }
}
