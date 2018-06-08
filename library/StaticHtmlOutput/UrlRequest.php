<?php
/**
 * @package WP Static HTML Output
 *
 * Copyright (c) 2011 Leon Stafford
 */

class StaticHtmlOutput_UrlRequest
{
	protected $_url;
	
	protected $_response;

	public function __construct($url)
	{
		$this->_url = filter_var(trim($url), FILTER_VALIDATE_URL);

		$response = wp_remote_get($this->_url,array('timeout'=>300)); //set a long time out

		$this->_response = '';

		if (is_wp_error($response)) {
			error_log('WP_ERROR');
			error_log(print_r($response, true));
			$this->_response = 'FAIL';
		} else {
			$this->_response = $response;
		}

	}

	public function getUrl()
	{
		return $this->_url;
	}

	public function getUrlWithoutFilename()
	{
        $file_info = pathinfo($this->getUrl());

        return isset($file_info['extension'])
            ? str_replace($file_info['filename'] . "." . $file_info['extension'], "", $this->getUrl())
            : $this->getUrl();
	}

	public function checkResponse()
	{
		return $this->_response;
	}
	
	public function setResponseBody($newBody)
	{
		if (is_array($this->_response))
		{
			$this->_response['body'] = $newBody;
		}
	}
	
	public function getResponseBody()
	{
		return isset($this->_response['body']) ? $this->_response['body'] : '';
	}
	
	public function getContentType()
	{
		return isset($this->_response['headers']['content-type']) ? $this->_response['headers']['content-type'] : null;
	}
	
	public function isHtml()
	{
		return stripos($this->getContentType(), 'html') !== false;
	}

	public function isCSS()
	{
		return stripos($this->getContentType(), 'css') !== false;
	}

	public function isCrawlableContentType()
	{

        $crawable_types = array(
            "text/plain",
            "application/javascript",
            "application/json",
            "application/xml",
            "text/css",
        );

        if (in_array($this->getContentType(), $crawable_types)) {
            //error_log($this->getUrl());
            //error_log($this->getContentType());
            return true;
        }

        return false;
	}

	public function isRewritable()
	{
		$contentType = $this->getContentType();
		return (stripos($contentType, 'html') !== false) || (stripos($contentType, 'text') !== false);
	}
	
	public function cleanup()
	{
		$responseBody = $this->getResponseBody();

		// strip WP identifiers from html files
		// note: pregreplace over DOMDocument acceptable here as we're dealing with constant
		// WP geerated output, not varying texts
		if ($this->isHtml()) {
				$responseBody = preg_replace('/<link rel=["\' ](shortlink|canonical|pingback|alternate|EditURI|wlwmanifest|index|profile|prev|next|wlwmanifest)["\' ](.*?)>/si', '', $responseBody);
				$responseBody = preg_replace('/<meta name=["\' ]generator["\' ](.*?)>/si', '', $responseBody);
				$responseBody = preg_replace('/<link(.*).w.org(.*)\/>/i', '', $responseBody);


		}

		if ($this->isCSS()) {

			$regex = array(
			"`^([\t\s]+)`ism"=>'',
			"`^\/\*(.+?)\*\/`ism"=>"",
			"`([\n\A;]+)\/\*(.+?)\*\/`ism"=>"$1",
			"`([\n\A;\s]+)//(.+?)[\n\r]`ism"=>"$1\n",
			"`(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+`ism"=>"\n"
			);

			$responseBody = preg_replace(array_keys($regex), $regex, $responseBody);
		}

		// rewrite all the things, starting with longest paths down to shortest
		// ie, do wp-content/themes/mytheme before wp-content

		// rewrite the theme directory 
		$original_theme_dir = str_replace(home_url(), '', get_template_directory_uri());
        $new_wp_content = '/' . filter_input(INPUT_POST, 'rewriteWPCONTENT');
        $new_theme_root = $new_wp_content . '/' . filter_input(INPUT_POST, 'rewriteTHEMEROOT');
        $new_theme_dir = $new_theme_root . '/' . filter_input(INPUT_POST, 'rewriteTHEMEDIR');

		$responseBody = str_replace($original_theme_dir, $new_theme_dir, $responseBody);

		// rewrite the theme theme root just in case
		$original_theme_root = str_replace(get_home_path(), '/', get_theme_root());

		$responseBody = str_replace($original_theme_root, $new_theme_root, $responseBody);

		// rewrite uploads dir
		$default_upload_dir = wp_upload_dir(); // need to store as var first
		$original_uploads_dir = str_replace(get_home_path(), '/', $default_upload_dir['basedir']);
		$new_uploads_dir = $new_wp_content . '/' . filter_input(INPUT_POST, 'rewriteUPLOADS');

		$responseBody = str_replace($original_uploads_dir, $new_uploads_dir, $responseBody);

		// rewrite plugins dir
		$original_plugins_dir = str_replace(get_home_path(), '/', WP_PLUGIN_DIR);
		$new_plugins_dir = $new_wp_content . '/' . filter_input(INPUT_POST, 'rewritePLUGINDIR');

		$responseBody = str_replace($original_plugins_dir, $new_plugins_dir, $responseBody);

		// rewrite wp-content  dir
		$original_wp_content = '/wp-content'; // TODO: check if this has been modified/use constant

		$responseBody = str_replace($original_wp_content, $new_wp_content, $responseBody);

		// rewrite wp-includes  dir
		$original_wp_includes = '/' . WPINC;
		$new_wp_includes = '/' . filter_input(INPUT_POST, 'rewriteWPINC');

		$responseBody = str_replace($original_wp_includes, $new_wp_includes, $responseBody);

		// TODO: strip query strings for just our host
		// TODO: handle different url protocols
		// TODO: if no preg_replace for the query strings found, then we must go DOMDocument...


		// TODO: strip comments from JS files

		$this->setResponseBody($responseBody);

	}
    
	public function extractAllUrls($baseUrl) {
		$allUrls = array();
	
		if (!$this->isHtml() && !$this->isCrawlableContentType()) {
            //error_log('UrlRequest was not a valid HTML file - not extracting links!');
            return array();
        }

        // TODO: could use this to get any relative urls, also...
        # find ALL urls on page:
        #preg_match_all(
        #    '@((https?://)?([-\\w]+\\.[-\\w\\.]+)+\\w(:\\d+)?(/([-\\w/_\\.]*(\\?\\S+)?)?)*)@',
        #    $this->_response['body'],
        #    $allURLsInResponseBody);

        // looks only for urls starting with base name, also needs to search for relative links
        if (
            preg_match_all(
                '/' . str_replace('/', '\/', $baseUrl) . '[^"\'#\? ]+/i', // find this
                $this->_response['body'], // in this
                $matches // save matches into this array
            )
        ) {
			$allUrls = array_unique($matches[0]);
		} 

        // do an extra check for url links in CSS file:
        if ($this->getContentType() == 'text/css') {
            if( preg_match_all(
#                '/url\((.+?)\);/i', // find any links 
                '/url\(([\s])?([\"|\'])?(.*?)([\"|\'])?([\s])?\)/i', // find any urls in CSS
                $this->_response['body'], // in this
                $matches // save matches into this array
                )
            ) {
                //error_log('FOUND URLS IN CSS!');
                // returns something like fonts/generatepress.eot

                // we need to prepend the fullpath to the CSS file, trimming the basename
                $found_relative_urls = array();

                foreach($matches[3] as $relative_url) {
                    $found_relative_urls[] = $this->getUrlWithoutFilename() . $relative_url;
                }

                $allUrls = array_unique (array_merge ($allUrls, array_unique($found_relative_urls)));
            }
        }

        if (!empty($allUrls)) {
            //error_log(print_r($allUrls, true));

            return $allUrls;
        } else {
            //error_log($this->getUrl());
            //error_log($this->getContentType());
            //error_log('DIDNT FIND ANY LINKS IN RESPONSE BODY THAT WE WANT TO ADD TO ARCHIVE');
            return array();
        }
		
	}
	
	public function replaceBaseUrl($oldBaseUrl, $newBaseUrl)
	{
		if ($this->isRewritable())
		{
			$responseBody = str_replace($oldBaseUrl, $newBaseUrl, $this->getResponseBody());
			$responseBody = str_replace('<head>', "<head>\n<base href=\"" . esc_attr($newBaseUrl) . "\" />\n", $responseBody);

			/* fix for cases where URL has been escaped/modified, ie
				http:\/\/banana.com\/
				//banana.com
				https:// -> http:// */
			$oldDomain = parse_url($oldBaseUrl);
			$newDomain = parse_url($newBaseUrl);

			// Fix JSON encoded URLs
			$oldBaseUrlJsonEncoded = substr(json_encode($oldBaseUrl), 1, -1);
			$newBaseUrlJsonEncoded = substr(json_encode($newBaseUrl), 1, -1);
			$responseBody = str_replace($oldBaseUrlJsonEncoded, $newBaseUrlJsonEncoded, $responseBody);

			// Fix JSON encoded protocol relative URLs
			$oldBaseUrlJsonEncodedRelative = substr(json_encode(substr($oldBaseUrl, strlen($oldDomain['scheme']) + 1)), 1, -1);
			$newBaseUrlJsonEncodedRelative = substr(json_encode(substr($newBaseUrl, strlen($newDomain['scheme']) + 1)), 1, -1);
			$responseBody = str_replace($oldBaseUrlJsonEncodedRelative, $newBaseUrlJsonEncodedRelative, $responseBody);

			// Fix URL encoded URLs
			$oldBaseUrlEncoded = urlencode($oldBaseUrl);
			$newBaseUrlEncoded = urlencode($newBaseUrl);
			$responseBody = str_replace($oldBaseUrlEncoded, $newBaseUrlEncoded, $responseBody);

			// Fix URL encoded protocol relative URLs
			$oldBaseUrlEncodedRelative = urlencode(substr($oldBaseUrl, strlen($oldDomain['scheme']) + 1));
			$newBaseUrlEncodedRelative = urlencode(substr($newBaseUrl, strlen($newDomain['scheme']) + 1));
			$responseBody = str_replace($oldBaseUrlEncodedRelative, $newBaseUrlEncodedRelative, $responseBody);

			// Fix plain text URLs
			$responseBody = str_replace($oldBaseUrl, $newBaseUrl, $responseBody);

			// Fix plain text protocol relative URLs
			$oldBaseUrlRelative = substr($oldBaseUrl, strlen($oldDomain['scheme']) + 1);
			$newBaseUrlRelative = substr($newBaseUrl, strlen($newDomain['scheme']) + 1);
			$responseBody = str_replace($oldBaseUrlRelative, $newBaseUrlRelative, $responseBody);

			$responseBody = str_replace($oldDomain['host'], $newDomain['host'], $responseBody);

			$this->setResponseBody($responseBody);
		}
	}
}
