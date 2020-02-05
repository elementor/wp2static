<?php

namespace WP2Static;

use WP_CLI;

class Options {
    public $wp2static_options = [];
    public $wp2static_option_key = null;
    public $wp2static_options_keys = [
        // CONFIRMED CORE
        // ## 1 ## detection
        // set defaults, allow filter to override from other plugin 
        'detectCustomPostTypes',
        // 'detectFeedURLs', // move to feed detection/processor addon
        'detectPages',
        'detectPosts',
        'detectUploads',
        // 'detectWPIncludesAssets', moved to addon, enable by default?
        // ## 2 ## crawling
        'basicAuthPassword',
        'basicAuthUser',
        'includeDiscoveredAssets',
        // ## 3 ## post-processing
        'deploymentURL',
        // MOVE: advanced HTML Processor addon
        'allowOfflineUsage', // move to advanced HTML Processor addon
        'baseHREF',
        'useBaseHref',
        'useDocumentRelativeURLs',
        'useSiteRootRelativeURLs',
        'createEmptyFavicon', // move to HTML cleanup addon
        'forceHTTPS',
        'forceRewriteSiteURLs', // ?
        'removeCanonical', // move to HTML cleanup addon
        'removeConditionalHeadComments', // move to HTML cleanup addon
        'removeHTMLComments', // move to HTML cleanup addon
        'removeWPLinks', // move to HTML cleanup addon
        'removeRobotsNoIndex', // move to HTML cleanup addon
        'removeWPMeta', // move to HTML cleanup addon
        // MOVE: advanced CSS Processor addon
        'parseCSS', // move to advanced CSS parser addon
        // MOVE: advanced Post-Processing addon
        'renameRules', // should be able to use just one of these?
        'rewriteRules', // arb. code rewrite shld be supported by hooks already
        // ## 4 ## deployment
        'completionEmail',
        'completionWebhook', // new
        'deployBatchSize',
        'delayBetweenAPICalls', // move to deployer addons
        // ## 5 ## MOVE To new JobsOptions
        'redeployOnPostUpdates', // extend with more options
    ];

    public $whitelisted_keys = [
        // CONFIRMED CORE
        // ## 1 ## detection
        // set defaults, allow filter to override from other plugin 
        'detectCategoryPagination', // questionable - move to addon
        'detectCustomPostTypes',
        // 'detectFeedURLs', // move to feed detection/processor addon
        'detectPages',
        'detectPostPagination',
        'detectPosts',
        'detectUploads',
        // 'detectWPIncludesAssets', moved to addon, enable by default?
        // ## 2 ## crawling
        'basicAuthPassword',
        'basicAuthUser',
        'includeDiscoveredAssets',
        // ## 3 ## post-processing
        'renameRules', // should be able to use just one of these?
        'rewriteRules', // arb. code rewrite shld be supported by hooks already
        // MOVE: advanced HTML Processor addon
        'allowOfflineUsage', // move to advanced HTML Processor addon
        'baseHREF',
        'useBaseHref',
        'useDocumentRelativeURLs',
        'useSiteRootRelativeURLs',
        'createEmptyFavicon', // move to HTML cleanup addon
        'forceHTTPS',
        'forceRewriteSiteURLs', // ?
        'removeCanonical', // move to HTML cleanup addon
        'removeConditionalHeadComments', // move to HTML cleanup addon
        'removeHTMLComments', // move to HTML cleanup addon
        'removeWPLinks', // move to HTML cleanup addon
        'removeRobotsNoIndex', // move to HTML cleanup addon
        'removeWPMeta', // move to HTML cleanup addon
        // MOVE: advanced CSS Processor addon
        'parseCSS', // move to advanced CSS parser addon
        // ## 4 ## deployment
        'completionEmail',
        'completionWebhook', // new
        'deployBatchSize',
        'delayBetweenAPICalls', // move to deployer addons
        // ## 5 ## MOVE To new JobsOptions
        'redeployOnPostUpdates', // extend with more options
    ];

    public function __construct( string $option_key ) {
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

    /**
     *  Set an option
     *
     * @param mixed $value new value for option
     * @throws WP2StaticException
     */
    public function __set( string $name, $value ) : Options {
        $this->wp2static_options[ $name ] = $value;

        if ( ! array_key_exists( $name, $this->wp2static_options ) ) {
            $err = 'Trying to save an unrecognized option: ' . $name;
            WsLog::l( $err );
            throw new WP2StaticException( $err );
        }

        if ( empty( $value ) ) {
            unset( $this->wp2static_options[ $name ] );
        }

        return $this;
    }

    /**
     *  Set an option
     *
     * @param mixed $value new value for option
     * @throws WP2StaticException
     */
    public function setOption( string $name, $value ) : Options {
        return $this->__set( $name, $value );
    }

    /**
     *  Get an option
     *
     * @return mixed options's value
     */
    public function __get( string $name ) {
        $value = array_key_exists( $name, $this->wp2static_options ) ?
            $this->wp2static_options[ $name ] : null;

        return $value;
    }

    /**
     *  Get an option
     *
     * @return mixed options's value
     */
    public function getOption( string $name ) {
        return $this->__get( $name );
    }

    /**
     *  Get all options
     *
     * @return mixed[] all options
     */
    public function getAllOptions(
        bool $reveal_sensitive_values = false,
        string $filter = ''
    ) : array {
        $options_array = array();

        $this->whitelisted_keys = apply_filters(
            'wp2static_whitelist_option_keys',
            $this->whitelisted_keys
        );

        foreach ( $this->wp2static_options_keys as $key ) {

            $value = '*******************';

            if ( in_array( $key, $this->whitelisted_keys ) ) {
                $value = $this->__get( $key );
            } elseif ( $reveal_sensitive_values ) {
                $value = $this->__get( $key );
            }

            $options_array[] = [
                'Option name' => $key,
                'Value' => $value,
            ];
        }

        if ( $filter !== '' ) {
            $options_array = array_filter(
                $options_array,
                function($item) use ($filter) {
                    return (strpos($item['Option name'], $filter) !== false);
                }
            );
        }

        return $options_array;
    }

    /**
     *  Get all settings (transformed options in alternate format)
     *
     * @return mixed[] all settings
     */
    public function getSettings() : array {
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
        $settings['baseUrl'] =
            isset( $settings['baseUrl'] ) ?
            rtrim( $settings['baseUrl'], '/' ) . '/' :
            SiteInfo::getUrl( 'site' );

        return $settings;
    }

    public function optionExists( string $name ) : bool {
        return in_array( $name, $this->wp2static_options_keys );
    }

    public function save() : bool {
        return update_option(
            $this->wp2static_option_key,
            $this->wp2static_options
        );
    }

    public function delete() : bool {
        return delete_option( $this->wp2static_option_key );
    }

    public function saveAllOptions() : void {
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

