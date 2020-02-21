<?php

namespace WP2Static;

/*
    Manage WP Cron schedules for processing job queue

*/
class WPCron {

    public static function setRecurringEvent( int $interval ) : void {
        $next_timestamp = wp_next_scheduled( 'wp2static_process_queue' );

        if ( $interval === 0 ) {

            if ( ! $next_timestamp ) {
                return;
            }

            wp_unschedule_event( $next_timestamp, 'wp2static_process_queue' );
            return;
        }

        // remove existing first
        if ( $next_timestamp ) {
            wp_unschedule_event( $next_timestamp, 'wp2static_process_queue' );
        }

        $interval = $interval . 'min' . ( $interval > 1 ? 's' : '' );

        error_log( PHP_EOL . $interval . PHP_EOL );

        $result = wp_schedule_event( time(), $interval, 'wp2static_process_queue' );

        if ( ! $result ) {
            error_log( 'Unable to schedule WP Cron recurring event' );
        }
    }

    public static function clearRecurringEvent() : void {
        $next_timestamp = wp_next_scheduled( 'wp2static_process_queue' );

        if ( ! $next_timestamp ) {
            return;
        }

        wp_unschedule_event( $next_timestamp, 'wp2static_process_queue' );
    }

    /**
     * Register custom WP Cron schedule intervals
     *
     * @param mixed[] $schedules array of CRON schedules
     * @return mixed[] array of CRON schedules
     */
    public static function wp2static_custom_cron_schedules( array $schedules ) : array {
        $schedules['1min'] = [
            'interval' => 1 * MINUTE_IN_SECONDS,
            'display' => 'Every minute',
        ];

        $schedules['5mins'] = [
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display' => 'Every 5 minutes',
        ];

        $schedules['10mins'] = [
            'interval' => 10 * MINUTE_IN_SECONDS,
            'display' => 'Every 10 minutes',
        ];

        return $schedules;
    }


}
