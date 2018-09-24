<?php


class TXTProcessor {

  public function __construct($txt_document, $wp_site_url){
    $this->wp_site_url = $wp_site_url;   
    $this->txt_doc = $txt_document;
  }

  public function isInternalLink($link) {
    // check link is same host as $this->url and not a subdomain
    return parse_url($link, PHP_URL_HOST) == parse_url($this->wp_site_url, PHP_URL_HOST);
  }

	public function normalizeURLs($url) {

  }

  public function getTXT() {
    return $this->txt_doc;
  }
}

