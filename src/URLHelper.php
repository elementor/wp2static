<?php

namespace WP2Static;

class URLHelper {
    /*
     * Takes either an http or https URL and returns a // protocol-relative URL
     *
     * @param string URL either http or https
     * @return string URL protocol-relative
     */
    public static function getProtocolRelativeURL( $url ) {
        $protocol_relative_url = str_replace(
            array(
                'https:',
                'http:',
            ),
            array(
                '',
                '',
            ),
            $url
        );

        return $protocol_relative_url;
    }

    public static function startsWithHash( $url ) {
        // TODO: this won't fire for absolute URLs unless strip site_url first?
        // quickly abort for invalid URLs
        if ( $url[0] === '#' ) {
            return true;
        }
    }

    public static function isMailto( $url ) {
        if ( substr( $url, 0, 7 ) == 'mailto:' ) {
            return true;
        }
    }

    public static function isProtocolRelative( $url ) {
        if ( $url[0] === '/' ) {
            if ( $url[1] === '/' ) {
                return true;
            }
        }

        return false;
    }

    public static function protocolRelativeToAbsoluteURL( $url, $site_url ) {
        if ( ! is_string( $site_url ) ) {
            $err = 'Site URL not defined ';
            WsLog::l( $err );
            throw new Exception( $err );
        }

        $url = str_replace(
            self::getProtocolRelativeURL( $site_url ),
            $site_url,
            $url
        );

        return $url;
    }

    /*
     * Detect if a URL belongs to our WP site
     * We check against known internal prefixes and WP site host
     *
     * @param string $link Any potential URL
     * @param string $site_url_host WP Site URL host
     * @return boolean true for explicit match
     */
    public static function isInternalLink( $url, $site_url_host ) {
        // quickly match known internal links   ./   ../   /
        $first_char = $url[0];

        if ( $first_char === '.' || $first_char === '/' ) {
            return true;
        }

        $url_host = parse_url( $url, PHP_URL_HOST );

        if ( $url_host === $site_url_host ) {
            return true;
        }

        return false;
    }
}
