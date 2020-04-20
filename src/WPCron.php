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

        WsLog::l( 'Setting auto queue processing interval to ' . $interval );

        $result = wp_schedule_event( time(), $interval, 'wp2static_process_queue' );

        if ( ! $result ) {
            WsLog::l( 'Unable to schedule WP Cron recurring event' );
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

    /**
     * Override WP-Cron to use WP2Static's http basic auth creds if set
     *
     * @param mixed[] $cron_request WP-Cron request
     * @return mixed[] WP-Cron request
     */
    public static function wp2static_cron_with_http_basic_auth( array $cron_request ) : array {
        $auth_user = CoreOptions::getValue( 'basicAuthUser' );
        $auth_password = CoreOptions::getValue( 'basicAuthPassword' );

        if ( ! $auth_user || ! $auth_password ) {
            return $cron_request;
        }

        $headers = [
            'Authorization' =>
                sprintf( 'Basic %s', base64_encode( $auth_user . ':' . $auth_password ) ),
        ];

        $cron_request['args']['headers'] =
            isset( $cron_request['args']['headers'] ) ?
                array_merge( $cron_request['args']['headers'], $headers ) :
                $headers;

        return $cron_request;
    }
}
