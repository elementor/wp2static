<?php
/**
 * Uninstall Simply Static
 */

// exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

// Delete Simply Static's settings
delete_option( 'simply-static' );

require_once plugin_dir_path( __FILE__ ) . 'includes/class-ss-plugin.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/models/class-ss-model.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/models/class-ss-page.php';

// Drop the Pages table
Simply_Static\Page::drop_table();
