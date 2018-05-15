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

	public function __construct($url, $cleanMeta = false)
	{
		$this->_url = filter_var(trim($url), FILTER_VALIDATE_URL);
		$this->_cleanMeta = $cleanMeta;

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

	public function isRewritable()
	{
		$contentType = $this->getContentType();
		return (stripos($contentType, 'html') !== false) || (stripos($contentType, 'text') !== false);
	}
	
	public function cleanup()
	{
		if ($this->isHtml())
		{
			if ($this->_cleanMeta)
			{
				$responseBody = preg_replace('/<link rel=["\' ](pingback|alternate|EditURI|wlwmanifest|index|profile|prev)["\' ](.*?)>/si', '', $this->getResponseBody());
				$responseBody = preg_replace('/<meta name=["\' ]generator["\' ](.*?)>/si', '', $responseBody);
				$this->setResponseBody($responseBody);
			}
		}
	}
    
	public function extractAllUrls($baseUrl)
	{
		$allUrls = array();
	
        error_log("\n\n entering extractAllUrls \n\n");
        error_log($baseUrl);
        error_log($this->getUrl());

		// TODO: will this follow urls for JS/CSS easily by adjusting?
        // TODO: just add options to check for content type JS or CSS
		if (!$this->isHtml()) {
            error_log('UrlRequest was not a valid HTML file - not extracting links!');
            return [];
        }

        // we have a valid HTML response, look for matching urls in         

        if (
            preg_match_all(
                '/' . str_replace('/', '\/', $baseUrl) . '[^"\'#\? ]+/i', // find this
                $this->_response['body'], // in this
                $matches // save matches into this array
            )
        ) {
            error_log('URL PRODUCED MATCHES');
			$allUrls = array_unique($matches[0]);
		} else {
            error_log('URL DIDN"T PRODUCE MATCHES');
        }
		
		return $allUrls;
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

            error_log('OLD DOMAIN');
            error_log(print_r($oldDomain, true));
            error_log('NEW DOMAIN');
            error_log(print_r($newDomain, true));

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
