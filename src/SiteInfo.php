<?php

namespace WP2Static;

use Exception;

/*
    Singleton instance to allow instantiating once and allow reading
    static properties throughout plugin
*/
class SiteInfo {

    private static $instance = null;

    /**
     * Site info.
     *
     * @var array
     */
    private static $info = array();

    /**
     * Set site info using trailingslashed paths/URLs.
     *
     * @see https://codex.wordpress.org/Determining_Plugin_and_Content_Directories
     */
    public function __construct() {
        $upload_path_and_url = wp_upload_dir();

        self::$info = [
            // Core
            'site_path' => ABSPATH,
            'site_url' => trailingslashit( site_url() ),

            /*
                Note:  'home_path' => get_home_path(),
                // errors trying to find it in WP2Static\get_home_path()...
            */
            'home_url' => trailingslashit( get_home_url() ),
            'includes_path' => trailingslashit( ABSPATH . WPINC ),
            'includes_url' => includes_url(),

            /*
                TODO: Q on subdir:

                Does it matter?
                'subdirectory' => $this->isSiteInstalledInSubDirectory(),

                A: It shouldn't, but current mechanism for rewriting URLs
                hassome cases that require knowledge of it...
            */

            // Content
            'content_path' => trailingslashit( WP_CONTENT_DIR ),
            'content_url' => trailingslashit( content_url() ),
            'uploads_path' =>
                trailingslashit( $upload_path_and_url['basedir'] ),
            'uploads_url' => trailingslashit( $upload_path_and_url['baseurl'] ),

            // Plugins
            'plugins_path' => trailingslashit( WP_PLUGIN_DIR ),
            'plugins_url' => trailingslashit( plugins_url() ),

            // Themes
            'themes_root_path' => trailingslashit( get_theme_root() ),
            'themes_root_url' => trailingslashit( get_theme_root_uri() ),
            'parent_theme_path' => trailingslashit( get_template_directory() ),
            'parent_theme_url' =>
                trailingslashit( get_template_directory_uri() ),
            'child_theme_path' => trailingslashit( get_stylesheet_directory() ),
            'child_theme_url' =>
                trailingslashit( get_stylesheet_directory_uri() ),

        /*
            // TODO: rm these once refactored to use consistent naming
            $this->wp_site_subdir = $this->subdirectory;
            $this->wp_site_url = $this->site_url;
            $this->wp_site_path = $this->site_path;
            $this->wp_uploads_url = $this->uploads_url;
        */
        ];
    }

    /**
     * Get Path via name
     *
     * @param string $name
     * @return string|bool|null
     * @throws Exception
     */
    public static function getPath( $name ) {
        if ( self::$instance === null ) {
             self::$instance = new SiteInfo();
        }

        // TODO: Move trailingslashit() here ???
        $key = $name . '_path';

        if ( ! array_key_exists( $key, self::$info ) ) {
            $err = 'Attempted to access missing SiteInfo path';
            WsLog::l( $err );
            throw new Exception( $err );
        }

        return self::$info[ $key ];
    }

    /**
     * Get URL via name
     *
     * @param string $name
     * @return string|bool|null
     * @throws Exception
     */
    public static function getUrl( $name ) {
        if ( self::$instance === null ) {
             self::$instance = new SiteInfo();
        }

        $key = $name . '_url';

        if ( ! array_key_exists( $key, self::$info ) ) {
            $err = 'Attempted to access missing SiteInfo URL';
            WsLog::l( $err );
            throw new Exception( $err );
        }

        return self::$info[ $key ];
    }

    // TODO Use WP_Http 'curl_enabled' => $this->hasCurlSupport(),
    // didn't see the method vailable in WP_Http
    public static function hasCURLSupport() {
        if ( self::$instance === null ) {
             self::$instance = new SiteInfo();
        }

        return extension_loaded( 'curl' );
    }

    public static function isUploadsWritable() {
        if ( self::$instance === null ) {
             self::$instance = new SiteInfo();
        }

        $uploads_dir = self::$info['uploads_path'];
        return file_exists( $uploads_dir ) && is_writeable( $uploads_dir );
    }

    // ??? 'permalink_structure' => get_option( 'permalink_structure' ),
    public static function permalinksAreDefined() {
        if ( self::$instance === null ) {
             self::$instance = new SiteInfo();
        }

        return strlen( get_option( 'permalink_structure' ) );
    }

    public function debug() {
        var_export( self::$info );
    }

    public static function getAllInfo() {
        return self::$info;
    }
}

