<?php

class WPSHO_PostSettings {

    public static function get( $sets = array() ) {

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
            'useBasicAuth',
            'basicAuthPassword',
            'basicAuthUser',
            'discoverNewURLs',
        );

        $key_sets['processing'] = array(
            'removeConditionalHeadComments',
            'allowOfflineUsage',
            'baseHREF',
            'rewrite_rules',
            'rename_rules',
            'removeWPMeta',
            'removeWPLinks',
            'useBaseHref',
            'useRelativeURLs',
            'removeConditionalHeadComments',
            'removeWPMeta',
            'removeWPLinks',
            'removeHTMLComments',
        );

        $key_sets['advanced'] = array(
            'crawl_increment',
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
            $settings[ $key ] =
                isset( $_POST[ $key ] ) ?
                $_POST[ $key ] :
                null;
        }

        /*
            Settings requiring transformation
        */

        $settings['crawl_increment'] =
            isset( $_POST['crawl_increment'] ) ?
            (int) $_POST['crawl_increment'] :
            1;

        // any baseUrl required if creating an offline ZIP
        $settings['baseUrl'] =
            isset( $_POST['baseUrl'] ) ?
            rtrim( $_POST['baseUrl'], '/' ) . '/' :
            'http://example.com/';

        return array_filter( $settings );
    }
}

