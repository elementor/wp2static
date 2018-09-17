<?php


class CSSProcessor {

  public function __construct($html_document){
    error_log('instantiating CSS Processor');
  }

	public function cleanup($wp_site_environment, $overwrite_slug_targets) {
    // PERF: ~ 30ms for HTML or CSS
    // TODO: skip binary file processing in func
    // TODO: move to CSSProcessor
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
  }
}

