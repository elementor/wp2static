<?php

namespace WP2Static;

use DOMElement;

class URLRewriter {

    private $asset_downloader;
    private $page_url;

    /**
     * URLRewriter constructor
     *
     * @param mixed[] $rewrite_rules URL rewrite rules
     */
    public function __construct(
        string $page_url,
        AssetDownloader $asset_downloader
    ) {
        $this->page_url = $page_url;
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

        if ( ! URLHelper::isInternalLink( $url, SiteInfo::getSiteURLHost() ) ) {
            return $url;
        }

        if ( URLHelper::isProtocolRelative( $url ) ) {
            $url = URLHelper::protocolRelativeToAbsoluteURL(
                $url,
                SiteInfo::getUrl('site')
            );
        }

        // normalize site root-relative URLs here to absolute site-url
        if ( $url[0] === '/' ) {
            if ( $url[1] !== '/' ) {
                $url = SiteInfo::getUrl('site') . ltrim( $url, '/' );
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

        if ( CoreOptions::getValue( 'includeDiscoveredAssets' ) ) {
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
            CoreOptions::getValue('rewrite_rules')['site_url_patterns'],
            CoreOptions::getValue('rewrite_rules')['destination_url_patterns'],
            $url
        );

        /*
         * Note: We want to to perform as many functions on the URL, not have
         * to access the element multiple times. So, once we have it, do all
         * the things to it before sending back/updating the attribute
         */
        $url_post_processor = new PostProcessElementURLStructure();

        // Page our rewriting target URL exists in, rewritten to target domain
        $rewritting_page_url = str_replace(
            $this->rewrite_rules['site_url_patterns'],
            $this->rewrite_rules['destination_url_patterns'],
            $this->page_url
        );

        $url = $url_post_processor->postProcessElementURLStructure(
            $url,
            $rewritting_page_url
        );

        return $url;
    }

}
