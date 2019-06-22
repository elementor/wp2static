<?php

namespace WP2Static;

class UrlQueue {

    public static function createTable() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_urls';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            url VARCHAR(2083) NOT NULL,
            status VARCHAR(10),
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function addUrl( string $url ) : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_urls';

        $wpdb->insert(
            $table_name,
            array(
                'url' => $url,
            )
        );
    }

    public static function markURLCrawled( string $url ) : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_urls';

        $wpdb->update( 
            $table_name, 
            [ 'status' => 'crawled' ], 
            [ 'url' => $url ]
        );
    }

    public static function hasCrawlableURLs() : int {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_urls';

        $rowcount = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_name WHERE" .
            " NOT (status <=> 'crawled')"
        );

        return $rowcount;
    }

    /**
     *  Get all crawlable URLs
     *
     *  @return string[] All crawlable URLs
     */
    public static function getCrawlableURLs() : array {
        global $wpdb;
        $urls = [];

        $table_name = $wpdb->prefix . 'wp2static_urls';

        $rows = $wpdb->get_results(
            "SELECT url FROM $table_name WHERE" .
            " NOT (status <=> 'crawled')"
        );


        foreach ( $rows as $row ) {
            $urls[] = $row->url;
        } 

        return $urls;
    }

    public static function truncate() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_urls';

        $wpdb->query( "TRUNCATE TABLE $table_name" );
    }
}
