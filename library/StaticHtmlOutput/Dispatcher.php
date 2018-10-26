<?php

// TODO: this file / methods are being called on public site page loads,
// should only be triggered when in the dashboard!
$ajax_action = isset( $_POST['ajax_action'] ) ? $_POST['ajax_action'] : '';

// bypass instantiating plugin for specific AJAX requests
if ( $ajax_action === 'crawl_site' || $ajax_action === 'crawl_again' ) {
    require_once dirname( __FILE__ ) .
        '/SiteCrawler.php';

    wp_die();
    return null;
} elseif ( $ajax_action == 'bitbucket_prepare_export' ) {
    require_once dirname( __FILE__ ) .
        '/Netlify.php';

    wp_die();
    return null;
} elseif ( $ajax_action == 'bitbucket_upload_files' ) {
    require_once dirname( __FILE__ ) .
        '/Netlify.php';

    wp_die();
    return null;
} elseif ( $ajax_action == 'github_prepare_export' ) {
    require_once dirname( __FILE__ ) .
        '/GitHub.php';

    wp_die();
    return null;
} elseif ( $ajax_action == 'github_upload_blobs' ) {
    require_once dirname( __FILE__ ) .
        '/GitHub.php';

    wp_die();
    return null;
} elseif ( $ajax_action == 'github_finalise_export' ) {
    require_once dirname( __FILE__ ) .
        '/GitHub.php';

    wp_die();
    return null;
} elseif ( $ajax_action == 'test_blob_create' ) {
    require_once dirname( __FILE__ ) .
        '/GitHub.php';

    wp_die();
    return null;
} elseif ( $ajax_action == 'gitlab_prepare_export' ) {
    require_once dirname( __FILE__ ) .
        '/GitLab.php';

    wp_die();
    return null;
} elseif ( $ajax_action == 'gitlab_upload_files' ) {
    require_once dirname( __FILE__ ) .
        '/GitLab.php';

    wp_die();
    return null;
} elseif ( $ajax_action == 'test_gitlab' ) {
    require_once dirname( __FILE__ ) .
        '/GitLab.php';

    wp_die();
    return null;
} elseif ( $ajax_action == 'test_bitbucket' ) {
    require_once dirname( __FILE__ ) .
        '/Netlify.php';

    wp_die();
    return null;
} elseif ( $ajax_action == 'test_netlify' ) {
    require_once dirname( __FILE__ ) .
        '/Netlify.php';

    wp_die();
    return null;
} elseif ( $ajax_action == 'netlify_do_export' ) {
    require_once dirname( __FILE__ ) .
        '/Netlify.php';

    wp_die();
    return null;
} elseif ( $ajax_action == 'test_s3' ) {
    require_once dirname( __FILE__ ) .
        '/S3.php';

    wp_die();
    return null;
} elseif ( $ajax_action == 's3_prepare_export' ) {
    require_once dirname( __FILE__ ) .
        '/S3.php';

    wp_die();
    return null;
} elseif ( $ajax_action == 's3_transfer_files' ) {
    require_once dirname( __FILE__ ) .
        '/S3.php';

    wp_die();
    return null;
} elseif ( $ajax_action == 'cloudfront_invalidate_all_items' ) {
    require_once dirname( __FILE__ ) .
        '/S3.php';

    wp_die();
    return null;
} elseif ( $ajax_action == 'test_ftp' ) {
    require_once dirname( __FILE__ ) .
        '/FTP.php';

    wp_die();
    return null;
} elseif ( $ajax_action == 'ftp_prepare_export' ) {
    require_once dirname( __FILE__ ) .
        '/FTP.php';

    wp_die();
    return null;
} elseif ( $ajax_action == 'ftp_transfer_files' ) {
    require_once dirname( __FILE__ ) .
        '/FTP.php';

    wp_die();
    return null;
}
