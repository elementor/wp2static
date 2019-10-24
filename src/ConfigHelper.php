<?php

namespace WP2Static;

use Exception;

class ConfigHelper {
    /*
     * Checks we can adjust the max_execution_time ini option
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
