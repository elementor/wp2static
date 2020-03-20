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
     */
    public function __construct() {

    }

    /**
     * Rewrite URLs in file to destination_url
     *
     * @param string $filename file to rewrite URLs in
     * @throws WP2StaticException
     */
    public static function rewrite( string $filename ) : void {
        $destination_url = apply_filters(
            'wp2static_set_destination_url',
            untrailingslashit( CoreOptions::getValue( 'deploymentURL' ) )
        );

        $wordpress_site_url =
            apply_filters(
                'wp2static_set_wordpress_site_url',
                untrailingslashit( SiteInfo::getUrl( 'site' ) )
            );

        $file_contents = file_get_contents( $filename );

        // TODO: allow empty file saving here? Exception for style.css
        if ( ! $file_contents ) {
            return;
        }

        $search_patterns = [
            $wordpress_site_url,
            addcslashes( $wordpress_site_url, '/' ),
            URLHelper::getProtocolRelativeURL( $wordpress_site_url ),
            addcslashes( URLHelper::getProtocolRelativeURL( $wordpress_site_url ), '/' ),
        ];

        $replace_patterns = [
            $destination_url,
            addcslashes( $destination_url, '/' ),
            URLHelper::getProtocolRelativeURL( $destination_url ),
            addcslashes( URLHelper::getProtocolRelativeURL( $destination_url ), '/' ),
        ];

        $rewritten_contents = str_replace(
            $search_patterns,
            $replace_patterns,
            $file_contents
        );

        file_put_contents( $filename, $rewritten_contents );
    }
}

