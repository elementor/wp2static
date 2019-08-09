<?php

namespace WP2Static;

class CrawlQueue {

    public static function createTable() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_urls';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            url VARCHAR(2083) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Add all Urls to queue
     *
     * @param string[] $urls List of URLs to crawl
     */
    public static function addUrls( array $urls ) : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_urls';

        $placeholders = [];
        $values = [];
        foreach ( $urls as $url ) {
            $placeholders[] = "(%s)";
            $values[] = rawurldecode($url);
        }

        $queryString = 'INSERT INTO ' . $table_name . ' (url) VALUES ' . implode(', ', $placeholders);

        $query = $wpdb->prepare( $queryString, $values );
        $wpdb->query( $query );
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

        $rows = $wpdb->get_results( "SELECT url FROM $table_name" );

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
