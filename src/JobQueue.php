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
    public static function addJob( $job_type ) : void {
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

        $rows = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );

        foreach ( $rows as $row ) {
            $urls[] = $row;
        }

        return $urls;
    }

    /**
     *  Get all waiting jobs
     *
     *  @return string[] All waiting jobs
     */
    public static function getProcessableJobs() : array {
        global $wpdb;
        $jobs = [];

        $table_name = $wpdb->prefix . 'wp2static_jobs';

        $rows = $wpdb->get_results( "SELECT * FROM $table_name WHERE status = 'waiting' ORDER BY created_at DESC" );

        foreach ( $rows as $row ) {
            $jobs[] = $row;
        }

        return $jobs;
    }

    public static function setStatus( $id, $status ) : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_jobs';

        $wpdb->update(
            $table_name,
            ['status' => $status],
            ['id' => $id]
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

        $total_jobs = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

        return $total_jobs;
    }

    /**
     *  Clear JobQueue via truncate or deletion
     *
     */
    public static function truncate() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_jobs';

        $wpdb->query( "TRUNCATE TABLE $table_name" );

        $total_jobs = self::getTotalJobs();

        if ( $total_jobs > 0 ) {
            // TODO: simulate lack of permissios to truncate
            error_log('failed to truncate JobQueue: try deleting instead');
        }
    }
}
