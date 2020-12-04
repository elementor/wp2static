<?php

namespace WP2Static;

class DeployCache {

    const DEFAULT_NAMESPACE = 'default';

    public static function createTable() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_deploy_cache';

        $charset_collate = $wpdb->get_charset_collate();

        $check_table_query =
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $wpdb->esc_like( $table_name )
            );

        // if table exists, check structure
        if ( $wpdb->get_var( $check_table_query ) === $table_name ) {
            // @todo We can remove this eventually
            // If the ID column is missing, just remove the table and start
            // again because dbDelta isn't adding it correctly
            $id_row = $wpdb->get_row(
                "SHOW COLUMNS FROM $table_name WHERE Field = 'id'"
            );

            if ( ! $id_row ) {
                $wpdb->query(
                    "DROP TABLE IF EXISTS $table_name;"
                );
            }
        }

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            path_hash CHAR(32) NOT NULL,
            path VARCHAR(2083) NOT NULL,
            file_hash CHAR(32) NOT NULL,
            namespace VARCHAR(128) NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY path_hash_ns_idx (path_hash, namespace)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function addFile(
        string $local_path,
        string $namespace = self::DEFAULT_NAMESPACE,
        ?string $file_hash = null
    ) : void {
        global $wpdb;

        $deploy_cache_table = $wpdb->prefix . 'wp2static_deploy_cache';

        $post_processed_dir = ProcessedSite::getPath();

        $deployed_file = $post_processed_dir . $local_path;

        $path_hash = md5( $deployed_file );

        if ( ! $file_hash ) {
            $file_contents = file_get_contents( $deployed_file );

            if ( ! $file_contents ) {
                return;
            }

            $file_hash = md5( $file_contents );
        }

        $sql = "INSERT INTO {$deploy_cache_table} (path_hash,path,file_hash,namespace)" .
            ' VALUES (%s,%s,%s,%s) ON DUPLICATE KEY UPDATE file_hash = %s, namespace = %s';

        $sql = $wpdb->prepare(
          // Insert values
            $sql,
            $path_hash,
            $local_path,
            $file_hash,
            $namespace,
            // Duplicate key values
            $file_hash,
            $namespace
        );

        $wpdb->query( $sql );
    }

    /**
     * Checks if file can skip deployment
     *  - uses hash of file and path's hash
     */
    public static function fileisCached(
        string $local_path,
        string $namespace = self::DEFAULT_NAMESPACE,
        ?string $file_hash = null
    ) : bool {
        global $wpdb;

        $post_processed_dir = ProcessedSite::getPath();

        $deployed_file = $post_processed_dir . $local_path;

        $path_hash = md5( $deployed_file );

        if ( ! $file_hash ) {
            $file_contents = file_get_contents( $deployed_file );

            if ( ! $file_contents ) {
                return false;
            }

            $file_hash = md5( $file_contents );
        }

        $table_name = $wpdb->prefix . 'wp2static_deploy_cache';

        $sql = $wpdb->prepare(
            "SELECT path_hash FROM $table_name WHERE" .
            ' path_hash = %s AND file_hash = %s AND namespace = %s LIMIT 1',
            $path_hash,
            $file_hash,
            $namespace
        );

        $hash = $wpdb->get_var( $sql );

        return (bool) $hash;
    }

    public static function truncate(
        string $namespace = self::DEFAULT_NAMESPACE
    ) : void {
        WsLog::l( 'Deleting DeployCache' );

        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_deploy_cache';

        $sql = "DELETE FROM $table_name WHERE namespace = %s";
        $sql = $wpdb->prepare( $sql, $namespace );
        $wpdb->query( $sql );
    }

    /**
     *  Count Paths in Deploy Cache
     */
    public static function getTotal(
        string $namespace = self::DEFAULT_NAMESPACE
    ) : int {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_deploy_cache';

        $sql = "SELECT count(*) FROM $table_name WHERE namespace = %s";
        $sql = $wpdb->prepare( $sql, $namespace );
        $total = $wpdb->get_var( $sql );

        return $total;
    }

    /**
     *  @return mixed[] namespace totals
     */
    public static function getTotalsByNamespace() : array {
        global $wpdb;
        $counts = [];

        $table_name = $wpdb->prefix . 'wp2static_deploy_cache';

        $sql = "SELECT namespace, COUNT(*) AS count FROM $table_name GROUP BY namespace";
        $rows = $wpdb->get_results( $sql );

        foreach ( $rows as $row ) {
            $counts[ $row->namespace ] = $row->count;
        }

        return $counts;
    }


    /**
     *  Get all cached paths
     *
     *  @return string[] All cached paths
     */
    public static function getPaths(
        string $namespace = self::DEFAULT_NAMESPACE
    ) : array {
        global $wpdb;
        $urls = [];

        $table_name = $wpdb->prefix . 'wp2static_deploy_cache';

        $sql = "SELECT path FROM $table_name WHERE namespace = %s ORDER BY path";
        $sql = $wpdb->prepare( $sql, $namespace );
        $urls = $wpdb->get_col( $sql );

        return $urls;
    }
}
