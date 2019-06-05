<?php

namespace WP2Static;

use DOMDocument;

class DOMIterator {

    private $settings;
    private $site_url;
    private $site_url_host;
    private $page_url;
    private $rewrite_rules;
    private $include_discovered_assets;
    private $asset_downloader;

    /**
     * DOMIterator constructor
     *
     * @param mixed[] $rewrite_rules URL rewrite rules
     */
    public function __construct(
        string $site_url,
        string $site_url_host,
        string $page_url,
        array $rewrite_rules,
        bool $include_discovered_assets,
        AssetDownloader $asset_downloader
    ) {
        $this->site_url = $site_url;
        $this->site_url_host = $site_url_host;
        $this->page_url = $page_url;
        $this->rewrite_rules = $rewrite_rules;
        $this->include_discovered_assets = $include_discovered_assets;
        $this->asset_downloader = $asset_downloader;
    }

    public function processHTML(
        string $html_document,
        string $page_url
    ) : DOMDocument {
        // detect if a base tag exists while in the loop
        // use in later base href creation to decide: append or create
        $base_element = null;
        $head_element = null;

        $page_url = $page_url;

        // instantiate the XML body here
        $xml_doc = new DOMDocument();

        // PERF: 70% of function time
        // prevent warnings, via https://stackoverflow.com/a/9149241/1668057
        libxml_use_internal_errors( true );
        $xml_doc->loadHTML( $html_document );
        libxml_use_internal_errors( false );

        // start the full iterator here, along with copy of dom
        $elements = iterator_to_array(
            $xml_doc->getElementsByTagName( '*' )
        );

        $url_rewriter = new URLRewriter(
            $this->site_url,
            $this->site_url_host,
            $this->page_url,
            $this->rewrite_rules,
            $this->include_discovered_assets,
            $this->asset_downloader
        );

        foreach ( $elements as $element ) {
            switch ( $element->tagName ) {
                case 'meta':
                    $meta_processor = new MetaProcessor(
                        $this->site_url,
                        $this->site_url_host,
                        $this->page_url,
                        $this->rewrite_rules,
                        $this->include_discovered_assets,
                        $this->settings['removeWPMeta'],
                        $this->asset_downloader
                    );
                    $meta_processor->processMeta( $element );
                    break;
                case 'form':
                    FormProcessor::process( $element );
                    break;
                case 'a':
                    $url_rewriter->processElementURL( $element );
                    break;
                case 'img':
                    $url_rewriter->processElementURL( $element );
                    $src_set_processor = new ImgSrcSetProcessor(
                        $this->site_url,
                        $this->site_url_host,
                        $this->page_url,
                        $this->rewrite_rules,
                        $this->include_discovered_assets,
                        $this->asset_downloader,
                    );
                    $src_set_processor->processImageSrcSet( $element );
                    break;
                case 'head':
                    $head_element = $element;
                    $head_processor = new HeadProcessor();
                    $base_element = $head_processor->processHead( $element );
                    break;
                case 'link':
                    // NOTE: not to confuse with anchor element
                    $url_rewriter->processElementURL( $element );

                    if ( isset( $this->settings['removeWPLinks'] ) ) {
                        RemoveLinkElementsBasedOnRelAttr::remove( $element );
                    }

                    if ( isset( $this->settings['removeCanonical'] ) ) {
                        $canonical_remover = new CanonicalLinkRemover();
                        $canonical_remover->removeCanonicalLink( $element );
                    }

                    break;
                case 'script':
                    /*
                        Script tags may contain src=
                        can also contain URLs within scripts
                        and escaped urls
                    */
                    $url_rewriter->processElementURL( $element );

                    // get the CDATA sections within <SCRIPT>
                    foreach ( $element->childNodes as $node ) {
                        if ( $node->nodeType == XML_CDATA_SECTION_NODE ) {
                            $cdata_processor = new CDATAProcessor();
                            $cdata_processor->processCDATA(
                                $node,
                                $xml_doc,
                                $this->settings['rewrite_rules']
                            );
                        }
                    }
                    break;
            }
        }

        // NOTE: $base_element is being recored during iteration of
        // elements, this prevents us from needing to do another iteration
        $base_href_processor = new BaseHrefProcessor(
            $this->settings['baseHREF'],
            $this->settings['allowOfflineUsage']
        );

        $base_href_processor->dealWithBaseHREFElement(
            $xml_doc,
            $base_element,
            $head_element
        );

        // allow empty favicon to prevent extra browser request
        if ( isset( $this->settings['createEmptyFavicon'] ) ) {
            $favicon_creator = new FaviconRequestBlocker();
            $favicon_creator->createEmptyFaviconLink( $xml_doc );
        }

        if ( isset( $this->settings['removeHTMLComments'] ) ) {
            $comment_stripper = new HTMLCommentStripper();
            $comment_stripper->stripHTMLComments( $xml_doc );
        }

        return $xml_doc;
    }
}
