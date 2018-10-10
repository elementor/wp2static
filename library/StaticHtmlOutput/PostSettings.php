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
            'allowOfflineUsage',
            'basicAuthPassword',
            'basicAuthUser',
            'discoverNewURLs',
        );

        $key_sets['processing'] = array(
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
        );

        $key_sets['advanced'] = array(
            'workingDirectory',
            'crawl_increment',
            'diffBasedDeploys',
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
        );

        foreach ( $sets as $set ) {
            $target_keys = array_merge( $target_keys, $key_sets[$set] );
        }

        foreach ( $target_keys as $key ) {
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

        return array_filter($settings);

        return $settings;
    }
}
