<?php


class HTMLProcessor {

  public function __construct($html_document){
    error_log('instantiating HTMLProcessor');

    // instantiate the XML body here 
    $this->xml_doc = new DOMDocument(); 
  
    // PERF: 70% of function time
    // prevent warnings, via https://stackoverflow.com/a/9149241/1668057
    libxml_use_internal_errors(true);
    $this->xml_doc->loadHTML($html_document); 
    libxml_use_internal_errors(false);
  }

	public function normalizeURLs() {
    if (! $this->isRewritable() ) {
      return;
    }
 
    $base = new Net_URL2($this->url);

    foreach($this->xml_doc->getElementsByTagName('a') as $link) { 
      $original_link = $link->getAttribute("href");

      // TODO: apply only to links starting with .,..,/, or any with just a path, like banana.png
      $abs = $base->resolve($original_link);
      $link->setAttribute('href', $abs);
    }
  }

	public function cleanup($wp_site_environment, $overwrite_slug_targets) {
    // PERF: ~ 30ms for HTML or CSS
    // TODO: skip binary file processing in func
		if ($this->isCSS()) {
			$regex = array(
			"`^([\t\s]+)`ism"=>'',
			"`^\/\*(.+?)\*\/`ism"=>"",
			"`([\n\A;]+)\/\*(.+?)\*\/`ism"=>"$1",
			"`([\n\A;\s]+)//(.+?)[\n\r]`ism"=>"$1\n",
			"`(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+`ism"=>"\n"
			);

			$rewritten_CSS = preg_replace(array_keys($regex), $regex, $this->response['body']);
      $this->setResponseBody($rewritten_CSS);
		}

		if ($this->isRewritable()) {
      if ($this->isHtml()) {

        // PERF: 22% of function time
        $this->stripWPMetaElements();
        // PERF: 20% of function time
        $this->stripWPLinkElements();
        // PERF: 25% of function time
        $this->removeQueryStringsFromInternalLinks();
        // PERF: 30% of function time
        $this->rewriteWPPaths($wp_site_environment, $overwrite_slug_targets);
        $this->detectEscapedSiteURLs($wp_site_environment, $overwrite_slug_targets);

        // write the response body here
        $this->setResponseBody($this->xml_doc->saveHtml());
      }
    }

	}
}

