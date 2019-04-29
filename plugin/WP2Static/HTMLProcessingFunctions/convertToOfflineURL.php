<?php

function convertToOfflineURL( $url_to_change, $page_url, $placeholder_url  ) {
    $current_page_path_to_root = '';
    $current_page_path = parse_url( $page_url, PHP_URL_PATH );
    $number_of_segments_in_path = explode( '/', $current_page_path );
    $num_dots_to_root = count( $number_of_segments_in_path ) - 2;

    for ( $i = 0; $i < $num_dots_to_root; $i++ ) {
        $current_page_path_to_root .= '../';
    }

    $rewritten_url = str_replace(
        $placeholder_url,
        '',
        $url_to_change
    );

    $offline_url = $current_page_path_to_root . $rewritten_url;

    // add index.html if no extension
    if ( substr( $offline_url, -1 ) === '/' ) {
        // TODO: check XML/RSS case
        $offline_url .= 'index.html';
    }

    return $offline_url;
}
