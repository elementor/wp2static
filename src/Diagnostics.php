<?php

namespace WP2Static;

class Diagnostics {

    /**
     * Check whether site it publicly accessible
     */
    public static function check_local_dns_resolution() : string {
        if ( self::isEnabled( 'shell_exec' ) ) {
            $site_host = parse_url( SiteInfo::getUrl( 'site' ), PHP_URL_HOST );

            $output =
                shell_exec( "/usr/sbin/traceroute $site_host" );

            if ( ! is_string( $output ) ) {
                return 'Unknown';
            }

            $hops_in_route = substr_count( $output, PHP_EOL );

            $resolves_to_local_ip4 =
                ( strpos( $output, '127.0.0.1' ) !== false );
            $resolves_to_local_ip6 = ( strpos( $output, '::1' ) !== false );

            $resolves_locally =
                $resolves_to_local_ip4 || $resolves_to_local_ip6;

            if ( $resolves_locally && $hops_in_route < 2 ) {
                return 'Yes';
            } else {
                return 'No';
            }
        } else {
            WsLog::l( 'no shell_exec available for local DNS checking' );
            return 'Unknown';
        }
    }

    /**
     * Check whether a PHP function is enabled
     *
     * @param string $function_name list of menu items
     */
    public static function isEnabled( string $function_name ) : bool {
        $disable_functions = ini_get( 'disable_functions' );

        if ( ! is_string( $disable_functions ) ) {
            return false;
        }

        $is_enabled =
            is_callable( $function_name ) &&
            false === stripos( $disable_functions, $function_name );

        return $is_enabled;
    }

}
