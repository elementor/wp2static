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
	

        // TODO: will this follow urls for JS/CSS easily by adjusting?
		if ($this->isHtml() && preg_match_all('/' . str_replace('/', '\/', $baseUrl) . '[^"\'#\? ]+/i', $this->_response['body'], $matches))
		{
			$allUrls = array_unique($matches[0]);
		}
		
		return $allUrls;
	}
	
	public function replaceBaseUlr($oldBaseUrl, $newBaseUrl)
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
            $oldDomain = $oldDomain['host'];
            $newDomain = parse_url($newBaseUrl);
            $newDomain = $newDomain['host'];
			$responseBody = str_replace($oldDomain, $newDomain, $responseBody);

			$this->setResponseBody($responseBody);
		}
	}
}
