<?php

namespace WP2Static;

use Exception;

class Archive extends Base {

    public function __construct() {
        $this->path = SiteInfo::getPath('uploads') .
            'wp2static-exported-site/';
        $this->crawl_list = '';
        $this->export_log = '';
    }

    public function currentArchiveExists() {
        return is_dir( $this->path );
    }

    public function create() {

        if ( ! wp_mkdir_p( $this->path ) ) {
            $err = "Couldn't create archive directory:" . $this->path;
            WsLog::l( $err );
            throw new Exception( $err );
        }
    }
}

