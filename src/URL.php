<?php
/*
    URL object

    uses https://github.com/wasinger/url
*/

namespace WP2Static;

class URL {

    /**
     * @var \Wa72\Url\Url
     */
    private $parent_page_url;
    /**
     * @var \Wa72\Url\Url
     */
    private $url;

    /**
     * URL constructor
     *
     * @param string $parent_page_url URL optional parent page to make
     * absolute URL from
     * @throws WP2StaticException
     */
    public function __construct( string $url, string $parent_page_url = null ) {
        $url = new \Wa72\Url\Url( $url );

        if ( $parent_page_url ) {
            $this->parent_page_url = new \Wa72\Url\Url( $parent_page_url );
            $this->url = $url->makeAbsolute( $this->parent_page_url );
        } else {
            // test absolute URL
            if ( ! $url->getHost() ) {
                throw new WP2StaticException(
                    'Trying to create unsupported URL'
                );
            }

            $this->url = $url;
        }
    }

    /**
     * Return the URL as a string
     */
    public function get() : string {
        return $this->url;
    }

    /**
     * Rewrite host and scheme to destination URL's
     *  - mutates URL object in place
     *
     * @param string $destination_url URL rewrite rules
     */
    public function rewriteHostAndProtocol( string $destination_url ) : void {
        $destination_url = new \Wa72\Url\Url( $destination_url );

        $this->url->setHost( $destination_url->getHost() );
        $this->url->setScheme( $destination_url->getScheme() );
    }
}

