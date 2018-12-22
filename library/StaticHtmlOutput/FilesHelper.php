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

    public static function getThemeFiles( $theme_type, $wp_content_subdir ) {
        require_once dirname( __FILE__ ) . '/WPSite.php';
        $wp_site = new WPSite();

        $files = array();
        $template_path = '';
        $template_url = '';

        if ( $theme_type === 'parent' ) {
            $template_path = $wp_site->parent_theme_path;
            $template_url = get_template_directory_uri();
        } else {
            $template_path = $wp_site->child_theme_path;
            $template_url = get_stylesheet_directory_uri();
        }

        $directory = $template_path;

        if ( is_dir( $directory ) ) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $directory,
                    RecursiveDirectoryIterator::SKIP_DOTS
                )
            );

            foreach ( $iterator as $fileName => $fileObject ) {
                $path_crawlable = self::filePathLooksCrawlable( $fileName );

                $detectedFileName =
                    $wp_content_subdir .
                    str_replace(
                        $template_path,
                        $template_url,
                        $fileName
                    );

                $detectedFileName =
                    str_replace(
                        get_home_url(),
                        '',
                        $detectedFileName
                    );

                if ( $path_crawlable ) {
                    array_push(
                        $files,
                        $detectedFileName
                    );
                }
            }
        }

        return $files;
    }

    public static function detectVendorFiles( $wp_site_url ) {
        require_once dirname( __FILE__ ) . '/WPSite.php';
        $wp_site = new WPSite();

        $vendor_files = [];

        if ( class_exists( '\\Elementor\Api' ) ) {
            $elementor_font_dir = WP_PLUGIN_DIR .
                '/elementor/assets/lib/font-awesome';

            $elemementor_URLs = self::getListOfLocalFilesByUrl(
                $elementor_font_dir
            );

            $vendor_files = array_merge( $vendor_files, $elemementor_URLs );
        }

        if ( defined( 'WPSEO_VERSION' ) ) {
            $yoast_sitemaps = array(
                '/sitemap_index.xml',
                '/post-sitemap.xml',
                '/page-sitemap.xml',
                '/category-sitemap.xml',
                '/author-sitemap.xml',
            );

            $vendor_files = array_merge( $vendor_files, $yoast_sitemaps );
        }

        if ( is_dir( WP_PLUGIN_DIR . '/soliloquy/' ) ) {
            $soliloquy_assets = WP_PLUGIN_DIR .
                '/soliloquy/assets/css/images/';

            $soliloquy_URLs = self::getListOfLocalFilesByUrl(
                $soliloquy_assets
            );

            $vendor_files = array_merge( $vendor_files, $soliloquy_URLs );
        }

        if ( class_exists( 'autoptimizeMain' ) ) {
            $autoptimize_cache_dir =
                $wp_site->wp_content_path . '/cache/autoptimize';

            // get difference between home and wp-contents URL
            $prefix = str_replace(
                $wp_site->site_url,
                '/',
                $wp_site->wp_content_URL
            );

            $autoptimize_URLs = self::getAutoptimizeCacheFiles(
                $autoptimize_cache_dir,
                $wp_site->wp_content_path,
                $prefix
            );

            $vendor_files = array_merge( $vendor_files, $autoptimize_URLs );
        }

        if ( class_exists( 'Custom_Permalinks' ) ) {
            global $wpdb;

            $query = "
                SELECT meta_value
                FROM %s
                WHERE meta_key = '%s'
                ";

            $custom_permalinks = [];

            $posts = $wpdb->get_results(
                sprintf(
                    $query,
                    $wpdb->postmeta,
                    'custom_permalink'
                )
            );

            if ( $posts ) {
                foreach ( $posts as $post ) {
                    $custom_permalinks[] = $wp_site_url . $post->meta_value;
                }

                $vendor_files = array_merge(
                    $vendor_files,
                    $custom_permalinks
                );
            }
        }

        if ( class_exists( 'molongui_authorship' ) ) {
            $molongui_path = WP_PLUGIN_DIR . '/molongui-authorship';

            $molongui_URLs = self::getListOfLocalFilesByUrl(
                $molongui_path
            );

            $vendor_files = array_merge( $vendor_files, $molongui_URLs );
        }

        return $vendor_files;
    }

    public static function recursively_scan_dir( $dir, $siteroot, $list_path ) {
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

                    file_put_contents(
                        $list_path,
                        $filename,
                        FILE_APPEND | LOCK_EX
                    );

                    chmod( $list_path, 0664 );
                }
            }
        }
    }

    /*
        Autoptimize puts it's cache dir one level above the uploads URL
        ie, domain.com/cache/ or domain.com/subdir/cache/

        so, we grab all the files from the its actual cache dir

        then strip the site path and any subdir path (no extra logic needed?)
    */
    public static function getAutoptimizeCacheFiles(
        $cache_dir,
        $path_to_trim,
        $prefix
        ) {

        $files = array();

        $directory = $cache_dir;

        if ( is_dir( $directory ) ) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $directory,
                    RecursiveDirectoryIterator::SKIP_DOTS
                )
            );

            foreach ( $iterator as $fileName => $fileObject ) {
                $path_crawlable = self::filePathLooksCrawlable( $fileName );

                if ( $path_crawlable ) {
                    array_push(
                        $files,
                        $prefix .
                        home_url( str_replace( $path_to_trim, '', $fileName ) )
                    );
                }
            }
        }

        return $files;
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
                $path_crawlable = self::filePathLooksCrawlable( $fileName );

                if ( $path_crawlable ) {
                    array_push(
                        $files,
                        home_url( str_replace( ABSPATH, '', $fileName ) )
                    );
                }
            }
        }

        return $files;
    }

    public static function filePathLooksCrawlable( $file_name ) {
        $path_info = pathinfo( $file_name );

        if ( ! is_file( $file_name ) ) {
            return false;
        }

        $filenames_to_ignore = array(
            '.DS_Store',
            '.PHP',
            '.SQL',
            '.git',
            '.idea',
            '.ini',
            '.map',
            '.php',
            '.sql',
            '.yarn',
            'WP-STATIC',
            '__MACOSX',
            'backwpup',
            'bower.json',
            'bower_components',
            'composer.json',
            'current-export',
            'gulpfile.js',
            'latest-export',
            'node_modules',
            'package.json',
            'pb_backupbuddy',
            'previous-export',
            'thumbs.db',
            'vendor',
            'wp-static-html-output', // exclude earlier version exports
        );

        foreach ( $filenames_to_ignore as $ignorable ) {
            if ( strpos( $file_name, $ignorable ) !== false ) {
                return false;
            }
        }

        if ( $path_info['basename'][0] === '.' ) {
            return false;
        }

        if ( ! isset( $path_info['extension'] ) ) {
            return false;
        }

        $extensions_to_ignore =
            array(
                'php',
                'phtml',
                'tpl',
                'less',
                'scss',
                'po',
                'mo',
                'tar.gz',
                'zip',
                'txt',
                'po',
                'pot',
                'dist',
                'sh',
                'sh',
                'mo',
                'md',
            );

        if ( in_array( $path_info['extension'], $extensions_to_ignore ) ) {
            return false;
        }

        return true;
    }

    public static function buildInitialFileList(
        $viaCLI = false,
        $uploadsPath,
        $uploadsURL,
        $workingDirectory
        ) {
        require_once dirname( __FILE__ ) . '/WPSite.php';
        $wp_site = new WPSite();

        $baseUrl = untrailingslashit( home_url() );

        $urlsQueue = array_merge(
            array( trailingslashit( $baseUrl ) ),
            array( '/robots.txt' ),
            array( '/favicon.ico' ),
            array( '/sitemap.xml' ),
            self::getThemeFiles(
                'parent',
                $wp_site->getWPContentSubDirectory()
            ),
            self::getThemeFiles(
                $wp_site->child_theme_path,
                $wp_site->getWPContentSubDirectory()
            ),
            self::detectVendorFiles( $wp_site->site_url ),
            self::getAllWPPostURLs( $baseUrl )
        );

        $urlsQueue = array_unique(
            array_merge(
                $urlsQueue,
                self::getListOfLocalFilesByUrl( $uploadsURL )
            )
        );

        $str = implode( "\n", $urlsQueue );

        // TODO: modify each function vs doing here for perf
        $wp_site_url = get_home_url();
        $str = str_replace(
            $wp_site_url,
            '',
            $str
        );

        file_put_contents(
            $uploadsPath . '/WP-STATIC-INITIAL-CRAWL-LIST.txt',
            $str
        );

        chmod( $uploadsPath . '/WP-STATIC-INITIAL-CRAWL-LIST.txt', 0664 );

        return count( $urlsQueue );
    }

    public static function buildFinalFileList(
        $viaCLI = false,
        $additionalUrls,
        $uploadsPath,
        $uploadsURL,
        $workingDirectory
        ) {
        require_once dirname( __FILE__ ) . '/WPSite.php';
        $wp_site = new WPSite();

        file_put_contents(
            $workingDirectory . '/WP-STATIC-CURRENT-ARCHIVE.txt',
            $archiveDir
        );

        chmod(
            $workingDirectory . '/WP-STATIC-CURRENT-ARCHIVE.txt',
            0664
        );

        if ( ! file_exists( $archiveDir ) ) {
            wp_mkdir_p( $archiveDir );
        }

        $baseUrl = untrailingslashit( home_url() );

        $urlsQueue = array_merge(
            array( trailingslashit( $baseUrl ) ),
            self::getThemeFiles(
                $wp_site->parent_theme_path,
                $wp_site->getWPContentSubDirectory()
            ),
            self::getThemeFiles(
                $wp_site->child_theme_path,
                $wp_site->getWPContentSubDirectory()
            ),
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
            $uploadsPath . '/WP-STATIC-INITIAL-CRAWL-LIST.txt',
            $str
        );

        chmod( $uploadsPath . '/WP-STATIC-INITIAL-CRAWL-LIST.txt', 0664 );

        file_put_contents(
            $workingDirectory . '/WP-STATIC-CRAWLED-LINKS.txt',
            ''
        );

        chmod( $uploadsPath . '/WP-STATIC-CRAWLED-LINKS.txt', 0664 );

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

            // if subdirectory, rm first segment from URL to avoid duplicates
            // TODO: handle WP-CLI case and test if really needed
            // was removing too much on a Bedrock subdir
            // if ( isset( $_POST['subdirectory'] ) ) {
            // array_shift( $path_segments );
            // }
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

        return array_unique( $postURLs );
    }
}

