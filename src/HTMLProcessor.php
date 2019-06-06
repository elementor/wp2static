<?php

namespace WP2Static;

use DOMDocument;
use DOMComment;
use DOMAttr;
use DOMNode;
use DOMElement;
use DOMXPath;
use Exception;

/*
 * Processes HTML files while crawling.
 * Works in multiple phases:
 *
 * Parses HTML into a DOMDocument and iterates all elements
 * For each element, it checks the type and handles with specific functions
 *
 * For elements containing links we want to process, we first normalize it:
 * to get all URLs to a uniform structure for later processing, we convert to
 * absolute URLs and rewrite to the Destination URL for ease of rewriting
 * we also transform URLs if the user requests document-relative or offline
 * URLs in the final output
 *
 * Before returning the final HTML for saving, we perform bulk transformations
 * such as enforcing UTF-8 encoding, replacing any site URLs to Destination or
 * transforming to site root-relative URLs
 *
 */
class HTMLProcessor {

    public $base_element;
    public $ch;
    public $crawlable_filetypes;
    public $destination_url;
    public $head_element;
    public $page_url;
    public $processed_urls;
    public $rewrite_rules;
    public $site_url;
    public $site_url_host;
    public $user_rewrite_rules;
    public $xml_doc;
    private $settings;

    /**
     * HTMLProcessor constructor
     *
     * @param mixed[] $rewrite_rules URL replacement pattern
     * @param resource $ch cURL handle
     */
    public function __construct(
        array $rewrite_rules,
        string $site_url_host,
        string $destination_url,
        string $user_rewrite_rules,
        $ch
    ) {
        $plugin = Controller::getInstance();
        $this->settings = $plugin->options->getSettings( true );

        $this->rewrite_rules = $rewrite_rules;
        $this->user_rewrite_rules = $user_rewrite_rules;
        $this->site_url_host = $site_url_host;
        $this->destination_url = $destination_url;
        $this->site_url = SiteInfo::getUrl( 'site' );
        $this->ch = $ch;

        $this->processed_urls = [];

    }
}
