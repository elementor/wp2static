<?php

/*
    make link absolute, using current page to determine full path

    take a URL like '../theimage.jpg'

    and the current page URL like https://site.com/cat/post/

    and return the full path to the first URL, like

    and the current page URL like https://site.com/cat/theimage.jpg

*/
function normalizeURL( $url, $page_url ) {
    require_once __DIR__ . '/../../URL2/URL2.php';

    $page_url = new Net_url2( $page_url );

    $absolute_url = $page_url->resolve( $url );

    return $absolute_url;
}

