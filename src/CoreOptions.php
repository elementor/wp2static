<?php

namespace WP2Static;

/*
    Simple interface to wp2static_core_options DB table


*/
class CoreOptions {

    private static $table_name = 'wp2static_core_options';

    public static function init() : void {
        self::createTable();

        global $wpdb;

        // check for required options, seed if non-existant
        $detectPosts = self::get('detectPosts');

        if ( ! isset( $detectPosts ) ) {
            error_log('detectPosts not found, seeding coreOptions');
            self::seedOptions();
        }
    }

    public static function createTable() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            value VARCHAR(255) NOT NULL,
            label VARCHAR(255) NULL,
            description VARCHAR(255) NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Seed options
     *
     */
    public static function seedOptions() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

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
            'queueJobOnPostSave',
            '1',
            'Post save',
            'Queues a new job every time a Post or Page is saved.');

        $queries[] = $wpdb->prepare(
            $query_string,
            'queueJobOnPostDelete',
            '1',
            'Post delete',
            'Queues a new job every time a Post or Page is deleted.');

        $queries[] = $wpdb->prepare(
            $query_string,
            'processQueueInterval',
            '0',
            'Interval to process queue with WP-Cron',
            'WP-Cron will attempt to process job queue at this interval (minutes)');

        $queries[] = $wpdb->prepare(
            $query_string,
            'autoJobQueueDetection',
            '1',
            'Detect URLs',
            '');

        $queries[] = $wpdb->prepare(
            $query_string,
            'autoJobQueueCrawling',
            '1',
            'Crawl Site',
            '');

        $queries[] = $wpdb->prepare(
            $query_string,
            'autoJobQueuePostProcessing',
            '1',
            'Post-process',
            '');

        $queries[] = $wpdb->prepare(
            $query_string,
            'autoJobQueueDeployment',
            '1',
            'Deploy',
            '');

        $queries[] = $wpdb->prepare(
            $query_string,
            'basicAuthUser',
            '',
            'Basic Auth User',
            'Username for basic authentication.');

        $queries[] = $wpdb->prepare(
            $query_string,
            'basicAuthPassword',
            '',
            'Basic Auth Password',
            'Password for basic authentication.');

        $queries[] = $wpdb->prepare(
            $query_string,
            'includeDiscoveredAssets',
            '1',
            'Include Discovered Assets',
            'Include URLs discovered during crawling.');

        $queries[] = $wpdb->prepare(
            $query_string,
            'deploymentURL',
            'https://example.com',
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
            'completionWebhookMethod',
            'POST',
            'Completion Webhook Method',
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

        $table_name = $wpdb->prefix . self::$table_name;

        $sql = $wpdb->prepare(
            "SELECT value FROM $table_name WHERE" . ' name = %s LIMIT 1',
            $name);

        $option_value = $wpdb->get_var( $sql );

        if ( ! is_string( $option_value ) ) {
            return '';
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

        $table_name = $wpdb->prefix . self::$table_name;

        $sql = $wpdb->prepare(
            "SELECT name, value, label, description FROM $table_name WHERE" . ' name = %s LIMIT 1',
            $name);

        $option = $wpdb->get_results( $sql );

        // decrypt password fields
        if ($option[0]->name === 'basicAuthPassword') {
            $option[0]->value =
                self::encrypt_decrypt('decrypt', $option[0]->value);
        }

        return $option[0];
    }

    /**
     * Get all options (value, description, label, etc)
     *
     * @return mixed array of options
     */
    public static function getAll() {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        $sql = "SELECT value, label, description FROM $table_name ORDER BY label";

        $options = $wpdb->get_results( $sql );

        return $options;
    }

    /*
     * Naive encypting/decrypting
     *
     * @throws WP2StaticException
     */
    public static function encrypt_decrypt(string $action, string $string) : string {
        $encrypt_method = "AES-256-CBC";

        $secret_key =
            defined( 'AUTH_KEY' ) ?
            constant( 'AUTH_KEY' ) :
            'LC>_cVZv34+W.P&_8d|ejfr]d31h)J?z5n(LB6iY=;P@?5/qzJSyB3qctr,.D$[L';

        $secret_iv =
            defined( 'AUTH_SALT' ) ?
            constant( 'AUTH_SALT' ) :
            'ec64SSHB{8|AA_ThIIlm:PD(Z!qga!/Dwll 4|i.?UkC§NNO}z?{Qr/q.KpH55K9';

        $key = hash('sha256', $secret_key);
        $variate = substr(hash('sha256', $secret_iv), 0, 16);

        if ( $action == 'decrypt' ) {
            return (string) openssl_decrypt((string) base64_decode($string), $encrypt_method, $key, 0, $variate);
        }

        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $variate);

        return (string) base64_encode((string) $output);
    }

    /**
     * Save all options POST'ed via UI
     *
     */
    public static function savePosted( string $screen = 'core') : void {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        switch ( $screen ) {
            case 'core':
                $wpdb->update(
                    $table_name,
                    ['value' => esc_url_raw($_POST['deploymentURL'])],
                    ['name' => 'deploymentURL']
                );

                $wpdb->update(
                    $table_name,
                    ['value' => (int) $_POST['detectCustomPostTypes']],
                    ['name' => 'detectCustomPostTypes']
                );

                $wpdb->update(
                    $table_name,
                    ['value' => (int) $_POST['detectPosts']],
                    ['name' => 'detectPosts']
                );

                $wpdb->update(
                    $table_name,
                    ['value' => (int) $_POST['detectPages']],
                    ['name' => 'detectPages']
                );

                $wpdb->update(
                    $table_name,
                    ['value' => (int) $_POST['detectUploads']],
                    ['name' => 'detectUploads']
                );

                $wpdb->update(
                    $table_name,
                    ['value' => sanitize_text_field($_POST['basicAuthUser'])],
                    ['name' => 'basicAuthUser']
                );

                $wpdb->update(
                    $table_name,
                    ['value' => self::encrypt_decrypt('encrypt', sanitize_text_field($_POST['basicAuthPassword']))],
                    ['name' => 'basicAuthPassword']
                );

                $wpdb->update(
                    $table_name,
                    ['value' => (int) $_POST['includeDiscoveredAssets']],
                    ['name' => 'includeDiscoveredAssets']
                );

                $wpdb->update(
                    $table_name,
                    ['value' => sanitize_email($_POST['completionEmail'])],
                    ['name' => 'completionEmail']
                );

                $wpdb->update(
                    $table_name,
                    ['value' => esc_url_raw($_POST['completionWebhook'])],
                    ['name' => 'completionWebhook']
                );

                $wpdb->update(
                    $table_name,
                    ['value' => sanitize_text_field($_POST['completionWebhookMethod'])],
                    ['name' => 'completionWebhookMethod']
                );

                break;
            case 'jobs':
                $wpdb->update(
                    $table_name,
                    ['value' => (int) $_POST['queueJobOnPostSave']],
                    ['name' => 'queueJobOnPostSave']
                );

                $wpdb->update(
                    $table_name,
                    ['value' => (int) $_POST['queueJobOnPostDelete']],
                    ['name' => 'queueJobOnPostDelete']
                );

                $processQueueInterval = (int) $_POST['processQueueInterval'];
                $wpdb->update(
                    $table_name,
                    ['value' => $processQueueInterval],
                    ['name' => 'processQueueInterval']
                );
                WPCron::setRecurringEvent($processQueueInterval);

                $wpdb->update(
                    $table_name,
                    ['value' => (int) $_POST['autoJobQueueDetection']],
                    ['name' => 'autoJobQueueDetection']
                );

                $wpdb->update(
                    $table_name,
                    ['value' => (int) $_POST['autoJobQueueCrawling']],
                    ['name' => 'autoJobQueueCrawling']
                );

                $wpdb->update(
                    $table_name,
                    ['value' => (int) $_POST['autoJobQueuePostProcessing']],
                    ['name' => 'autoJobQueuePostProcessing']
                );

                $wpdb->update(
                    $table_name,
                    ['value' => (int) $_POST['autoJobQueueDeployment']],
                    ['name' => 'autoJobQueueDeployment']
                );


                break;
        }
    }
}

