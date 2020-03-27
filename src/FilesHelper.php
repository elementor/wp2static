<?php

namespace WP2Static;

use RecursiveIteratorIterator;
use RecursiveArrayIterator;
use RecursiveDirectoryIterator;

class FilesHelper {

    /**
     * Recursively delete a directory
     *
     * @throws WP2StaticException
     */
    public static function delete_dir_with_files( string $dir ) : void {
        if ( is_dir( $dir ) ) {
            $dir_files = scandir( $dir );

            if ( ! $dir_files ) {
                $err = 'Trying to delete nonexistant dir: ' . $dir;
                WsLog::l( $err );
                throw new WP2StaticException( $err );
            }

            $files = array_diff( $dir_files, [ '.', '..' ] );

            foreach ( $files as $file ) {
                ( is_dir( "$dir/$file" ) ) ?
                self::delete_dir_with_files( "$dir/$file" ) :
                unlink( "$dir/$file" );
            }

            rmdir( $dir );
        }
    }

    /**
     * Recursively scan a directory and save all filenames to list
     *
     * @throws WP2StaticException
     */
    public static function recursively_scan_dir(
        string $dir,
        string $siteroot,
        string $list_path
    ) : void {
        $dir = str_replace( '//', '/', $dir );
        $files = scandir( $dir );

        if ( ! $files ) {
            $err = 'Trying to scan nonexistant dir: ' . $dir;
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

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

    /**
     * Get public URLs for all files in a local directory
     *
     * @return string[] list of URLs
     */
    public static function getListOfLocalFilesByDir( string $dir ) : array {
        $files = [];

        $site_path = SiteInfo::getPath( 'site' );

        if ( is_dir( $dir ) ) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $dir,
                    RecursiveDirectoryIterator::SKIP_DOTS
                )
            );

            foreach ( $iterator as $filename => $file_object ) {
                $path_crawlable = self::filePathLooksCrawlable( $filename );

                if ( $path_crawlable ) {
                    if ( is_string( $site_path ) ) {
                        $url = str_replace( $site_path, '/', $filename );

                        if ( is_string( $url ) ) {
                            $files[] = $url;
                        }
                    }
                }
            }
        }

        return $files;
    }

    public static function filePathLooksCrawlable( string $file_name ) : bool {
        $filenames_to_ignore = [
            '.DS_Store',
            '.PHP',
            '.SQL',
            '.crt',
            '.git',
            '.idea',
            '.ini',
            '.less',
            '.map',
            '.md',
            '.mo',
            '.mo',
            '.php',
            '.php',
            '.phtml',
            '.po',
            '.po',
            '.pot',
            '.scss',
            '.sh',
            '.sh',
            '.sql',
            '.tar.gz',
            '.tpl',
            '.txt',
            '.yarn',
            '.zip',
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
            'tinymce',
            'wp-static-html-output', // exclude earlier version exports
            'wp2static-crawled-site',
            'wp2static-processed-site',
            'wp2static-addon',
            'LICENSE',
            'README',
            'static-html-output-plugin',
            'wp2static-working-files',
            'wpallexport',
            'wpallimport',
        ];

        $matches = 0;

        str_replace( $filenames_to_ignore, '', $file_name, $matches );

        if ( $matches > 0 ) {
            return false;
        }

        return true;
    }

    // TODO: finish porting these over
    /**
     * Detect all other URL types (TODO: continue to split me into classes)
     *
     * @return string[] list of URLs
     */
    public static function getAllTHEOTHERSTUFFPOSTS(
        string $wp_site_url
    ) : array {
        global $wpdb;

        $post_urls = [];
        $unique_post_types = [];

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
                    $permalink_structure = get_option( 'permalink_structure' );
                    $permalink = WPOverrides::get_permalink(
                        $post->ID,
                        $permalink_structure
                    );
                    break;
                case 'attachment':
                    $permalink = get_attachment_link( $post->ID );
                    break;
                default:
                    $permalink = get_post_permalink( $post->ID );
                    break;
            }

            if ( ! is_string( $permalink ) ) {
                continue;
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

            if (
                ! $parsed_link || ! array_key_exists( 'path', $parsed_link )
            ) {
                continue;
            }

            $link_path = $parsed_link['path'];

            if ( ! is_string( $link_path ) ) {
                continue;
            }

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
        $args = [
            'public'   => true,
        ];

        $taxonomies = get_taxonomies( $args, 'objects' );

        $category_links = [];

        foreach ( $taxonomies as $taxonomy ) {
            if ( ! property_exists( $taxonomy, 'name' ) ) {
                continue;
            }

            $terms = get_terms(
                $taxonomy->name,
                [
                    'hide_empty' => true,
                ]
            );

            if ( ! is_iterable( $terms ) ) {
                continue;
            }

            foreach ( $terms as $term ) {
                if ( is_string( $term ) ) {
                    continue;
                }

                $term_link = get_term_link( $term );

                if ( ! is_string( $term_link ) ) {
                    continue;
                }

                $permalink = trim( $term_link );
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

        // get all comment links
        $comment_pagination_urls =
            DetectCommentPaginationURLs::detect( $wp_site_url );

        $post_urls = array_merge(
            $post_urls,
            $comment_pagination_urls
        );

        return $post_urls;
    }

    /**
     * Clean all detected URLs before use
     *
     * @param string[] $urls list of URLs
     * @return string[] list of URLs
     * @throws WP2StaticException
     */
    public static function cleanDetectedURLs( array $urls ) : array {
        $home_url = SiteInfo::getUrl( 'home' );

        if ( ! is_string( $home_url ) ) {
            $err = 'Home URL not defined ';
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        $cleaned_urls = array_map(
            // trim hashes/query strings
            function ( $url ) use ( $home_url ) {
                if ( ! $url ) {
                    return;
                }

                // NOTE: 2 x str_replace's significantly faster than
                // 1 x str_replace with search/replace arrays of 2 length
                $url = str_replace(
                    $home_url,
                    '/',
                    $url
                );

                $url = str_replace(
                    '//',
                    '/',
                    $url
                );

                if ( ! is_string( $url ) ) {
                    return;
                }

                $url = strtok( $url, '#' );

                if ( ! $url ) {
                    return;
                }

                $url = strtok( $url, '?' );

                return $url;
            },
            $urls
        );

        return $cleaned_urls;
    }

    /**
     * Make dir with permissions or set permissions on existing
     *
     * Attempts modes in order: 0775, 0755, 0777 to help when
     * using via UI and WP-CLI 
     *
     * @throws WP2StaticException
     */
    public static function mkdir_with_permisssions( string $dir ) : void {
        // mkdir recursively
        if ( ! is_dir( $dir ) ) {
            if ( ! mkdir( $dir, 0775, true ) ) {
                if ( ! mkdir( $dir, 0755, true ) ) {
                    if ( ! mkdir( $dir, 0777, true ) ) {
                        $err = 'Unable to create dir: ' . $dir;
                        WsLog::l( $err );
                        throw new WP2StaticException( $err );
                    }
                }
            }
        } else {
            if ( ! chmod( $dir, 0775 ) ) {
                if ( ! chmod( $dir, 0755 ) ) {
                    if ( ! chmod( $dir, 0777 ) ) {
                        $err = 'Unable to set directory mode: ' . $dir;
                        WsLog::l( $err );
                        throw new WP2StaticException( $err );
                    }
                }
            }
        }
    }
}

