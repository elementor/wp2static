<?php

// TODO: better named WPEnvironment
class WPSite {

    public function __construct() {
        // WP URL paths
        $wp_upload_path_and_url = wp_upload_dir();
        $this->uploads_url = $wp_upload_path_and_url['baseurl'];
        $this->site_url = get_home_url() . '/';
        $this->parent_theme_URL = get_template_directory_uri();
        $this->wp_content_URL = content_url();

        // WP dir paths
        $this->site_path = ABSPATH;
        $this->plugins_path = $this->getWPDirFullPath( 'plugins' );
        $this->wp_uploads_path = $this->getWPDirFullPath( 'uploads' );
        $this->wp_includes_path = $this->getWPDirFullPath( 'wp-includes' );
        $this->wp_content_path = $this->getWPDirFullPath( 'wp-content' );
        $this->theme_root_path = $this->getWPDirFullPath( 'theme-root' );
        $this->parent_theme_path = $this->getWPDirFullPath( 'parent-theme' );
        $this->child_theme_path = $this->getWPDirFullPath( 'child-theme' );
        $this->child_theme_active =
            $this->parent_theme_path !== $this->child_theme_path;

        $this->permalink_structure = get_option( 'permalink_structure' );

        $this->wp_inc = '/' . WPINC;

        $this->wp_content = WP_CONTENT_DIR;
        $this->wp_uploads =
                str_replace( ABSPATH, '/', $this->wp_uploads_path );
        $this->wp_plugins = str_replace( ABSPATH, '/', WP_PLUGIN_DIR );
        $this->wp_themes = str_replace( ABSPATH, '/', get_theme_root() );
        $this->wp_active_theme =
            str_replace( home_url(), '', get_template_directory_uri() );

        // TODO: pre-generate as much as possible here to avoid
        // extra overhead during the high cyclical functions
        $this->detect_base_url();

        $this->subdirectory = $this->isSiteInstalledInSubDirectory();

        $this->uploads_writable = $this->uploadsPathIsWritable();
        $this->permalinks_set = $this->permalinksAreDefined();
        $this->curl_enabled = $this->hasCurlSupport();
    }

    public function isSiteInstalledInSubDirectory() {
        $parsed_site_url = parse_url( rtrim( $this->site_url, '/' ) );

        if ( isset( $parsed_site_url['path'] ) ) {
            return $parsed_site_url['path'];
        }

        return false;
    }

    public function uploadsPathIsWritable() {
        return $this->wp_uploads_path && is_writable( $this->wp_uploads_path );
    }

    public function hasCurlSupport() {
        return extension_loaded( 'curl' );
    }

    public function permalinksAreDefined() {
        return strlen( get_option( 'permalink_structure' ) );
    }

    public function detect_base_url() {
        $site_url = get_option( 'siteurl' );
        $home = get_option( 'home' );
    }

    public function getOriginalPaths() {
        $orig_dir_name = array();

        $tokens = explode( '/', get_template_directory_uri() );
        $orig_dir_name['theme_dir'] = $tokens[ count( $tokens ) - 1 ];
        $orig_dir_name['theme_root'] = $tokens[ count( $tokens ) - 2 ];
        // TODO: use this as a safer way to get wp-content in rewriting areas
        // in case user has changed their wp-content path
        $orig_dir_name['wp_contents'] = $tokens[ count( $tokens ) - 3 ];

        $default_upload_dir = wp_upload_dir();
        $tokens = explode(
            '/',
            str_replace( ABSPATH, '/', $default_upload_dir['basedir'] )
        );
        $orig_dir_name['upload_dir'] = $tokens[ count( $tokens ) - 1 ];

        $tokens = explode( '/', WP_PLUGIN_DIR );
        $orig_dir_name['plugin_dir'] = $tokens[ count( $tokens ) - 1 ];

        $tokens = explode( '/', WPINC );
        $orig_dir_name['includes_dir'] = $tokens[ count( $tokens ) - 1 ];

        return $orig_dir_name;

    }

    /*
        Function below assumes people may have changed the default
        paths for WP directories

        ie,
            don't assume wp-contents is a subdir of ABSPATH
            don't asssume uploads is a subdir of wp-contents or even 'uploads'
    */
    public function getWPDirFullPath( $wp_dir ) {
        $full_path = '';

        switch ( $wp_dir ) {
            case 'wp-content':
                $full_path = WP_CONTENT_DIR;

                break;

            case 'uploads':
                $upload_dir_info = wp_upload_dir();
                $full_path = $upload_dir_info['basedir'];

                break;

            case 'wp-includes':
                // NOTE: currently cannot be changed outside WP core
                $full_path = ABSPATH . WPINC;

                break;

            case 'plugins':
                $full_path = WP_PLUGIN_DIR;

                break;

            case 'theme-root':
                $full_path = get_theme_root();

                break;

            case 'parent-theme':
                $full_path = get_template_directory();

                break;

            case 'child-theme':
                $full_path = get_stylesheet_directory();

                break;
        }

        return rtrim( $full_path, '/' );
    }

    public function getWPDirNameOnly( $wp_dir ) {
        $wp_dir_name = '';

        switch ( $wp_dir ) {
            case 'child-theme':
            case 'parent-theme':
            case 'wp-content':
            case 'wp-includes':
            case 'uploads':
            case 'theme-root':
            case 'plugins':
                $wp_dir_name = $this->getLastPathSegment(
                    $this->getWPDirFullPath( $wp_dir )
                );

                break;

        }

        return rtrim( $wp_dir_name, '/' );
    }

    public function getLastPathSegment( $path ) {
        $path_segments = explode( '/', $path );

        return end( $path_segments );
    }

    /*
        For when we have a site like domain.com
        and wp-content themes and plugins are under /wp/
    */
    public function getWPContentSubDirectory() {
        $parsed_URL = parse_url( $this->parent_theme_URL );
        $path_segments = explode( '/', $parsed_URL['path'] );

        /*
            Returns:

            [0] =>
            [1] => wp
            [2] => wp-content
            [3] => themes
            [4] => twentyseventeen

        */

        if ( count( $path_segments ) === 5 ) {
            return $path_segments[1] . '/';
        } else {
            return '';
        }
    }
}

