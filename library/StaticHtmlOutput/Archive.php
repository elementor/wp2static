<?php

class Archive {

    public function __construct() {
        $target_settings = array(
            'general',
            'wpenv',
        );

        if ( isset( $_POST['selected_deployment_option'] ) ) {
            require_once dirname( __FILE__ ) .
                '/../StaticHtmlOutput/PostSettings.php';

            $this->settings = WPSHO_PostSettings::get( $target_settings );

        } else {
            error_log('TODO: load settings from DB');
        }

        $this->path = '';
        $this->name = '';
        $this->crawl_list = '';
        $this->export_log = '';
    }

    public function setToCurrentArchive() {
        // makes this archive's instance link to the current export's
        $handle = fopen(
            $this->settings['wp_uploads_path'] . '/WP-STATIC-CURRENT-ARCHIVE',
            'r'
        );

        $this->path = stream_get_line( $handle, 0 );
        $this->name = basename( $this->path );
    }

    public function currentArchiveExists() {
        return is_file( $this->settings['wp_uploads_path'] .
            '/WP-STATIC-CURRENT-ARCHIVE' );
    }

    public function create() {
        $this->name = $this->settings['working_directory'] .
            '/wp-static-html-output-' . time();

        $this->path = $this->name . '/';
        $this->name = basename( $this->path );

        if ( wp_mkdir_p( $this->path ) ) {
            file_put_contents(
                $this->settings['wp_uploads_path'] . '/WP-STATIC-CURRENT-ARCHIVE',
                $this->path
            );
        } else {
            error_log( "Couldn't create archive directory at " . $this->path );
        }
    }
}
