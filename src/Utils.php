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
}
