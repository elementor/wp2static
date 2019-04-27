<?php

// @codingStandardsIgnoreStart
$ajax_action = isset( $_POST['ajax_action'] ) ? $_POST['ajax_action'] : '';
// @codingStandardsIgnoreEnd

$deployers_dir = dirname( __FILE__ ) . '/../deployers';

// NOTE: bypass instantiating plugin for specific AJAX requests
if ( $ajax_action === 'crawl_site' || $ajax_action === 'crawl_again' ) {
    require_once dirname( __FILE__ ) .
        '/WP2Static.php';
    require_once dirname( __FILE__ ) .
        '/SiteCrawler.php';

    wp_die();
    return null;
}

