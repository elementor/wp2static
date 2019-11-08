<?php
/*
    SimpleRewriter

    A processed version of a StaticSite, with URLs rewritten, folders renamed
    and other modifications made to prepare it for a Deployer
*/

namespace WP2Static;

class SimpleRewriter {

    /**
     * SimpleRewriter constructor
     *
     */
    public function __construct() {

    }

    /**
     * Rewrite URLs in file to destination_url
     *
     * @param string $filename file to rewrite URLs in
     * @throws WP2StaticException
     */
    public static function rewrite( $filename ) : void {
        $destination_url = apply_filters( 'wp2static_set_destination_url', '' ); 

        $wordpress_site_url =
            apply_filters(
                'wp2static_set_wordpress_site_url',
                SiteInfo::getUrl('site') ); 

        $file_contents = file_get_contents( $filename );

        // error_log( $wordpress_site_url );
        // error_log( $destination_url );die();

        $rewritten_contents = str_replace(
            $wordpress_site_url,
            $destination_url,
            $file_contents);

        file_put_contents( $filename, $rewritten_contents );
    }
}

