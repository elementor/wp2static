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
}
