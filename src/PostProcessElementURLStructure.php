<?php

namespace WP2Static;

class PostProcessElementURLStructure {

    private $allow_offline_usage;
    private $destination_url;
    private $site_url;
    private $use_document_relative_urls;
    private $use_site_root_relative_urls;

    public function __construct(
        string $destination_url,
        string $site_url,
        bool $allow_offline_usage,
        bool $use_document_relative_urls,
        bool $use_site_root_relative_urls
    ) {
        $this->destination_url = $destination_url;
        $this->site_url = $site_url;
        $this->allow_offline_usage = $allow_offline_usage;
        $this->use_document_relative_urls = $use_document_relative_urls;
        $this->use_site_root_relative_urls = $use_site_root_relative_urls;
    }

    /*
     * After we have normalized the element's URL and have an absolute
     * Placeholder URL, we can perform transformations, such as making it
     * an offline URL or document relative

     * site root relative URLs can be bulk rewritten before outputting HTML
     * so we don't both doing those here

     * We need to do while iterating the URLs, as we cannot accurately
     * iterate individual URLs in bulk rewriting mode and each URL
     * needs to be rewritten in a different manner for offline mode rewriting
     *
     * @return string Rewritten URL
     *
    */
    public function postProcessElementURLStructure(
        string $url,
        string $page_url
    ) : string {
        // TODO: move detection func higher
        if ( $this->use_document_relative_urls ) {
            $url = ConvertToDocumentRelativeURL::convert(
                $url,
                $page_url,
                $this->site_url,
                $this->allow_offline_usage
            );
        }

        if ( $this->use_site_root_relative_urls ) {
            $url = ConvertToSiteRootRelativeURL::convert(
                $url,
                $this->destination_url
            );
        }

        return $url;
    }
}
