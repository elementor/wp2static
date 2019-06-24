<?php

namespace WP2Static;

class DeployQueue {

    public static function createTable() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_deploy_queue';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            local_path VARCHAR(2083) NOT NULL,
            remote_path VARCHAR(2083) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function addPath(
        string $local_path, string $remote_path
    ) : void {
        global $wpdb;

        $deploy_queue_table = $wpdb->prefix . 'wp2static_deploy_queue';
        $path_data = [
            'local_path' => $local_path,
            'remote_path' => $remote_path
        ];
        $wpdb->insert( $deploy_queue_table, $path_data, [ '%s','%s' ] );
    }

    /**
     *  Get all crawlable URLs
     *
     *  @return string[] All crawlable URLs
     */
    public static function getDeployableURLs( int $limit = 0 ) : array {
        global $wpdb;
        $urls = [];

        $table_name = $wpdb->prefix . 'wp2static_deploy_queue';

        $query = "SELECT local_path, remote_path FROM $table_name";

        if ( $limit ) {
            $query .= " LIMIT $limit";
        }

        $rows = $wpdb->get_results( $query );

        foreach ( $rows as $row ) {
            $urls[$row->local_path] = $row->remote_path;
        }

        return $urls;
    }

    public static function truncate() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_deploy_queue';

        $wpdb->query( "TRUNCATE TABLE $table_name" );
    }

    public static function remove( string $local_path ) : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_deploy_queue';

        $wpdb->delete(
            $table_name,
            [
                'local_path' => $local_path,
            ]
        );
    }
}
