<?php

class StaticHtmlOutput_Options {
    protected $_options = array();
    protected $_optionKey = null;

    public function __construct( $optionKey ) {
        $options = get_option( $optionKey );

        if ( false === $options ) {
            $options = array();
        }

        $this->_options = $options;
        $this->_optionKey = $optionKey;
    }

    public function __set( $name, $value ) {
        $this->_options[ $name ] = $value;

        return $this;
    }

    public function setOption( $name, $value ) {
        return $this->__set( $name, $value );
    }

    public function __get( $name ) {
        $value = array_key_exists( $name, $this->_options ) ?
            $this->_options[ $name ] : null;
        return $value;
    }

    public function getOption( $name ) {
        return $this->__get( $name );
    }

    public function save() {
        return update_option( $this->_optionKey, $this->_options );
    }

    public function delete() {
        return delete_option( $this->_optionKey );
    }

    public function saveAllPostData() {
        $options_to_save = array(
            'additionalUrls',
            'excludeURLs',
            'allowOfflineUsage',
            'baseUrl',
            'baseUrl-bunnycdn',
            'baseUrl-dropbox',
            'baseUrl-folder',
            'baseUrl-ftp',
            'baseUrl-github',
            'baseUrl-netlify',
            'baseUrl-s3',
            'baseUrl-zip',
            'baseUrl-zip',
            'basicAuthPassword',
            'basicAuthUser',
            'bunnycdnAPIKey',
            'bunnycdnPullZoneName',
            'bunnycdnRemotePath',
            'completionEmail',
            'cfDistributionId',
            'crawl_increment',
            'diffBasedDeploys',
            'discoverNewURLs',
            'dropboxAccessToken',
            'dropboxFolder',
            'ftpPassword',
            'ftpRemotePath',
            'ftpServer',
            'ftpUsername',
            'ghBranch',
            'ghPath',
            'ghToken',
            'ghRepo',
            'ghBlobIncrement',
            'ghBlobDelay',
            'netlifyPersonalAccessToken',
            'netlifySiteID',
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
            's3Bucket',
            's3Key',
            's3Region',
            's3RemotePath',
            's3Secret',
            'selected_deployment_option',
            'targetFolder',
            'useActiveFTP',
            'useBaseHref',
            'useBasicAuth',
            'useRelativeURLs',
            'workingDirectory',
        );

        foreach ( $options_to_save as $option ) {
            // TODO: set which fields should get which sanitzation upon saving
            // TODO: validate before save & avoid making empty settings fields
            $this->setOption( $option, filter_input( INPUT_POST, $option ) );
            $this->save();
        }
    }
}

