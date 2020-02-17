<?php

namespace WP2Static;

use WP_CLI;

/**
 * Generate a static copy of your website & publish remotely
 */
class CLI {
    /**
     * Display system information and health check
     */
    public function diagnostics() : void {
        WP_CLI::line(
            PHP_EOL . 'WP2Static' . PHP_EOL
        );

        $environmental_info = array(
            array(
                'key' => 'PLUGIN VERSION',
                'value' => Controller::VERSION,
            ),
            array(
                'key' => 'PHP_VERSION',
                'value' => phpversion(),
            ),
            array(
                'key' => 'PHP MAX EXECUTION TIME',
                'value' => ini_get( 'max_execution_time' ),
            ),
            array(
                'key' => 'OS VERSION',
                'value' => php_uname(),
            ),
            array(
                'key' => 'WP VERSION',
                'value' => get_bloginfo( 'version' ),
            ),
            array(
                'key' => 'WP URL',
                'value' => get_bloginfo( 'url' ),
            ),
            array(
                'key' => 'WP SITEURL',
                'value' => get_option( 'siteurl' ),
            ),
            array(
                'key' => 'WP HOME',
                'value' => get_option( 'home' ),
            ),
            array(
                'key' => 'WP ADDRESS',
                'value' => get_bloginfo( 'wpurl' ),
            ),
        );

        WP_CLI\Utils\format_items(
            'table',
            $environmental_info,
            array( 'key', 'value' )
        );

        $active_plugins = get_option( 'active_plugins' );

        WP_CLI::line( PHP_EOL . 'Active plugins:' . PHP_EOL );

        foreach ( $active_plugins as $active_plugin ) {
            WP_CLI::line( $active_plugin );
        }

        WP_CLI::line( PHP_EOL );

        WP_CLI::line(
            'There are a total of ' . count( $active_plugins ) .
            ' active plugins on this site.' . PHP_EOL
        );

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

    /**
     * Generate a static copy of your WordPress site.
     */
    public function generate() : void {
        $start_time = microtime();

        $plugin = Controller::getInstance();
        $plugin->generate_filelist_preview();
        $plugin->prepare_for_export();
        $plugin->crawl_site();
        $plugin->post_process_archive_dir();

        $end_time = microtime();

        $duration = $this->microtime_diff( $start_time, $end_time );

        WP_CLI::success(
            "Generated static site archive in $duration seconds"
        );
    }

    /**
     * Deploy the generated static site.
     * ## OPTIONS
     *
     * @param string[] $args CLI args
     * @param string[] $assoc_args CLI args
     */
    public function deploy(
        array $args,
        array $assoc_args
    ) : void {
        do_action('wp2static_deploy', ProcessedSite::getPath());
    }

    /**
     * Read / write plugin options
     *
     * ## OPTIONS
     *
     * <list> [--reveal-sensitive-values]
     *
     * Get all option names and values (explicitly reveal sensitive values)
     *
     * <get> <option-name>
     *
     * Get or set a specific option via name
     *
     * <set> <option-name> <value>
     *
     * Set a specific option via name
     *
     *
     * ## EXAMPLES
     *
     * List all options
     *
     *     wp wp2static options list
     *
     * List all options (revealing sensitive values)
     *
     *     wp wp2static options list --reveal_sensitive_values
     *
     * Get option
     *
     *     wp wp2static options get detectPages
     *
     * Set option
     *
     *     wp wp2static options set detectPages 1
     *     wp wp2static options set queueJobOnPostSave 1
     *
     * @param string[] $args CLI args
     * @param string[] $assoc_args CLI args
     */
    public function options(
        array $args,
        array $assoc_args
    ) : void {
        $action = isset( $args[0] ) ? $args[0] : null;
        $option_name = isset( $args[1] ) ? $args[1] : null;
        $value = isset( $args[2] ) ? $args[2] : null;
        $reveal_sensitive_values = false;

        if ( empty( $action ) ) {
            WP_CLI::error( 'Missing required argument: <get|set|list>' );
        }

        $plugin = Controller::getInstance();

        if ( $action === 'get' ) {
            if ( empty( $option_name ) ) {
                WP_CLI::error( 'Missing required argument: <option-name>' );
            }

            if ( ! $plugin->options->optionExists( $option_name ) ) {
                WP_CLI::error( 'Invalid option name' );
            } else {
                $option_value =
                    $plugin->options->getOption( $option_name );

                WP_CLI::line( $option_value );
            }
        }

        if ( $action === 'set' ) {
            if ( empty( $option_name ) ) {
                WP_CLI::error( 'Missing required argument: <option-name>' );
            }

            if ( empty( $value ) ) {
                WP_CLI::error( 'Missing required argument: <value>' );
            }

            if ( ! $plugin->options->optionExists( $option_name ) ) {
                WP_CLI::error( 'Invalid option name' );
            } else {
                $plugin->options->setOption( $option_name, $value );
                $plugin->options->save();

                $result = $plugin->options->getOption( $option_name );

                if ( $result !== $value ) {
                    WP_CLI::error( 'Option not able to be updated' );
                }
            }
        }

        if ( $action === 'unset' ) {
            if ( empty( $option_name ) ) {
                WP_CLI::error( 'Missing required argument: <option-name>' );
            }

            if ( ! $plugin->options->optionExists( $option_name ) ) {
                WP_CLI::error( 'Invalid option name' );
            }

            $plugin->options->setOption( $option_name, '' );
            $plugin->options->save();
            $result = $plugin->options->getOption( $option_name );

            if ( ! empty( $result ) ) {
                WP_CLI::error( 'Option not able to be updated' );
            }
        }

        if ( $action === 'list' ) {
            if ( isset( $assoc_args['reveal-sensitive-values'] ) ) {
                $reveal_sensitive_values = true;
            }

            $options =
                $plugin->options->getAllOptions( $reveal_sensitive_values );

            WP_CLI\Utils\format_items(
                'table',
                $options,
                array( 'Option name', 'Value' )
            );
        }
    }

    public function showWizardMenu($level = 0) {
        switch($level) {
            default:
            case 0:
                WP_CLI::line( "Enter the number of the desired menu item:" );
                WP_CLI::line( "" );
                WP_CLI::line( "0) Quick-start: generate static site with current options" );
                WP_CLI::line( "1) Options - view/manage WP2Static options" );
                WP_CLI::line( "2) Jobs - view/manage" );
                WP_CLI::line( "3) Caches - view/manage" );
                WP_CLI::line( "4) Diagnostics" );
                WP_CLI::line( "--------------" );
                WP_CLI::line( "q) Exit to shell" );
                WP_CLI::line( "" );
            break;
            case 1:
                WP_CLI::line( "Enter the number of the desired menu item:" );
                WP_CLI::line( "" );
                WP_CLI::line( "0) Guided options configuration" );
                WP_CLI::line( "1) Show currently set options" );
                WP_CLI::line( "2) Reset to default options" );
                WP_CLI::line( "--------------" );
                WP_CLI::line( "b) Back to main menu" );
                WP_CLI::line( "q) Exit to shell" );
                WP_CLI::line( "" );
            break;
            case 2:
                WP_CLI::line( "Enter the number of the desired menu item:" );
                WP_CLI::line( "" );
                WP_CLI::line( "0) Show latest jobs" );
                WP_CLI::line( "1) Cancel running job" );
                WP_CLI::line( "2) Detect URLs" );
                WP_CLI::line( "3) Crawl Site" );
                WP_CLI::line( "4) Post-process site" );
                WP_CLI::line( "5) Deploy" );
                WP_CLI::line( "--------------" );
                WP_CLI::line( "b) Back to main menu" );
                WP_CLI::line( "q) Exit to shell" );
                WP_CLI::line( "" );
            break;
            case 3:
                WP_CLI::line( "Enter the number of the desired menu item:" );
                WP_CLI::line( "" );
                WP_CLI::line( "0) Show all cache statistics" );
                WP_CLI::line( "1) Show Detected URLs" );
                WP_CLI::line( "2) Delete Detected URLs" );
                WP_CLI::line( "3) Show Crawl Cache URLs" );
                WP_CLI::line( "4) Delete Crawl Cache URLs" );
                WP_CLI::line( "5) Delete Generated Static Site files" );
                WP_CLI::line( "6) Delete Post-processed Static Site files" );
                WP_CLI::line( "7) Show Deploy Cache URLs" );
                WP_CLI::line( "8) Delete Deploy Cache URLs" );
                WP_CLI::line( "--------------" );
                WP_CLI::line( "b) Back to main menu" );
                WP_CLI::line( "q) Exit to shell" );
                WP_CLI::line( "" );
            break;
            case 4:
                WP_CLI::line( "Enter the number of the desired menu item:" );
                WP_CLI::line( "" );
                WP_CLI::line( "0) Show diagnostics" );
                WP_CLI::line( "1) Email diagnostics" );
                WP_CLI::line( "2) Save diagnostics to file" );
                WP_CLI::line( "--------------" );
                WP_CLI::line( "b) Back to main menu" );
                WP_CLI::line( "q) Exit to shell" );
                WP_CLI::line( "" );
            break;
            // options wizard
            case 8:
                WP_CLI::line( PHP_EOL . "Detection level" );
                WP_CLI::line( "===============" . PHP_EOL );
                WP_CLI::line( "Affects which WordPress URLs are going " .
                    "to be crawled when generating your static site" . PHP_EOL );
                WP_CLI::line( "0) Homepage only" );
                WP_CLI::line( "1) Most common URLs (Post, Pages, Archives, etc)" );
                WP_CLI::line( "2) Maximum URL detection" );
                WP_CLI::line( "3) Custom (let me choose exactly what's detected)" );
                WP_CLI::line( "--------------" );
                WP_CLI::line( "b) Back to Options menu" );
                WP_CLI::line( "q) Exit to shell" );
                WP_CLI::line( "" );
            break;
        }

    }

    public function routeWizardSelection($level, $selection) {
        $selection_map = [
            0 => [
                0 => 'wp2static_cli_quick_start',
                1 => 'wp2static_cli_options_menu',
                2 => 'wp2static_cli_jobs_menu',
                3 => 'wp2static_cli_caches_menu',
                4 => 'wp2static_cli_diagnostics_menu',
                'q' => 'wp2static_cli_exit_to_shell',
            ],
            1 => [
                0 => 'wp2static_cli_options_launch_wizard',
                1 => 'wp2static_cli_options_list',
                'b' => 'wizard',
                'q' => 'wp2static_cli_exit_to_shell',
            ],
            2 => [
                0 => 'wp2static_cli_jobs_launch_wizard',
                2 => 'wp2static_cli_jobs_exec_detect',
                'b' => 'wizard',
                'q' => 'wp2static_cli_exit_to_shell',
            ],
            3 => [
                0 => 'wp2static_cli_caches_launch_wizard',
                1 => 'wp2static_cli_caches_list_detected_urls',
                2 => 'wp2static_cli_caches_truncate_crawl_queue',
                'b' => 'wizard',
                'q' => 'wp2static_cli_exit_to_shell',
            ],
            4 => [
                0 => 'wp2static_cli_diagnostics_launch_wizard',
                'b' => 'wizard',
                'q' => 'wp2static_cli_exit_to_shell',
            ],
            8 => [
                0 => 'wp2static_cli_options_set_detect_homepage_only',
                1 => 'wp2static_cli_options_set_detect_common',
                2 => 'wp2static_cli_options_set_detect_maximum',
                3 => 'wp2static_cli_options_set_detect_wizard',
                'b' => 'wp2static_cli_options_menu',
                'q' => 'wp2static_cli_exit_to_shell',
            ],
        ];

        if ( ! is_callable( [ $this, $selection_map[$level][$selection] ] ) ) {
            WP_CLI::line('Tried to call missing function');
            $this->showWizardWaitForSelection($level);
        } else {
            call_user_func( [ $this, $selection_map[$level][$selection] ] );
        }
    }

    public function wp2static_cli_exit_to_shell() {
        WP_CLI::line( PHP_EOL . "### Exiting to shell, goodbye! ###" . PHP_EOL );

        WP_CLI::halt(0);
    }

    public function wp2static_cli_options_set_detect_common() {
        WP_CLI::line( PHP_EOL . "### Setting Common URL detection  ###" . PHP_EOL );

        $plugin = Controller::getInstance();

        $detections = [
            'detectArchives',
            'detectAttachments',
            'detectCategoryPagination',
            'detectChildTheme',
            'detectCustomPostTypes',
            'detectHomepage',
            'detectPages',
            'detectParentTheme',
            'detectPostPagination',
            'detectPosts',
            'detectUploads',
        ];

        foreach( $detections as $detection ) {
            $plugin->options->setOption( $detection, 1 );
        }

        $plugin->options->save();

        WP_CLI::line( PHP_EOL . "Common URL detection set!" . PHP_EOL );

        $this->showWizardWaitForSelection(8);
    }

    public function wp2static_cli_options_set_detect_homepage_only() {
        WP_CLI::line( PHP_EOL . "### Setting Homepage only URL detection  ###" . PHP_EOL );

        $plugin = Controller::getInstance();

        $detections = [
            'detectArchives',
            'detectAttachments',
            'detectCategoryPagination',
            'detectChildTheme',
            'detectCommentPagination',
            'detectComments',
            'detectCustomPostTypes',
            'detectFeedURLs',
            'detectPages',
            'detectParentTheme',
            'detectPluginAssets',
            'detectPostPagination',
            'detectPosts',
            'detectUploads',
            'detectVendorCacheDirs',
            'detectWPIncludesAssets',
        ];

        foreach( $detections as $detection ) {
            $plugin->options->setOption( $detection, 0 );
        }

        $plugin->options->setOption( 'detectHomepage', 1 );

        $plugin->options->save();

        WP_CLI::line( PHP_EOL . "Homepage only URL detection set!" . PHP_EOL );

        $this->showWizardWaitForSelection(8);
    }

    public function wp2static_cli_options_set_detect_maximum() {
        WP_CLI::line( PHP_EOL . "### Setting maximum URL detection  ###" . PHP_EOL );

        $plugin = Controller::getInstance();

        $detections = [
            'detectArchives',
            'detectAttachments',
            'detectCategoryPagination',
            'detectChildTheme',
            'detectCommentPagination',
            'detectComments',
            'detectCustomPostTypes',
            'detectFeedURLs',
            'detectHomepage',
            'detectPages',
            'detectParentTheme',
            'detectPluginAssets',
            'detectPostPagination',
            'detectPosts',
            'detectUploads',
            'detectVendorCacheDirs',
            'detectWPIncludesAssets',
        ];

        foreach( $detections as $detection ) {
            $plugin->options->setOption( $detection, 1 );
        }

        $plugin->options->save();

        WP_CLI::line( PHP_EOL . "Maximum URL detection set!" . PHP_EOL );

        $this->showWizardWaitForSelection(8);
    }

    public function wp2static_cli_caches_truncate_crawl_queue() {
        WP_CLI::line( PHP_EOL . "### Deleting Crawl Queue ###" . PHP_EOL );

        CrawlQueue::truncate();

        WP_CLI::line( PHP_EOL . "Crawl Queue Deleted!" . PHP_EOL );

        $this->showWizardWaitForSelection(3);
    }

    public function wp2static_cli_clear_screen() {
        echo "\e[H\e[J";
    }

    public function wp2static_cli_jobs_exec_detect() {

        WP_CLI::line( "### Detect URLs ###" );

        $this->detect();

        $this->showWizardWaitForSelection(2);
    }

    public function wp2static_cli_quick_start() {
        $this->wp2static_cli_clear_screen();
        WP_CLI::line( "### Quick-start: generate static site with current options###" );

        $this->detect();
        $this->crawl();
        $this->post_process();

        $processed_site_dir =
            SiteInfo::getPath( 'uploads') . 'wp2static-processed-site';

        WP_CLI::success( PHP_EOL . "Processed static site dir: $processed_site_dir"  . PHP_EOL );

        $this->showWizardWaitForSelection(0);
    }

    public function wp2static_cli_options_launch_wizard() {
        WP_CLI::line( PHP_EOL . "### Options - view/manage WP2Static options ###" . PHP_EOL);

        $this->showWizardWaitForSelection(8);
    }

    public function wp2static_cli_options_menu() {
        WP_CLI::line( PHP_EOL . "### Guided options configuration ###" . PHP_EOL);

        $this->showWizardWaitForSelection(1);
    }

    public function wp2static_cli_jobs_menu() {
        WP_CLI::line( "### Options - view/manage WP2Static jobs ###" );
        WP_CLI::line( "" );

        $this->showWizardWaitForSelection(2);
    }

    public function wp2static_cli_caches_menu() {
        WP_CLI::line( "### Options - view/manage WP2Static caches ###" );
        WP_CLI::line( "" );

        $this->showWizardWaitForSelection(3);
    }

    public function wp2static_cli_diagnostics_menu() {
        WP_CLI::line( "### Options - diagnostics  ###" );
        WP_CLI::line( "" );

        $this->showWizardWaitForSelection(4);
    }

    public function wp2static_test_called_func_2() {
        WP_CLI::line( "Called function 2 based on user selection!" );
    }

    public function wp2static_cli_options_list() {
        WP_CLI::line( PHP_EOL . "### Showing all options ###" . PHP_EOL );

        $this->options(['list'], []);

        $this->showWizardWaitForSelection(1);
    }


    public function showWizardWaitForSelection($level) {
        $this->showWizardMenu($level);

        $userval = trim( fgets( STDIN ) );

        $this->routeWizardSelection( $level, $userval );
    }

    public function wizard(
        array $args = [],
        array $assoc_args = []
    ) : void {

        WP_CLI::line( "Welcome to WP2Static! Use this interactive wizard or run commands directly, as per the docs: https://wp2static.com" );

        // check if plugin has been setup

        $level = 0;
        $this->showWizardWaitForSelection($level);
    }

    /**
     * Crawls site, creating or updating the static site
     *
     * ## OPTIONS
     *
     * [--show-progress]
     *
     * Show progress indicator while crawling
     *
     */
    public function crawl( array $args, array $assoc_args ) : void {
        $action = isset( $args[0] ) ? $args[0] : null;
        $option_name = isset( $args[1] ) ? $args[1] : null;
        $value = isset( $args[2] ) ? $args[2] : null;

        $progress = isset( $assoc_args['show-progress'] );

        $crawler = new Crawler();
        $crawler->crawlSite( StaticSite::getPath(), $progress );
    }

    /*
     * Detect WordPress URLs to crawl, based on saved options
     *
     */
    public function detect() : void {
        $detected_count = URLDetector::detectURLs();

        WP_CLI::log( "$detected_count URLs detected." );
    }

    /*
     * Makes a copy of crawled static site with processing applied
     *
     */
    public function post_process() : void {
        $post_processor = new PostProcessor();

        $processed_site_dir =
            SiteInfo::getPath( 'uploads') . 'wp2static-processed-site';
        $processed_site = new ProcessedSite( $processed_site_dir );

        $post_processor->processStaticSite( StaticSite::getPath(), $processed_site);
    }

    /*
     * Crawl Queue
     *
     * <list>
     *
     * List all URLs in the CrawlCache
     *
     * <count>
     *
     * Show total number of URLs in CrawlCache 
     *
     * <delete>
     *
     * Empty all URLs from CrawlCache 
     */
    public function crawl_cache( array $args, array $assoc_args ) {
        $action = isset( $args[0] ) ? $args[0] : null;
        $option_name = isset( $args[1] ) ? $args[1] : null;
        $value = isset( $args[2] ) ? $args[2] : null;

        if ( $action === 'list' ) {
            $urls = CrawlCache::getHashes();

            foreach( $urls as $url ) {
                WP_CLI::line( $url );
            }
        }

        if ( $action === 'count' ) {
            $urls = CrawlCache::getHashes();

            WP_CLI::line( count( $urls ) );
        }

        if ( $action === 'delete' ) {

            if ( ! isset( $assoc_args['force'] ) ) {
                WP_CLI::line( PHP_EOL . "no --force given. Please type 'yes' to confirm deletion of Crawl Cache" . PHP_EOL );
                
                $userval = trim( fgets( STDIN ) );

                if ( $userval !== 'yes' ) {
                    WP_CLI::error( 'Failed to delete Crawl Cache' ); 
                }
            }

            CrawlCache::truncate();

            WP_CLI::success( 'Deleted Crawl Cache' ); 
        }
    }

    /*
     * Crawl Queue
     *
     * <list>
     *
     * List all URLs in the CrawlQueue
     *
     * <count>
     *
     * Show total number of URLs in CrawlQueue 
     *
     * <delete>
     *
     * Empty all URLs from CrawlQueue 
     */
    public function crawl_queue( array $args, array $assoc_args ) {
        $action = isset( $args[0] ) ? $args[0] : null;
        $option_name = isset( $args[1] ) ? $args[1] : null;
        $value = isset( $args[2] ) ? $args[2] : null;

        if ( $action === 'list' ) {
            $urls = CrawlQueue::getCrawlableURLs();

            foreach( $urls as $url ) {
                WP_CLI::line( $url );
            }
        }

        if ( $action === 'count' ) {
            $urls = CrawlQueue::getCrawlableURLs();

            WP_CLI::line( count( $urls ) );
        }

        if ( $action === 'delete' ) {

            if ( ! isset( $assoc_args['force'] ) ) {
                WP_CLI::line( PHP_EOL . "no --force given. Please type 'yes' to confirm deletion of CrawlQueue" . PHP_EOL );
                
                $userval = trim( fgets( STDIN ) );

                if ( $userval !== 'yes' ) {
                    WP_CLI::error( 'Failed to delete Crawl Queue' ); 
                }
            }

            CrawlQueue::truncate();

            WP_CLI::success( 'Deleted Crawl Queue' ); 
        }
    }

    public function wp2static_cli_caches_list_detected_urls() {
        $this->crawl_queue(['list'], []);

        WP_CLI::line( PHP_EOL . "Run this command directly with:" . PHP_EOL );
        WP_CLI::line( PHP_EOL . "wp wp2static crawl_queue list" . PHP_EOL );

        $this->showWizardWaitForSelection(3);
    }

    /**
     * WordPress Site operations
     *
     * ## OPTIONS
     *
     *
     * <list_urls>
     *
     * List all URLs in the CrawlQueue
     *
     * <clear_detected_urls>
     *
     * Remove all URLs from the CrawlQueue
     *
     */
    public function wordpress_site(
        array $args,
        array $assoc_args
    ) : void {
        $action = isset( $args[0] ) ? $args[0] : null;
        $option_name = isset( $args[1] ) ? $args[1] : null;
        $value = isset( $args[2] ) ? $args[2] : null;

        WP_CLI::line( "action: $action" );

        // also validate expected $action vs any
        if ( empty( $action ) ) {
            WP_CLI::error(
                'Missing required argument: ' .
                '<list_urls');
        }

        if ( $action === 'list_urls' ) {
        }
    }

    /*
     * Processed Site
     *
     * <list>
     *
     * List all files in the Processed Site directory
     *
     * <count>
     *
     * Show total number of files in Processed Site directory 
     *
     * <delete>
     *
     * Delete all generated Processed Site files from server
     *
     */
    public function processed_site(
        array $args,
        array $assoc_args
    ) : void {
        $action = isset( $args[0] ) ? $args[0] : null;
        $option_name = isset( $args[1] ) ? $args[1] : null;
        $value = isset( $args[2] ) ? $args[2] : null;

        // also validate expected $action vs any
        if ( empty( $action ) ) {
            WP_CLI::error(
                'Missing required argument: ' .
                '<delete>');
        }

        if ( $action === 'delete' ) {
            if ( ! isset( $assoc_args['force'] ) ) {
                WP_CLI::line( PHP_EOL . "no --force given. Please type 'yes' to confirm deletion of ProcessedSite file cache" . PHP_EOL );
                
                $userval = trim( fgets( STDIN ) );

                if ( $userval !== 'yes' ) {
                    WP_CLI::error( 'Failed to delete Processed Static Site file cache' ); 
                }
            }

            ProcessedSite::delete();
        }
    }

    /*
     * Static Site
     *
     * <list>
     *
     * List all files in the Static Site directory
     *
     * <count>
     *
     * Show total number of files in Static Site directory 
     *
     * <delete>
     *
     * Delete all generated Static Site files from server
     *
     *   -- also deletes the CrawlCache
     *
     */
    public function static_site(
        array $args,
        array $assoc_args
    ) : void {
        $action = isset( $args[0] ) ? $args[0] : null;
        $option_name = isset( $args[1] ) ? $args[1] : null;
        $value = isset( $args[2] ) ? $args[2] : null;

        // also validate expected $action vs any
        if ( empty( $action ) ) {
            WP_CLI::error(
                'Missing required argument: ' .
                '<delete>');
        }

        if ( $action === 'delete' ) {
            if ( ! isset( $assoc_args['force'] ) ) {
                WP_CLI::line( PHP_EOL . "no --force given. Please type 'yes' to confirm deletion of StaticSite file cache" . PHP_EOL );
                
                $userval = trim( fgets( STDIN ) );

                if ( $userval !== 'yes' ) {
                    WP_CLI::error( 'Failed to delete Static Site file cache' ); 
                }
            }

            StaticSite::delete();
        }
    }
}

