<?php

namespace WP2Static;

class RemoveQueryStringFromInternalLink {

    public function removeQueryStringFromInternalLink( string $url ) : string {
        if ( strpos( $url, '?' ) !== false ) {
            // strip anything from the ? onwards
            $url = strtok( $url, '?' );
        }

        if ( $url === '' ) {
            return '';
        }

        if ( ! is_string( $url ) ) {
            return '';
        }

        return $url;
    }
}
