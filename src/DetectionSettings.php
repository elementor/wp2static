<?php

namespace WP2Static;

/*
    Singleton instance to allow instantiating once and allow reading
    static properties throughout plugin

    "Export" encapsulates both Generate + Deploy actions
*/
class DetectionSettings {

    private static $instance = null;

    /**
     * Detection Settings.
     *
     * @var array
     */
    private static $settings = array();

    /**
     * Set export settings.
     *
     */
    public function __construct() {
    }

    /**
     * Get setting via name
     *
     * @throws WP2StaticException
     * @returns mixed export setting value
     */
    public static function get( string $name )  {
        if ( self::$instance === null ) {
             self::$instance = new DetectionSettings();
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
     * Get setting via name
     *
     * @throws WP2StaticException
     */
    public static function loadSettingsFromDBOptions( array $options ) : void {
        if ( self::$instance === null ) {
             self::$instance = new DetectionSettings();
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

