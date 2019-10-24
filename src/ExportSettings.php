<?php

namespace WP2Static;

/*
    Singleton instance to allow instantiating once and allow reading
    static properties throughout plugin

    "Export" encapsulates both Generate + Deploy actions
*/
class ExportSettings {

    private static $instance = null;

    /**
     * Site info.
     *
     * @var array
     */
    private static $settings = array();

    /**
     * Set export settings.
     *
     */
    public function __construct() {
        // add filter to allow user to specify extra downloadable extensions
        $crawlable_filetypes = [];
        $crawlable_filetypes['img'] = 1;
        $crawlable_filetypes['jpeg'] = 1;
        $crawlable_filetypes['jpg'] = 1;
        $crawlable_filetypes['png'] = 1;
        $crawlable_filetypes['webp'] = 1;
        $crawlable_filetypes['gif'] = 1;
        $crawlable_filetypes['svg'] = 1;

        // properties which should not change during plugin execution
        self::$settings = [
            'crawlable_filetypes' => $crawlable_filetypes,

        ];
    }

    /**
     * Get setting via name
     *
     * @throws WP2StaticException
     * @returns mixed export setting value
     */
    public static function get( string $name )  {
        if ( self::$instance === null ) {
             self::$instance = new ExportSettings();
        }

        $key = $name;

        if (
             ! array_key_exists( $key, self::$settings ) ||
             ! self::$settings[ $key ]
        ) {
            return '';
        }

        return self::$settings[ $key ];
    }

    /**
     * Set destination URL dynamically
     *
     * @throws WP2StaticException
     */
    public static function setDestinationURL( string $url ) : void {
        if ( self::$instance === null ) {
             self::$instance = new ExportSettings();
        }

        if ( ! is_string( $url ) ) {
            $err = 'Cannot set empty Destination URL';
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        self::$settings[ 'destination_url' ] = $url;
    }

    /**
     * Load rewrite rules (depends on destination_url)
     *
     * Note: these differ than self::$settings['rewriteRules'] 
     * which are the user-defined rewrite rules
     *
     * @throws WP2StaticException
     */
    public static function loadRewriteRules() : void {
        if ( self::$instance === null ) {
             self::$instance = new ExportSettings();
        }

        if ( ! self::$settings['destination_url'] ) {
            $err = 'Destination URL not defined';
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        $rewrite_rules = RewriteRules::generate(
            SiteInfo::getUrl('site'),
            self::$settings['destination_url']);

        self::$settings[ 'rewrite_rules' ] = $rewrite_rules;
    }

    /**
     * Get setting via name
     *
     * @throws WP2StaticException
     */
    public static function loadSettingsFromDBOptions( array $options ) : void {
        if ( self::$instance === null ) {
             self::$instance = new ExportSettings();
        }

        self::$settings = array_merge(
            self::$settings,
            $options
        );
    }

    public function debug() : void {
        var_export( self::$settings );
    }

    /**
     *  Get all WP site info
     *
     *  @return mixed[]
     */
    public static function getAllSettings() : array {
        return self::$settings;
    }
}

