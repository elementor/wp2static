<?php

namespace WP2Static;

class Archive extends Base {

    public function __construct() {
        $this->loadSettings(
            array( 'wpenv' )
        );

        $this->path = $this->settings['wp_uploads_path'] .
            '/wp2static-exported-site/';
        $this->name = '';
        $this->crawl_list = '';
        $this->export_log = '';
    }

    public function currentArchiveExists() {
        return is_dir( $this->path );
    }

    public function create() {
        $this->name = $this->settings['wp_uploads_path'] .
            '/wp2static-exported-site';

        $this->path = $this->name . '/';
        $this->name = basename( $this->path );

        if ( ! wp_mkdir_p( $this->path ) ) {
            $err = "Couldn't create archive directory:" .  $this->path;
            WsLog::l( $err );
            throw new Exception( $err );
        }
    }
}

