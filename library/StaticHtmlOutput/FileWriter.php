<?php

class FileWriter {
  public function __construct($url, $content, $file_type){
    $this->url = $url;
    $this->content = $content;
    $this->file_type = $file_type;
  }

	public function saveFile($archiveDir) {
		$urlInfo = parse_url($this->url);
		$pathInfo = array();

		if ( !isset($urlInfo['path']) ) {
			return false;
		}

		// set what the new path will be based on the given url
		if( $urlInfo['path'] != '/' ) {
			$pathInfo = pathinfo($urlInfo['path']);
		} else {
			$pathInfo = pathinfo('index.html');
		}

		// set fileDir to the directory name else empty	
		$fileDir = $archiveDir . (isset($pathInfo['dirname']) ? $pathInfo['dirname'] : '');

		// set filename to index if there is no extension and basename and filename are the same
		if (empty($pathInfo['extension']) && $pathInfo['basename'] === $pathInfo['filename']) {
			$fileDir .= '/' . $pathInfo['basename'];
			$pathInfo['filename'] = 'index';
		}

		if (!file_exists($fileDir)) {
			wp_mkdir_p($fileDir);
		}

		$fileExtension = ''; 

		if(isset($pathInfo['extension'])) {
			$fileExtension = $pathInfo['extension']; 
		} else if( $this->file_type == 'html' ) {
			$fileExtension = 'html'; 
		} else {
			// TODO: is this being called or too late?
			$fileExtension = StaticHtmlOutput_UrlHelper::getExtensionFromContentType($url->getContentType()); 
		}

		$fileName = '';

		// set path for homepage to index.html, else build filename
		if ($urlInfo['path'] == '/') {
      // TODO: isolate and fix the cause requiring this trim:
			$fileName = rtrim($fileDir, '.') . 'index.html';
		} else {
			$fileName = $fileDir . '/' . $pathInfo['filename'] . '.' . $fileExtension;
		}

		$fileContents = $this->content;
		
    if ($fileContents) {
			file_put_contents($fileName, $fileContents);
		} else {
			WsLog::l('SAVING URL: FILE IS EMPTY ' . $this->url);
      error_log($this->url);
      error_log($this->content);
		}
	}
}
