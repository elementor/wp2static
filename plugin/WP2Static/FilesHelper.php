<?php

function filter_arr_empty_vals( $url ) {
    return ( strpos( $url, ' ' ) === false );
}

class WP2Static_FilesHelper {

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

    public static function getThemeFiles( $theme_type ) {
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

            foreach ( $iterator as $filename => $file_object ) {
                $path_crawlable = self::filePathLooksCrawlable( $filename );

                $detected_filename =
                    str_replace(
                        $template_path,
                        $template_url,
                        $filename
                    );

                $detected_filename =
                    str_replace(
                        get_home_url(),
                        '',
                        $detected_filename
                    );

                if ( $path_crawlable ) {
                    array_push(
                        $files,
                        $detected_filename
                    );
                }
            }
        }

        return $files;
    }

    public static function detectVendorFiles( $wp_site_url ) {
        require_once dirname( __FILE__ ) . '/WPSite.php';
        $wp_site = new WPSite();

        $vendor_files = array();

        if ( class_exists( '\\Elementor\Api' ) ) {
            $elementor_font_dir = WP_PLUGIN_DIR .
                '/elementor/assets/lib/font-awesome';

            $elementor_urls = self::getListOfLocalFilesByUrl(
                $elementor_font_dir
            );

            $vendor_files = array_merge( $vendor_files, $elementor_urls );
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

            $soliloquy_urls = self::getListOfLocalFilesByUrl(
                $soliloquy_assets
            );

            $vendor_files = array_merge( $vendor_files, $soliloquy_urls );
        }

        // cache dir used by Autoptimize and other themes/plugins
        $vendor_cache_dir =
            $wp_site->wp_content_path . '/cache/';

        if ( is_dir( $vendor_cache_dir ) ) {

            // get difference between home and wp-contents URL
            $prefix = str_replace(
                $wp_site->site_url,
                '/',
                $wp_site->wp_content_url
            );

            $vendor_cache_urls = self::getVendorCacheFiles(
                $vendor_cache_dir,
                $wp_site->wp_content_path,
                $prefix
            );

            $vendor_files = array_merge( $vendor_files, $vendor_cache_urls );
        }

        if ( class_exists( 'Custom_Permalinks' ) ) {
            global $wpdb;

            $query = "
                SELECT meta_value
                FROM %s
                WHERE meta_key = '%s'
                ";

            $custom_permalinks = array();

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

            $molongui_urls = self::getListOfLocalFilesByUrl(
                $molongui_path
            );

            $vendor_files = array_merge( $vendor_files, $molongui_urls );
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
                    // TODO: tidy up _SERVER
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
        Autoptimize and other vendors use a cache dir one level above the
        uploads URL

        ie, domain.com/cache/ or domain.com/subdir/cache/

        so, we grab all the files from the its actual cache dir

        then strip the site path and any subdir path (no extra logic needed?)
    */
    public static function getVendorCacheFiles(
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

            foreach ( $iterator as $filename => $file_object ) {
                $path_crawlable = self::filePathLooksCrawlable( $filename );

                if ( $path_crawlable ) {
                    array_push(
                        $files,
                        $prefix .
                        home_url( str_replace( $path_to_trim, '', $filename ) )
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

            foreach ( $iterator as $filename => $file_object ) {
                $path_crawlable = self::filePathLooksCrawlable( $filename );

                if ( $path_crawlable ) {
                    array_push(
                        $files,
                        home_url( str_replace( ABSPATH, '', $filename ) )
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
            'wpallexport',
            'wpallimport',
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
            'wp2static-exported-site',
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
        $via_cli = false,
        $uploads_path,
        $uploads_url,
        $settings
        ) {
        require_once dirname( __FILE__ ) . '/WPSite.php';
        $wp_site = new WPSite();

        $base_url = untrailingslashit( home_url() );

        // TODO: detect robots.txt, etc before adding
        $url_queue = array_merge(
            array( trailingslashit( $base_url ) ),
            array( '/robots.txt' ),
            array( '/favicon.ico' ),
            array( '/sitemap.xml' )
        );

        /*
            URLs to optionally detect
                'detectArchives',
                'detectAttachments',
                'detectCategoryPagination',
                'detectCommentPagination',
                'detectComments',
                'detectCustomPostTypes',
                'detectFeedURLs',
                'detectPostPagination',

        // other options:

         - robots
         - favicon
         - sitemaps

        */
        if ( isset( $settings['detectAttachments'] ) ) {
            $url_queue = array_merge(
                $url_queue,
                self::getAllWPAttachmentURLs( $base_url )
            );
        }

        if ( isset( $settings['detectPosts'] ) ) {
            $url_queue = array_merge(
                $url_queue,
                self::getAllWPPostURLs( $base_url )
            );
        }

        if ( isset( $settings['detectPages'] ) ) {
            $url_queue = array_merge(
                $url_queue,
                self::getAllWPPageURLs( $base_url )
            );
        }

        if ( isset( $settings['detectCustomPostTypes'] ) ) {
            $url_queue = array_merge(
                $url_queue,
                self::getAllWPCustomPostTypeURLs( $base_url )
            );
        }

        if ( isset( $settings['detectUploads'] ) ) {
            $url_queue = array_merge(
                $url_queue,
                self::getListOfLocalFilesByUrl( $uploads_url )
            );
        }

        if ( isset( $settings['detectParentTheme'] ) ) {
            $url_queue = array_merge(
                $url_queue,
                self::getThemeFiles( 'parent' )
            );
        }

        if ( isset( $settings['detectChildTheme'] ) ) {
            $url_queue = array_merge(
                $url_queue,
                self::getThemeFiles( 'child' )
            );
        }

        if ( isset( $settings['detectVendorCacheDirs'] ) ) {
            $url_queue = array_merge(
                $url_queue,
                self::detectVendorFiles( $wp_site->site_url )
            );
        }

        $url_queue = self::cleanDetectedURLs( $url_queue );

        $url_queue = apply_filters(
            'wp2static_modify_initial_crawl_list',
            $url_queue
        );

        $unique_urls = array_unique( $url_queue );
        sort( $unique_urls );

        $initial_crawl_list_total = count( $unique_urls );

        $str = implode( "\n", $unique_urls );

        $initial_crawl_file = $uploads_path .
            '/WP-STATIC-INITIAL-CRAWL-LIST.txt';

        $initial_crawl_total = $uploads_path .
            '/WP-STATIC-INITIAL-CRAWL-TOTAL.txt';

        file_put_contents(
            $initial_crawl_file,
            $str
        );

        chmod( $initial_crawl_file, 0664 );

        file_put_contents(
            $initial_crawl_total,
            $initial_crawl_list_total
        );

        if ( ! is_file( $initial_crawl_total ) ) {
            return false;
        }

        chmod( $initial_crawl_total, 0664 );

        return count( $url_queue );
    }

    public static function getAllWPPostURLs( $wp_site_url ) {
        global $wpdb;

        $post_urls = array();

        $query = "
            SELECT ID
            FROM %s
            WHERE post_status = '%s'
            AND post_type = 'post'";

        $posts = $wpdb->get_results(
            sprintf(
                $query,
                $wpdb->posts,
                'publish'
            )
        );

        foreach ( $posts as $post ) {
            $permalink = get_permalink( $post->ID );

            if ( strpos( $permalink, '?post_type' ) !== false ) {
                continue;
            }

            $post_urls[] = $permalink;
        }

        return $post_urls;
    }

    public static function getAllWPPageURLs( $wp_site_url ) {
        global $wpdb;

        $page_urls = array();

        $query = "
            SELECT ID
            FROM %s
            WHERE post_status = '%s'
            AND post_type = 'page'";

        $pages = $wpdb->get_results(
            sprintf(
                $query,
                $wpdb->posts,
                'publish'
            )
        );

        foreach ( $pages as $page ) {
            $permalink = get_page_link( $page->ID );

            if ( strpos( $permalink, '?post_type' ) !== false ) {
                continue;
            }

            $page_urls[] = $permalink;
        }

        return $page_urls;
    }

    public static function getAllWPCustomPostTypeURLs( $wp_site_url ) {
        global $wpdb;

        $post_urls = array();
        $unique_post_types = array();

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

        foreach ( $posts as $post ) {
            // capture all post types
            $unique_post_types[] = $post->post_type;

            $permalink = get_post_permalink( $post->ID );

            if ( strpos( $permalink, '?post_type' ) !== false ) {
                continue;
            }

            $post_urls[] = $permalink;
        }

        return $post_urls;
    }

    public static function getAllWPAttachmentURLs( $wp_site_url ) {
        global $wpdb;

        $post_urls = array();
        $unique_post_types = array();

        $query = "
            SELECT ID,post_type
            FROM %s
            WHERE post_status = '%s'
            AND post_type = 'attachment'";

        $posts = $wpdb->get_results(
            sprintf(
                $query,
                $wpdb->posts,
                'publish'
            )
        );

        foreach ( $posts as $post ) {
            $permalink = get_attachment_link( $post->ID );

            if ( strpos( $permalink, '?post_type' ) !== false ) {
                continue;
            }

            $post_urls[] = $permalink;
        }

        return $post_urls;
    }

    public static function getAllTHEOTHERSTUFFPOSTS( $wp_site_url ) {
        global $wpdb;

        $post_urls = array();
        $unique_post_types = array();

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

        foreach ( $posts as $post ) {
            // capture all post types
            $unique_post_types[] = $post->post_type;

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

            if ( strpos( $permalink, '?post_type' ) !== false ) {
                continue;
            }

            $post_urls[] = $permalink;

            /*
                Get the post's URL and each sub-chunk of the path as a URL

                  ie http://domain.com/2018/01/01/my-post/ to yield:

                    http://domain.com/2018/01/01/my-post/
                    http://domain.com/2018/01/01/
                    http://domain.com/2018/01/
                    http://domain.com/2018/
            */

            $parsed_link = parse_url( $permalink );
            // rely on WP's site URL vs reconstructing from parsed
            // subdomain, ie http://domain.com/mywpinstall/
            $link_host = $wp_site_url . '/';
            $link_path = $parsed_link['path'];

            // NOTE: Windows filepath support
            $path_segments = explode( '/', $link_path );

            // remove first and last empty elements
            array_shift( $path_segments );
            array_pop( $path_segments );

            $number_of_segments = count( $path_segments );

            // build each URL
            for ( $i = 0; $i < $number_of_segments; $i++ ) {
                $full_url = $link_host;

                for ( $x = 0; $x <= $i; $x++ ) {
                    $full_url .= $path_segments[ $x ] . '/';
                }
                $post_urls[] = $full_url;
            }
        }

        // gets all category page links
        $args = array(
            'public'   => true,
        );

        $taxonomies = get_taxonomies( $args, 'objects' );

        $category_links = array();

        foreach ( $taxonomies as $taxonomy ) {
            $terms = get_terms(
                $taxonomy->name,
                array(
                    'hide_empty' => true,
                )
            );

            foreach ( $terms as $term ) {
                $permalink = trim( get_term_link( $term ) );
                $total_posts = $term->count;

                $term_url = str_replace(
                    $wp_site_url,
                    '',
                    $permalink
                );

                $category_links[ $term_url ] = $total_posts;

                $post_urls[] = $permalink;
            }
        }

        // get all pagination links for each category
        $category_pagination_urls =
            self::getPaginationURLsForCategories( $category_links );

        // get all pagination links for each post_type
        $post_pagination_urls =
            self::getPaginationURLsForPosts(
                array_unique( $unique_post_types )
            );

        // get all comment links
        $comment_pagination_urls =
            self::getPaginationURLsForComments( $wp_site_url );

        $post_urls = array_merge(
            $post_urls,
            $post_pagination_urls,
            $category_pagination_urls,
            $comment_pagination_urls
        );

        return $post_urls;
    }

    public static function cleanDetectedURLs( $urls ) {
        // NOTE: initial de-dup for faster processing
        $unique_urls = array_unique( $urls );

        $wp_site_url = get_home_url();

        $url_queue = array_filter(
            $unique_urls,
            'filter_arr_empty_vals'
        );

        $stripped_urls = str_replace(
            $wp_site_url,
            '/',
            $url_queue
        );

        $deslashed_urls = str_replace(
            '//',
            '/',
            $stripped_urls
        );

        // trim hashes/query strings
        function stripTrailingCharacters( $url ) {
            $url = strtok( $url, '#' );
            $url = strtok( $url, '?' );

            return $url;
        }

        $detokenized_urls = array_map(
            'stripTrailingCharacters',
            $deslashed_urls
        );

        $cleaned_urls = array_unique( $detokenized_urls );

        return $cleaned_urls;
    }

    public static function getPaginationURLsForPosts( $post_types ) {
        global $wpdb, $wp_rewrite;

        $pagination_base = $wp_rewrite->pagination_base;
        $default_posts_per_page = get_option( 'posts_per_page' );

        $urls_to_include = array();

        foreach ( $post_types as $post_type ) {
            $query = "
                SELECT ID,post_type
                FROM %s
                WHERE post_status = '%s'
                AND post_type = '%s'";

            $count = $wpdb->get_results(
                sprintf(
                    $query,
                    $wpdb->posts,
                    'publish',
                    $post_type
                )
            );

            $post_type_obj = get_post_type_object( $post_type );
            $plural_form = strtolower( $post_type_obj->labels->name );

            $count = $wpdb->num_rows;

            $total_pages = ceil( $count / $default_posts_per_page );

            for ( $page = 1; $page <= $total_pages; $page++ ) {
                $pagination_url =
                    "/{$plural_form}/{$pagination_base}/{$page}";

                $urls_to_include[] = str_replace(
                    '/posts/',
                    '/',
                    $pagination_url
                );
            }
        }

        return $urls_to_include;
    }

    public static function getPaginationURLsForCategories( $categories ) {
        global $wp_rewrite;

        $urls_to_include = array();
        $pagination_base = $wp_rewrite->pagination_base;
        $default_posts_per_page = get_option( 'posts_per_page' );

        foreach ( $categories as $term => $total_posts ) {
            $total_pages = ceil( $total_posts / $default_posts_per_page );

            for ( $page = 1; $page <= $total_pages; $page++ ) {
                $urls_to_include[] =
                    "{$term}/{$pagination_base}/{$page}";
            }
        }

        return $urls_to_include;
    }

    public static function getPaginationURLsForComments( $wp_site_url ) {
        global $wp_rewrite;

        $urls_to_include = array();
        $comments_pagination_base = $wp_rewrite->comments_pagination_base;

        foreach ( get_comments() as $comment ) {
            $comment_url = get_comment_link( $comment->comment_ID );
            $comment_url = strtok( $comment_url, '#' );

            $urls_to_include[] = str_replace(
                $wp_site_url,
                '',
                $comment_url
            );
        }

        return array_unique( $urls_to_include );
    }
}

