<?php

namespace WP2Static;

class JobQueue {

    public static function createTable() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_jobs';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            job_type VARCHAR(30) NOT NULL,
            status VARCHAR(30) NOT NULL,
            duration SMALLINT(6) UNSIGNED NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Add Job to queue
     *
     * @param string $job_type Type of job
     * ie detect, crawl, post_process, deploy
     */
    public static function addJob( string $job_type ) : void {
        WsLog::l( 'Adding job: ' . $job_type );

        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_jobs';

        // TODO: squash any of same job_types with 'waiting' status
        // setting this one to be the one that runs next

        $query_string = "INSERT INTO $table_name (job_type, status) VALUES (%s, 'waiting');";
        $query = $wpdb->prepare( $query_string, $job_type );

        $wpdb->query( $query );
    }

    /**
     *  Get all crawlable URLs
     *
     *  @return string[] All crawlable URLs
     */
    public static function getJobs() : array {
        global $wpdb;
        $urls = [];

        $table_name = $wpdb->prefix . 'wp2static_jobs';

        $rows = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id DESC" );

        foreach ( $rows as $row ) {
            $urls[] = $row;
        }

        return $urls;
    }

    /**
     *  Check for any jobs in progress
     *
     *  @return bool All waiting jobs
     */
    public static function jobsInProgress() : bool {
        global $wpdb;
        $jobs = [];

        $table_name = $wpdb->prefix . 'wp2static_jobs';

        $jobs_in_progress = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_name
            WHERE status = 'processing'"
        );

        return $jobs_in_progress > 0;
    }

    /**
     *  Get all waiting jobs
     *
     *  @return mixed[] All waiting jobs
     */
    public static function getProcessableJobs() : array {
        global $wpdb;
        $jobs = [];

        $table_name = $wpdb->prefix . 'wp2static_jobs';

        $rows = $wpdb->get_results(
            "SELECT * FROM $table_name
            WHERE status = 'waiting'
            ORDER BY id ASC"
        );

        foreach ( $rows as $row ) {
            $jobs[] = $row;
        }

        return $jobs;
    }

    /*
        Skip processing jobs where a more recent job of same type exists

    */
    public static function squashQueue() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_jobs';

        // TODO: loop for each job_type
        $job_types = [
            'detect',
            'crawl',
            'post_process',
            'deploy',
        ];

        foreach ( $job_types as $job_type ) {
            // get all jobs for a type where status is 'waiting'
            $waiting_jobs = $wpdb->get_results(
                "SELECT * FROM $table_name
                WHERE job_type = '$job_type'
                AND status = 'waiting'
                ORDER BY created_at DESC"
            );

            // abort if less than 2 jobs of same type in waiting status
            if ( $waiting_jobs < 2 ) {
                WsLog::l( 'less than 2 jobs for this type, continuing' );
                WsLog::l( (string) count( $waiting_jobs ) );
                continue;
            }

            // select all
            $waiting_jobs = $wpdb->get_results(
                "SELECT * FROM $table_name
                WHERE job_type = '$job_type'
                AND status = 'waiting'
                ORDER BY created_at DESC"
            );

            // remove latest one
            array_shift( $waiting_jobs );

            // set all but most recent one to 'skipped'
            foreach ( $waiting_jobs as $waiting_job ) {
                $wpdb->update(
                    $table_name,
                    [ 'status' => 'skipped' ],
                    [ 'id' => $waiting_job->id ]
                );

            }
        }
    }

    public static function setStatus( int $id, string $status ) : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_jobs';

        $wpdb->update(
            $table_name,
            [ 'status' => $status ],
            [ 'id' => $id ]
        );
    }

    /**
     *  Get total count of jobs
     *
     *  @return int Total jobs
     */
    public static function getTotalJobs() : int {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_jobs';

        $total_jobs = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );

        return $total_jobs;
    }

    /**
     *  Get count of waiting jobs
     *
     *  @return int Waiting jobs
     */
    public static function getWaitingJobs() : int {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_jobs';

        $total_jobs = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE status = 'waiting'" );

        return $total_jobs;
    }

    /**
     *  Clear JobQueue via truncate or deletion
     */
    public static function truncate() : void {
        WsLog::l( 'Deleting all jobs from JobQueue' );

        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_jobs';

        $wpdb->query( "TRUNCATE TABLE $table_name" );

        $total_jobs = self::getTotalJobs();

        if ( $total_jobs > 0 ) {
            WsLog::l( 'failed to truncate JobQueue: try deleting instead' );
        }
    }
}
