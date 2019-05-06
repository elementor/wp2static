<?php

namespace WP2Static;

class DBSettings {

    public static function get( $sets = array(), $specify_keys = array() ) {
        $plugin = Controller::getInstance();

        $settings = array();
        $key_sets = array();
        $target_keys = array();

        $key_sets['general'] = array(
            'baseUrl',
            'debug_mode',
            'selected_deployment_option',
        );

        $key_sets['crawling'] = array(
            'additionalUrls',
            'basicAuthPassword',
            'basicAuthUser',
            'crawlPort',
            'crawl_delay',
            'crawlUserAgent',
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
            'excludeURLs',
            'parse_css',
            'useBasicAuth',
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
            'removeCanonical',
        );

        $key_sets['advanced'] = array(
            'crawl_increment',
            'completionEmail',
            'delayBetweenAPICalls',
            'deployBatchSize',
        );

        $key_sets['folder'] = array(
            'baseUrl-folder',
            'targetFolder',
        );

        $key_sets['zip'] = array(
            'baseUrl-zip',
            'allowOfflineUsage',
        );

        $key_sets['wpenv'] = array(
            'wp_site_url',
            'wp_site_path',
            'wp_site_subdir',
            'wp_uploads_path',
            'wp_uploads_url',
            'wp_active_theme',
            'wp_themes',
            'wp_uploads',
            'wp_plugins',
            'wp_content',
            'wp_inc',
        );

        $key_sets = apply_filters(
            'wp2static_add_post_and_db_keys',
            $key_sets
        );

        foreach ( $sets as $set ) {
            $target_keys = array_merge( $target_keys, $key_sets[ $set ] );
        }

        foreach ( $target_keys as $key ) {
            $settings[ $key ] = $plugin->options->{ $key };
        }

        require_once dirname( __FILE__ ) . '/../WP2Static/WPSite.php';
        $wp_site = new WPSite();

        foreach ( $key_sets['wpenv'] as $key ) {
            $settings[ $key ] = $wp_site->{ $key };
        }

        $settings['crawl_increment'] =
            isset( $plugin->options->crawl_increment ) ?
            (int) $plugin->options->crawl_increment :
            1;

        // NOTE: any baseUrl required if creating an offline ZIP
        $settings['baseUrl'] = rtrim( $plugin->options->baseUrl, '/' ) . '/';

        return array_filter( $settings );
    }
}

