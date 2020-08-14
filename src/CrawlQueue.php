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
            hashed_url CHAR(32) NOT NULL,
            crawled_time DATETIME,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        \WP2Static\Controller::ensure_index(
            $table_name,
            'hashed_url',
            "CREATE UNIQUE INDEX hashed_url ON $table_name (hashed_url)"
        );
        \WP2Static\Controller::ensure_index(
            $table_name,
            'crawled_time',
            "CREATE INDEX crawled_time ON $table_name (crawled_time)"
        );
    }

    /**
     * Add all Urls to queue
     *
     * @param string[] $urls List of URLs to crawl
     */
    public static function addUrls( array $urls ) : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_urls';
        $url_count = count( $urls );

        while ( $url_count ) {
            $chunk = array_slice( $urls, 0, 100 );
            $urls = array_slice( $urls, 100 );
            $url_count = count( $urls );
            $placeholders = array_fill( 0, count( $chunk ), '(%s, %s)' );
            $values = [];

            foreach ( $chunk as $url ) {
                array_push( $values, md5( $url ), rawurldecode( $url ) );
            }

            $query_string =
                'INSERT IGNORE INTO ' . $table_name . ' (hashed_url, url) VALUES ' .
                implode( ', ', $placeholders );
            $query = $wpdb->prepare( $query_string, $values );

            $wpdb->query( $query );
        }
    }

    /**
     * Set crawled_time to NOW() for each URL.
     *
     * @param string[] $urls List of URLs.
     */
    public static function updateCrawledTimes( array $urls ) : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_urls';
        $wpdb->query( 'START TRANSACTION' );
        foreach ( $urls as $url ) {
            $query = $wpdb->prepare(
                "UPDATE $table_name SET crawled_time = NOW() WHERE url = %s",
                $url
            );
            $wpdb->query( $query );
        }
        $wpdb->query( 'COMMIT' );
    }

    /**
     *  Get all crawlable URLs
     *
     *  @return string[] All crawlable URLs
     */
    public static function getCrawlablePaths() : array {
        global $wpdb;
        $urls = [];

        $table_name = $wpdb->prefix . 'wp2static_urls';

        $rows = $wpdb->get_results( "SELECT id, url FROM $table_name ORDER by url ASC" );

        foreach ( $rows as $row ) {
            $urls[ $row->id ] = $row->url;
        }

        return $urls;
    }

    /**
     * Get a chunk of URLs to crawl.
     *
     * @param string $crawl_start_time Start time of the crawl.
     * @param int $size Max number of URLs to return.
     * @return array<string> Array of URLs.
     */
    public static function getChunk( string $crawl_start_time, int $size ) : array {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_urls';

        $query = $wpdb->prepare(
            "SELECT id, url FROM $table_name
             WHERE crawled_time IS NULL OR crawled_time <= %s
             LIMIT $size",
            $crawl_start_time
        );
        $rows = $wpdb->get_results( $query );

        $urls = [];
        foreach ( $rows as $row ) {
            $urls[ $row->id ] = $row->url;
        }
        return $urls;
    }

    /**
     * Remove multiple URLs at once
     *
     * @param array<string> $ids
     * @return void
     */
    public static function rmUrlsById( array $ids ) : void {
        global $wpdb;

        $ids = array_map(
            function ( string $id ) {
                return absint( $id );
            },
            $ids
        );
        $ids = implode( ',', array_map( 'absint', $ids ) );

        $table_name = $wpdb->prefix . 'wp2static_urls';

        $wpdb->query( "DELETE FROM $table_name WHERE ID IN($ids)" );
    }

    /**
     *  Get total crawlable URLs
     *
     *  @return int Total crawlable URLs
     */
    public static function getTotalCrawlableURLs() : int {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_urls';

        $total_urls = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );

        return $total_urls;
    }

    /**
     *  Clear CrawlQueue via truncate or deletion
     */
    public static function truncate() : void {
        WsLog::l( 'Deleting CrawlQueue (Detected URLs)' );

        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_urls';

        $wpdb->query( "TRUNCATE TABLE $table_name" );

        $total_urls = self::getTotalCrawlableURLs();

        if ( $total_urls > 0 ) {
            WsLog::l( 'failed to truncate CrawlQueue: try deleting instead' );
        }
    }

    /**
     *  Count URLs in Crawl Queue
     */
    public static function getTotal() : int {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_urls';

        $total = $wpdb->get_var( "SELECT count(*) FROM $table_name" );

        return $total;
    }
}
