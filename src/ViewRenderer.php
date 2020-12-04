<?php

namespace WP2Static;

class ViewRenderer {

    public static function renderOptionsPage() : void {
        CoreOptions::init();

        $view = [];
        $view['nonce_action'] = 'wp2static-ui-options';

        $view['coreOptions'] = [
            'basicAuthPassword' => CoreOptions::get( 'basicAuthPassword' ),
            'basicAuthUser' => CoreOptions::get( 'basicAuthUser' ),
            'completionEmail' => CoreOptions::get( 'completionEmail' ),
            'completionWebhook' => CoreOptions::get( 'completionWebhook' ),
            'completionWebhookMethod' => CoreOptions::get( 'completionWebhookMethod' ),
            'detectCustomPostTypes' => CoreOptions::get( 'detectCustomPostTypes' ),
            'detectPages' => CoreOptions::get( 'detectPages' ),
            'detectPosts' => CoreOptions::get( 'detectPosts' ),
            'detectUploads' => CoreOptions::get( 'detectUploads' ),
            'deploymentURL' => CoreOptions::get( 'deploymentURL' ),
            'useCrawlCaching' => CoreOptions::get( 'useCrawlCaching' ),
        ];

        require_once WP2STATIC_PATH . 'views/options-page.php';
    }

    public static function renderDiagnosticsPage() : void {
        $view = [];
        $view['memoryLimit'] = ini_get( 'memory_limit' );
        $view['coreOptions'] = CoreOptions::getAll();
        $view['site_info'] = SiteInfo::getAllInfo();
        $view['phpOutOfDate'] = version_compare( PHP_VERSION, '7.3', '<' );
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
        $view['nonce_action'] = 'wp2static-log-page';
        $view['logs'] = WsLog::getAll();

        require_once WP2STATIC_PATH . 'views/logs-page.php';
    }

    public static function renderAddonsPage() : void {
        $view = [];
        $view['nonce_action'] = 'wp2static-addons-page';
        $view['addons'] = Addons::getAll();

        require_once WP2STATIC_PATH . 'views/addons-page.php';
    }

    public static function renderCrawlQueue() : void {
        if ( ! is_admin() ) {
            http_response_code( 403 );
            die( 'Forbidden' );
        }

        if ( ! empty( $_GET['action'] ) && ! empty( $_GET['id'] ) && is_array( $_GET['id'] ) ) {
            switch ( $_GET['action'] ) {
                case 'remove':
                    CrawlQueue::rmUrlsById( $_GET['id'] );
                    break;
            }
        }

        $urls = CrawlQueue::getCrawlablePaths();
        // Apply search
        if ( ! empty( $_GET['s'] ) ) {
            $s = $_GET['s'];
            $urls = array_filter(
                $urls,
                function ( $url ) use ( $s ) {
                    return stripos( $url, $s ) !== false;
                }
            );
        }

        $page_size = 200;
        $page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $view = [
            'paginator' => new Paginator( $urls, $page_size, $page ),
        ];

        require_once WP2STATIC_PATH . 'views/crawl-queue-page.php';
    }

    public static function renderCrawlCache() : void {
        if ( ! is_admin() ) {
            http_response_code( 403 );
            die( 'Forbidden' );
        }

        if ( ! empty( $_GET['action'] ) && ! empty( $_GET['id'] ) && is_array( $_GET['id'] ) ) {
            switch ( $_GET['action'] ) {
                case 'remove':
                    CrawlCache::rmUrlsById( $_GET['id'] );
                    break;
            }
        }

        $urls = CrawlCache::getURLs();

        // Apply search
        if ( ! empty( $_GET['s'] ) ) {
            $s = $_GET['s'];
            $urls = array_filter(
                $urls,
                function ( $url ) use ( $s ) {
                    return stripos( isset( $url->url ) ? $url->url : '', $s ) !== false;
                }
            );
        }

        $page_size = 200;
        $page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $view = [
            'paginator' => new Paginator( $urls, $page_size, $page ),
        ];

        require_once WP2STATIC_PATH . 'views/crawl-cache-page.php';
    }

    public static function renderPostProcessedSitePaths() : void {
        if ( ! is_admin() ) {
            http_response_code( 403 );
            die( 'Forbidden' );
        }

        $paths = ProcessedSite::getPaths();

        // Apply search
        if ( ! empty( $_GET['s'] ) ) {
            $s = $_GET['s'];
            $paths = array_filter(
                $paths,
                function ( $path ) use ( $s ) {
                    return stripos( $path, $s ) !== false;
                }
            );
        }

        $page_size = 200;
        $page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $view = [
            'paginator' => new Paginator( $paths, $page_size, $page ),
        ];

        require_once WP2STATIC_PATH . 'views/post-processed-site-paths-page.php';
    }

    public static function renderStaticSitePaths() : void {
        if ( ! is_admin() ) {
            http_response_code( 403 );
            die( 'Forbidden' );
        }

        $paths = StaticSite::getPaths();

        // Apply search
        if ( ! empty( $_GET['s'] ) ) {
            $s = $_GET['s'];
            $paths = array_filter(
                $paths,
                function ( $path ) use ( $s ) {
                    return stripos( $path, $s ) !== false;
                }
            );
        }

        $page_size = 200;
        $page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $view = [
            'paginator' => new Paginator( $paths, $page_size, $page ),
        ];

        require_once WP2STATIC_PATH . 'views/static-site-paths-page.php';
    }

    public static function renderDeployCache() : void {
        if ( ! is_admin() ) {
            http_response_code( 403 );
            die( 'Forbidden' );
        }

        $paths = isset( $_GET['deploy_namespace'] )
            ? DeployCache::getPaths( $_GET['deploy_namespace'] )
            : DeployCache::getPaths();

        // Apply search
        if ( ! empty( $_GET['s'] ) ) {
            $s = $_GET['s'];
            $paths = array_filter(
                $paths,
                function ( $path ) use ( $s ) {
                    return stripos( $path, $s ) !== false;
                }
            );
        }

        $page_size = 200;
        $page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $view = [
            'paginator' => new Paginator( $paths, $page_size, $page ),
        ];

        require_once WP2STATIC_PATH . 'views/deploy-cache-page.php';
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

    public static function renderRunPage() : void {
        $view = [];

        require_once WP2STATIC_PATH . 'views/run-page.php';
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

        if ( apply_filters( 'wp2static_deploy_cache_totals_by_namespace', false ) ) {
            $view['deployCacheTotalPathsByNamespace']
                = DeployCache::getTotalsByNamespace();
        }

        $view['uploads_path'] = SiteInfo::getPath( 'uploads' );
        $view['nonce_action'] = 'wp2static-caches-page';

        require_once WP2STATIC_PATH . 'views/caches-page.php';
    }


}
