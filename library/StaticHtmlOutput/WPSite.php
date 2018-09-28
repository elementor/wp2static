<?php

// TODO: better named WPEnvironment
class WPSite {

    public function __construct() {
        // WP URL paths
        $wp_upload_path_and_url = wp_upload_dir();
        $this->uploads_url = $wp_upload_path_and_url['baseurl'];
        $this->site_url = get_site_url() . '/';

        // WP dir paths
        $this->site_path = ABSPATH;
        $this->plugins_path = $this->getWPDirFullPath( 'plugins' );
        $this->uploads_path = $this->getWPDirFullPath( 'uploads' );
        $this->wp_includes_path = $this->getWPDirFullPath( 'wp-includes' );
        $this->wp_contents_path = $this->getWPDirFullPath( 'wp-contents' );
        $this->theme_root_path = $this->getWPDirFullPath( 'theme-root' );
        $this->parent_theme_path = $this->getWPDirFullPath( 'parent-theme' );
        $this->child_theme_path = $this->getWPDirFullPath( 'child-theme' );
        $this->child_theme_active =
            $this->parent_theme_path !== $this->child_theme_path;

        $this->permalink_structure = get_option( 'permalink_structure' );
        error_log($this->permalink_structure);

        // TODO: pre-generate as much as possible here to avoid
        //       extra overhead during the high cyclical functions

        $this->detect_base_url();
        
        $this->subdirectory = $this->isSiteInstalledInSubdomain();
    }

    public function isSiteInstalledInSubdomain() {
        $parsed_site_url = parse_url( rtrim($this->site_url, '/') );

        if ( isset( $parsed_site_url['path'] ) ) {
            return $parsed_site_url['path'];
        }

        return false;
    }

    public function uploadsPathIsWritable() {
        return $this->uploads_path && is_writable($this->uploads_path);
    }

    public function hasCurlSupport() {
        return extension_loaded('curl');
    }

    public function permalinksAreDefined() {
        return strlen(get_option('permalink_structure'));
    }

    public function detect_base_url() {
        $site_url = get_option( 'siteurl' );
        $home = get_option( 'home' );
    }	

    public function systemRequirementsAreMet() {
        return $this->uploadsPathIsWritable() &&
            $this->hasCurlSupport() &&
            $this->permalinksAreDefined();
    }

    public function getOriginalPaths() {
        $original_directory_names = array();

        $tokens = explode('/', get_template_directory_uri());
        $original_directory_names['theme_dir'] = $tokens[sizeof($tokens)-1];
        $original_directory_names['theme_root'] = $tokens[sizeof($tokens)-2];
        // TODO: use this as a safer way to get wp-content in rewriting areas
        // in case user has changed their wp-content path
        $original_directory_names['wp_contents'] = $tokens[sizeof($tokens)-3];

        $default_upload_dir = wp_upload_dir(); 
        $tokens = explode('/', str_replace(ABSPATH, '/', $default_upload_dir['basedir']));
        $original_directory_names['upload_dir'] = $tokens[sizeof($tokens)-1];


        $tokens = explode('/', WP_PLUGIN_DIR);
        $original_directory_names['plugin_dir'] = $tokens[sizeof($tokens)-1];

        $tokens = explode('/', WPINC);
        $original_directory_names['includes_dir'] = $tokens[sizeof($tokens)-1];

        return $original_directory_names;

    }

    /*
        function below assumes people may have changed the default 
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
                $full_path =  $upload_dir_info['basedir'];

                break;

            case 'wp-includes':
                // NOTE: currently cannot be changed outside WP core
                $full_path =  ABSPATH . WPINC;

                break;

            case 'plugins':
                $full_path = WP_PLUGIN_DIR;

                break;

            case 'theme-root':
                $full_path = get_theme_root();

                break;

            case 'parent-theme':
                $full_path = get_template_directory() ;

                break;

            case 'child-theme':
                $full_path = get_stylesheet_directory() ;

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
}
