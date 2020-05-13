<?php

namespace WP2Static;

class DetectArchiveURLs {

    /**
     * Detect Archive URLs
     *
     * Get list of archive URLs
     * ie
     *      /2020/04/
     *      /2020/05/
     *      /2020/
     *
     * @return string[] list of archive URLs
     */
    public static function detect( string $wp_site_url ) : array {
        global $wpdb;

        $archive_urls = [];

        $archive_urls_with_markup = '';

        $yearly_archives = wp_get_archives(
            [
                'type'            => 'yearly',
                'echo'            => 0,
            ]
        );

        $archive_urls_with_markup .=
            is_string( $yearly_archives ) ? $yearly_archives : '';

        $monthly_archives = wp_get_archives(
            [
                'type'            => 'monthly',
                'echo'            => 0,
            ]
        );

        $archive_urls_with_markup .=
            is_string( $monthly_archives ) ? $monthly_archives : '';

        $daily_archives = wp_get_archives(
            [
                'type'            => 'daily',
                'echo'            => 0,
            ]
        );

        $archive_urls_with_markup .=
            is_string( $daily_archives ) ? $daily_archives : '';

        $url_matching_regex = '#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#';
        preg_match_all( $url_matching_regex, $archive_urls_with_markup, $matches );

        foreach ( $matches[0] as $url ) {
            $archive_urls[] = str_replace(
                $wp_site_url,
                '/',
                $url
            );
        }

        return $archive_urls;
    }
}
