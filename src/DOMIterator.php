<?php

namespace WP2Static;

use DOMDocument;

class DOMIterator {

    private $asset_downloader;
    private $page_url;

    /**
     * DOMIterator constructor
     *
     */
    public function __construct(
        string $page_url,
        AssetDownloader $asset_downloader
    ) {
        $this->page_url = $page_url;
        $this->asset_downloader = $asset_downloader;
    }

    public function processHTML( string $html_document ) : DOMDocument {
        // detect if a base tag exists while in the loop
        // use in later base href creation to decide: append or create
        $base_element = null;
        $head_element = null;

        // instantiate the XML body here
        $xml_doc = new DOMDocument();

        // PERF: 70% of function time
        // prevent warnings, via https://stackoverflow.com/a/9149241/1668057
        libxml_use_internal_errors( true );
        $xml_doc->loadHTML(
            mb_convert_encoding( $html_document, 'HTML-ENTITIES', 'UTF-8' )
        );
        libxml_use_internal_errors( false );

        // start the full iterator here, along with copy of dom
        $elements = iterator_to_array(
            $xml_doc->getElementsByTagName( '*' ));

        $url_rewriter = new URLRewriter(
            $this->page_url,
            $this->asset_downloader);

        foreach ( $elements as $element ) {
            switch ( $element->tagName ) {
                case 'meta':
                    /*
                        Refactoring:

                            element processor requires:

                             - siteInfo (why?)
                             - rewriteRules
                             - settings (for bool flags)

                            can rm URLRewriter injection

                    */
                    $meta_processor = new MetaProcessor(
                        $this->page_url,
                        $url_rewriter
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
                        $this->page_url,
                        $this->asset_downloader
                    );
                    $src_set_processor->processImageSrcSet( $element );
                    break;
                case 'head':
                    $head_element = $element;
                    $head_processor = new HeadProcessor(
                        ExportSettings::get( 'removeConditionalHeadComments' ));

                    $base_element = $head_processor->processHead( $element );
                    break;
                case 'link':
                    // NOTE: not to confuse with anchor element
                    $url_rewriter->processElementURL( $element );

                    if ( $this->remove_wp_links ) {
                        RemoveLinkElementsBasedOnRelAttr::removeLinkElement(
                            $element
                        );
                    }

                    if ( $this->remove_canonical_links ) {
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
                                $xml_doc
                            );
                        }
                    }

                    break;
            }
        }

        // NOTE: $base_element is being recored during iteration of
        // elements, this prevents us from needing to do another iteration
        $base_href_processor = new BaseHrefProcessor(
            ExportSettings::get( 'destination_url' ),
            ExportSettings::get( 'allowOfflineUsage' ));

        $base_href_processor->dealWithBaseHREFElement(
            $xml_doc,
            $base_element,
            $head_element);

        // allow empty favicon to prevent extra browser request
        if ( $this->create_empty_favicon ) {
            $favicon_creator = new FaviconRequestBlocker();
            $favicon_creator->createEmptyFaviconLink( $xml_doc );
        }

        if ( $this->remove_html_comments ) {
            $comment_stripper = new HTMLCommentStripper();
            $comment_stripper->stripHTMLComments( $xml_doc );
        }

        return $xml_doc;
    }
}
