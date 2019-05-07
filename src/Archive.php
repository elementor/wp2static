<?php

namespace WP2Static;

use Exception;

class Archive extends Base {

    public function __construct() {
        $site_info = new SiteInfo();
        $site_info = $site_info->get();

        $this->path = $site_info['uploads_path'] .
            '/wp2static-exported-site/';
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

