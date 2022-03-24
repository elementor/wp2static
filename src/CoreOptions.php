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

        $columns = array_keys(
            array_merge(
                ...$wpdb->get_results(
                    sprintf( 'SELECT * FROM %s LIMIT 1', $table_name ),
                    ARRAY_A
                )
            )
        );

        if ( in_array( 'description', $columns ) ) {
            $wpdb->query( "ALTER TABLE $table_name DROP COLUMN description" );
        }
        if ( in_array( 'label', $columns ) ) {
            $wpdb->query( "ALTER TABLE $table_name DROP COLUMN label" );
        }

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
        string $type,
        string $name,
        string $default_value,
        string $label,
        string $description,
        ?string $default_blob_value = null,
        ?string $filter_name = null
    ) : array {
        return [
            'type' => $type,
            'name' => $name,
            'default_value' => $default_value,
            'label' => $label,
            'description' => $description,
            'default_blob_value' => $default_blob_value,
            'filter_name' => $filter_name ? $filter_name : "wp2static_option_$name",
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
                'boolean',
                'detectCustomPostTypes',
                '1',
                'Detect Custom Post Types',
                'Include Custom Post Types in URL Detection.'
            ),
            self::makeOptionSpec(
                'boolean',
                'detectPages',
                '1',
                'Detect Pages',
                'Include Pages in URL Detection.'
            ),
            self::makeOptionSpec(
                'boolean',
                'detectPosts',
                '1',
                'Detect Posts',
                'Include Posts in URL Detection.'
            ),
            self::makeOptionSpec(
                'boolean',
                'detectUploads',
                '1',
                'Detect Uploads',
                'Include Uploads in URL Detection.'
            ),
            self::makeOptionSpec(
                'boolean',
                'queueJobOnPostSave',
                '1',
                'Post Save',
                'Queues a new job every time a Post or Page is saved.'
            ),
            self::makeOptionSpec(
                'boolean',
                'queueJobOnPostDelete',
                '1',
                'Post Delete',
                'Queues a new job every time a Post or Page is deleted.'
            ),
            self::makeOptionSpec(
                'boolean',
                'processQueueImmediately',
                '0',
                'Process Queue Immediately',
                'Begin processing the queue as soon as a job is added, without waiting for WP-Cron.'
            ),
            self::makeOptionSpec(
                'integer',
                'processQueueInterval',
                '0',
                'Process Queue Interval',
                'WP-Cron will attempt to process the job queue at this interval'
            ),
            self::makeOptionSpec(
                'boolean',
                'autoJobQueueDetection',
                '1',
                'Detect URLs',
                ''
            ),
            self::makeOptionSpec(
                'boolean',
                'autoJobQueueCrawling',
                '1',
                'Crawl Site',
                ''
            ),
            self::makeOptionSpec(
                'boolean',
                'autoJobQueuePostProcessing',
                '1',
                'Post-Process',
                ''
            ),
            self::makeOptionSpec(
                'boolean',
                'autoJobQueueDeployment',
                '1',
                'Deploy',
                ''
            ),
            self::makeOptionSpec(
                'string',
                'basicAuthUser',
                '',
                'Basic Auth User',
                'Username for basic authentication.'
            ),
            self::makeOptionSpec(
                'string',
                'deploymentURL',
                'https://example.com',
                'Deployment URL',
                'URL your static site will be hosted at.'
            ),
            self::makeOptionSpec(
                'password',
                'basicAuthPassword',
                '',
                'Basic Auth Password',
                'Password for basic authentication.'
            ),
            self::makeOptionSpec(
                'boolean',
                'useCrawlCaching',
                '1',
                'Use CrawlCache',
                'Skip crawling unchanged URLs.',
                null,
                'wp2static_use_crawl_cache'
            ),
            self::makeOptionSpec(
                'string',
                'completionEmail',
                '',
                'Completion Email',
                'Email to send deployment completion notification to.'
            ),
            self::makeOptionSpec(
                'string',
                'completionWebhook',
                '',
                'Completion Webhook',
                'Webhook to send deployment completion notification to.'
            ),
            self::makeOptionSpec(
                'string',
                'completionWebhookMethod',
                'POST',
                'Completion Webhook Method',
                'How to send completion webhook payload (GET|POST).'
            ),

            // Advanced options
            self::makeOptionSpec(
                'integer',
                'crawlConcurrency',
                '1',
                'Crawl Concurrency',
                'The maximum number of files that will be crawled at the same time.'
            ),
            self::makeOptionSpec(
                'array',
                'fileExtensionsToIgnore',
                '1',
                'File Extensions to Ignore',
                'Files with these extensions will be ignored while crawling.',
                implode(
                    "\n",
                    [
                        '.bat',
                        '.crt',
                        '.DS_Store',
                        '.git',
                        '.idea',
                        '.ini',
                        '.less',
                        '.map',
                        '.md',
                        '.mo',
                        '.php',
                        '.PHP',
                        '.phtml',
                        '.po',
                        '.pot',
                        '.scss',
                        '.sh',
                        '.sql',
                        '.SQL',
                        '.tar.gz',
                        '.tpl',
                        '.txt',
                        '.yarn',
                        '.zip',
                    ]
                )
            ),
            self::makeOptionSpec(
                'array',
                'filenamesToIgnore',
                '1',
                'Directory and File Names to Ignore',
                'Directories and files with these names will be ignored while crawling.',
                implode(
                    "\n",
                    [
                        '__MACOSX',
                        '.babelrc',
                        '.git',
                        '.gitignore',
                        '.gitkeep',
                        '.htaccess',
                        '.php',
                        '.svn',
                        '.travis.yml',
                        'backwpup',
                        'bower_components',
                        'bower.json',
                        'composer.json',
                        'composer.lock',
                        'config.rb',
                        'current-export',
                        'Dockerfile',
                        'gulpfile.js',
                        'latest-export',
                        'LICENSE',
                        'Makefile',
                        'node_modules',
                        'package.json',
                        'pb_backupbuddy',
                        'plugins/wp2static',
                        'previous-export',
                        'README',
                        'static-html-output-plugin',
                        '/tests/',
                        'thumbs.db',
                        'tinymce',
                        'wc-logs',
                        'wpallexport',
                        'wpallimport',
                        'wp-static-html-output', // exclude earlier version exports
                        'wp2static-addon',
                        'wp2static-crawled-site',
                        'wp2static-processed-site',
                        'wp2static-working-files',
                        'yarn-error.log',
                        'yarn.lock',
                    ]
                )
            ),
            self::makeOptionSpec(
                'array',
                'hostsToRewrite',
                '1',
                'Hosts to Rewrite',
                'Hosts to rewrite to the deployment URL.',
                'localhost'
            ),
            self::makeOptionSpec(
                'boolean',
                'skipURLRewrite',
                '0',
                'Skip URL Rewrite',
                'Don\'t rewrite any URLs. This may give a slight speed-up when the'
                . ' deployment URL is the same as WordPress\'s URL.'
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

        $opt_spec = self::optionSpecs()[ $name ];

        if ( ! $opt_spec ) {
            WsLog::w( 'Attempt to getValue of unknown option $name' );
            return '';
        }

        $table_name = $wpdb->prefix . self::$table_name;

        $sql = $wpdb->prepare(
            "SELECT value FROM $table_name WHERE" . ' name = %s LIMIT 1',
            $name
        );

        $option_value = $wpdb->get_var( $sql );

        if ( ! $option_value || ! is_string( $option_value ) ) {
            $option_value = (string) $opt_spec['default_value'];
        }

        if ( $opt_spec['type'] === 'password' ) {
            $option_value = self::encrypt_decrypt( 'decrypt', $option_value );
        }

        // default deploymentURL is '/', else remove trailing slash
        if ( $name === 'deploymentURL' ) {
            if ( $option_value !== '/' ) {
                $option_value = untrailingslashit( $option_value );
            }
        }

        $option_value = apply_filters( (string) $opt_spec['filter_name'], $option_value );

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
     * Get option default BLOB value
     *
     * @throws WP2StaticException
     * @return string option default BLOB value
     */
    public static function getDefaultBlobValue( string $name ) : string {
        $val = self::optionSpecs()[ $name ]['default_blob_value'];
        return $val ? $val : '';
    }

    /**
     * @return array<string>
     */
    public static function getDefaultLineDelimitedBlobValue( string $name ) : array {
        $vals = preg_split(
            '/\r\n|\r|\n/',
            self::getDefaultBlobValue( $name )
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
        $opt_spec = self::optionSpecs() [ $name ];

        // decrypt password fields
        if ( $opt_spec['type'] === 'password' ) {
            $option->value =
                self::encrypt_decrypt( 'decrypt', $option->value );
        }

        if ( $option ) {
            $option->unfiltered_value = $option->value;
            $option->value = apply_filters( (string) $opt_spec['filter_name'], $option->value );
        } elseif ( $opt_spec ) {
            $opt = array_merge( $opt_spec ); // Make a copy so we don't modify $cached_option_specs
            $opt['unfiltered_value'] = $opt_spec['default_value'];
            $opt['blob_value'] = $opt_spec['default_blob_value'];
            if ( $opt_spec['filter_name'] ) {
                $opt['value'] = apply_filters(
                    $opt_spec['filter_name'],
                    $opt_spec['default_value']
                );
            } else {
                $opt['value'] = $opt_spec['default_value'];
            }
            return $opt;
        }

        return (object) array_merge( $opt_spec, (array) $option );
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

        $options_map = [];
        foreach ( $options as $opt ) {
            $options_map[ $opt->name ] = (array) $opt;
        }

        $ret = [];
        foreach ( self::optionSpecs() as $opt_spec ) {
            $name = $opt_spec['name'];
            $opt = $options_map[ $name ];
            if ( ! $opt ) {
                 // Make a copy so we don't modify $cached_option_specs
                $opt = array_merge( $opt_spec );
                $opt['unfiltered_value'] = $opt_spec['default_value'];
                $opt['blob_value'] = $opt_spec['default_blob_value'];
                $opt['value'] = apply_filters(
                    (string) $opt_spec['filter_name'],
                    $opt_spec['default_value']
                );
                $ret[ $name ] = $opt;
            } else {
                $val = $opt['value'];

                if ( $opt_spec['type'] === 'password' ) {
                    $val = self::encrypt_decrypt( 'decrypt', $val );
                }

                $opt['unfiltered_value'] = $val;
                $opt['value'] = apply_filters( (string) $opt_spec['filter_name'], $val );
                $ret[ $name ] = (object) array_merge( $opt_spec, $opt );
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
                $crawl_concurrency = intval( $_POST['crawlConcurrency'] );
                $wpdb->update(
                    $table_name,
                    [ 'value' => $crawl_concurrency < 1 ? 1 : $crawl_concurrency ],
                    [ 'name' => 'crawlConcurrency' ]
                );

                $file_extensions_to_ignore = preg_replace(
                    '/^\s+|\s+$/m',
                    '',
                    $_POST['fileExtensionsToIgnore']
                );
                $wpdb->update(
                    $table_name,
                    [ 'blob_value' => $file_extensions_to_ignore ],
                    [ 'name' => 'fileExtensionsToIgnore' ]
                );

                $filenames_to_ignore = preg_replace(
                    '/^\s+|\s+$/m',
                    '',
                    $_POST['filenamesToIgnore']
                );
                $wpdb->update(
                    $table_name,
                    [ 'blob_value' => $filenames_to_ignore ],
                    [ 'name' => 'filenamesToIgnore' ]
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

                $wpdb->update(
                    $table_name,
                    [ 'value' => isset( $_POST['skipURLRewrite'] ) ? 1 : 0 ],
                    [ 'name' => 'skipURLRewrite' ]
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

