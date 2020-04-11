<?php

namespace WP2Static;

class CrawlCache {

    public static function createTable() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_crawl_cache';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            hashed_url CHAR(32) NOT NULL,
            url VARCHAR(2083) NOT NULL,
            time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (hashed_url)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     *  Get all Crawl Cache URLs
     *
     *  @return string[] All URLs
     */
    public static function getHashes() : array {
        global $wpdb;
        $urls = [];

        $table_name = $wpdb->prefix . 'wp2static_crawl_cache';

        $rows = $wpdb->get_results( "SELECT hashed_url FROM $table_name" );

        foreach ( $rows as $row ) {
            $urls[] = $row->hashed_url;
        }

        return $urls;
    }

    public static function addUrl( string $url ) : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_crawl_cache';

        $wpdb->insert(
            $table_name,
            [
                'time' => current_time( 'mysql' ),
                'hashed_url' => md5( $url ),
                'url' => $url,
            ]
        );
    }

    // TODO: enable date filter as option/alternate method
    public static function getUrl( string $url ) : string {
        global $wpdb;

        $hashed_url = md5( $url );

        $table_name = $wpdb->prefix . 'wp2static_crawl_cache';

        $sql = $wpdb->prepare(
            "SELECT hashed_url FROM $table_name WHERE" .
            ' hashed_url = %s LIMIT 1',
            $hashed_url
        );

        $hashed_url = $wpdb->get_var( $sql );

        return (string) $hashed_url;
    }

    /**
     *  Get all URLs in CrawlCache
     *
     *  @return string[] All crawlable URLs
     */
    public static function getURLs() : array {
        global $wpdb;
        $urls = [];

        $table_name = $wpdb->prefix . 'wp2static_crawl_cache';

        $rows = $wpdb->get_results( "SELECT url FROM $table_name ORDER BY url" );

        foreach ( $rows as $row ) {
            $urls[] = $row->url;
        }

        return $urls;
    }

    public static function rmUrl( string $url ) : void {
        global $wpdb;

        $hashed_url = md5( $url );

        $table_name = $wpdb->prefix . 'wp2static_crawl_cache';

        $wpdb->delete(
            $table_name,
            [
                'hashed_url' => md5( $url ),
            ]
        );
    }

    /**
     *  Clear CrawlCache via truncation
     */
    public static function truncate() : void {
        WsLog::l( 'Deleting CrawlCache' );

        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_crawl_cache';

        $wpdb->query( "TRUNCATE TABLE $table_name" );

        $totalcrawl_cache = self::getTotal();

        if ( $totalcrawl_cache > 0 ) {
            WsLog::l( 'Failed to truncate CrawlCache: try deleting instead' );
        }
    }

    /**
     *  Count URLs in Crawl Cache
     */
    public static function getTotal() : int {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_crawl_cache';

        $total = $wpdb->get_var( "SELECT count(*) FROM $table_name" );

        return $total;
    }
}
