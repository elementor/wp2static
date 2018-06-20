<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Name:       Simply Static
 * Plugin URI:        http://simplystatic.co/
 * Description:       Produces a static HTML version of your WordPress install and adjusts URLs accordingly.
 * Version:           2.1.0
 * Author:            Code of Conduct LLC
 * Author URI:        http://codeofconduct.co/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       simply-static
 * Domain Path:       /languages
 */

/**
 * Check that we're using at least version 5.3 of PHP
 */
if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
	if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
		if ( ! is_plugin_active( plugin_basename( __FILE__ ) ) ) {
			$message = __( '<b>Simply Static</b> requires PHP 5.3 or higher, and the plugin has now deactivated itself.', 'simply-static' ) .
				'<br />' .
				__( 'Contact your hosting company or your system administrator and ask for an upgrade to version 5.3 of PHP.', 'simply-static' );
			printf( "<p style='color: #444; font-size: 13px; line-height: 1.5; font-family: -apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Oxygen-Sans,Ubuntu,Cantarell,\"Helvetica Neue\",sans-serif'>%s</p>", $message );
			exit();
		}

		deactivate_plugins( __FILE__ );
	}
} else {
	// Loading up Simply Static in a separate file so that there's nothing to
	// trigger a PHP error in this file (e.g. by using namespacing)
	require_once plugin_dir_path( __FILE__ ) . 'includes/load.php';
}
