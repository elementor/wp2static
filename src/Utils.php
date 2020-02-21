<?php

namespace WP2Static;

use Exception;

class Utils {
    /*
     * Takes either an http or https URL and returns a // protocol-relative URL
     *
     * @param string $start timer start
     * @param string $end timer end
     * @return float time between start and finish
     */
    public static function microtime_diff(
        string $start,
        string $end = null
    ) : float {
        if ( ! $end ) {
            $end = microtime();
        }

        list( $start_usec, $start_sec ) = explode( ' ', $start );
        list( $end_usec, $end_sec ) = explode( ' ', $end );

        $diff_sec = intval( $end_sec ) - intval( $start_sec );
        $diff_usec = floatval( $end_usec ) - floatval( $start_usec );

        return floatval( $diff_sec ) + $diff_usec;
    }

    /*
     * Adjusts the max_execution_time ini option
     *
     */
    public static function set_max_execution_time() : void {
        if (
            ! function_exists( 'set_time_limit' ) ||
            ! function_exists( 'ini_get' )
        ) {
            return;
        }

        $current_max_execution_time  = ini_get( 'max_execution_time' );
        $proposed_max_execution_time =
            ( $current_max_execution_time == 30 ) ? 31 : 30;
        set_time_limit( $proposed_max_execution_time );
        $current_max_execution_time = ini_get( 'max_execution_time' );

        if ( $proposed_max_execution_time == $current_max_execution_time ) {
            set_time_limit( 0 );
        }
    }
}
