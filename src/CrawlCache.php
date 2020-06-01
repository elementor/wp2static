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
            page_hash CHAR(32) NOT NULL,
            time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            status SMALLINT DEFAULT 200 NOT NULL,
            redirect_to VARCHAR(2083) NULL,
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

    public static function addUrl( string $url, string $page_hash, int $status,
                                   ?string $redirect_to ) : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_crawl_cache';
        $sql = "insert into {$table_name} (time, hashed_url, url, page_hash, status, redirect_to)
                VALUES (%s, %s, %s, %s, %s, %s) ON DUPLICATE KEY
                UPDATE time = %s, page_hash = %s, status = %s, redirect_to = %s";
        $sql = $wpdb->prepare(
            $sql,
            current_time( 'mysql' ),
            md5( $url ),
            $url,
            $page_hash,
            $status,
            $redirect_to,
            current_time( 'mysql' ),
            $page_hash,
            $status,
            $redirect_to,
        );

        $wpdb->query( $sql );
    }

    // TODO: enable date filter as option/alternate method
    public static function getUrl( string $url, string $page_hash ) : string {
        global $wpdb;

        $hashed_url = md5( $url );

        $table_name = $wpdb->prefix . 'wp2static_crawl_cache';

        $sql = $wpdb->prepare(
            "SELECT hashed_url FROM $table_name WHERE" .
            ' hashed_url = %s and page_hash = %s  LIMIT 1',
            [ $hashed_url, $page_hash ]
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

        $rows = $wpdb->get_results( "SELECT url, page_hash FROM $table_name ORDER BY url" );

        foreach ( $rows as $row ) {
            $urls[ $row->url ] = $row->page_hash;
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
