<?php
/*
    SimpleRewriter

    A processed version of a StaticSite, with URLs rewritten, folders renamed
    and other modifications made to prepare it for a Deployer
*/

namespace WP2Static;

class SimpleRewriter {

    /**
     * Rewrite URLs in file to destination_url
     *
     * @param string $filename file to rewrite URLs in
     * @throws WP2StaticException
     */
    public static function rewrite( string $filename ) : void {
        $file_contents = file_get_contents( $filename );

        if ( $file_contents === false ) {
            $file_contents = '';
        }

        $rewritten_contents = self::rewriteFileContents( $file_contents );

        file_put_contents( $filename, $rewritten_contents );
    }

    /**
     * Rewrite URLs in a string to destination_url
     *
     * @param string $file_contents
     * @return string
     */
    public static function rewriteFileContents( string $file_contents ) : string
    {
        // TODO: allow empty file saving here? Exception for style.css
        if ( ! $file_contents ) {
            return '';
        }

        if ( (int) CoreOptions::getValue( 'skipURLRewrite' ) === 1 ) {
            return $file_contents;
        }

        $destination_url = apply_filters(
            'wp2static_set_destination_url',
            CoreOptions::getValue( 'deploymentURL' )
        );

        $wordpress_site_url = apply_filters(
            'wp2static_set_wordpress_site_url',
            untrailingslashit( SiteInfo::getUrl( 'site' ) )
        );

        $wordpress_site_url = untrailingslashit( $wordpress_site_url );
        $destination_url = untrailingslashit( $destination_url );
        $destination_url_rel = URLHelper::getProtocolRelativeURL( $destination_url );
        $destination_url_rel_c = addcslashes( $destination_url_rel, '/' );

        $replacement_patterns = [
            $wordpress_site_url => $destination_url,
            URLHelper::getProtocolRelativeURL( $wordpress_site_url ) =>
                URLHelper::getProtocolRelativeURL( $destination_url ),
            addcslashes( URLHelper::getProtocolRelativeURL( $wordpress_site_url ), '/' ) =>
                addcslashes( URLHelper::getProtocolRelativeURL( $destination_url ), '/' ),
        ];

        $hosts = CoreOptions::getLineDelimitedBlobValue( 'hostsToRewrite' );

        foreach ( $hosts as $host ) {
            if ( $host ) {
                $host_rel = URLHelper::getProtocolRelativeURL( 'http://' . $host );

                $replacement_patterns[ 'http:' . $host_rel ] = $destination_url;
                $replacement_patterns[ 'https:' . $host_rel ] = $destination_url;
                $replacement_patterns[ $host_rel ] = $destination_url_rel;
                $replacement_patterns[ addcslashes( $host_rel, '/' ) ] = $destination_url_rel_c;
            }
        }

        $rewritten_contents = strtr(
            $file_contents,
            $replacement_patterns
        );

        return $rewritten_contents;
    }
}

