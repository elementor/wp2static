<?php

namespace WP2Static;

use Exception;

class EnvironmentalInfo {
    /*
     * Returns environmental info for logging
     *
     * @param string lines of environmental info
     * @return string lines of environmental info
     */
    public static function log(
        string $plugin_version,
        Options $options,
        SiteInfo $site_info
    ) : string {
        // TODO: remove WP calls already done in SiteInfo
        $info = array(
            'EXPORT START: ' . date( 'Y-m-d h:i:s' ),
            'PLUGIN VERSION: ' . $plugin_version,
            'PHP VERSION: ' . phpversion(),
            'OS VERSION: ' . php_uname(),
            'PHP MEMORY LIMIT: ' . ini_get( 'memory_limit' ),
            'WP VERSION: ' . get_bloginfo( 'version' ),
            'WP URL: ' . get_bloginfo( 'url' ),
            'WP SITEURL: ' . get_option( 'siteurl' ),
            'WP HOME: ' . get_option( 'home' ),
            'WP ADDRESS: ' . get_bloginfo( 'wpurl' ),
            defined( 'WP_CLI' ) ? 'WP-CLI: YES' : 'WP-CLI: NO',
            'STATIC EXPORT URL: ' . $site_info->destination_url,
            'PERMALINK STRUCTURE: ' . get_option( 'permalink_structure' ),
        );

        if ( isset( $_SERVER['SERVER_SOFTWARE'] ) ) {
            $info[] = 'SERVER SOFTWARE: ' . $_SERVER['SERVER_SOFTWARE'];
        }

        $info[] = 'ACTIVE PLUGINS: ';

        $active_plugins = get_option( 'active_plugins' );

        foreach ( $active_plugins as $active_plugin ) {
            $info[] = $active_plugin;
        }

        $info[] = 'ACTIVE THEME: ';

        $theme = wp_get_theme();

        $info[] = $theme->get( 'Name' ) . ' is version ' .
            $theme->get( 'Version' );

        $info[] = 'WP2STATIC OPTIONS: ';

        $options = $this->options->getAllOptions( false );

        foreach ( $options as $key[] => $value ) {
            $info[] = "{$value['Option name']}: {$value['Value']}";
        }

        $info[] = 'SITE URL PATTERNS: ' .
            $this->rewrite_rules['site_url_patterns'];

        $info[] = 'DESTINATION URL PATTERNS: ' .
            $this->rewrite_rules['destination_url_patterns'];

        $extensions = get_loaded_extensions();

        $info[] = 'INSTALLED EXTENSIONS: ' .
            join( PHP_EOL, $extensions );

        WsLog::lines( $info );
    }
}
