<?php

class WPSHO_DBSettings {

    public static function get( $sets = array() ) {
        $plugin = StaticHtmlOutput_Controller::getInstance();

//        error_log(print_r($plugin->options, true));die();

        $settings = array();
        $key_sets = array();
        $target_keys = array();

        $key_sets['general'] = array(
            'selected_deployment_option',
            'baseUrl',
        );

        $key_sets['crawling'] = array(
            'additionalUrls',
            'excludeURLs',
            'basicAuthPassword',
            'basicAuthUser',
            'discoverNewURLs',
        );

        $key_sets['processing'] = array(
            'removeConditionalHeadComments',
            'allowOfflineUsage',
            'rewritePLUGINDIR',
            'rewriteTHEMEDIR',
            'rewriteTHEMEROOT',
            'rewriteUPLOADS',
            'rewriteWPCONTENT',
            'rewriteWPINC',
            'rewriteWPPaths',
            'new_wp_content_path',
            'new_themes_path',
            'new_wp_content_path',
            'new_active_theme_path',
            'new_wp_inc_path',
            'new_themes_path',
            'new_uploads_path',
            'new_wp_content_path',
            'new_plugins_path',
            'new_wp_content_path',
            'new_wpinc_path',
            'processing_method',
            'removeWPMeta',
            'removeWPLinks',
            'useBaseHref',
            'useBasicAuth',
            'useRelativeURLs',
            'removeConditionalHeadComments',
            'removeWPMeta',
            'removeWPLinks',
            'removeHTMLComments',
        );

        $key_sets['advanced'] = array(
            'workingDirectory',
            'crawl_increment',
            'diffBasedDeploys',
            'completionEmail',
        );

        $key_sets['folder'] = array(
            'baseUrl-folder',
            'targetFolder',
        );

        $key_sets['zip'] = array(
            'baseUrl-zip',
            'allowOfflineUsage',
        );

        $key_sets['github'] = array(
            'baseUrl-github',
            'ghBranch',
            'ghPath',
            'ghToken',
            'ghRepo',
            'ghBlobIncrement',
            'ghBlobDelay',
        );

        $key_sets['bitbucket'] = array(
            'baseUrl-bitbucket',
            'bbBranch',
            'bbPath',
            'bbToken',
            'bbRepo',
            'bbBlobIncrement',
            'bbBlobDelay',
        );

        $key_sets['gitlab'] = array(
            'baseUrl-gitlab',
            'glBranch',
            'glPath',
            'glToken',
            'glProject',
            'glBlobIncrement',
            'glBlobDelay',
        );

        $key_sets['ftp'] = array(
            'baseUrl-ftp',
            'ftpPassword',
            'ftpRemotePath',
            'ftpServer',
            'ftpUsername',
            'useActiveFTP',
            'ftpBlobIncrement',
            'ftpBlobDelay',
        );

        $key_sets['bunnycdn'] = array(
            'baseUrl-bunnycdn',
            'bunnycdnAPIKey',
            'bunnycdnPullZoneName',
            'bunnycdnRemotePath',
            'bunnyBlobIncrement',
            'bunnyBlobDelay',
        );

        $key_sets['s3'] = array(
            'baseUrl-s3',
            'cfDistributionId',
            's3Bucket',
            's3Key',
            's3Region',
            's3RemotePath',
            's3Secret',
            's3BlobIncrement',
            's3BlobDelay',
        );

        $key_sets['netlify'] = array(
            'baseUrl-netlify',
            'netlifyHeaders',
            'netlifyPersonalAccessToken',
            'netlifyRedirects',
            'netlifySiteID',
        );


        $key_sets['wpenv'] = array(
            'wp_site_url',
            'wp_site_path',
            'wp_site_subdir',
            'wp_uploads_path',
            'wp_uploads_url',
            'baseUrl',
            'wp_active_theme',
            'wp_themes',
            'wp_uploads',
            'wp_plugins',
            'wp_content',
            'wp_inc',
        );

        foreach ( $sets as $set ) {
            $target_keys = array_merge( $target_keys, $key_sets[ $set ] );
        }

        foreach ( $target_keys as $key ) {
            $settings[ $key ] = $plugin->options->{ $key };
        }

        // NOTE: CLI method doesn't have WPSite object sent via post,
        // so let's grab it here
        require_once dirname( __FILE__ ) . '/../StaticHtmlOutput/WPSite.php';
        $wp_site = new WPSite();

        foreach ( $key_sets['wpenv'] as $key ) {
            $settings[ $key ] = $wp_site->{ $key };
        }
        
        // NOTE: override from missing setting in CLI chain 
        $settings['wp_uploads_url'] = $wp_site->uploads_url;
        $settings['wp_site_url'] = $wp_site->site_url;
        $settings['wp_site_path'] = $wp_site->site_path;

/*
    // TODO: Much more coming back in wp_site, but with different names
    // set from the view. Need to normalize these keys
    // error_log(print_r($wp_site, true));die();

    [uploads_url] => http://localhost/wp-content/uploads
    [site_url] => http://localhost/
    [site_path] => /var/www/htdocs/
    [plugins_path] => /var/www/htdocs/wp-content/plugins
    [wp_uploads_path] => /var/www/htdocs/wp-content/uploads
    [wp_includes_path] => /var/www/htdocs/wp-includes
    [wp_contents_path] =>
    [theme_root_path] => /var/www/htdocs/wp-content/themes
    [parent_theme_path] => /var/www/htdocs/wp-content/themes/twentyseventeen
    [child_theme_path] => /var/www/htdocs/wp-content/themes/twentyseventeen
    [child_theme_active] =>
    [permalink_structure] => /pages/%postname%/
    [wp_inc] => /wp-includes
    [wp_content] => //var/www/htdocs/wp-content
    [wp_uploads] => /wp-content/uploads
    [wp_plugins] => /wp-content/plugins
    [wp_themes] => /wp-content/themes
    [wp_active_theme] => /wp-content/themes/twentyseventeen
    [subdirectory] =>
    [uploads_writable] => 1
    [permalinks_set] => 18
    [curl_enabled] => 1
*/


        /*
            Settings requiring transformation
        */

        // TODO: shift this logic to places it's actually used
        $settings['working_directory'] =
            isset( $plugin->options->working_directory ) ?
            $plugin->options->working_directory :
            $plugin->options->wp_uploads_path;


        $settings['crawl_increment'] =
            isset( $plugin->options->crawl_increment ) ?
            (int) $plugin->options->crawl_increment :
            1;

        // any baseUrl required if creating an offline ZIP
        $settings['baseUrl'] = rtrim( $plugin->options->baseUrl, '/' ) . '/';
        // TODO: detect if empty, set to 'http://example.com/'; 

        return array_filter( $settings );
    }
}

