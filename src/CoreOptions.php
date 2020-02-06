<?php

namespace WP2Static;

/*
    Simple interface to wp2static_core_options DB table


*/
class CoreOptions {

    private static $table_name = 'wp2static_core_options';

    public static function createTable() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . self::table_name;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            value VARCHAR(255) NOT NULL,
            label VARCHAR(255) NULL,
            description VARCHAR(255) NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // TODO: update all other refs to upgrade.php to use SiteInfo for safety
        require_once SiteInfo::getPath('includes') . 'upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Seed options
     *
     */
    public static function seedOptions() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . self::table_name;

        $queries = [];

        $query_string = "INSERT INTO $table_name (name, value, label, description) VALUES (%s, %s, %s, %s);";

        $queries[] = $wpdb->prepare(
            $query_string,
            'detectCustomPostTypes',
            '1',
            'Detect Custom Post Types',
            'Include Custom Post Types in URL Detection.');

        $queries[] = $wpdb->prepare(
            $query_string,
            'detectPages',
            '1',
            'Detect Pages',
            'Include Pages in URL Detection.');

        $queries[] = $wpdb->prepare(
            $query_string,
            'detectPosts',
            '1',
            'Detect Posts',
            'Include Posts in URL Detection.');

        $queries[] = $wpdb->prepare(
            $query_string,
            'detectUploads',
            '1',
            'Detect Uploads',
            'Include Uploads in URL Detection.');

        $queries[] = $wpdb->prepare(
            $query_string,
            'basicAuthUser',
            '',
            'Basic Auth User',
            'Username for basic authentication.');

        // TODO: needs to be encrypted
        $queries[] = $wpdb->prepare(
            $query_string,
            'basicAuthPassword',
            '',
            'Basic Auth Password',
            'Password for basic authentication.');

        $queries[] = $wpdb->prepare(
            $query_string,
            'includeDiscoveredAssets',
            '',
            'Include Discovered Assets',
            'Include URLs discovered during crawling.');

        $queries[] = $wpdb->prepare(
            $query_string,
            'deploymentURL',
            '',
            'Deployment URL',
            'URL your static site will be hosted at.');

        $queries[] = $wpdb->prepare(
            $query_string,
            'completionEmail',
            '',
            'Completion Email',
            'Email to send deployment completion notification to.');

        $queries[] = $wpdb->prepare(
            $query_string,
            'completionWebhook',
            '',
            'Completion Webhook',
            'Webhook to send deployment completion notification to.');

        $queries[] = $wpdb->prepare(
            $query_string,
            'completionWebhookType',
            'POST',
            'Completion Webhook Type',
            'How to send completion webhook payload (GET|POST).');

        foreach ( $queries as $query ) {
            $wpdb->query( $query );
        }
    }

    /**
     * Get option value
     *
     * @throws WP2StaticException
     * @return string option value
     */
    public static function getValue( string $name ) : string {
        global $wpdb;

        $table_name = $wpdb->prefix . self::table_name;

        $sql = $wpdb->prepare(
            "SELECT value FROM $table_name WHERE" . ' name = %s LIMIT 1',
            $name);

        $option_value = $wpdb->get_var( $sql );

        if ( ! is_string( $option_value ) ) {
            $err = 'Failed to retrieve core option value';
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        return $option_value;
    }

    /**
     * Get option (value, description, label, etc)
     *
     * @return mixed option
     */
    public static function get( string $name ) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::table_name;

        $sql = $wpdb->prepare(
            "SELECT value, label, description FROM $table_name WHERE" . ' name = %s LIMIT 1',
            $name);

        $option = $wpdb->get_var( $sql );

        return $option;
    }

    /**
     * Get all options (value, description, label, etc)
     *
     * @return mixed array of options
     */
    public static function getAll( string $name ) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::table_name;

        $sql = $wpdb->prepare(
            "SELECT value, label, description FROM $table_name WHERE" . ' name = %s LIMIT 1',
            $name);

        $options = $wpdb->get_results( $sql );

        return $option;
    }
}

