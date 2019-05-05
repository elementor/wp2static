<?php

namespace WP2Static;

class ConvertToOfflineURL {
    /*
        When publishing our static site for offline usage, we need to ensure a
        few transformations are made:

         - with no site relativity possible to rely on, we need all links to be
           document relative, ie to go to parent page, use ../page not /page or
           file:///page (allowing for C:\page,/home/me/page, etc

         - with no webserver to render default documents like /index.html, we
           need to rewrite all links to full post/index.html style URLs

    */
    public static function convert(
        $url_to_change, $page_url, $placeholder_url
    ) {

        $current_page_path_to_root = '';
        $current_page_path = parse_url( $page_url, PHP_URL_PATH );

        if ( ! is_string( $current_page_path ) ) {
            return $url_to_change;
        }

        $number_of_segments_in_path = explode( '/', $current_page_path );
        $num_dots_to_root = count( $number_of_segments_in_path ) - 2;

        $page_url_without_domain = str_replace(
            $placeholder_url,
            '',
            $page_url
        );


        /*
            For target URLs at the same level or higher level as the current
            page, strip the current page from the target URL

            Match current page in target URL to determine
        */
        if ( strpos( $url_to_change, $page_url_without_domain ) !== false ) {
            $rewritten_url = str_replace(
                $page_url_without_domain,
                '',
                $url_to_change
            );

            // TODO: into one array or match/replaces
            $rewritten_url = str_replace(
                $placeholder_url,
                '',
                $rewritten_url
            );

            $offline_url = $rewritten_url;
        }

        /*
            For target URLs not below the current page's hierarchy
            build the document relative path from current page  
        */
        else {
            for ( $i = 0; $i < $num_dots_to_root; $i++ ) {
                $current_page_path_to_root .= '../';
            }

            $rewritten_url = str_replace(
                $placeholder_url,
                '',
                $url_to_change
            );

            $offline_url = $current_page_path_to_root . $rewritten_url;
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

        if ( strpos( basename( $offline_url ), '.' ) === false ) {
            $offline_url .= '/index.html';
            $offline_url = str_replace( '//', '/', $offline_url );
        }

        return $offline_url;
    }
}
