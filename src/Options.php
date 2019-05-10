<?php

namespace WP2Static;

class Options {
    public $wp2static_options = array();
    public $wp2static_option_key = null;
    public $wp2static_options_keys = array(
        'additionalUrls',
        'allowOfflineUsage',
        'baseHREF',
        'baseUrl',
        'baseUrl-folder',
        'baseUrl-zip',
        'basicAuthPassword',
        'basicAuthUser',
        'completionEmail',
        'crawl_delay',
        'crawl_increment',
        'crawlPort',
        'crawlUserAgent',
        'delayBetweenAPICalls',
        'detectArchives',
        'detectAttachments',
        'detectCategoryPagination',
        'detectChildTheme',
        'detectCommentPagination',
        'detectComments',
        'detectCustomPostTypes',
        'detectFeedURLs',
        'detectHomepage',
        'detectPages',
        'detectParentTheme',
        'detectPluginAssets',
        'detectPostPagination',
        'detectPosts',
        'detectUploads',
        'detectVendorCacheDirs',
        'detectWPIncludesAssets',
        'deployBatchSize',
        'excludeURLs',
        'parse_css',
        'removeConditionalHeadComments',
        'removeHTMLComments',
        'removeCanonical',
        'removeWPLinks',
        'removeWPMeta',
        'rewrite_rules',
        'rename_rules',
        'selected_deployment_option',
        'targetFolder',
        'useActiveFTP',
        'useBaseHref',
        'useBasicAuth',
        'useRelativeURLs',
    );

    public $whitelisted_keys = array(
        'additionalUrls',
        'allowOfflineUsage',
        'baseHREF',
        'baseUrl',
        'baseUrl-bitbucket',
        'baseUrl-folder',
        'baseUrl-zip',
        'baseUrl-zip',
        'basicAuthUser',
        'completionEmail',
        'crawl_delay',
        'crawl_increment',
        'crawlPort',
        'crawlUserAgent',
        'delayBetweenAPICalls',
        'detectArchives',
        'detectAttachments',
        'detectCategoryPagination',
        'detectChildTheme',
        'detectCommentPagination',
        'detectComments',
        'detectCustomPostTypes',
        'detectFeedURLs',
        'detectHomepage',
        'detectPages',
        'detectParentTheme',
        'detectPluginAssets',
        'detectPostPagination',
        'detectPosts',
        'detectUploads',
        'detectVendorCacheDirs',
        'detectWPIncludesAssets',
        'deployBatchSize',
        'excludeURLs',
        'ghBranch',
        'ghCommitMessage',
        'ghPath',
        'ghRepo',
        'parse_css',
        'removeConditionalHeadComments',
        'removeHTMLComments',
        'removeCanonical',
        'removeWPLinks',
        'removeWPMeta',
        'rewrite_rules',
        'rename_rules',
        'selected_deployment_option',
        'targetFolder',
        'useActiveFTP',
        'useBaseHref',
        'useBasicAuth',
        'useRelativeURLs',
    );

    public function __construct( $option_key ) {
        $this->wp2static_options_keys = apply_filters(
            'wp2static_add_option_keys',
            $this->wp2static_options_keys
        );

        $this->whitelisted_keys = apply_filters(
            'wp2static_whitelist_option_keys',
            $this->whitelisted_keys
        );

        $options = get_option( $option_key );

        if ( false === $options ) {
            $options = array();
        }

        $this->wp2static_options = $options;
        $this->wp2static_option_key = $option_key;
    }

    public function __set( $name, $value ) {
        $this->wp2static_options[ $name ] = $value;

        if ( empty( $value ) ) {
            unset( $this->wp2static_options[ $name ] );
        }

        // NOTE: this is required, not certain why, investigate
        // and make more intuitive
        return $this;
    }

    public function setOption( $name, $value ) {
        return $this->__set( $name, $value );
    }

    public function __get( $name ) {
        $value = array_key_exists( $name, $this->wp2static_options ) ?
            $this->wp2static_options[ $name ] : null;
        return $value;
    }

    public function getOption( $name ) {
        return $this->__get( $name );
    }

    public function getAllOptions( $reveal_sensitive_values = false ) {
        $options_array = array();

        foreach ( $this->wp2static_options_keys as $key ) {

            $value = '*******************';

            if ( in_array( $key, $this->whitelisted_keys ) ) {
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

    public function getSettings() {
        $settings = [];

        $this->wp2static_options_keys = apply_filters(
            'wp2static_add_option_keys',
            $this->wp2static_options_keys
        );

        foreach ( $this->wp2static_options_keys as $key ) {
            $value = $this->__get( $key );

            $settings[ $key ] = $value;
        }

        /*
            Settings requiring transformation
        */
        $settings['crawl_increment'] =
            isset( $settings['crawl_increment'] ) ?
            (int) $settings['crawl_increment'] :
            1;

        $settings['baseUrl'] =
            isset( $settings['baseUrl'] ) ?
            rtrim( $settings['baseUrl'], '/' ) . '/' :
            SiteInfo::getUrl( 'site' );

        return $settings;
    }

    public function optionExists( $name ) {
        return in_array( $name, $this->wp2static_options_keys );
    }

    public function save() {
        return update_option(
            $this->wp2static_option_key,
            $this->wp2static_options
        );
    }

    public function delete() {
        return delete_option( $this->wp2static_option_key );
    }

    public function saveAllPostData() {
        $this->wp2static_options_keys = apply_filters(
            'wp2static_add_option_keys',
            $this->wp2static_options_keys
        );

        foreach ( $this->wp2static_options_keys as $option ) {
            // TODO: set which fields should get which sanitzation upon saving
            // TODO: validate before save & avoid making empty settings fields
            $this->setOption( $option, filter_input( INPUT_POST, $option ) );
            $this->save();
        }
    }
}

