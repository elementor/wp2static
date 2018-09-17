<?php

class FileWriter {
  public function __construct($url, $content){
    error_log('calling FileWriter');
  }

  public function filePathFromOriginalURL($url) {
    // take origin URL, spit out path for writing file in archive
  }

	public function saveUrlData(StaticHtmlOutput_UrlRequest $url, $archiveDir) {
		$urlInfo = parse_url($url->url);
		$pathInfo = array();

		//WsLog::l('urlInfo :' . $urlInfo['path']);
		/* will look like
			
			(homepage)

			[scheme] => http
			[host] => 172.18.0.3
			[path] => /

			(closed url segment)

			[scheme] => http
			[host] => 172.18.0.3
			[path] => /feed/

			(file with extension)

			[scheme] => http
			[host] => 172.18.0.3
			[path] => /wp-content/themes/twentyseventeen/assets/css/ie8.css

		*/

		// TODO: here we can allow certain external host files to be crawled

		// validate our inputs
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
		if (empty($pathInfo['extension']) && $pathInfo['basename'] == $pathInfo['filename']) {
			$fileDir .= '/' . $pathInfo['basename'];
			$pathInfo['filename'] = 'index';
		}

		//$fileDir = preg_replace('/(\/+)/', '/', $fileDir);

		if (!file_exists($fileDir)) {
			wp_mkdir_p($fileDir);
		}

		$fileExtension = ''; 

		// TODO: was isHtml() method modified to include more than just html
		// if there's no extension set or content type matches html, set it to html
		// TODO: seems to be flawed for say /feed/ urls, which would not be xml content type..
		if(  isset($pathInfo['extension'])) {
			$fileExtension = $pathInfo['extension']; 
		} else if( $url->isHtml() ) {
			$fileExtension = 'html'; 
		} else {
			// guess mime type
			
			$fileExtension = StaticHtmlOutput_UrlHelper::getExtensionFromContentType($url->getContentType()); 
		}

		$fileName = '';

		// set path for homepage to index.html, else build filename
		if ($urlInfo['path'] == '/') {
			$fileName = $fileDir . 'index.html';
		} else {
			$fileName = $fileDir . '/' . $pathInfo['filename'] . '.' . $fileExtension;
		}

    // fix for # 103 - weird case with inline style images in nested subdirs
    // should be a non-issue if using DOMDoc instead of regex parsing
		
		$fileName = str_replace(');', '', $fileName);
		// TODO: find where this extra . is coming from (current dir indicator?)
		$fileName = str_replace('.index.html', 'index.html', $fileName);
		// remove 2 or more slashes from paths
		$fileName = preg_replace('/(\/+)/', '/', $fileName);


		$fileContents = $url->response['body'];
		
		// TODO: what was the 'F' check for?1? Comments exist for a reason
		if ($fileContents != '' && $fileContents != 'F') {
			file_put_contents($fileName, $fileContents);
		} else {
			WsLog::l('SAVING URL: UNABLE TO SAVE FOR SOME REASON');
		}
	}

}
