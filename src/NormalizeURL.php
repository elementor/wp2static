<?php

/*
    Make link absolute, using current page to determine full path

    take a document-relative URL like '../theimage.jpg'

    a site root-relative URL like '/theimage.jpg'

    and the current page URL like https://site.com/cat/post/

      - or -

    TODO: a protocol-relative URL like //mydomain.com/theimage.jpg

    and return the full path to the URL, like

    https://site.com/cat/theimage.jpg


*/

namespace WP2Static;

class NormalizeURL {

    public static function normalize( $url, $page_url ) {
        $page_url = new \Net_URL2( $page_url );

        $absolute_url = $page_url->resolve( $url );

        return $absolute_url;
    }
}

