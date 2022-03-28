<?php

namespace WP2Static;

class Addons {
    public static function createTable() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_addons';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            slug VARCHAR(191) NOT NULL,
            type VARCHAR(249) NOT NULL,
            name VARCHAR(249) NOT NULL,
            docs_url VARCHAR(2083) NOT NULL,
            description VARCHAR(249) NOT NULL,
            enabled TINYINT(1) UNSIGNED DEFAULT 0 NOT NULL,
            PRIMARY KEY  (slug)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function registerAddon(
        string $slug,
        string $type,
        string $name,
        string $docs_url,
        string $description
    ) : void {
        // TODO: guard against unknown addon type

        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_addons';

        $sql = "INSERT IGNORE INTO {$table_name} (slug,type,name,docs_url,description)" .
            ' VALUES (%s,%s,%s,%s,%s)';

        $sql = $wpdb->prepare( $sql, $slug, $type, $name, $docs_url, $description );

        $wpdb->query( $sql );
    }

    /**
     * Get all Addons
     *
     * @return mixed[] array of Addon objects
     */
    public static function getAll( string $type = 'all' ) : array {
        global $wpdb;
        $addons = [];

        $table_name = $wpdb->prefix . 'wp2static_addons';

        $query_string = "SELECT * FROM $table_name";
        $query_params = [];
        if ( $type !== 'all' ) {
            $query_string .= ' WHERE type = %s';
            $query_params[] = $type;
        }
        $query_string .= ' ORDER BY type DESC';

        return $wpdb->get_results(
            $wpdb->prepare( $query_string, $query_params )
        );
    }

    /**
     * Get enabled Addons of a given type
     *
     * @param string $type Type of addon to return
     * @return mixed[] array of Addon objects
     */
    public static function getType( string $type ) : array {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_addons';

        $query = $wpdb->prepare(
            "SELECT * FROM $table_name WHERE type = %s AND enabled = 1 ORDER BY slug",
            $type
        );
        $addons = $wpdb->get_results( $query );

        return $addons;
    }

    /**
     *  Deregister Addons
     */
    public static function truncate() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_addons';

        $wpdb->query( "TRUNCATE TABLE $table_name" );

        WsLog::l( 'Deregistered all Addons' );
    }

    /**
     * Get enabled deployer
     *
     * "There can be only one!"
     *
     * @return string|bool deployment add-on slug or false
     */
    public static function getDeployer() {
        $addons = self::getType( 'deploy' );

        if ( empty( $addons ) ) {
            return false;
        }

        return $addons[0]->slug;
    }
}
