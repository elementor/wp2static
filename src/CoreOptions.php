<?php

namespace WP2Static;

/*
    Simple interface to wp2static_core_options DB table


*/
class CoreOptions {

    /**
     * @var ?array<string, array<string, ?string>>
     */
    private static $cached_option_specs = null;

    /**
     * @var string
     */
    private static $table_name = 'wp2static_core_options';

    public static function init() : void {
        self::createTable();
        self::seedOptions();
    }

    public static function createTable() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            value VARCHAR(249) NOT NULL,
            blob_value BLOB,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        $wpdb->query( "ALTER TABLE $table_name DROP COLUMN IF EXISTS description" );
        $wpdb->query( "ALTER TABLE $table_name DROP COLUMN IF EXISTS label" );

        Controller::ensureIndex(
            $table_name,
            'name',
            "CREATE UNIQUE INDEX name ON $table_name (name)"
        );
    }

    /**
     * @return array<string, ?string>
     */
    public static function makeOptionSpec(
        string $name,
        string $default_value,
        string $label,
        string $description,
        ?string $default_blob_value = null
    ) : array {
        return [
            'name' => $name,
            'default_value' => $default_value,
            'label' => $label,
            'description' => $description,
            'default_blob_value' => $default_blob_value,
        ];
    }

    /**
     * @return array<string, array<string, ?string>>
     */
    public static function optionSpecs() : array {
        if ( self::$cached_option_specs ) {
            return self::$cached_option_specs;
        }

        $specs = [
            self::makeOptionSpec(
                'detectCustomPostTypes',
                '1',
                'Detect Custom Post Types',
                'Include Custom Post Types in URL Detection.'
            ),
            self::makeOptionSpec(
                'detectPages',
                '1',
                'Detect Pages',
                'Include Pages in URL Detection.'
            ),
            self::makeOptionSpec(
                'detectPosts',
                '1',
                'Detect Posts',
                'Include Posts in URL Detection.'
            ),
            self::makeOptionSpec(
                'detectUploads',
                '1',
                'Detect Uploads',
                'Include Uploads in URL Detection.'
            ),
            self::makeOptionSpec(
                'queueJobOnPostSave',
                '1',
                'Post save',
                'Queues a new job every time a Post or Page is saved.'
            ),
            self::makeOptionSpec(
                'queueJobOnPostDelete',
                '1',
                'Post delete',
                'Queues a new job every time a Post or Page is deleted.'
            ),
            self::makeOptionSpec(
                'processQueueImmediately',
                '0',
                'Process queue immediately after enqueueing jobs',
                ''
            ),
            self::makeOptionSpec(
                'processQueueInterval',
                '0',
                'Interval to process queue with WP-Cron',
                'WP-Cron will attempt to process job queue at this interval (minutes)'
            ),
            self::makeOptionSpec(
                'autoJobQueueDetection',
                '1',
                'Detect URLs',
                ''
            ),
            self::makeOptionSpec(
                'autoJobQueueCrawling',
                '1',
                'Crawl Site',
                ''
            ),
            self::makeOptionSpec(
                'autoJobQueuePostProcessing',
                '1',
                'Post-process',
                ''
            ),
            self::makeOptionSpec(
                'autoJobQueueDeployment',
                '1',
                'Deploy',
                ''
            ),
            self::makeOptionSpec(
                'basicAuthUser',
                '',
                'Basic Auth User',
                'Username for basic authentication.'
            ),
            self::makeOptionSpec(
                'deploymentURL',
                'https://example.com',
                'Deployment URL',
                'URL your static site will be hosted at.'
            ),
            self::makeOptionSpec(
                'basicAuthPassword',
                '',
                'Basic Auth Password',
                'Password for basic authentication.'
            ),
            self::makeOptionSpec(
                'useCrawlCaching',
                '1',
                'Use CrawlCache',
                'Skip crawling unchanged URLs.'
            ),
            self::makeOptionSpec(
                'completionEmail',
                '',
                'Completion Email',
                'Email to send deployment completion notification to.'
            ),
            self::makeOptionSpec(
                'completionWebhook',
                '',
                'Completion Webhook',
                'Webhook to send deployment completion notification to.'
            ),
            self::makeOptionSpec(
                'completionWebhookMethod',
                'POST',
                'Completion Webhook Method',
                'How to send completion webhook payload (GET|POST).'
            ),

            // Advanced options
            self::makeOptionSpec(
                'skipURLRewrite',
                '0',
                'Skip URL Rewrite',
                'Don\'t rewrite any URLs. This may give a slight speed-up when the'
                . ' deployment URL is the same as WordPress\'s URL.'
            ),
            self::makeOptionSpec(
                'hostsToRewrite',
                '1',
                'Hosts to Rewrite',
                'Hosts to rewrite to the deployment URL.',
                'localhost'
            ),
        ];

        $ret = [];
        foreach ( $specs as $s ) {
            $ret[ $s['name'] ] = $s;
        }
        self::$cached_option_specs = $ret;
        return $ret;
    }

    /**
     * Seed options
     */
    public static function seedOptions() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        $query_string =
            "INSERT IGNORE INTO $table_name (name, value, blob_value)
VALUES (%s, %s, %s);";

        foreach ( self::optionSpecs() as $os ) {
            $query = $wpdb->prepare(
                $query_string,
                $os['name'],
                $os['default_value'],
                $os['default_blob_value']
            );
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
            $name
        );

        $option_value = $wpdb->get_var( $sql );

        if ( ! $option_value || ! is_string( $option_value ) ) {
            $os = self::optionSpecs()[ $name ];
            if ( ! $os ) {
                return '';
            }
            $option_value = (string) $os['default_value'];
        }

        if ( $name === 'basicAuthPassword' ) {
            return self::encrypt_decrypt( 'decrypt', $option_value );
        }

        // default deploymentURL is '/', else remove trailing slash
        if ( $name === 'deploymentURL' ) {
            if ( $option_value === '/' ) {
                return $option_value;
            }

            return untrailingslashit( $option_value );
        }

        return $option_value;
    }

    /**
     * Get option BLOB value
     *
     * @throws WP2StaticException
     * @return string option BLOB value
     */
    public static function getBlobValue( string $name ) : string {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        $sql = $wpdb->prepare(
            "SELECT blob_value FROM $table_name WHERE" . ' name = %s LIMIT 1',
            $name
        );

        $option_value = $wpdb->get_var( $sql );

        if ( ! is_string( $option_value ) ) {
            $os = self::optionSpecs()[ $name ];
            if ( ! $os ) {
                return '';
            }
            $option_value = (string) $os['default_blob_value'];
        }

        return $option_value;
    }

    /**
     * @return array<string>
     */
    public static function getLineDelimitedBlobValue( string $name ) : array {
        $vals = preg_split(
            '/\r\n|\r|\n/',
            self::getBlobValue( $name )
        );

        if ( ! $vals ) {
            return [];
        }

        return $vals;
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
            "SELECT name, value, blob_value
             FROM $table_name WHERE" . ' name = %s LIMIT 1',
            $name
        );

        $option = $wpdb->get_row( $sql );

        // decrypt password fields
        if ( $option->name === 'basicAuthPassword' ) {
            $option->value =
                self::encrypt_decrypt( 'decrypt', $option->value );
        }

        $os = self::optionSpecs() [ $name ];

        if ( ! $option && $os ) {
            $opt = array_merge( $os ); // Make a copy so we don't modify $cached_option_specs
            $opt['value'] = $os['default_value'];
            $opt['blob_value'] = $os['default_blob_value'];
            return $opt;
        }

        return (object) array_merge( $os, (array) $option );
    }

    /**
     * Get all options (value, description, label, etc)
     *
     * @return array<string, mixed> array of option name to option object
     */
    public static function getAll() {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        $sql = "SELECT name, value, blob_value FROM $table_name";

        $options = $wpdb->get_results( $sql );

        // convert array of stdObjects to array of arrays
        // for easier presentation in WP-CLI
        // TODO: perform in view
        $options = array_map(
            function ( $obj ) {
                // hide sensitive values
                if ( $obj->name === 'basicAuthPassword' ) {
                    $obj->value = '***************';
                }

                return (array) $obj;
            },
            $options
        );

        $options_map = [];
        foreach ( $options as $o ) {
            $options_map[ $o['name'] ] = $o;
        }

        $ret = [];
        foreach ( self::optionSpecs() as $os ) {
            $name = $os['name'];
            $opt = $options_map[ $name ];
            if ( ! $opt ) {
                $opt = array_merge( $os ); // Make a copy so we don't modify $cached_option_specs
                $opt['value'] = $os['default_value'];
                $opt['blob_value'] = $os['default_blob_value'];
                $ret[ $name ] = $opt;
            } else {
                $ret[ $name ] = (object) array_merge( $os, $opt );
            }
        }

        return $ret;
    }

    /*
     * Naive encypting/decrypting
     *
     * @throws WP2StaticException
     */
    public static function encrypt_decrypt( string $action, string $string ) : string {
        $encrypt_method = 'AES-256-CBC';

        $secret_key =
            defined( 'AUTH_KEY' ) ?
            constant( 'AUTH_KEY' ) :
            'LC>_cVZv34+W.P&_8d|ejfr]d31h)J?z5n(LB6iY=;P@?5/qzJSyB3qctr,.D$[L';

        $secret_iv =
            defined( 'AUTH_SALT' ) ?
            constant( 'AUTH_SALT' ) :
            'ec64SSHB{8|AA_ThIIlm:PD(Z!qga!/Dwll 4|i.?UkC§NNO}z?{Qr/q.KpH55K9';

        $key = hash( 'sha256', $secret_key );
        $variate = substr( hash( 'sha256', $secret_iv ), 0, 32 );
        $hex_key = (string) hex2bin( $key );
        $hex_iv = (string) hex2bin( $variate );

        if ( $action == 'decrypt' ) {
            return (string) openssl_decrypt(
                (string) base64_decode( $string ),
                $encrypt_method,
                $hex_key,
                0,
                $hex_iv
            );
        }

        $output = openssl_encrypt( $string, $encrypt_method, $hex_key, 0, $hex_iv );

        return (string) base64_encode( (string) $output );
    }

    /**
     * Save all options POST'ed via UI
     */
    public static function savePosted( string $screen = 'core' ) : void {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        switch ( $screen ) {
            case 'core':
                $wpdb->update(
                    $table_name,
                    [ 'value' => isset( $_POST['detectCustomPostTypes'] ) ? 1 : 0 ],
                    [ 'name' => 'detectCustomPostTypes' ]
                );

                $wpdb->update(
                    $table_name,
                    [ 'value' => isset( $_POST['detectPosts'] ) ? 1 : 0 ],
                    [ 'name' => 'detectPosts' ]
                );

                $wpdb->update(
                    $table_name,
                    [ 'value' => isset( $_POST['detectPages'] ) ? 1 : 0 ],
                    [ 'name' => 'detectPages' ]
                );

                $wpdb->update(
                    $table_name,
                    [ 'value' => isset( $_POST['detectUploads'] ) ? 1 : 0 ],
                    [ 'name' => 'detectUploads' ]
                );

                $wpdb->update(
                    $table_name,
                    [ 'value' => esc_url_raw( $_POST['deploymentURL'] ) ],
                    [ 'name' => 'deploymentURL' ]
                );

                $wpdb->update(
                    $table_name,
                    [ 'value' => sanitize_text_field( $_POST['basicAuthUser'] ) ],
                    [ 'name' => 'basicAuthUser' ]
                );

                $wpdb->update(
                    $table_name,
                    [
                        'value' =>
                        self::encrypt_decrypt(
                            'encrypt',
                            sanitize_text_field( $_POST['basicAuthPassword'] )
                        ),
                    ],
                    [ 'name' => 'basicAuthPassword' ]
                );

                $wpdb->update(
                    $table_name,
                    [ 'value' => isset( $_POST['useCrawlCaching'] ) ? 1 : 0 ],
                    [ 'name' => 'useCrawlCaching' ]
                );

                $wpdb->update(
                    $table_name,
                    [ 'value' => sanitize_email( $_POST['completionEmail'] ) ],
                    [ 'name' => 'completionEmail' ]
                );

                $wpdb->update(
                    $table_name,
                    [ 'value' => esc_url_raw( $_POST['completionWebhook'] ) ],
                    [ 'name' => 'completionWebhook' ]
                );

                $wpdb->update(
                    $table_name,
                    [ 'value' => sanitize_text_field( $_POST['completionWebhookMethod'] ) ],
                    [ 'name' => 'completionWebhookMethod' ]
                );

                break;
            case 'jobs':
                $queue_on_post_save = isset( $_POST['queueJobOnPostSave'] ) ? 1 : 0;
                $queue_on_post_delete = isset( $_POST['queueJobOnPostDelete'] ) ? 1 : 0;
                $process_queue_immediately = isset( $_POST['processQueueImmediately'] ) ? 1 : 0;

                $wpdb->update(
                    $table_name,
                    [ 'value' => $queue_on_post_save ],
                    [ 'name' => 'queueJobOnPostSave' ]
                );

                $wpdb->update(
                    $table_name,
                    [ 'value' => $queue_on_post_delete ],
                    [ 'name' => 'queueJobOnPostDelete' ]
                );

                $wpdb->update(
                    $table_name,
                    [ 'value' => $process_queue_immediately ],
                    [ 'name' => 'processQueueImmediately' ]
                );

                $process_queue_interval =
                    isset( $_POST['processQueueInterval'] ) ?
                     $_POST['processQueueInterval'] : 0;

                $wpdb->update(
                    $table_name,
                    [ 'value' => $process_queue_interval ],
                    [ 'name' => 'processQueueInterval' ]
                );

                WPCron::setRecurringEvent( $process_queue_interval );

                $wpdb->update(
                    $table_name,
                    [ 'value' => isset( $_POST['autoJobQueueDetection'] ) ? 1 : 0 ],
                    [ 'name' => 'autoJobQueueDetection' ]
                );

                $wpdb->update(
                    $table_name,
                    [ 'value' => isset( $_POST['autoJobQueueCrawling'] ) ? 1 : 0 ],
                    [ 'name' => 'autoJobQueueCrawling' ]
                );

                $wpdb->update(
                    $table_name,
                    [ 'value' => isset( $_POST['autoJobQueuePostProcessing'] ) ? 1 : 0 ],
                    [ 'name' => 'autoJobQueuePostProcessing' ]
                );

                $wpdb->update(
                    $table_name,
                    [ 'value' => isset( $_POST['autoJobQueueDeployment'] ) ? 1 : 0 ],
                    [ 'name' => 'autoJobQueueDeployment' ]
                );

                break;
            case 'advanced':
                $wpdb->update(
                    $table_name,
                    [ 'value' => isset( $_POST['skipURLRewrite'] ) ? 1 : 0 ],
                    [ 'name' => 'skipURLRewrite' ]
                );

                $hosts_to_rewrite = preg_replace(
                    '/^\s+|\s+$/m',
                    '',
                    $_POST['hostsToRewrite']
                );
                $wpdb->update(
                    $table_name,
                    [ 'blob_value' => $hosts_to_rewrite ],
                    [ 'name' => 'hostsToRewrite' ]
                );
                break;
        }
    }

    /**
     * Save individual option
     *
     * @param mixed $value Updated option value
     */
    public static function save( string $name, $value ) : void {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$table_name;

        // TODO: some validation on save types
        $wpdb->update(
            $table_name,
            [ 'value' => $value ],
            [ 'name' => $name ]
        );
    }
}

