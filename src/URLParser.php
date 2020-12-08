<?php

namespace WP2Static;

trait URLParser {

    /**
     * URL encoder according to RFC 3986
     *
     * Originally forked from https://github.com/VIPnytt/SitemapParser
     *
     * Returns a string containing the encoded URL with disallowed characters
     * converted to their percentage encodings.
     *
     * @link http://publicmind.in/blog/url-encoding/
     *
     * @param string $url
     * @return string
     */
    protected function urlEncode( $url ) {
        $reserved = [
            ':' => '!%3A!ui',
            '/' => '!%2F!ui',
            '?' => '!%3F!ui',
            '#' => '!%23!ui',
            '[' => '!%5B!ui',
            ']' => '!%5D!ui',
            '@' => '!%40!ui',
            '!' => '!%21!ui',
            '$' => '!%24!ui',
            '&' => '!%26!ui',
            "'" => '!%27!ui',
            '(' => '!%28!ui',
            ')' => '!%29!ui',
            '*' => '!%2A!ui',
            '+' => '!%2B!ui',
            ',' => '!%2C!ui',
            ';' => '!%3B!ui',
            '=' => '!%3D!ui',
            '%' => '!%25!ui',
        ];
        return (string) preg_replace(
            array_values( $reserved ),
            array_keys( $reserved ),
            rawurlencode( $url )
        );
    }

    /**
     * Validate URL
     *
     * @param string $url
     * @return bool
     */
    protected function urlValidate( $url ) {
        return (
            filter_var( $url, FILTER_VALIDATE_URL ) &&
            ( $parsed = parse_url( $url ) ) !== false &&
            isset( $parsed['host'] ) &&
            isset( $parsed['scheme'] ) &&
            $this->urlValidateHost( $parsed['host'] ) &&
            $this->urlValidateScheme( $parsed['scheme'] )
        );
    }

    /**
     * Validate host name
     *
     * @link https://stackoverflow.com/q/1755144/1668057
     *
     * @param  string $host
     * @return bool
     */
    protected static function urlValidateHost( $host ) {
        return (
            // valid chars check
            preg_match(
                '/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i',
                $host
            )
            // overall length check
            && preg_match( '/^.{1,253}$/', $host )
            // length of each label
            && preg_match( '/^[^\.]{1,63}(\.[^\.]{1,63})*$/', $host )
        );
    }

    /**
     * Validate URL scheme
     *
     * @param  string $scheme
     * @return bool
     */
    protected static function urlValidateScheme( $scheme ) {
        return in_array(
            $scheme,
            [
                'http',
                'https',
            ]
        );
    }
}
