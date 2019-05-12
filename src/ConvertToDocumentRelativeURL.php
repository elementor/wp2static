<?php

namespace WP2Static;

class ConvertToDocumentRelativeURL {

    /*
     * Convert absolute URL to document-relative.
     * Required for offline URLs
     *
     * @param string $url URL to change
     * @param string $page_url URL of current page to determine hierarchy
     * @param string $destination_url Site URL reference for rewriting
     * @param bool $offline_mode Whether to append index.html to URLs
     * @return string Rewritten URL
     */
    public static function convert(
        $url, $page_url, $destination_url, $offline_mode = false
    ) {
        $current_page_path_to_root = '';
        $current_page_path = parse_url( $page_url, PHP_URL_PATH );

        if ( ! is_string( $current_page_path ) ) {
            return $url;
        }

        $number_of_segments_in_path = explode( '/', $current_page_path );
        $num_dots_to_root = count( $number_of_segments_in_path ) - 2;

        $page_url_without_domain = str_replace(
            $destination_url,
            '',
            $page_url
        );

        /*
            For target URLs at the same level or higher level as the current
            page, strip the current page from the target URL

            Match current page in target URL to determine
        */
        // TODO: encountering occurrances of empty $page_url_without_domain
        if ( strpos( $url, $page_url_without_domain ) !== false ) {
            $rewritten_url = str_replace(
                $page_url_without_domain,
                '',
                $url
            );

            // TODO: into one array or match/replaces
            $rewritten_url = str_replace(
                $destination_url,
                '',
                $rewritten_url
            );

            $offline_url = $rewritten_url;
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
                $url
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
        if ( ! is_string( $offline_url ) ) {
            return false;
        }

        if ( $offline_mode ) {
            // if last char is a ., we're linking to a dir path, add index.html
            $last_char_is_slash = substr( $offline_url, -1 ) == '/';

            $basename_doesnt_contain_dot =
                strpos( basename( $offline_url ), '.' ) === false;
            
            if ( $last_char_is_slash || $basename_doesnt_contain_dot ) {
                $offline_url .= '/index.html';
                $offline_url = str_replace( '//', '/', $offline_url );
            }
        }

        return $offline_url;
    }
}
