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
            $placeholders[] = '(%s)';
            $values[] = rawurldecode( $url );
        }

        $query_string =
            'INSERT INTO ' . $table_name . ' (url) VALUES ' .
            implode( ', ', $placeholders );
        $query = $wpdb->prepare( $query_string, $values );

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

    /**
     *  Get total crawlable URLs
     *
     *  @return int Total crawlable URLs
     */
    public static function getTotalCrawlableURLs() : int {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_urls';

        $total_urls = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

        return $total_urls;
    }

    /**
     *  Clear CrawlQueue via truncate or deletion
     *
     */
    public static function truncate() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_urls';

        $wpdb->query( "TRUNCATE TABLE $table_name" );

        $total_urls = self::getTotalCrawlableURLs();

        if ( $total_urls > 0 ) {
            // TODO: simulate lack of permissios to truncate
            error_log('failed to truncate CrawlQueue: try deleting instead');
        }
    }
}
