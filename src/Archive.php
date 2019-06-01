<?php

namespace WP2Static;

class Archive extends Base {
    public $path;

    public function __construct() {
        $this->path = SiteInfo::getPath( 'uploads' ) .
            'wp2static-exported-site/';
    }

    public function currentArchiveExists() : bool {
        return is_dir( $this->path );
    }

    /**
     * Create archive
     *
     * @throws WP2StaticException
     */
    public function create() : void {
        if ( ! wp_mkdir_p( $this->path ) ) {
            $err = "Couldn't create archive directory:" . $this->path;
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }
    }
}

