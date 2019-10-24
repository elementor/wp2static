<?php
/*
    URL object

    uses https://github.com/wasinger/url
*/

namespace WP2Static;

class URL {

    private $parent_page_url;
    private $absolute;
    private $rewritten_to_destination;
    private $offline_mode;

    /**
     * DOMIterator constructor
     *
     * @param string $rewrite_rules URL rewrite rules
     */
    public function __construct(string $url, string $parent_page_url) {
        $url = new \Wa72\Url\Url( $url );
        $this->parent_page_url = new \Wa72\Url\Url( $parent_page_url );
        $this->absolute = $url->makeAbsolute( $this->parent_page_url );
    }

    /**
     * Return the URL as a string
     *
     */
    public function get() {
        return $this->absolute;
    }

    /**
     * Rewrite host and scheme to destination URL's
     *
     * @param string $rewrite_rules URL rewrite rules
     */
    public function rewriteHostAndProtocol(string $destination_url) {
        $destination_url = new \Wa72\Url\Url( $destination_url );

        $this->absolute->setHost( $destination_url->getHost() );
        $this->absolute->setScheme( $destination_url->getScheme() );
    }
}

