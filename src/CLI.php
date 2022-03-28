<?php

namespace WP2Static;

use WP_CLI;

/**
 * Generate a static copy of your website & publish remotely
 */
class CLI {
    /**
     * @var array<string>
     */
    private $assoc_args = null;

    /**
     * Display system information and health check
     */
    public function diagnostics() : void {
        WP_CLI::line(
            PHP_EOL . 'WP2Static' . PHP_EOL
        );

        $environmental_info = [
            [
                'key' => 'PLUGIN VERSION',
                'value' => WP2STATIC_VERSION,
            ],
            [
                'key' => 'PHP_VERSION',
                'value' => phpversion(),
            ],
            [
                'key' => 'PHP MAX EXECUTION TIME',
                'value' => ini_get( 'max_execution_time' ),
            ],
            [
                'key' => 'OS VERSION',
                'value' => php_uname(),
            ],
            [
                'key' => 'WP VERSION',
                'value' => get_bloginfo( 'version' ),
            ],
            [
                'key' => 'WP URL',
                'value' => get_bloginfo( 'url' ),
            ],
            [
                'key' => 'WP SITEURL',
                'value' => get_option( 'siteurl' ),
            ],
            [
                'key' => 'WP HOME',
                'value' => get_option( 'home' ),
            ],
            [
                'key' => 'WP ADDRESS',
                'value' => get_bloginfo( 'wpurl' ),
            ],
        ];

        WP_CLI\Utils\format_items(
            'table',
            $environmental_info,
            [ 'key', 'value' ]
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
     * Get info on plugin status.
     *
     * ## OPTIONS
     *
     * [--no-next]
     *
     * <jobs>
     *
     * Get status on jobs
     *
     * <crawled-site>
     *
     * Get status on the crawled site
     *
     * <processed-site>
     *
     * Get status on the processed site
     *
     * <deployers>
     *
     * Get info on deployers
     *
     * ## EXAMPLES
     *
     * List current plugin status and show next step
     *
     *     wp wp2static status
     *
     * List current plugin status but don't show next step
     *
     *     wp wp2static status --no-next
     *
     * @param string[] $args  Arguments after command
     * @param string[] $assoc_args  Parameters after command
     */
    public function status(
      array $args,
      array $assoc_args
    ) : void {
        list($action) = $this->parseArgs( $args );
        $this->assoc_args = $assoc_args;

        if ( $action === 'jobs' ) {
            $this->jobStatus();
        } elseif ( $action === 'crawled-site' ) {
            $this->crawledSiteStatus();
        } elseif ( $action === 'processed-site' ) {
            $this->processedSiteStatus();
        } elseif ( $action === 'deployers' ) {
            $this->deployersStatus();
        } else {
            $this->defaultStatus();
        }

        // separate output from next command
        WP_CLI::line();
    }

    /**
     * Output default status
     */
    protected function defaultStatus() : void {
        // TODO handle when count doesn't change but different URLs are present
        // - maybe that's an edge case that it's not worth caring about...
        $detected_url_count = URLDetector::countURLs();
        // WP_CLI::line(sprintf('%d URLs detected', $detected_url_count));

        $crawlable_urls = CrawlQueue::getCrawlablePaths();
        if ( count( $crawlable_urls ) === 0 ) {
            WP_CLI::line( 'No URLs are queued for crawling.' );
            $this->hintDetectNext();
            return;
        } elseif ( $detected_url_count > count( $crawlable_urls ) ) {
            WP_CLI::warning(
                sprintf(
                    'There are more URLs on the site (%d) than queued for crawling (%d).',
                    $detected_url_count,
                    count( $crawlable_urls )
                )
            );
            $this->hintDetectNext();
            return;
        } else {
            WP_CLI::line(
                WP_CLI::colorize(
                    sprintf( '%%c%d URLs%%n %%mqueued for crawling%%n', count( $crawlable_urls ) )
                )
            );
        }

        $crawled_urls = StaticSite::getPaths();
        if ( $crawled_urls ) {
            WP_CLI::line(
                WP_CLI::colorize(
                    sprintf( '%%c%d URLs%%n %%mcrawled%%n', count( $crawled_urls ) )
                )
            );
            if ( count( $crawled_urls ) < count( $crawlable_urls ) ) {
                WP_CLI::line(
                    WP_CLI::colorize(
                        sprintf(
                            '%%MThere are more URLs queued for crawling (%d)'
                            . ' than there are urls that have been crawled (%d)%%n',
                            count( $crawlable_urls ),
                            count( $crawled_urls )
                        )
                    )
                );

                if ( $this->should_show_next() ) {
                    WP_CLI::line(
                        WP_CLI::colorize(
                            "\n\tYou can run `%gwp wp2static status crawled-site%n`"
                            . " to see the list of path differences\n"
                        )
                    );
                }
                  $this->hintCrawlNext();
            }
        } else {
            WP_CLI::line( 'No URLs crawled.' );
            $this->hintCrawlNext();
        }

        $processed_site_path = ProcessedSite::getPath();
        $processed_site_urls = [];
        if ( ! is_dir( $processed_site_path ) ) {
            WP_CLI::line(
                WP_CLI::colorize( '%rProcessed site does not exist%n' )
            );
            $this->hintProcessNext();
        } else {
            $processed_site_urls = ProcessedSite::getPaths();
            WP_CLI::line(
                WP_CLI::colorize(
                    sprintf(
                        '%%c%d URLs%%n in the %%mprocessed site%%n',
                        count( $processed_site_urls )
                    )
                )
            );
            if ( count( $processed_site_urls ) < count( $crawled_urls ) ) {
                WP_CLI::line(
                    WP_CLI::colorize(
                        sprintf(
                            '%%MThere are more URLs crawled (%d)'
                            . ' than there are urls that have been processed (%d)%%n',
                            count( $crawled_urls ),
                            count( $processed_site_urls )
                        )
                    )
                );

                if ( $this->should_show_next() ) {
                    WP_CLI::line(
                        WP_CLI::colorize(
                            "\n\tYou can run `%gwp wp2static status processed-site%n`"
                            . " to see the list of path differences\n"
                        )
                    );
                }
                $this->hintProcessNext();
            }
        }

        if ( count( $processed_site_urls ) > 0 ) {
            $deployer_enabled = Addons::getDeployer();
            if ( $deployer_enabled ) {
                $this->hintDeployNext();
            } else {
                $this->hintEnableDeployer();
            }
        }
    }

    private function deployersStatus() : void {
        $deployers = Addons::getAll( 'deploy' );

        $data = array_map(
            function( $_ ) {
                return [
                    'name'        => $_->name,
                    'slug'        => $_->slug,
                    'description' => $_->description,
                    'enabled'     => $_->enabled ? 'Yes' : 'No',
                ];
            },
            $deployers
        );

        WP_CLI\Utils\format_items(
            'table',
            $data,
            [ 'name', 'description', 'slug', 'enabled' ]
        );

        if ( Addons::getDeployer() ) {
            $this->hintDeployNext();
        } else {
            $this->hintEnableDeployer();
        }
    }

    private function hintEnableDeployer() : void {
        if ( $this->should_show_next() ) {
            WP_CLI::line(
                WP_CLI::colorize(
                    "\n\tYou can run `%gwp wp2static addons toggle <slug>%n`"
                    . ' to enable a deployer'
                )
            );
        }
    }

    private function hintDeployNext() : void {
        if ( $this->should_show_next() ) {
            WP_CLI::line(
                WP_CLI::colorize(
                    "\n\tYou can run `%gwp wp2static deploy%n`"
                    . ' to deploy your site'
                )
            );
        }
    }

    private function crawledSiteStatus() : void {
        $crawlable_urls = array_map(
            '\WP2Static\Crawler::transformPath',
            CrawlQueue::getCrawlablePaths(),
        );
        $crawled_urls = StaticSite::getPaths();

        $queued_but_not_crawled_urls = array_diff( $crawlable_urls, $crawled_urls );
        $crawled_but_not_queued_urls = array_diff( $crawled_urls, $crawlable_urls );

        if ( count( $queued_but_not_crawled_urls ) > 0 ) {
            WP_CLI::line( 'You have URLs that are queued but not crawled:' );
            WP_CLI\Utils\format_items(
                'table',
                $this->urlsToTableData( $queued_but_not_crawled_urls ),
                [ 'url' ]
            );
        }

        if ( count( $crawled_but_not_queued_urls ) > 0 ) {
            WP_CLI::line( 'You have URLs that are crawled but not queued:' );
            WP_CLI\Utils\format_items(
                'table',
                $this->urlsToTableData( $crawled_but_not_queued_urls ),
                [ 'url' ]
            );
        }
    }

    private function processedSiteStatus() : void {
        $crawled_urls = StaticSite::getPaths();
        $processed_site_urls = ProcessedSite::getPaths();

        $crawled_but_not_processed_urls = array_diff( $crawled_urls, $processed_site_urls );
        $processed_but_not_crawled_urls = array_diff( $processed_site_urls, $crawled_urls );

        if ( count( $crawled_but_not_processed_urls ) > 0 ) {
            WP_CLI::line( 'You have URLs that are crawled but not processed:' );
            WP_CLI\Utils\format_items(
                'table',
                $this->urlsToTableData( $crawled_but_not_processed_urls ),
                [ 'url' ]
            );
        }

        if ( count( $processed_but_not_crawled_urls ) > 0 ) {
            if ( count( $crawled_but_not_processed_urls ) > 0 ) {
                WP_CLI::line();
            }

            WP_CLI::line( 'You have URLs that are processed but not crawled:' );
            WP_CLI\Utils\format_items(
                'table',
                $this->urlsToTableData( $processed_but_not_crawled_urls ),
                [ 'url' ]
            );
        }
    }

    /**
     * Convert a list of URLs to a table for CLI output
     *
     * @param array<string> $urls List of URLs
     * @return array<array<string>>
     */
    private function urlsToTableData( array $urls ) : array {
        return array_map(
            function( $url ) {
                return [ 'url' => $url ]; },
            $urls
        );
    }

    private function hintProcessNext() : void {
        if ( $this->should_show_next() ) {
            WP_CLI::line(
                WP_CLI::colorize( "\n\tYou should run `%gwp wp2static post_process%n`" )
            );
        }
    }

    /**
     * Show next action hint for detect unless suppressed.
     *
     * @uses should_show_next() to know if should show next or not
     */
    private function hintDetectNext() : void {
        if ( $this->should_show_next() ) {
            WP_CLI::line(
                WP_CLI::colorize(
                    "\n\tYou should run `%gwp wp2static detect%n`"
                    . " to add URLs to the crawl queue\n"
                )
            );
        }
    }

    /**
     * Show next action hint for crawl unless suppressed.
     *
     * @uses should_show_next() to know if should show next or not
     */
    private function hintCrawlNext() : void {
        if ( $this->should_show_next() ) {
            WP_CLI::line(
                WP_CLI::colorize( "\n\tYou should run `%gwp wp2static crawl%n`\n" )
            );
        }
    }

    /**
     * Check to see if display of next is enabled
     */
    private function should_show_next() : bool {
        if ( ! isset( $this->assoc_args['next'] ) ) {
            return true;
        }

        return ! ! $this->assoc_args['next'];
    }

    /**
     * Parse CLI args
     *
     * @param string[] $args CLI args
     *
     * @return mixed[] string or null: `[ $action, $option_name, $value ]`
     */
    private function parseArgs( $args ) {
        $action      = isset( $args[0] ) ? $args[0] : null;
        $option_name = isset( $args[1] ) ? $args[1] : null;
        $value       = isset( $args[2] ) ? $args[2] : null;

        return [ $action, $option_name, $value ];
    }

    /**
     * Output job status details.
     */
    protected function jobStatus() : void {
        $job_count = JobQueue::getWaitingJobsCount();
        WP_CLI::line( sprintf( 'Waiting job count: %d', $job_count ) );
        WP_CLI::line(
            sprintf(
                'Total job count: %d',
                JobQueue::getTotalJobs()
            )
        );

        $jobs_by_type = JobQueue::getJobCountByType();
        $output_data = [];
        $output_headers = [ 'type', 'count' ];
        foreach ( $jobs_by_type as $type => $count ) {
            $output_data[] = [
                'type' => $type,
                'count' => $count,
            ];
        }
        WP_CLI\Utils\format_items( 'table', $output_data, $output_headers );
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
        CoreOptions::init();
        $deployer = Addons::getDeployer();

        if ( ! $deployer ) {
            WP_CLI::line( 'No deployment add-ons are enabled, skipping deployment.' );
        } else {
            WsLog::l( 'Starting deployment' );
            do_action( 'wp2static_deploy', ProcessedSite::getPath(), $deployer );
        }
        WsLog::l( 'Starting post-deployment actions' );
        do_action( 'wp2static_post_deploy_trigger', $deployer );
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

        CoreOptions::init();

        $plugin = Controller::getInstance();

        if ( $action === 'get' ) {
            if ( empty( $option_name ) ) {
                WP_CLI::error( 'Missing required argument: <option-name>' );
                return;
            }

            // decrypt basicAuthPassword
            if ( $option_name === 'basicAuthPassword' ) {
                $option_value = CoreOptions::encrypt_decrypt(
                    'decrypt',
                    CoreOptions::getValue( $option_name )
                );
            } else {
                $option_value = CoreOptions::getValue( $option_name );
            }

            WP_CLI::line( $option_value );
        }

        if ( $action === 'set' ) {
            if ( empty( $option_name ) ) {
                WP_CLI::error( 'Missing required argument: <option-name>' );
                return;
            }

            // encrypt basic auth pwd
            if ( ! empty( $value ) && $option_name === 'basicAuthPassword' ) {
                $value = CoreOptions::encrypt_decrypt(
                    'encrypt',
                    $value
                );
            }

            // TODO: assert expected result
            CoreOptions::save( $option_name, $value );

        }

        if ( $action === 'list' ) {
            $options = CoreOptions::getAll();

            WP_CLI\Utils\format_items(
                'table',
                $options,
                [ 'name', 'value' ]
            );
        }
    }

    public function wp2static_cli_options_set_detect_common() : void {
        WP_CLI::line( PHP_EOL . '### Setting Common URL detection  ###' . PHP_EOL );

        $plugin = Controller::getInstance();

        $detections = [
            'detectArchives',
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

        foreach ( $detections as $detection ) {
            CoreOptions::save( $detection, 1 );
        }

        WP_CLI::line( PHP_EOL . 'Common URL detection set!' . PHP_EOL );
    }

    public function wp2static_cli_options_set_detect_homepage_only() : void {
        WP_CLI::line( PHP_EOL . '### Setting Homepage only URL detection  ###' . PHP_EOL );

        $plugin = Controller::getInstance();

        $detections = [
            'detectArchives',
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

        foreach ( $detections as $detection ) {
            CoreOptions::save( $detection, 0 );
        }

        // TODO: use filter, rm homepage option?
        CoreOptions::save( $detection, 1 );

        WP_CLI::line( PHP_EOL . 'Homepage only URL detection set!' . PHP_EOL );
    }

    public function wp2static_cli_options_set_detect_maximum() : void {
        WP_CLI::line( PHP_EOL . '### Setting maximum URL detection  ###' . PHP_EOL );

        $plugin = Controller::getInstance();

        $detections = [
            'detectArchives',
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

        foreach ( $detections as $detection ) {
            CoreOptions::save( $detection, 1 );
        }

        WP_CLI::line( PHP_EOL . 'Maximum URL detection set!' . PHP_EOL );
    }

    /**
     * Print multilines of input text via WP-CLI
     */
    public function multilinePrint( string $string ) : void {
        $msg = trim( str_replace( [ "\r", "\n" ], '', $string ) );

        $msg = preg_replace( '!\s+!', ' ', $msg );

        WP_CLI::line( PHP_EOL . $msg . PHP_EOL );
    }

    /**
     * Crawls site, creating or updating the static site
     *
     * @param string[] $args Arguments after command
     * @param string[] $assoc_args Parameters after command
     */
    public function crawl( array $args, array $assoc_args ) : void {
        CoreOptions::init();
        Controller::wp2staticCrawl();
    }

    /**
     * Detect WordPress URLs to crawl, based on saved options
     */
    public function detect() : void {
        CoreOptions::init();
        $detected_count = URLDetector::enqueueURLs();
    }

    /**
     * Makes a copy of crawled static site with processing applied
     */
    public function post_process() : void {
        CoreOptions::init();
        $post_processor = new PostProcessor();
        $post_processor->processStaticSite( StaticSite::getPath() );
    }

    /**
     * Process any jobs in the queue.
     */
    public function process_queue() : void {
        $job_count = JobQueue::getWaitingJobsCount();

        if ( $job_count === 0 ) {
            WP_CLI::success( 'No jobs in queue' );
        } else {
            WP_CLI::line( ' Processing ' . $job_count . ' job' . ( $job_count > 1 ? 's' : '' ) );

            Controller::wp2staticProcessQueue();

            WP_CLI::success( 'Done processing queue' );
        }
    }

    /**
     * Crawl Cache
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
     *
     * @param string[] $args Arguments after command
     * @param string[] $assoc_args Parameters after command
     */
    public function crawl_cache( array $args, array $assoc_args ) : void {
        $action = isset( $args[0] ) ? $args[0] : null;

        if ( $action === 'list' ) {
            $urls = CrawlCache::getHashes();

            foreach ( $urls as $url ) {
                WP_CLI::line( $url );
            }
        }

        if ( $action === 'count' ) {
            $urls = CrawlCache::getHashes();

            WP_CLI::line( (string) count( $urls ) );
        }

        if ( $action === 'delete' ) {

            if ( ! isset( $assoc_args['force'] ) ) {
                $this->multilinePrint(
                    "no --force given. Please type 'yes' to confirm
                    deletion of Crawl Cache"
                );

                $userval = trim( (string) fgets( STDIN ) );

                if ( $userval !== 'yes' ) {
                    WP_CLI::error( 'Failed to delete Crawl Cache' );
                }
            }

            CrawlCache::truncate();

            WP_CLI::success( 'Deleted Crawl Cache' );
        }
    }

    /**
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
     *
     * @param string[] $args Arguments after command
     * @param string[] $assoc_args Parameters after command
     */
    public function crawl_queue( array $args, array $assoc_args ) : void {
        $action = isset( $args[0] ) ? $args[0] : null;

        if ( $action === 'list' ) {
            $urls = CrawlQueue::getCrawlablePaths();

            foreach ( $urls as $url ) {
                WP_CLI::line( $url );
            }
        }

        if ( $action === 'count' ) {
            $urls = CrawlQueue::getCrawlablePaths();

            WP_CLI::line( (string) count( $urls ) );
        }

        if ( $action === 'delete' ) {

            if ( ! isset( $assoc_args['force'] ) ) {
                $this->multilinePrint(
                    "no --force given. Please type 'yes' to confirm
                    deletion of CrawlQueue"
                );

                $userval = trim( (string) fgets( STDIN ) );

                if ( $userval !== 'yes' ) {
                    WP_CLI::error( 'Failed to delete Crawl Queue' );
                }
            }

            CrawlQueue::truncate();

            WP_CLI::success( 'Deleted Crawl Queue' );
        }
    }

    /**
     * Processed Site
     *
     * <delete>
     *
     * Delete all generated Processed Site files from server
     *
     * @param string[] $args Arguments after command
     * @param string[] $assoc_args Parameters after command
     */
    public function processed_site( array $args, array $assoc_args ) : void {
        $action = isset( $args[0] ) ? $args[0] : null;

        // also validate expected $action vs any
        if ( empty( $action ) ) {
            WP_CLI::error(
                'Missing required argument: ' .
                '<delete>'
            );
        }

        if ( $action === 'delete' ) {
            if ( ! isset( $assoc_args['force'] ) ) {
                $this->multilinePrint(
                    "no --force given. Please type 'yes' to confirm deletion
                     of ProcessedSite file cache"
                );

                $userval = trim( (string) fgets( STDIN ) );

                if ( $userval !== 'yes' ) {
                    WP_CLI::error( 'Failed to delete Processed Static Site file cache' );
                }
            }

            ProcessedSite::delete();
        }
    }

    /**
     * Static Site
     *
     * <delete>
     *
     * Delete all generated Static Site files from server
     *
     *   -- also deletes the CrawlCache
     *
     * @param string[] $args Arguments after command
     * @param string[] $assoc_args Parameters after command
     */
    public function static_site( array $args, array $assoc_args ) : void {
        $action = isset( $args[0] ) ? $args[0] : null;

        // also validate expected $action vs any
        if ( empty( $action ) ) {
            WP_CLI::error(
                'Missing required argument: ' .
                '<delete>'
            );
        }

        if ( $action === 'delete' ) {
            if ( ! isset( $assoc_args['force'] ) ) {
                $this->multilinePrint(
                    "no --force given. Please type 'yes' to confirm deletion
                     of StaticSite file cache"
                );

                $userval = trim( (string) fgets( STDIN ) );

                if ( $userval !== 'yes' ) {
                    WP_CLI::error( 'Failed to delete Static Site file cache' );
                }
            }

            StaticSite::delete();
        }
    }

    /**
     * Deploy Cache
     *
     * <list>
     *
     * List all URLs in the DeployCache
     *
     * <count>
     *
     * Show total number of URLs in DeployCache
     *
     * <delete>
     *
     * Empty all URLs from DeployCache
     *
     * @param string[] $args Arguments after command
     * @param string[] $assoc_args Parameters after command
     */
    public function deploy_cache( array $args, array $assoc_args ) : void {
        $action = isset( $args[0] ) ? $args[0] : null;

        if ( $action === 'list' ) {
            $paths = DeployCache::getPaths();

            foreach ( $paths as $url ) {
                WP_CLI::line( $url );
            }
        }

        if ( $action === 'count' ) {
            WP_CLI::line( (string) count( DeployCache::getTotal() ) );
        }

        if ( $action === 'delete' ) {

            if ( ! isset( $assoc_args['force'] ) ) {
                $this->multilinePrint(
                    "no --force given. Please type 'yes' to confirm
                    deletion of Deploy Cache"
                );

                $userval = trim( (string) fgets( STDIN ) );

                if ( $userval !== 'yes' ) {
                    WP_CLI::error( 'Failed to delete Deploy Cache' );
                }
            }

            DeployCache::truncate();

            WP_CLI::success( 'Deleted Deploy Cache' );
        }
    }

    /**
     * Full Workflow
     *
     * Executes all core workflows: detect, crawl, post_process & deploy
     *
     * @param string[] $args Arguments after command
     * @param string[] $assoc_args Parameters after command
     */
    public function full_workflow( array $args, array $assoc_args ) : void {
        $this->detect();
        $this->crawl( [], [] );
        $this->post_process();
        $this->deploy( [], [] );
    }

    /**
     * delete_all_cache
     *
     * Deletes all caches
     *
     * @param string[] $args Arguments after command
     * @param string[] $assoc_args Parameters after command
     */
    public function delete_all_cache( array $args, array $assoc_args ) : void {
        if ( ! isset( $assoc_args['force'] ) ) {
            $this->multilinePrint(
                "no --force given. Please type 'yes' to confirm
                deletion of Crawl Cache"
            );

            $userval = trim( (string) fgets( STDIN ) );

            if ( $userval !== 'yes' ) {
                WP_CLI::error( 'Failed to delete Crawl Cache' );
            }
        }

        Controller::deleteAllCaches();
    }

    /**
     * Addons
     *
     * <list>
     *
     * List all registered Add-ons
     *
     * @param string[] $args Arguments after command
     * @param string[] $assoc_args Parameters after command
     * @throws WP2StaticException
     */
    public function addons( array $args, array $assoc_args ) : void {
        $action = isset( $args[0] ) ? $args[0] : null;

        if ( $action === 'list' ) {
            $addons = Addons::getAll();

            $pretty_addons = [];

            foreach ( $addons as $addon ) {
                $pretty_addons[] = [
                    'Enabled' => $addon->enabled,
                    'Slug' => $addon->slug,
                    'Name' => $addon->name,
                    'Description' => $addon->description,
                    'Docs' => $addon->docs_url,
                ];
            }

            WP_CLI\Utils\format_items(
                'table',
                $pretty_addons,
                [ 'Enabled', 'Slug', 'Name', 'Description', 'Docs' ]
            );
        }

        if ( $action === 'toggle' ) {
            $addon_slug = isset( $args[1] ) ? $args[1] : null;

            if ( ! $addon_slug ) {
                throw new WP2StaticException(
                    'No addon slug given for CLI toggling'
                );

            }

            // TODO Output details on if addon was enabled or disabled
            Controller::wp2staticToggleAddon( $addon_slug );
        }
    }
}

