<?php
/**
 * General helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class autoptimizeUtils
{
    /**
     * Returns true when mbstring is available.
     *
     * @param bool|null $override Allows overriding the decision.
     *
     * @return bool
     */
    public static function mbstring_available( $override = null )
    {
        static $available = null;

        if ( null === $available ) {
            $available = \extension_loaded( 'mbstring' );
        }

        if ( null !== $override ) {
            $available = $override;
        }

        return $available;
    }

    /**
     * Returns true when iconv is available.
     *
     * @param bool|null $override Allows overriding the decision.
     *
     * @return bool
     */
    public static function iconv_available( $override = null )
    {
        static $available = null;

        if ( null === $available ) {
            $available = \extension_loaded( 'iconv' );
        }

        if ( null !== $override ) {
            $available = $override;
        }

        return $available;
    }

    /**
     * Multibyte-capable strpos() if support is available on the server.
     * If not, it falls back to using \strpos().
     *
     * @param string      $haystack Haystack.
     * @param string      $needle   Needle.
     * @param int         $offset   Offset.
     * @param string|null $encoding Encoding. Default null.
     *
     * @return int|false
     */
    public static function strpos( $haystack, $needle, $offset = 0, $encoding = null )
    {
        if ( self::mbstring_available() ) {
            return ( null === $encoding ) ? \mb_strpos( $haystack, $needle, $offset ) : \mb_strlen( $haystack, $needle, $offset, $encoding );
        } elseif ( self::iconv_available() ) {
            return ( null === $encoding ) ? \iconv_strpos( $haystack, $needle, $offset ) : \iconv_strpos( $haystack, $needle, $offset, $encoding );
        } else {
            return \strpos( $haystack, $needle, $offset );
        }
    }

    /**
     * Attempts to return the number of characters in the given $string if
     * mbstring or iconv is available. Returns the number of bytes
     * (instead of characters) as fallback.
     *
     * @param string      $string   String.
     * @param string|null $encoding Encoding.
     *
     * @return int Number of charcters or bytes in given $string
     *             (characters if/when supported, bytes otherwise).
     */
    public static function strlen( $string, $encoding = null )
    {
        if ( self::mbstring_available() ) {
            return ( null === $encoding ) ? \mb_strlen( $string ) : \mb_strlen( $string, $encoding );
        } elseif ( self::iconv_available() ) {
            return ( null === $encoding ) ? @iconv_strlen( $string ) : @iconv_strlen( $string, $encoding );
        } else {
            return \strlen( $string );
        }
    }

    /**
     * Our wrapper around implementations of \substr_replace()
     * that attempts to not break things horribly if at all possible.
     * Uses mbstring and/or iconv if available, before falling back to regular
     * substr_replace() (which works just fine in the majority of cases).
     *
     * @param string      $string      String.
     * @param string      $replacement Replacement.
     * @param int         $start       Start offset.
     * @param int|null    $length      Length.
     * @param string|null $encoding    Encoding.
     *
     * @return string
     */
    public static function substr_replace( $string, $replacement, $start, $length = null, $encoding = null )
    {
        if ( self::mbstring_available() ) {
            $strlen = self::strlen( $string, $encoding );

            if ( $start < 0 ) {
                if ( -$start < $strlen ) {
                    $start = $strlen + $start;
                } else {
                    $start = 0;
                }
            } elseif ( $start > $strlen ) {
                $start = $strlen;
            }

            if ( null === $length || '' === $length ) {
                $start2 = $strlen;
            } elseif ( $length < 0 ) {
                $start2 = $strlen + $length;
                if ( $start2 < $start ) {
                    $start2 = $start;
                }
            } else {
                $start2 = $start + $length;
            }

            if ( null === $encoding ) {
                $leader  = $start ? \mb_substr( $string, 0, $start ) : '';
                $trailer = ( $start2 < $strlen ) ? \mb_substr( $string, $start2, null ) : '';
            } else {
                $leader  = $start ? \mb_substr( $string, 0, $start, $encoding ) : '';
                $trailer = ( $start2 < $strlen ) ? \mb_substr( $string, $start2, null, $encoding ) : '';
            }

            return "{$leader}{$replacement}{$trailer}";
        } elseif ( self::iconv_available() ) {
            $strlen = self::strlen( $string, $encoding );

            if ( $start < 0 ) {
                $start = \max( 0, $strlen + $start );
                $start = $strlen + $start;
                if ( $start < 0 ) {
                    $start = 0;
                }
            } elseif ( $start > $strlen ) {
                $start = $strlen;
            }

            if ( $length < 0 ) {
                $length = \max( 0, $strlen - $start + $length );
            } elseif ( null === $length || ( $length > $strlen ) ) {
                $length = $strlen;
            }

            if ( ( $start + $length ) > $strlen ) {
                $length = $strlen - $start;
            }

            if ( null === $encoding ) {
                return self::iconv_substr( $string, 0, $start ) . $replacement . self::iconv_substr( $string, $start + $length, $strlen - $start - $length );
            }

            return self::iconv_substr( $string, 0, $start, $encoding ) . $replacement . self::iconv_substr( $string, $start + $length, $strlen - $start - $length, $encoding );
        }

        return ( null === $length ) ? \substr_replace( $string, $replacement, $start ) : \substr_replace( $string, $replacement, $start, $length );
    }

    /**
     * Wrapper around iconv_substr().
     *
     * @param string      $s        String.
     * @param int         $start    Start offset.
     * @param int|null    $length   Length.
     * @param string|null $encoding Encoding.
     *
     * @return string
     */
    protected static function iconv_substr( $s, $start, $length = null, $encoding = null )
    {
        if ( $start < 0 ) {
            $start = self::strlen( $s, $encoding ) + $start;
            if ( $start < 0 ) {
                $start = 0;
            }
        }

        if ( null === $length ) {
            $length = 2147483647;
        } elseif ( $length < 0 ) {
            $length = self::strlen( $s, $encoding ) + ( $length - $start );
            if ( $length < 0 ) {
                return '';
            }
        }

        return (string) ( null === $encoding ) ? \iconv_substr( $s, $start, $length ) : \iconv_substr( $s, $start, $length, $encoding );
    }

    /**
     * Decides whether this is a "subdirectory site" or not.
     *
     * @param bool $override Allows overriding the decision when needed.
     *
     * @return bool
     */
    public static function siteurl_not_root( $override = null )
    {
        static $subdir = null;

        if ( null === $subdir ) {
            $parts  = self::get_ao_wp_site_url_parts();
            $subdir = ( isset( $parts['path'] ) && ( '/' !== $parts['path'] ) );
        }

        if ( null !== $override ) {
            $subdir = $override;
        }

        return $subdir;
    }

    /**
     * Parse AUTOPTIMIZE_WP_SITE_URL into components using \parse_url(), but do
     * so only once per request/lifecycle.
     *
     * @return array
     */
    public static function get_ao_wp_site_url_parts()
    {
        static $parts = array();

        if ( empty( $parts ) ) {
            $parts = \parse_url( AUTOPTIMIZE_WP_SITE_URL );
        }

        return $parts;
    }

    /**
     * Modify given $cdn_url to include the site path when needed.
     *
     * @param string $cdn_url          CDN URL to tweak.
     * @param bool   $force_cache_miss Force a cache miss in order to be able
     *                                 to re-run the filter.
     *
     * @return string
     */
    public static function tweak_cdn_url_if_needed( $cdn_url, $force_cache_miss = false )
    {
        static $results = array();

        if ( ! isset( $results[ $cdn_url ] ) || $force_cache_miss ) {

            // In order to return unmodified input when there's no need to tweak.
            $results[ $cdn_url ] = $cdn_url;

            // Behind a default true filter for backcompat, and only for sites
            // in a subfolder/subdirectory, but still easily turned off if
            // not wanted/needed...
            if ( autoptimizeUtils::siteurl_not_root() ) {
                $check = apply_filters( 'autoptimize_filter_cdn_magic_path_check', true, $cdn_url );
                if ( $check ) {
                    $site_url_parts = autoptimizeUtils::get_ao_wp_site_url_parts();
                    $cdn_url_parts  = \parse_url( $cdn_url );
                    $schemeless     = self::is_protocol_relative( $cdn_url );
                    $cdn_url_parts  = self::maybe_replace_cdn_path( $site_url_parts, $cdn_url_parts );
                    if ( false !== $cdn_url_parts ) {
                        $results[ $cdn_url ] = self::assemble_parsed_url( $cdn_url_parts, $schemeless );
                    }
                }
            }
        }

        return $results[ $cdn_url ];
    }

    /**
     * When siteurl contans a path other than '/' and the CDN URL does not have
     * a path or it's path is '/', this will modify the CDN URL's path component
     * to match that of the siteurl.
     * This is to support "magic" CDN urls that worked that way before v2.4...
     *
     * @param array $site_url_parts Site URL components array.
     * @param array $cdn_url_parts  CDN URL components array.
     *
     * @return array|false
     */
    public static function maybe_replace_cdn_path( array $site_url_parts, array $cdn_url_parts )
    {
        if ( isset( $site_url_parts['path'] ) && '/' !== $site_url_parts['path'] ) {
            if ( ! isset( $cdn_url_parts['path'] ) || '/' === $cdn_url_parts['path'] ) {
                $cdn_url_parts['path'] = $site_url_parts['path'];
                return $cdn_url_parts;
            }
        }

        return false;
    }

    /**
     * Given an array or components returned from \parse_url(), assembles back
     * the complete URL.
     * If optional
     *
     * @param array $parsed_url URL components array.
     * @param bool  $schemeless Whether the assembled URL should be
     *                          protocol-relative (schemeless) or not.
     *
     * @return string
     */
    public static function assemble_parsed_url( array $parsed_url, $schemeless = false )
    {
        $scheme = isset( $parsed_url['scheme'] ) ? $parsed_url['scheme'] . '://' : '';
        if ( $schemeless ) {
            $scheme = '//';
        }
        $host     = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';
        $port     = isset( $parsed_url['port'] ) ? ':' . $parsed_url['port'] : '';
        $user     = isset( $parsed_url['user'] ) ? $parsed_url['user'] : '';
        $pass     = isset( $parsed_url['pass'] ) ? ':' . $parsed_url['pass'] : '';
        $pass     = ( $user || $pass ) ? "$pass@" : '';
        $path     = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '';
        $query    = isset( $parsed_url['query'] ) ? '?' . $parsed_url['query'] : '';
        $fragment = isset( $parsed_url['fragment'] ) ? '#' . $parsed_url['fragment'] : '';

        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /**
     * Returns true if given $url is protocol-relative.
     *
     * @param string $url URL to check.
     *
     * @return bool
     */
    public static function is_protocol_relative( $url )
    {
        $result = false;

        if ( ! empty( $url ) ) {
            $result = ( '/' === $url{1} ); // second char is `/`.
        }

        return $result;
    }

    /**
     * Canonicalizes the given path regardless of it existing or not.
     *
     * @param string $path Path to normalize.
     *
     * @return string
     */
    public static function path_canonicalize( $path )
    {
        $patterns     = array(
            '~/{2,}~',
            '~/(\./)+~',
            '~([^/\.]+/(?R)*\.{2,}/)~',
            '~\.\./~',
        );
        $replacements = array(
            '/',
            '/',
            '',
            '',
        );

        return preg_replace( $patterns, $replacements, $path );
    }

    /**
     * Checks to see if 3rd party services are available and stores result in option
     *
     * @param string $return_result should we return resulting service status array (default no).
     *
     * @return none if $return_result is false (default), array if $return_result is true.
     */
    public static function check_service_availability( $return_result = false )
    {
        $service_availability_resp = wp_remote_get( 'https://misc.optimizingmatters.com/api/autoptimize_service_availablity.json?from=aomain&ver=' . AUTOPTIMIZE_PLUGIN_VERSION );
        if ( ! is_wp_error( $service_availability_resp ) ) {
            if ( '200' == wp_remote_retrieve_response_code( $service_availability_resp ) ) {
                $availabilities = json_decode( wp_remote_retrieve_body( $service_availability_resp ), true );
                if ( is_array( $availabilities ) ) {
                    update_option( 'autoptimize_service_availablity', $availabilities );
                    if ( $return_result ) {
                        return $availabilities;
                    }
                }
            }
        }
    }

    /**
     * Returns true if the string is a valid regex.
     *
     * @param string $string String, duh.
     *
     * @return bool
     */
    public static function str_is_valid_regex( $string )
    {
        set_error_handler( function() {}, E_WARNING );
        $is_regex = ( false !== preg_match( $string, '' ) );
        restore_error_handler();

        return $is_regex;
    }
}
