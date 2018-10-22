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
            'removeWPMeta',
            'removeWPLinks',
            'useBaseHref',
            'useBasicAuth',
            'useRelativeURLs',
            'removeConditionalHeadComments',
            'removeWPMeta',
            'removeWPLinks',
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
            'glRepo',
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
        );

        $key_sets['bunnycdn'] = array(
            'baseUrl-bunnycdn',
            'bunnycdnAPIKey',
            'bunnycdnPullZoneName',
            'bunnycdnRemotePath',
        );

        $key_sets['s3'] = array(
            'baseUrl-s3',
            's3Bucket',
            's3Key',
            's3Region',
            's3RemotePath',
            's3Secret',
            'cfDistributionId',
        );

        $key_sets['dropbox'] = array(
            'baseUrl-dropbox',
            'dropboxAccessToken',
            'dropboxFolder',
        );

        $key_sets['netlify'] = array(
            'baseUrl-netlify',
            'netlifyPersonalAccessToken',
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

        // TODO: shift this logic to places it's actually used
        $settings['working_directory'] =
            isset( $_POST['workingDirectory'] ) ?
            $_POST['workingDirectory'] :
            $settings['wp_uploads_path'];

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

