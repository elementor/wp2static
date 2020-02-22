<?php

namespace WP2Static;

class ViewRenderer {

    public static function renderOptionsPage() : void {
        $view = [];
        $view['nonce_action'] = 'wp2static-ui-options';
        $view['options_templates'] = [
            __DIR__ . '/../views/core-detection-options.php',
            __DIR__ . '/../views/core-crawling-options.php',
            __DIR__ . '/../views/core-post-processing-options.php',
            __DIR__ . '/../views/core-deployment-options.php',
        ];

        $view['crawlingOptions'] = [
            'basicAuthUser' => CoreOptions::get( 'basicAuthUser' ),
            'basicAuthPassword' => CoreOptions::get( 'basicAuthPassword' ),
            'includeDiscoveredAssets' => CoreOptions::get( 'includeDiscoveredAssets' ),
        ];

        $view['detectionOptions'] = [
            'detectCustomPostTypes' => CoreOptions::get( 'detectCustomPostTypes' ),
            'detectPages' => CoreOptions::get( 'detectPages' ),
            'detectPosts' => CoreOptions::get( 'detectPosts' ),
            'detectUploads' => CoreOptions::get( 'detectUploads' ),
        ];

        $view['postProcessingOptions'] = [
            'deploymentURL' => CoreOptions::get( 'deploymentURL' ),
        ];

        $view['deploymentOptions'] = [
            'completionEmail' => CoreOptions::get( 'completionEmail' ),
            'completionWebhook' => CoreOptions::get( 'completionWebhook' ),
            'completionWebhookMethod' => CoreOptions::get( 'completionWebhookMethod' ),
        ];

        $view = apply_filters( 'wp2static_render_options_page_vars', $view );

        require_once WP2STATIC_PATH . 'views/options-page.php';
    }

    public static function renderDiagnosticsPage() : void {
        $view = [];
        $view['localDNSResolution'] = Diagnostics::check_local_dns_resolution();
        $view['memoryLimit'] = ini_get( 'memory_limit' );
        $view['coreOptions'] = CoreOptions::getAll();
        $view['site_info'] = SiteInfo::getAllInfo();
        $view['phpOutOfDate'] = PHP_VERSION < 7.3;
        $view['uploadsWritable'] = SiteInfo::isUploadsWritable();
        $view['maxExecutionTime'] = ini_get( 'max_execution_time' );
        $view['curlSupported'] = SiteInfo::hasCURLSupport();
        $view['permalinksDefined'] = SiteInfo::permalinksAreDefined();
        $view['domDocumentAvailable'] = class_exists( 'DOMDocument' );
        $view['extensions'] = get_loaded_extensions();

        require_once WP2STATIC_PATH . 'views/diagnostics-page.php';
    }

    public static function renderLogsPage() : void {
        $view = [];
        $view['logs'] = WsLog::getAll();

        require_once WP2STATIC_PATH . 'views/logs-page.php';
    }

    public static function renderCrawlQueue() : void {
        if ( ! is_admin() ) {
            http_response_code( 403 );
            die( 'Forbidden' );
        }

        $view = [];
        $view['urls'] = CrawlQueue::getCrawlableURLs();

        require_once WP2STATIC_PATH . 'views/crawl-queue-page.php';
    }

    public static function renderCrawlCache() : void {
        if ( ! is_admin() ) {
            http_response_code( 403 );
            die( 'Forbidden' );
        }

        $view = [];
        $view['urls'] = CrawlCache::getURLs();

        require_once WP2STATIC_PATH . 'views/crawl-cache-page.php';
    }

    public static function renderPostProcessedSitePaths() : void {
        if ( ! is_admin() ) {
            http_response_code( 403 );
            die( 'Forbidden' );
        }

        $view = [];
        $view['paths'] = ProcessedSite::getPaths();

        require_once WP2STATIC_PATH . 'views/post-processed-site-paths-page.php';
    }

    public static function renderStaticSitePaths() : void {
        if ( ! is_admin() ) {
            http_response_code( 403 );
            die( 'Forbidden' );
        }

        $view = [];
        $view['paths'] = StaticSite::getPaths();

        require_once WP2STATIC_PATH . 'views/static-site-paths-page.php';
    }

    public static function renderDeployCache() : void {
        if ( ! is_admin() ) {
            http_response_code( 403 );
            die( 'Forbidden' );
        }

        $view = [];
        $view['paths'] = DeployCache::getPaths();

        require_once WP2STATIC_PATH . 'views/deploy-cache-page.php';
    }

    public static function renderDeployQueue() : void {
        if ( ! is_admin() ) {
            http_response_code( 403 );
            die( 'Forbidden' );
        }

        $view = [];
        $view['paths'] = DeployQueue::getPaths();

        require_once WP2STATIC_PATH . 'views/deploy-queue-page.php';
    }

    public static function renderJobsPage() : void {
        $view = [];
        $view['nonce_action'] = 'wp2static-ui-job-options';
        $view['jobs'] = JobQueue::getJobs();

        $view['jobOptions'] = [
            'queueJobOnPostSave' => CoreOptions::get( 'queueJobOnPostSave' ),
            'queueJobOnPostDelete' => CoreOptions::get( 'queueJobOnPostDelete' ),
            'processQueueInterval' => CoreOptions::get( 'processQueueInterval' ),
            'autoJobQueueDetection' => CoreOptions::get( 'autoJobQueueDetection' ),
            'autoJobQueueCrawling' => CoreOptions::get( 'autoJobQueueCrawling' ),
            'autoJobQueuePostProcessing' => CoreOptions::get( 'autoJobQueuePostProcessing' ),
            'autoJobQueueDeployment' => CoreOptions::get( 'autoJobQueueDeployment' ),
        ];

        $view = apply_filters( 'wp2static_render_jobs_page_vars', $view );

        require_once WP2STATIC_PATH . 'views/jobs-page.php';
    }


    public static function renderCachesPage() : void {
        $view = [];

        // performance check vs map
        $disk_space = 0;

        $exported_site_dir = SiteInfo::getPath( 'uploads' ) . 'wp2static-crawled-site/';
        if ( is_dir( $exported_site_dir ) ) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $exported_site_dir
                )
            );

            foreach ( $files as $file ) {
                $disk_space += $file->getSize();
            }
        }

        $view['exportedSiteDiskSpace'] = sprintf( '%4.2f MB', $disk_space / 1048576 );
        // end check

        if ( is_dir( $exported_site_dir ) ) {
            $view['exportedSiteFileCount'] = iterator_count(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator(
                        $exported_site_dir,
                        \FilesystemIterator::SKIP_DOTS
                    )
                )
            );
        } else {
            $view['exportedSiteFileCount'] = 0;
        }

        // performance check vs map
        $disk_space = 0;
        $processed_site_dir = SiteInfo::getPath( 'uploads' ) . 'wp2static-processed-site/';

        if ( is_dir( $processed_site_dir ) ) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $processed_site_dir
                )
            );

            foreach ( $files as $file ) {
                $disk_space += $file->getSize();
            }
        }

        $view['processedSiteDiskSpace'] = sprintf( '%4.2f MB', $disk_space / 1048576 );
        // end check

        if ( is_dir( $processed_site_dir ) ) {
            $view['processedSiteFileCount'] = iterator_count(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator(
                        $processed_site_dir,
                        \FilesystemIterator::SKIP_DOTS
                    )
                )
            );
        } else {
            $view['processedSiteFileCount'] = 0;
        }

        $view['crawlQueueTotalURLs'] = CrawlQueue::getTotal();
        $view['crawlCacheTotalURLs'] = CrawlCache::getTotal();
        $view['deployCacheTotalPaths'] = DeployCache::getTotal();
        $view['deployQueueTotalPaths'] = DeployQueue::getTotal();
        $view['uploads_path'] = SiteInfo::getPath( 'uploads' );
        $view['nonce_action'] = 'wp2static-caches-page';

        require_once WP2STATIC_PATH . 'views/caches-page.php';
    }


}
