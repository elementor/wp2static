<?php

class StaticHtmlOutput_PostSettings {

    public static function get() {

        $settings = array();


        $post_array_keys = array(

            // deployment method
            'selected_deployment_option',

            // generic
            'baseUrl',

            // crawling settings
            'additionalUrls',
            'allowOfflineUsage',
            'basicAuthPassword',
            'basicAuthUser',
            'discoverNewURLs',

            'baseUrl-bunnycdn',
            'baseUrl-dropbox',
            'baseUrl-folder',
            'baseUrl-ftp',
            'baseUrl-github',
            'baseUrl-netlify',
            'baseUrl-s3',
            'baseUrl-zip',


            // processor
            'removeConditionalHeadComments',
            'rewritePLUGINDIR',
            'rewriteTHEMEDIR',
            'rewriteTHEMEROOT',
            'rewriteUPLOADS',
            'rewriteWPCONTENT',
            'rewriteWPINC',
            'rewriteWPPaths',
            'removeWPMeta',
            'removeWPLinks',
            'useBaseHref',
            'useBasicAuth',
            'useRelativeURLs',

            // advanced
            'workingDirectory',
            'crawl_increment',
            'diffBasedDeploys',

            // folder
            'targetFolder',

            //zip
            'allowOfflineUsage',

            // github
            'ghBranch',
            'ghPath',
            'ghToken',
            'ghRepo',

            // ftp 
            'ftpPassword',
            'ftpRemotePath',
            'ftpServer',
            'ftpUsername',
            'useActiveFTP',

            // bunnyDN
            'bunnycdnAPIKey',
            'bunnycdnPullZoneName',
            'bunnycdnRemotePath',

            // s3 / cloudfront
            's3Bucket',
            's3Key',
            's3Region',
            's3RemotePath',
            's3Secret',
            'cfDistributionId',

            // dropbox
            'dropboxAccessToken',
            'dropboxFolder',

            // netlify
            'netlifyPersonalAccessToken',
            'netlifySiteID',

            // wp environment
            'wp_site_url',
            'wp_site_path',
            'wp_site_subdir',
            'wp_uploads_path',
            'wp_uploads_url',
            'baseUrl',
        );

        foreach ( $post_array_keys as $key ) {
            $settings[$key] =
                isset( $_POST[$key] ) ?
                $_POST[$key] :
                null;
        }

        // TODO: shift this logic to places it's actually used
        $settings['working_directory'] =
            isset( $_POST['workingDirectory'] ) ?
            $_POST['workingDirectory'] :
            $settings['wp_uploads_path'];

        return $settings;
    }
}
