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
     * Apply post-processing to URLs such as converting to doc-root relative
     * or site-root relative
     *
     * At this point, $url_within_page has been rewritten to Destination domain
     *
     * @return string Rewritten URL
     *
    */
    public function postProcessElementURLStructure(
        string $url_within_page,
        string $url_of_page_being_processed
    ) : string {
        // error_log($url_within_page);
        // error_log($url_of_page_being_processed);die();
        

        if ( $this->use_document_relative_urls ) {
            return ConvertToDocumentRelativeURL::convert(
                $url_within_page,
                $url_of_page_being_processed,
                $this->destination_url,
                $this->allow_offline_usage
            );
        }

        if ( $this->use_site_root_relative_urls ) {
            return ConvertToSiteRootRelativeURL::convert(
                $url_within_page,
                $this->destination_url
            );
        }

        return '';
    }
}
