<?php

namespace WP2Static;

class SiteInfo {

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
            // 'home_path' => get_home_path(), // errors trying to find it in WP2Static\get_home_path()...
            'home_url' => trailingslashit( get_home_url() ),
            'includes_path' => trailingslashit( ABSPATH . WPINC ),
            'includes_url' => includes_url(),
            // ??? 'permalink_structure' => get_option( 'permalink_structure' ),
            // Does it matter? 'subdirectory' => $this->isSiteInstalledInSubDirectory(), // it shouldn't, but current mechanism for rewriting URLs has some cases that require knowledge of it...
            // ??? 'permalinks_set' => $this->permalinksAreDefined(),

            // Content
            'content_path' => trailingslashit( WP_CONTENT_DIR ),
            'content_url' => trailingslashit( content_url() ),
            'uploads_path' => trailingslashit( $upload_path_and_url['basedir'] ),
            'uploads_url' => trailingslashit( $upload_path_and_url['baseurl'] ),
            // ??? 'uploads_writable' => $this->uploadsPathIsWritable(),

            // Plugins
            'plugins_path'=> trailingslashit( WP_PLUGIN_DIR ),
            'plugins_url' => trailingslashit( plugins_url() ),

            // Themes
            'themes_root_path' => trailingslashit( get_theme_root() ),
            'themes_root_url' => trailingslashit( get_theme_root_uri() ),
            'parent_theme_path' => trailingslashit( get_template_directory() ),
            'parent_theme_url' => trailingslashit( get_template_directory_uri() ),
            'child_theme_path' => trailingslashit( get_stylesheet_directory() ),
            'child_theme_url' => trailingslashit( get_stylesheet_directory_uri() ),
            // ??? 'using_child_theme' => ( $this->parent_theme_path !== $this->child_theme_path ),

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
     * @param string $name
     * @return string|bool|null
     */
    public function getPath( $name ) {
// Move trailingslashit() here ???
        $key = $name . '_path';
        if ( ! array_key_exists( $key, self::$info ) ) {
            return null;
        }
        return self::$info[ $key ];
    }

    /**
     * @param string $name
     * @return string|bool|null
     */
    public function getUrl( $name ) {
        $key = $name . '_url';
        if ( ! array_key_exists( $key, self::$info ) ) {
            return null;
        }
        return self::$info[ $key ];
    }

/*
    public function getUrlBasename( $name ) {
    }
*/

    // TODO Use WP_Http 'curl_enabled' => $this->hasCurlSupport(), // didn't see the method vailable in WP_Http
    public function hasCURLSupport() {
        return extension_loaded( 'curl' );
    }

    public function isUploadsWritable() {
        $uploadsDir = self::$info['uploads_path'];
        return file_exists( $uploadsDir ) && is_writeable( $uploadsDir );
    }

    public function debug() {
        var_export( self::$info );
    }

    public function get() {
        return self::$info;
    }
}

$si = new SiteInfo();
