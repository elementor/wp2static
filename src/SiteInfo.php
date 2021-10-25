<?php

namespace WP2Static;

/*
    Singleton instance to allow instantiating once and allow reading
    static properties throughout plugin
*/
class SiteInfo {

    /**
     * @var SiteInfo
     */
    private static $instance = null;

    /**
     * Site info.
     *
     * @var mixed[]
     */
    private static $info = [];

    /**
     * Set site info using trailingslashed paths/URLs.
     *
     * @see https://codex.wordpress.org/Determining_Plugin_and_Content_Directories
     */
    public function __construct() {
        $upload_path_and_url = wp_upload_dir();
        $site_url = trailingslashit( site_url() );

        // properties which should not change during plugin execution
        self::$info = apply_filters(
            'wp2static_siteinfo',
            [
                // Core
                'site_path' => ABSPATH,
                'site_url' => $site_url,

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
                    has some cases that require knowledge of it...
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
            ]
        );
    }

    /**
     * Get Path via name
     *
     * @throws WP2StaticException
     */
    public static function getPath( string $name ) : string {
        if ( self::$instance === null ) {
             self::$instance = new SiteInfo();
        }

        // TODO: Move trailingslashit() here ???
        $key = $name . '_path';

        if ( ! array_key_exists( $key, self::$info ) ) {
            $err = 'Attempted to access missing SiteInfo path';
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        // Standardise all paths to use / (Windows support)
        $path = str_replace( '\\', '/', self::$info[ $key ] );

        return $path;
    }

    /**
     * Get URL via name
     *
     * @throws WP2StaticException
     */
    public static function getUrl( string $name ) : string {
        if ( self::$instance === null ) {
             self::$instance = new SiteInfo();
        }

        $key = $name . '_url';

        if ( ! array_key_exists( $key, self::$info ) ) {
            $err = 'Attempted to access missing SiteInfo URL';
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        return self::$info[ $key ];
    }

    // TODO Use WP_Http 'curl_enabled' => $this->hasCurlSupport(),
    // didn't see the method vailable in WP_Http
    public static function hasCURLSupport() : bool {
        if ( self::$instance === null ) {
             self::$instance = new SiteInfo();
        }

        return extension_loaded( 'curl' );
    }

    public static function isUploadsWritable() : bool {
        if ( self::$instance === null ) {
             self::$instance = new SiteInfo();
        }

        $uploads_dir = self::$info['uploads_path'];
        return file_exists( $uploads_dir ) && is_writeable( $uploads_dir );
    }

    public static function permalinksAreCompatible() : bool {
        if ( self::$instance === null ) {
             self::$instance = new SiteInfo();
        }

        $structure = get_option( 'permalink_structure' );

        return strlen( $structure ) && 0 === strcmp( $structure[-1], '/' );
    }

    public static function getPermalinks() : string {
        if ( self::$instance === null ) {
             self::$instance = new SiteInfo();
        }

        return get_option( 'permalink_structure' );
    }

    /**
     * Get Site URL host
     *
     * @throws WP2StaticException
     */
    public static function getSiteURLHost() : string {
        if ( self::$instance === null ) {
             self::$instance = new SiteInfo();
        }

        $url_host = parse_url( self::$info['site_url'], PHP_URL_HOST );

        if ( ! is_string( $url_host ) ) {
            $err = 'Failed to get hostname from Site URL';
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        return $url_host;
    }


    public function debug() : void {
        var_export( self::$info );
    }

    /**
     *  Get all WP site info
     *
     *  @return mixed[]
     */
    public static function getAllInfo() : array {
        if ( self::$instance === null ) {
             self::$instance = new SiteInfo();
        }

        return self::$info;
    }
}

