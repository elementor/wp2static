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

        // There was an improper unique index which we must be sure to remove
        if ( 1 === $wpdb->query( "SHOW INDEX FROM $table_name WHERE KEY_NAME = 'status'" ) ) {
            $wpdb->query( "DROP INDEX status ON $table_name" );
        }

        Controller::ensureIndex(
            $table_name,
            'status2',
            "CREATE INDEX status2 ON $table_name (status)"
        );
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
     *  Get all jobs
     *
     *  @return string[] All jobs
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

    /**
     * Get count of jobs organized by type
     *
     * @return int[] keys are job type and values are count
     */
    public static function getJobCountByType() : array {
        global $wpdb;
        $jobs = [];

        $table_name = $wpdb->prefix . 'wp2static_jobs';
        $query = "SELECT job_type, count(*) FROM $table_name GROUP BY job_type";

        $rows = $wpdb->get_results( $query, 'ARRAY_N' );
        foreach ( $rows as $row ) {
            $jobs[ $row[0] ] = $row[1];
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

    public static function getWaitingJobs() : int {
        return static::getWaitingJobsCount();
    }

    /**
     *  Get count of waiting jobs
     *
     *  @return int Waiting jobs
     */
    public static function getWaitingJobsCount() : int {
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

    /**
     *  Detect any 'processing' jobs that are not running and change status to 'failed'.
     *
     *  @throws \Throwable
     */
    public static function markFailedJobs() : void {
        global $wpdb;

        $job_types = [ 'detect', 'crawl', 'post_process', 'deploy' ];
        $table_name = $wpdb->prefix . 'wp2static_jobs';

        $wpdb->query( 'START TRANSACTION' );

        foreach ( $job_types as $type ) {
            try {
                $lock = "{$wpdb->prefix}.wp2static_jobs.$type";
                $query = "SELECT IS_FREE_LOCK('$lock') AS free";
                $free = intval( $wpdb->get_row( $query )->free );

                if ( $free ) {
                    $failed_jobs = $wpdb->query(
                        "UPDATE $table_name
                         SET status = 'failed'
                         WHERE job_type = '$type' AND status = 'processing'"
                    );
                    if ( $failed_jobs ) {
                        $s = $failed_jobs === 1 ? '' : 's';
                        WsLog::l( "$failed_jobs processing $type job$s marked as failed." );
                    }
                }

                $wpdb->query( 'COMMIT' );
            } catch ( \Throwable $e ) {
                $wpdb->query( 'ROLLBACK' );
                throw $e;
            }
        }
    }
}
