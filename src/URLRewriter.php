<?php

namespace WP2Static;

use DOMElement;

class URLRewriter {

    private $allow_offline_usage;
    private $asset_downloader;
    private $destination_url;
    private $include_discovered_assets;
    private $page_url;
    private $rewrite_rules;
    private $site_url;
    private $site_url_host;
    private $use_document_relative_urls;
    private $use_site_root_relative_urls;

    /**
     * URLRewriter constructor
     *
     * @param mixed[] $rewrite_rules URL rewrite rules
     */
    public function __construct(
        string $site_url,
        string $site_url_host,
        string $destination_url,
        bool $allow_offline_usage,
        bool $use_document_relative_urls,
        bool $use_site_root_relative_urls,
        string $page_url,
        array $rewrite_rules,
        bool $include_discovered_assets,
        AssetDownloader $asset_downloader
    ) {
        $this->site_url = $site_url;
        $this->site_url_host = $site_url_host;
        $this->destination_url = $destination_url;
        $this->allow_offline_usage = $allow_offline_usage;
        $this->use_document_relative_urls = $use_document_relative_urls;
        $this->use_site_root_relative_urls = $use_site_root_relative_urls;
        $this->page_url = $page_url;
        $this->rewrite_rules = $rewrite_rules;
        $this->include_discovered_assets = $include_discovered_assets;
        $this->asset_downloader = $asset_downloader;
    }

    /**
     * Process URL within a DOMElement
     *
     * @return void
     */
    public function processElementURL( DOMElement $element ) : void {
        list( $url, $attribute_to_change ) =
            $this->getURLAndTargetAttribute( $element );

        if ( ! $attribute_to_change ) {
            return;
        }

        $url = $this->rewriteLocalURL( $url );

        $element->setAttribute( $attribute_to_change, $url );
    }

    /**
     * Get URL and attribute to change from Element
     *
     * @return string[] url and name of attribute
     */
    public function getURLAndTargetAttribute( DOMElement $element ) : array {
        $attribute_to_change = '';

        if ( $element->hasAttribute( 'href' ) ) {
            $attribute_to_change = 'href';
        } elseif ( $element->hasAttribute( 'src' ) ) {
            $attribute_to_change = 'src';
        }

        $url_to_change = $element->getAttribute( $attribute_to_change );

        return [ $url_to_change, $attribute_to_change ];
    }


    public function rewriteLocalURL( string $url ) : string {
        if ( URLHelper::startsWithHash( $url ) ) {
            return $url;
        }

        if ( URLHelper::isMailto( $url ) ) {
            return $url;
        }

        if ( ! URLHelper::isInternalLink( $url, $this->site_url_host ) ) {
            return $url;
        }

        if ( URLHelper::isProtocolRelative( $url ) ) {
            $url = URLHelper::protocolRelativeToAbsoluteURL(
                $url,
                $this->site_url
            );
        }

        // normalize site root-relative URLs here to absolute site-url
        if ( $url[0] === '/' ) {
            if ( $url[1] !== '/' ) {
                $url = $this->site_url . ltrim( $url, '/' );
            }
        }

        // TODO: enfore trailing slash

        // TODO: download approved static files
            // defaults (images, fonts, css, js)

            // check for user additions

            // determine save path

            // check for existing image

            // check for cache

        // normalize the URL / make absolute
        $url = NormalizeURL::normalize(
            $url,
            $this->page_url
        );

        $query_string_remover = new RemoveQueryStringFromInternalLink();
        $url = $query_string_remover->removeQueryStringFromInternalLink( $url );

        if ( isset( $this->include_discovered_assets ) ) {
            // check url has extension at all
            $extension = pathinfo( $url, PATHINFO_EXTENSION );

            // only try to dl urls with extension
            if ( $extension ) {
                // TODO: where is the best place to put this
                // considering caching, ie, build array here
                // exclude Excludes, already crawled lists
                // then iterate just the ones not already on disk

                $this->asset_downloader->downloadAsset( $url, $extension );
            }
        }

        // after normalizing, we need to rewrite to Destination URL
        $url = str_replace(
            $this->rewrite_rules['site_url_patterns'],
            $this->rewrite_rules['destination_url_patterns'],
            $url
        );

        /*
         * Note: We want to to perform as many functions on the URL, not have
         * to access the element multiple times. So, once we have it, do all
         * the things to it before sending back/updating the attribute
         */
        $url_post_processor = new PostProcessElementURLStructure(
            $this->destination_url,
            $this->site_url,
            $this->allow_offline_usage,
            $this->use_document_relative_urls,
            $this->use_site_root_relative_urls
        );

        $url = $url_post_processor->postProcessElementURLStructure(
            $url,
            $this->page_url
        );

        return $url;
    }

}
