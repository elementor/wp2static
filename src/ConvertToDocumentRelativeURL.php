<?php

namespace WP2Static;

use Exception;

class ConvertToDocumentRelativeURL {
    /**
     * Convert absolute URL to document-relative.
     * Required for offline URLs
     */
    public static function convert(
        string $url_within_page,
        string $url_of_page_being_processed,
        string $destination_url,
        bool $offline_mode = false
    ) : string {
        error_log( 'doing doc rel stuff' );
        error_log( $url_within_page );
        error_log( $url_of_page_being_processed );
        error_log( $destination_url );
        error_log( $offline_mode );

        $current_page_path_to_root = '';

        $current_page_path = parse_url( $url_of_page_being_processed, PHP_URL_PATH );

        if ( ! $current_page_path ) {
            return $url_within_page;
        }

        error_log( '$current_page_path' );
        error_log( $current_page_path );

        $number_of_segments_in_path = explode( '/', $current_page_path );
        $num_dots_to_root = count( $number_of_segments_in_path ) - 2;

        $page_url_without_domain = str_replace(
            $destination_url,
            '',
            $url_of_page_being_processed
        );

        error_log( '$page_url_without_domain' );
        error_log( $page_url_without_domain );


        if ( $page_url_without_domain === '' ) {
            $err = 'Warning: empty $page_url_without_domain encountered ' .
                "url: {$url_within_page} \\n page_url: $url_of_page_being_processed \\n " .
                "site_url: {$destination_url} \\n offline mode: $offline_mode";
            WsLog::l( $err );
            error_log($err);

            return $url_within_page;
        }

        /*
            For target URLs at the same level or higher level as the current
            page, strip the current page from the target URL

            Match current page in target URL to determine
        */
        if (
            // when homepage of site, page url without domain will be empty
            $page_url_without_domain &&
            strpos( $url_within_page, $page_url_without_domain ) !== false &&
            $page_url_without_domain !== '/'
        ) {
            error_log('loop1');
            $rewritten_url = str_replace(
                $page_url_without_domain,
                '',
                $url_within_page
            );

            // TODO: into one array or match/replaces
            $rewritten_url = str_replace(
                $destination_url,
                '',
                $rewritten_url
            );

            $offline_url = $rewritten_url;

            error_log($offline_url);
        } else {
            /*
                For target URLs not below the current page's hierarchy
                build the document relative path from current page
            */
            for ( $i = 0; $i < $num_dots_to_root; $i++ ) {
                $current_page_path_to_root .= '../';
            }

            $rewritten_url = str_replace(
                $destination_url,
                '',
                $url_within_page
            );

            $offline_url = $current_page_path_to_root . $rewritten_url;

            /*
                Cover case of root relative URLs incorrectly ending as
                ..//some/path by replacing double slashes with /../
            */
            $offline_url = str_replace(
                '..//',
                '../../',
                $offline_url
            );
        }

        /*
            We must address the case where the WP site uses a URL such as
            `/some-page`, which is valid and will work outside offline
            use cases.

            For offline usage, we need to force any detected HTML content paths
            to have a trailing slash, allowing for easily appending `index.html`
            for proper offline usage compatibility.

            We can risk using file path detection here, as images and other
            assets will also need to be explcitly named for offline usage and
            should be handled elsewhere in the case they are being served
            without an extension.

            Here, we will detect for any URLs without a `.` in the last segment,
            append /index.html and strip and duplicate slashes

            /           => //index.html             => /index.html
            /some-post  => /some-post/index.html
            /some-post/ => /some-post//index.html   => /some-post/index.html
            /an-img.jpg # no match

        */
        if ( is_string( $offline_url ) && $offline_mode ) {
            // if last char is a ., we're linking to a dir path, add index.html
            $last_char_is_slash = substr( $offline_url, -1 ) == '/';

            $basename_doesnt_contain_dot =
                strpos( basename( $offline_url ), '.' ) === false;

            if ( $last_char_is_slash || $basename_doesnt_contain_dot ) {
                $offline_url .= '/index.html';
                $offline_url = str_replace( '//', '/', $offline_url );
            }
        }
        error_log( $offline_url );


        die();
        return $offline_url;
    }
}
