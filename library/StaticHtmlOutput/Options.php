<?php

class StaticHtmlOutput_Options {
    protected $_options = array();
    protected $_optionKey = null;
    protected $_options_keys = array(
        'additionalUrls',
        'allowOfflineUsage',
        'baseHREF',
        'baseUrl',
        'baseUrl-bitbucket',
        'baseUrl-bunnycdn',
        'baseUrl-folder',
        'baseUrl-ftp',
        'baseUrl-github',
        'baseUrl-gitlab',
        'baseUrl-netlify',
        'baseUrl-s3',
        'baseUrl-zip',
        'baseUrl-zip',
        'basicAuthPassword',
        'basicAuthUser',
        'bbBlobDelay',
        'bbBlobIncrement',
        'bbBranch',
        'bbPath',
        'bbRepo',
        'bbToken',
        'bunnyBlobDelay',
        'bunnyBlobIncrement',
        'bunnycdnAPIKey',
        'bunnycdnPullZoneName',
        'bunnycdnRemotePath',
        'cfDistributionId',
        'completionEmail',
        'crawl_increment',
        'discoverNewURLs',
        'excludeURLs',
        'ftpBlobDelay',
        'ftpBlobIncrement',
        'ftpPassword',
        'ftpRemotePath',
        'ftpServer',
        'ftpUsername',
        'ghBlobDelay',
        'ghBlobIncrement',
        'ghBranch',
        'ghPath',
        'ghRepo',
        'ghToken',
        'glBlobDelay',
        'glBlobIncrement',
        'glBranch',
        'glPath',
        'glProject',
        'glToken',
        'netlifyHeaders',
        'netlifyPersonalAccessToken',
        'netlifyRedirects',
        'netlifySiteID',
        'removeConditionalHeadComments',
        'removeHTMLComments',
        'removeWPLinks',
        'removeWPMeta',
        'rewrite_rules',
        'rename_rules',
        's3BlobDelay',
        's3BlobIncrement',
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
    );

    protected $_whitelisted_keys = array(
        'additionalUrls',
        'allowOfflineUsage',
        'baseHREF',
        'baseUrl',
        'baseUrl-bitbucket',
        'baseUrl-bunnycdn',
        'baseUrl-folder',
        'baseUrl-ftp',
        'baseUrl-github',
        'baseUrl-gitlab',
        'baseUrl-netlify',
        'baseUrl-s3',
        'baseUrl-zip',
        'baseUrl-zip',
        'basicAuthUser',
        'bbBlobDelay',
        'bbBlobIncrement',
        'bbBranch',
        'bbPath',
        'bbRepo',
        'bunnyBlobDelay',
        'bunnyBlobIncrement',
        'bunnycdnPullZoneName',
        'bunnycdnRemotePath',
        'cfDistributionId',
        'completionEmail',
        'crawl_increment',
        'discoverNewURLs',
        'excludeURLs',
        'ftpBlobDelay',
        'ftpBlobIncrement',
        'ftpRemotePath',
        'ftpServer',
        'ftpUsername',
        'ghBlobDelay',
        'ghBlobIncrement',
        'ghBranch',
        'ghPath',
        'ghRepo',
        'glBlobDelay',
        'glBlobIncrement',
        'glBranch',
        'glPath',
        'glProject',
        'netlifyHeaders',
        'netlifyRedirects',
        'netlifySiteID',
        'removeConditionalHeadComments',
        'removeHTMLComments',
        'removeWPLinks',
        'removeWPMeta',
        'rewrite_rules',
        'rename_rules',
        's3BlobDelay',
        's3BlobIncrement',
        's3Bucket',
        's3Key',
        's3Region',
        's3RemotePath',
        'selected_deployment_option',
        'targetFolder',
        'useActiveFTP',
        'useBaseHref',
        'useBasicAuth',
        'useRelativeURLs',
    );

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

        // NOTE: this is required, not certain why, investigate
        // and make more intuitive
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

    public function getAllOptions( $reveal_sensitive_values = false ) {
        $options_array = array();

        foreach ( $this->_options_keys as $key ) {

            $value = '*******************';

            if ( in_array( $key, $this->_whitelisted_keys ) ) {
                $value = $this->__get( $key );
            } elseif ( $reveal_sensitive_values ) {
                $value = $this->__get( $key );
            }

            $options_array[] = array(
                'Option name' => $key,
                'Value' => $value,
            );
        }

        return $options_array;
    }

    public function optionExists( $name ) {
        return in_array( $name, $this->_options_keys );
    }

    public function save() {
        return update_option( $this->_optionKey, $this->_options );
    }

    public function delete() {
        return delete_option( $this->_optionKey );
    }

    public function saveAllPostData() {
        foreach ( $this->_options_keys as $option ) {
            // TODO: set which fields should get which sanitzation upon saving
            // TODO: validate before save & avoid making empty settings fields
            $this->setOption( $option, filter_input( INPUT_POST, $option ) );
            $this->save();
        }
    }
}

