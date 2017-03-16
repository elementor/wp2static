<?php
/**
 * @package WP Static HTML Output
 *
 * Copyright (c) 2011 Leon Stafford
 */

/**
 * Url request class
 */
class StaticHtmlOutput_UrlRequest
{
	/**
	 * The URI resource
	 * @var string
	 */
	protected $_url;
	
	/**
	 * The raw response from the HTTP request
	 * @var string
	 */
	protected $_response;

	/**
	 * Constructor
	 * @param string $url URI resource
	 */
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
	
	/**
	 * Returns the sanitized url
	 * @return string
	 */
	public function getUrl()
	{
		return $this->_url;
	}

	public function checkResponse()
	{
		return $this->_response;
	}
	
	/**
	 * Allows to override the HTTP response body
	 * @param string $newBody
	 * @return void
	 */
	public function setResponseBody($newBody)
	{
		if (is_array($this->_response))
		{
			$this->_response['body'] = $newBody;
		}
	}
	
	/**
	 * Returns the HTTP response body
	 * @return string
	 */
	public function getResponseBody()
	{
		return isset($this->_response['body']) ? $this->_response['body'] : '';
	}
	
	/**
	 * Returns the content type
	 * @return string
	 */
	public function getContentType()
	{
		return isset($this->_response['headers']['content-type']) ? $this->_response['headers']['content-type'] : null;
	}
	
	/**
	 * Checks if content type is html
	 * @return bool
	 */
	public function isHtml()
	{
		return stripos($this->getContentType(), 'html') !== false;
	}
	
	/**
	 * Removes WordPress-specific meta tags
	 * @return void
	 */
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
	
	/**
	 * Extracts the list of unique urls
	 * @param string $baseUrl Base url of site. Used to extract urls that relate only to the current site.
	 * @return array
	 */
	public function extractAllUrls($baseUrl)
	{
		$allUrls = array();
		
		if ($this->isHtml() && preg_match_all('/' . str_replace('/', '\/', $baseUrl) . '[^"\'#\? ]+/i', $this->_response['body'], $matches))
		{
			$allUrls = array_unique($matches[0]);
		}
		
		return $allUrls;
	}
	
	/**
	 * Replaces base url
	 * @param string $oldBaseUrl
	 * @param string $newBaseUrl
	 * @return void
	 */
	public function replaceBaseUlr($oldBaseUrl, $newBaseUrl)
	{
		if ($this->isHtml())
		{
			$responseBody = str_replace($oldBaseUrl, $newBaseUrl, $this->getResponseBody());
			$responseBody = str_replace('<head>', "<head>\n<base href=\"" . esc_attr($newBaseUrl) . "\" />\n", $responseBody);
			$this->setResponseBody($responseBody);
		}
	}
}
