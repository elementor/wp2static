<?php

class StaticHtmlOutput_FilesHelper {

    protected $_directory;

    public function __construct() {
        $this->_directory = '';
    }

    public static function delete_dir_with_files( $dir ) {
        if ( is_dir( $dir ) ) {
            $files = array_diff( scandir( $dir ), array( '.', '..' ) );

            foreach ( $files as $file ) {
                ( is_dir( "$dir/$file" ) ) ?
                self::delete_dir_with_files( "$dir/$file" ) :
                unlink( "$dir/$file" );
            }

            return rmdir( $dir );
        }
    }

    public static function getParentThemeFiles() {
        return self::getListOfLocalFilesByUrl( get_template_directory_uri() );
    }

    public static function getChildThemeFiles() {
        return self::getListOfLocalFilesByUrl( get_stylesheet_directory_uri() );
    }

    public static function detectVendorFiles() {
        $vendor_files = [];

        if ( class_exists( 'autoptimizeMain' ) ) {
            $autoptimize_cache_dir = WP_CONTENT_DIR . '/cache/autoptimize';

            $autoptimize_URLs = self::getListOfLocalFilesByUrl(
                $autoptimize_cache_dir
            );

            $vendor_files = array_merge($vendor_files, $autoptimize_URLs);
        }

        if ( class_exists( 'Custom_Permalinks' ) ) {
select meta_value from wp_postmeta where meta_key = 'custom_permalink';

            $vendor_files = self::getListOfLocalFilesByUrl(
                $autoptimize_cache_dir
            );
        }

        return $vendor_files;
    }

    public static function recursively_scan_dir( $dir, $siteroot, $list_path ) {
        // rm duplicate slashes in path (TODO: fix cause)
        $dir = str_replace( '//', '/', $dir );
        $files = scandir( $dir );

        foreach ( $files as $item ) {
            if ( $item != '.' && $item != '..' && $item != '.git' ) {
                if ( is_dir( $dir . '/' . $item ) ) {
                    self::recursively_scan_dir(
                        $dir . '/' . $item,
                        $siteroot,
                        $list_path
                    );
                } elseif ( is_file( $dir . '/' . $item ) ) {
                    $subdir = str_replace(
                        '/wp-admin/admin-ajax.php',
                        '',
                        $_SERVER['REQUEST_URI']
                    );
                    $subdir = ltrim( $subdir, '/' );
                    $clean_dir =
                        str_replace( $siteroot . '/', '', $dir . '/' );
                    $clean_dir = str_replace( $subdir, '', $clean_dir );
                    $filename = $dir . '/' . $item . "\n";
                    $filename = str_replace( '//', '/', $filename );
                    // $this->wsLog('FILE TO ADD:');
                    // $this->wsLog($filename);
                    file_put_contents(
                        $list_path,
                        $filename,
                        FILE_APPEND | LOCK_EX
                    );
                }
            }
        }
    }

    public static function getListOfLocalFilesByUrl( $url ) {
        $files = array();

        $directory = str_replace( home_url( '/' ), ABSPATH, $url );

        if ( is_dir( $directory ) ) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $directory,
                    RecursiveDirectoryIterator::SKIP_DOTS
                )
            );

            foreach ( $iterator as $fileName => $fileObject ) {
                if ( self::fileNameLooksCrawlable( $fileName ) &&
                  self::filePathLooksCrawlable( $fileName )
                ) {
                    array_push(
                        $files,
                        home_url( str_replace( ABSPATH, '', $fileName ) )
                    );
                }
            }
        }

        return $files;
    }

    public static function fileNameLooksCrawlable( $file_name ) {
        return (
        ( ! strpos( $file_name, 'wp-static-html-output' ) !== false ) &&
        ( ! strpos( $file_name, 'previous-export' ) !== false ) &&
        is_file( $file_name )
        );
    }

    public static function filePathLooksCrawlable( $file_name ) {
        $path_info = pathinfo( $file_name );

        if ( $path_info['basename'][0] === '.' ) {
            return false;
        }

        return (
        isset( $path_info['extension'] ) &&
        ( ! in_array(
            $path_info['extension'],
            array( 'php', 'phtml', 'tpl', 'less', 'scss' )
        ) )
        );
    }

    public static function buildInitialFileList(
        $viaCLI = false,
        $uploadsPath,
        $uploadsURL,
        $workingDirectory,
        $pluginHook ) {

        $baseUrl = untrailingslashit( home_url() );

        $urlsQueue = array_merge(
            array( trailingslashit( $baseUrl ) ),
            self::getParentThemeFiles(),
            self::getChildThemeFiles(),
            self::detectVendorFiles(),
            self::getAllWPPostURLs( $baseUrl )
        );

        $urlsQueue = array_unique(
            array_merge(
                $urlsQueue,
                self::getListOfLocalFilesByUrl( $uploadsURL )
            )
        );

        $str = implode( "\n", $urlsQueue );
        file_put_contents(
            $uploadsPath . '/WP-STATIC-INITIAL-CRAWL-LIST',
            $str
        );

        return count( $urlsQueue );
    }

    // TODO: connect from Exporter
    public static function buildFinalFileList(
        $viaCLI = false,
        $additionalUrls,
        $uploadsPath,
        $uploadsURL,
        $workingDirectory,
        $pluginHook ) {

        file_put_contents(
            $workingDirectory . '/WP-STATIC-CURRENT-ARCHIVE',
            $archiveDir
        );

        if ( ! file_exists( $archiveDir ) ) {
            wp_mkdir_p( $archiveDir );
        }

        $baseUrl = untrailingslashit( home_url() );

        $urlsQueue = array_merge(
            array( trailingslashit( $baseUrl ) ),
            self::getParentThemeFiles(),
            self::getChildThemeFiles(),
            self::getAllWPPostURLs( $baseUrl ),
            explode( "\n", $additionalUrls )
        );

        // TODO: shift this as an option to exclusions area
        $urlsQueue = array_unique(
            array_merge(
                $urlsQueue,
                self::getListOfLocalFilesByUrl( $uploadsURL )
            )
        );

        $str = implode( "\n", $urlsQueue );
        file_put_contents(
            $uploadsPath . '/WP-STATIC-INITIAL-CRAWL-LIST',
            $str
        );

        file_put_contents(
            $workingDirectory . '/WP-STATIC-CRAWLED-LINKS',
            ''
        );

        return count( $urlsQueue );
    }

    public static function getAllWPPostURLs( $wp_site_url ) {
        global $wpdb;

        // NOTE: re using $wpdb->ret_results vs WP_Query
        // https://wordpress.stackexchange.com/a/151843/20982
        // get_results may be faster, but more error prone
        // TODO: benchmark the diff and use WP_Query if not noticably slower
        // NOTE: inheret post_status allows unlinked attchmnt page creation
        $query = "
            SELECT ID,post_type
            FROM %s
            WHERE post_status = '%s'
            AND post_type NOT IN ('%s','%s')";

        $posts = $wpdb->get_results(
            sprintf(
                $query,
                $wpdb->posts,
                'publish',
                'revision',
                'nav_menu_item'
            )
        );

        $postURLs = array();

        foreach ( $posts as $post ) {
            switch ( $post->post_type ) {
                case 'page':
                    $permalink = get_page_link( $post->ID );
                    break;
                case 'post':
                    $permalink = get_permalink( $post->ID );
                    break;
                case 'attachment':
                    $permalink = get_attachment_link( $post->ID );
                    break;
                default:
                    $permalink = get_post_permalink( $post->ID );
                    break;
            }

            /*
                Get the post's URL and each sub-chunk of the path as a URL

                  ie http://domain.com/2018/01/01/my-post/ to yield:

                    http://domain.com/2018/01/01/my-post/
                    http://domain.com/2018/01/01/
                    http://domain.com/2018/01/
                    http://domain.com/2018/
            */

            // TODO: failing on subdir installs here
            $parsed_link = parse_url( $permalink );
            // rely on WP's site URL vs reconstructing from parsed
            // subdomain, ie http://domain.com/mywpinstall/
            $link_host = $wp_site_url . '/';
            $link_path = $parsed_link['path'];

            // TODO: Windows filepath support?
            $path_segments = explode( '/', $link_path );

            // remove first and last empty elements
            array_shift( $path_segments );
            array_pop( $path_segments );

            // if subdomain, rm first segment from URL to avoid duplicates
            if ( isset( $_POST['subdirectory'] ) ) {
                array_shift( $path_segments );
            }

            $number_of_segments = count( $path_segments );

            // build each URL
            for ( $i = 0; $i < $number_of_segments; $i++ ) {
                $full_url = $link_host;

                for ( $x = 0; $x <= $i; $x++ ) {
                    $full_url .= $path_segments[ $x ] . '/';
                }
                $postURLs[] = $full_url;
            }
        }

        // gets all category page links
        $args = array(
            'public'   => true,
        );

        $taxonomies = get_taxonomies( $args, 'objects' );

        foreach ( $taxonomies as $taxonomy ) {
            $terms = get_terms(
                $taxonomy->name,
                array(
                    'hide_empty' => true,
                )
            );

            foreach ( $terms as $term ) {
                $permalink = get_term_link( $term );

                $postURLs[] = trim( $permalink );
            }
        }

        // de-duplicate the array
        return array_unique( $postURLs );
    }
}

