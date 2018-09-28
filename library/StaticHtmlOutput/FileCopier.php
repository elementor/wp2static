<?php

// TODO: if this fails to locate the local file for the remote,
// it should fall back to regular crawl processing method
// (where response status will also be checked in case of 404)
class FileCopier {
    public function __construct( $url, $wp_site_url, $wp_site_path ) {
        $this->url = $url;
        $this->wp_site_url = $wp_site_url;
        $this->wp_site_path = $wp_site_path;
    }

    public function getLocalFileForURL() {
        /*
        take the public URL and return the location on the filesystem

        ie http://domain.com/wp-content/somefile.jpg

        replace the WP site url with the WP site path

        ie

        replace http://domain.com/ with /var/www/domain.com/html/

        resulting in

        ie /var/www/domain.com/html/wp-content/somefile.jpg

        */
        return( str_replace( $this->wp_site_url, $this->wp_site_path, $this->url ) );
    }

    public function copyFile( $archive_dir ) {
        $urlInfo = parse_url( $this->url );
        $pathInfo = array();

        $local_file = $this->getLocalFileForURL();

        // TODO: here we can allow certain external host files to be crawled
        if ( ! isset( $urlInfo['path'] ) ) {
            return false;
        }

        $pathInfo = pathinfo( $urlInfo['path'] );

        // set fileDir to the directory name else empty
        $fileDir = $archive_dir . ( isset( $pathInfo['dirname'] ) ? $pathInfo['dirname'] : '' );

        if ( ! file_exists( $fileDir ) ) {
            wp_mkdir_p( $fileDir );
        }

        $fileExtension = $pathInfo['extension'];

        $fileName = $fileDir . '/' . $pathInfo['filename'] . '.' . $fileExtension;

        copy( $local_file, $fileName );
    }
}
