<?php
/**
 * Handles disk-cache-related operations.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class autoptimizeCache
{
    /**
     * Cache filename.
     *
     * @var string
     */
    private $filename;

    /**
     * Cache directory path (with a trailing slash).
     *
     * @var string
     */
    private $cachedir;

    /**
     * Whether gzipping is done by the web server or us.
     * True => we don't gzip, the web server does it.
     * False => we do it ourselves.
     *
     * @var bool
     */
    private $nogzip;

    /**
     * Ctor.
     *
     * @param string $md5 Hash.
     * @param string $ext Extension.
     */
    public function __construct( $md5, $ext = 'php' )
    {
        $this->cachedir = AUTOPTIMIZE_CACHE_DIR;
        $this->nogzip   = AUTOPTIMIZE_CACHE_NOGZIP;
        if ( ! $this->nogzip ) {
            $this->filename = AUTOPTIMIZE_CACHEFILE_PREFIX . $md5 . '.php';
        } else {
            if ( in_array( $ext, array( 'js', 'css' ) ) ) {
                $this->filename = $ext . '/' . AUTOPTIMIZE_CACHEFILE_PREFIX . $md5 . '.' . $ext;
            } else {
                $this->filename = AUTOPTIMIZE_CACHEFILE_PREFIX . $md5 . '.' . $ext;
            }
        }
    }

    /**
     * Returns true if the cached file exists on disk.
     *
     * @return bool
     */
    public function check()
    {
        return file_exists( $this->cachedir . $this->filename );
    }

    /**
     * Returns cache contents if they exist, false otherwise.
     *
     * @return string|false
     */
    public function retrieve()
    {
        if ( $this->check() ) {
            if ( false == $this->nogzip ) {
                return file_get_contents( $this->cachedir . $this->filename . '.none' );
            } else {
                return file_get_contents( $this->cachedir . $this->filename );
            }
        }
        return false;
    }

    /**
     * Stores given $data in cache.
     *
     * @param string $data Data to cache.
     * @param string $mime Mimetype.
     *
     * @return void
     */
    public function cache( $data, $mime )
    {
        if ( false === $this->nogzip ) {
            // We handle gzipping ourselves.
            $file    = 'default.php';
            $phpcode = file_get_contents( AUTOPTIMIZE_PLUGIN_DIR . 'config/' . $file );
            $phpcode = str_replace( array( '%%CONTENT%%', 'exit;' ), array( $mime, '' ), $phpcode );

            file_put_contents( $this->cachedir . $this->filename, $phpcode );
            file_put_contents( $this->cachedir . $this->filename . '.none', $data );
        } else {
            // Write code to cache without doing anything else.
            file_put_contents( $this->cachedir . $this->filename, $data );
            if ( apply_filters( 'autoptimize_filter_cache_create_static_gzip', false ) ) {
                // Create an additional cached gzip file.
                file_put_contents( $this->cachedir . $this->filename . '.gz', gzencode( $data, 9, FORCE_GZIP ) );
            }
        }
    }

    /**
     * Get cache filename.
     *
     * @return string
     */
    public function getname()
    {
        // NOTE: This could've maybe been a do_action() instead, however,
        // that ship has sailed.
        // The original idea here was to provide 3rd party code a hook so that
        // it can "listen" to all the complete autoptimized-urls that the page
        // will emit... Or something to that effect I think?
        apply_filters( 'autoptimize_filter_cache_getname', AUTOPTIMIZE_CACHE_URL . $this->filename );

        return $this->filename;
    }

    /**
     * Returns true if given `$file` is considered a valid Autoptimize cache file,
     * false otherwise.
     *
     * @param string $dir Directory name (with a trailing slash).
     * @param string $file Filename.
     * @return bool
     */
    protected static function is_valid_cache_file( $dir, $file )
    {
        if ( '.' !== $file && '..' !== $file &&
            false !== strpos( $file, AUTOPTIMIZE_CACHEFILE_PREFIX ) &&
            is_file( $dir . $file ) ) {

            // It's a valid file!
            return true;
        }

        // Everything else is considered invalid!
        return false;
    }

    /**
     * Clears contents of AUTOPTIMIZE_CACHE_DIR.
     *
     * @return void
     */
    protected static function clear_cache_classic()
    {
        $contents = self::get_cache_contents();
        foreach ( $contents as $name => $files ) {
            $dir = rtrim( AUTOPTIMIZE_CACHE_DIR . $name, '/' ) . '/';
            foreach ( $files as $file ) {
                if ( self::is_valid_cache_file( $dir, $file ) ) {
                    @unlink( $dir . $file ); // @codingStandardsIgnoreLine
                }
            }
        }

        @unlink( AUTOPTIMIZE_CACHE_DIR . '/.htaccess' ); // @codingStandardsIgnoreLine
    }

    /**
     * Recursively deletes the specified pathname (file/directory) if possible.
     * Returns true on success, false otherwise.
     *
     * @param string $pathname Pathname to remove.
     *
     * @return bool
     */
    protected static function rmdir( $pathname )
    {
        $files = self::get_dir_contents( $pathname );
        foreach ( $files as $file ) {
            $path = $pathname . '/' . $file;
            if ( is_dir( $path ) ) {
                self::rmdir( $path );
            } else {
                unlink( $path );
            }
        }

        return rmdir( $pathname );
    }

    /**
     * Clears contents of AUTOPTIMIZE_CACHE_DIR by renaming the current
     * cache directory into a new one with a unique name and then
     * re-creating the default (empty) cache directory.
     *
     * @return bool Returns true when everything is done successfully, false otherwise.
     */
    protected static function clear_cache_via_rename()
    {
        $ok       = false;
        $dir      = self::get_pathname_base();
        $new_name = self::get_unique_name();

        // Makes sure the new pathname is on the same level...
        $new_pathname = dirname( $dir ) . '/' . $new_name;
        $renamed      = @rename( $dir, $new_pathname ); // @codingStandardsIgnoreLine

        // When renamed, re-create the default cache directory back so it's
        // available again...
        if ( $renamed ) {
            $ok = self::cacheavail();
        }

        return $ok;
    }

    /**
     * Returns true when advanced cache clearing is enabled.
     *
     * @return bool
     */
    public static function advanced_cache_clear_enabled()
    {
        return apply_filters( 'autoptimize_filter_cache_clear_advanced', false );
    }

    /**
     * Returns a (hopefully) unique new cache folder name for renaming purposes.
     *
     * @return string
     */
    protected static function get_unique_name()
    {
        $prefix   = self::get_advanced_cache_clear_prefix();
        $new_name = uniqid( $prefix, true );

        return $new_name;
    }

    /**
     * Get cache prefix name used in advanced cache clearing mode.
     *
     * @return string
     */
    protected static function get_advanced_cache_clear_prefix()
    {
        $pathname = self::get_pathname_base();
        $basename = basename( $pathname );
        $prefix   = $basename . '-';

        return $prefix;
    }

    /**
     * Returns an array of file and directory names found within
     * the given $pathname without '.' and '..' elements.
     *
     * @param string $pathname Pathname.
     *
     * @return array
     */
    protected static function get_dir_contents( $pathname )
    {
        return array_slice( scandir( $pathname ), 2 );
    }

    /**
     * Wipes directories which were created as part of the fast cache clearing
     * routine (which renames the current cache directory into a new one with
     * a custom-prefixed unique name).
     *
     * @return bool
     */
    public static function delete_advanced_cache_clear_artifacts()
    {
        $dir    = self::get_pathname_base();
        $prefix = self::get_advanced_cache_clear_prefix();
        $parent = dirname( $dir );
        $ok     = false;

        // Returns the list of files without '.' and '..' elements.
        $files = self::get_dir_contents( $parent );
        foreach ( $files as $file ) {
            $path     = $parent . '/' . $file;
            $prefixed = ( false !== strpos( $path, $prefix ) );
            // Removing only our own (prefixed) directories...
            if ( is_dir( $path ) && $prefixed ) {
                $ok = self::rmdir( $path );
            }
        }

        return $ok;
    }

    /**
     * Returns the cache directory pathname used.
     * Done as a function so we canSlightly different
     * if multisite is used and `autoptimize_separate_blog_caches` filter
     * is used.
     *
     * @return string
     */
    public static function get_pathname()
    {
        $pathname = self::get_pathname_base();

        if ( is_multisite() && apply_filters( 'autoptimize_separate_blog_caches', true ) ) {
            $blog_id   = get_current_blog_id();
            $pathname .= $blog_id . '/';
        }

        return $pathname;
    }

    /**
     * Returns the base path of our cache directory.
     *
     * @return string
     */
    protected static function get_pathname_base()
    {
        $pathname = WP_CONTENT_DIR . AUTOPTIMIZE_CACHE_CHILD_DIR;

        return $pathname;
    }

    /**
     * Deletes everything from the cache directories.
     *
     * @param bool $propagate Whether to trigger additional actions when cache is purged.
     *
     * @return bool
     */
    public static function clearall( $propagate = true )
    {
        if ( ! self::cacheavail() ) {
            return false;
        }

        // TODO/FIXME: If cache is big, switch to advanced/new cache clearing automatically?
        if ( self::advanced_cache_clear_enabled() ) {
            self::clear_cache_via_rename();
        } else {
            self::clear_cache_classic();
        }

        // Remove the transient so it gets regenerated...
        delete_transient( 'autoptimize_stats' );

        // Cache was just purged, clear page cache and allow others to hook into our purging...
        if ( true === $propagate ) {
            if ( ! function_exists( 'autoptimize_do_cachepurged_action' ) ) {
                function autoptimize_do_cachepurged_action() {
                    do_action( 'autoptimize_action_cachepurged' );
                }
            }
            add_action( 'shutdown', 'autoptimize_do_cachepurged_action', 11 );
            add_action( 'autoptimize_action_cachepurged', array( 'autoptimizeCache', 'flushPageCache' ), 10, 0 );
        }

        // Warm cache (part of speedupper)!
        if ( apply_filters( 'autoptimize_filter_speedupper', true ) ) {
            $url   = site_url() . '/?ao_speedup_cachebuster=' . rand( 1, 100000 );
            $cache = @wp_remote_get( $url ); // @codingStandardsIgnoreLine
            unset( $cache );
        }

        return true;
    }

    /**
     * Wrapper for clearall but with false param
     * to ensure the event is not propagated to others
     * through our own hooks (to avoid infinite loops).
     *
     * @return bool
     */
    public static function clearall_actionless()
    {
        return self::clearall( false );
    }

    /**
     * Returns the contents of our cache dirs.
     *
     * @return array
     */
    protected static function get_cache_contents()
    {
        $contents = array();

        foreach ( array( '', 'js', 'css' ) as $dir ) {
            $contents[ $dir ] = scandir( AUTOPTIMIZE_CACHE_DIR . $dir );
        }

        return $contents;
    }

    /**
     * Returns stats about cached contents.
     *
     * @return array
     */
    public static function stats()
    {
        $stats = get_transient( 'autoptimize_stats' );

        // If no transient, do the actual scan!
        if ( ! is_array( $stats ) ) {
            if ( ! self::cacheavail() ) {
                return 0;
            }
            $stats = self::stats_scan();
            $count = $stats[0];
            if ( $count > 100 ) {
                // Store results in transient.
                set_transient(
                    'autoptimize_stats',
                    $stats,
                    apply_filters( 'autoptimize_filter_cache_statsexpiry', HOUR_IN_SECONDS )
                );
            }
        }

        return $stats;
    }

    /**
     * Performs a scan of cache directory contents and returns an array
     * with 3 values: count, size, timestamp.
     * count = total number of found files
     * size = total filesize (in bytes) of found files
     * timestamp = unix timestamp when the scan was last performed/finished.
     *
     * @return array
     */
    protected static function stats_scan()
    {
        $count = 0;
        $size  = 0;

        // Scan everything in our cache directories.
        foreach ( self::get_cache_contents() as $name => $files ) {
            $dir = rtrim( AUTOPTIMIZE_CACHE_DIR . $name, '/' ) . '/';
            foreach ( $files as $file ) {
                if ( self::is_valid_cache_file( $dir, $file ) ) {
                    if ( AUTOPTIMIZE_CACHE_NOGZIP &&
                        (
                            false !== strpos( $file, '.js' ) ||
                            false !== strpos( $file, '.css' ) ||
                            false !== strpos( $file, '.img' ) ||
                            false !== strpos( $file, '.txt' )
                        )
                    ) {
                        // Web server is gzipping, we count .js|.css|.img|.txt files.
                        $count++;
                    } elseif ( ! AUTOPTIMIZE_CACHE_NOGZIP && false !== strpos( $file, '.none' ) ) {
                        // We are gzipping ourselves via php, counting only .none files.
                        $count++;
                    }
                    $size += filesize( $dir . $file );
                }
            }
        }

        $stats = array( $count, $size, time() );

        return $stats;
    }

    /**
     * Ensures the cache directory exists, is writeable and contains the
     * required .htaccess files.
     * Returns false in case it fails to ensure any of those things.
     *
     * @return bool
     */
    public static function cacheavail()
    {
        if ( ! defined( 'AUTOPTIMIZE_CACHE_DIR' ) ) {
            // We didn't set a cache.
            return false;
        }

        foreach ( array( '', 'js', 'css' ) as $dir ) {
            if ( ! self::check_cache_dir( AUTOPTIMIZE_CACHE_DIR . $dir ) ) {
                return false;
            }
        }

        // Using .htaccess inside our cache folder to overrule wp-super-cache.
        $htaccess = AUTOPTIMIZE_CACHE_DIR . '/.htaccess';
        if ( ! is_file( $htaccess ) ) {
            /**
             * Create `wp-content/AO_htaccess_tmpl` file with
             * whatever htaccess rules you might need
             * if you want to override default AO htaccess
             */
            $htaccess_tmpl = WP_CONTENT_DIR . '/AO_htaccess_tmpl';
            if ( is_file( $htaccess_tmpl ) ) {
                $content = file_get_contents( $htaccess_tmpl );
            } elseif ( is_multisite() || ! AUTOPTIMIZE_CACHE_NOGZIP ) {
                $content = '<IfModule mod_expires.c>
        ExpiresActive On
        ExpiresByType text/css A30672000
        ExpiresByType text/javascript A30672000
        ExpiresByType application/javascript A30672000
</IfModule>
<IfModule mod_headers.c>
    Header append Cache-Control "public, immutable"
</IfModule>
<IfModule mod_deflate.c>
        <FilesMatch "\.(js|css)$">
        SetOutputFilter DEFLATE
    </FilesMatch>
</IfModule>
<IfModule mod_authz_core.c>
    <Files *.php>
        Require all granted
    </Files>
</IfModule>
<IfModule !mod_authz_core.c>
    <Files *.php>
        Order allow,deny
        Allow from all
    </Files>
</IfModule>';
            } else {
                $content = '<IfModule mod_expires.c>
        ExpiresActive On
        ExpiresByType text/css A30672000
        ExpiresByType text/javascript A30672000
        ExpiresByType application/javascript A30672000
</IfModule>
<IfModule mod_headers.c>
    Header append Cache-Control "public, immutable"
</IfModule>
<IfModule mod_deflate.c>
    <FilesMatch "\.(js|css)$">
        SetOutputFilter DEFLATE
    </FilesMatch>
</IfModule>
<IfModule mod_authz_core.c>
    <Files *.php>
        Require all denied
    </Files>
</IfModule>
<IfModule !mod_authz_core.c>
    <Files *.php>
        Order deny,allow
        Deny from all
    </Files>
</IfModule>';
            }
            @file_put_contents( $htaccess, $content ); // @codingStandardsIgnoreLine
        }

        // All OK!
        return true;
    }

    /**
     * Ensures the specified `$dir` exists and is writeable.
     * Returns false if that's not the case.
     *
     * @param string $dir Directory to check/create.
     *
     * @return bool
     */
    protected static function check_cache_dir( $dir )
    {
        // Try creating the dir if it doesn't exist.
        if ( ! file_exists( $dir ) ) {
            @mkdir( $dir, 0775, true ); // @codingStandardsIgnoreLine
            if ( ! file_exists( $dir ) ) {
                return false;
            }
        }

        // If we still cannot write, bail.
        if ( ! is_writable( $dir ) ) {
            return false;
        }

        // Create an index.html in there to avoid prying eyes!
        $idx_file = rtrim( $dir, '/\\' ) . '/index.html';
        if ( ! is_file( $idx_file ) ) {
            @file_put_contents( $idx_file, '<html><head><meta name="robots" content="noindex, nofollow"></head><body>Generated by <a href="http://wordpress.org/extend/plugins/autoptimize/" rel="nofollow">Autoptimize</a></body></html>' ); // @codingStandardsIgnoreLine
        }

        return true;
    }

    /**
     * Flushes as many page cache plugin's caches as possible.
     *
     * @return void
     */
    // @codingStandardsIgnoreStart
    public static function flushPageCache()
    {
        if ( function_exists( 'wp_cache_clear_cache' ) ) {
            if ( is_multisite() ) {
                $blog_id = get_current_blog_id();
                wp_cache_clear_cache( $blog_id );
            } else {
                wp_cache_clear_cache();
            }
        } elseif ( has_action( 'cachify_flush_cache' ) ) {
            do_action( 'cachify_flush_cache' );
        } elseif ( function_exists( 'w3tc_pgcache_flush' ) ) {
            w3tc_pgcache_flush();
        } elseif ( function_exists( 'wp_fast_cache_bulk_delete_all' ) ) {
            wp_fast_cache_bulk_delete_all();
        } elseif ( class_exists( 'WpFastestCache' ) ) {
            $wpfc = new WpFastestCache();
            $wpfc->deleteCache();
        } elseif ( class_exists( 'c_ws_plugin__qcache_purging_routines' ) ) {
            c_ws_plugin__qcache_purging_routines::purge_cache_dir(); // quick cache
        } elseif ( class_exists( 'zencache' ) ) {
            zencache::clear();
        } elseif ( class_exists( 'comet_cache' ) ) {
            comet_cache::clear();
        } elseif ( class_exists( 'WpeCommon' ) ) {
            // WPEngine cache purge/flush methods to call by default
            $wpe_methods = array(
                'purge_varnish_cache',
            );

            // More agressive clear/flush/purge behind a filter
            if ( apply_filters( 'autoptimize_flush_wpengine_aggressive', false ) ) {
                $wpe_methods = array_merge( $wpe_methods, array( 'purge_memcached', 'clear_maxcdn_cache' ) );
            }

            // Filtering the entire list of WpeCommon methods to be called (for advanced usage + easier testing)
            $wpe_methods = apply_filters( 'autoptimize_flush_wpengine_methods', $wpe_methods );

            foreach ( $wpe_methods as $wpe_method ) {
                if ( method_exists( 'WpeCommon', $wpe_method ) ) {
                    WpeCommon::$wpe_method();
                }
            }
        } elseif ( function_exists( 'sg_cachepress_purge_cache' ) ) {
            sg_cachepress_purge_cache();
        } elseif ( file_exists( WP_CONTENT_DIR . '/wp-cache-config.php' ) && function_exists( 'prune_super_cache' ) ) {
            // fallback for WP-Super-Cache
            global $cache_path;
            if ( is_multisite() ) {
                $blog_id = get_current_blog_id();
                prune_super_cache( get_supercache_dir( $blog_id ), true );
                prune_super_cache( $cache_path . 'blogs/', true );
            } else {
                prune_super_cache( $cache_path . 'supercache/', true );
                prune_super_cache( $cache_path, true );
            }
        }
    }
    // @codingStandardsIgnoreEnd
}
