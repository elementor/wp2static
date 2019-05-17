<?php

namespace WP2Static;

use Exception;

class DetectVendorFiles {

    public static function detect( $wp_site_url ) {
        $vendor_files = array();

        // Yoast
        if ( defined( 'WPSEO_VERSION' ) ) {
            $yoast_sitemaps = array(
                '/sitemap_index.xml',
                '/post-sitemap.xml',
                '/page-sitemap.xml',
                '/category-sitemap.xml',
                '/author-sitemap.xml',
            );

            $vendor_files = array_merge( $vendor_files, $yoast_sitemaps );
        }

        // cache dir used by Autoptimize and other themes/plugins
        $vendor_cache_dir =
            SiteInfo::getPath( 'content' ) . 'cache/';

        if ( is_dir( $vendor_cache_dir ) ) {
            $site_url = SiteInfo::getUrl( 'site' );
            $content_url = SiteInfo::getUrl( 'content' );

            if (
                 ! is_string( $site_url ) ||
                 ! is_string( $content_url )
                ) {
                $err = 'WP URLs not defined ';
                WsLog::l( $err );
                throw new Exception( $err );
            }

            // get difference between home and wp-contents URL
            $prefix = str_replace(
                $site_url,
                '/',
                $content_url
            );

            $vendor_cache_urls = DetectVendorCache::detect(
                $vendor_cache_dir,
                SiteInfo::getPath( 'content' ),
                $prefix
            );

            $vendor_files = array_merge( $vendor_files, $vendor_cache_urls );
        }

        if ( class_exists( 'Custom_Permalinks' ) ) {
            global $wpdb;

            $query = "
                SELECT meta_value
                FROM %s
                WHERE meta_key = '%s'
                ";

            $custom_permalinks = array();

            $posts = $wpdb->get_results(
                sprintf(
                    $query,
                    $wpdb->postmeta,
                    'custom_permalink'
                )
            );

            if ( $posts ) {
                foreach ( $posts as $post ) {
                    $custom_permalinks[] = $wp_site_url . $post->meta_value;
                }

                $vendor_files = array_merge(
                    $vendor_files,
                    $custom_permalinks
                );
            }
        }

        return $vendor_files;
    }
}
