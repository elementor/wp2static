<?php

// TODO: better named WPEnvironment
class WPSite {

    public function __construct() {
        $wp_upload_path_and_url = wp_upload_dir();
        $this->uploads_path = $wp_upload_path_and_url['basedir'];
        $this->uploads_url = $wp_upload_path_and_url['baseurl'];
        $this->site_path = ABSPATH;
        $this->site_url = get_site_url() . '/';
        $this->plugin_path = plugins_url('/', __FILE__);
        $this->detect_base_url();
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
        $this->subdirectory = '';

        // case for when WP is installed in a different place then being served
        if ( $site_url !== $home ) {
            $this->subdirectory = '/mysubdirectory';
        }

        $base_url = parse_url($site_url);

        if ( array_key_exists('path', $base_url ) && $base_url['path'] != '/' ) {
            $this->subdirectory = $base_url['path'];
        }
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

        // DEBUG: 
        error_log('wp-content:');
        error_log($this->getWPDirFullPath('wp-content'));
        error_log($this->getWPDirNameOnly('wp-content'));

        error_log('uploads:');
        error_log($this->getWPDirFullPath('uploads'));
        error_log($this->getWPDirNameOnly('uploads'));

        error_log('wp-includes:');
        error_log($this->getWPDirFullPath('wp-includes'));
        error_log($this->getWPDirNameOnly('wp-includes'));

        error_log('plugins:');
        error_log($this->getWPDirFullPath('plugins'));
        error_log($this->getWPDirNameOnly('plugins'));

        error_log('theme-root:');
        error_log($this->getWPDirFullPath('theme-root'));
        error_log($this->getWPDirNameOnly('theme-root'));

        error_log('active-parent-theme:');
        error_log($this->getWPDirFullPath('active-parent-theme'));
        error_log($this->getWPDirNameOnly('active-parent-theme'));

        error_log('active-child-theme:');
        error_log($this->getWPDirFullPath('active-child-theme'));
        error_log($this->getWPDirNameOnly('active-child-theme'));

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

            case 'active-parent-theme':
                $full_path = get_template_directory() ;

                break;

            case 'active-child-theme':
                $full_path = get_stylesheet_directory() ;

                break;
        }

        return rtrim( $full_path, '/' );
    }

    public function getWPDirNameOnly( $wp_dir ) {
        $wp_dir_name = '';

        switch ( $wp_dir ) {
            case 'active-child-theme':
            case 'active-parent-theme':
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
