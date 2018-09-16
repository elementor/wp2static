<?php

class StaticHtmlOutput_UrlRequest {
	protected $_basicAuthCredentials;

	public function __construct($url, $basicAuth) {
		$this->url = filter_var(trim($url), FILTER_VALIDATE_URL);
		$this->response = '';
    $this->xml_doc = null;

		$args = array(
			'timeout' => 20, // set timeout to within shared hosting common execution limit
			'sslverify'   => apply_filters( 'https_local_ssl_verify', false )
		);

		if ( !empty($basicAuth['useBasicAuth']) ) {
			$this->_basicAuthCredentials = base64_encode( $basicAuth['basicAuthUser'] . ':' . $basicAuth['basicAuthPassword'] );
			$args['headers'] = array( 'Authorization' => 'Basic ' . $this->_basicAuthCredentials );
		}
		
		$response = wp_remote_get( $this->url, $args); 


		if (is_wp_error($response)) {
			error_log('error in wp_remote_get response for URL:' . $this->url);
			error_log(print_r($response, true));
      WsLog::l('error in wp_remote_get response for URL: ' . $this->url);
			WsLog::l(print_r($response, true));
			$this->response = 'FAIL';
		} else {
			$this->response = $response;
		}
	}

	public function getUrlWithoutFilename() {
        $file_info = pathinfo($this->url);

        return isset($file_info['extension'])
            ? str_replace($file_info['filename'] . "." . $file_info['extension'], "", $this->url)
            : $this->url;
	}

	public function setResponseBody($newBody) {
		if (is_array($this->response)) {
			$this->response['body'] = $newBody;
		}
	}
	
	public function getContentType() {
		return isset($this->response['headers']['content-type']) ? $this->response['headers']['content-type'] : null;
	}
	
	public function isHtml() {
		return stripos($this->getContentType(), 'html') !== false;
	}

	public function isCSS() {
		return stripos($this->getContentType(), 'css') !== false;
	}

	public function isCrawlableContentType() {
        $crawable_types = array(
            "text/plain",
            "application/javascript",
            "application/json",
            "application/xml",
            "text/css",
        );

        if (in_array($this->getContentType(), $crawable_types)) {
            //error_log($this->url);
            //error_log($this->getContentType());
            return true;
        }

        return false;
	}

	public function isRewritable() {
		$contentType = $this->getContentType();

		return (stripos($contentType, 'html') !== false) || (stripos($contentType, 'text') !== false);
	}

	public function normalizeURLs() {
    if (! $this->isRewritable() ) {
      return;
    }
 
    $xml = new DOMDocument(); 
  
    // prevent warnings, via https://stackoverflow.com/a/9149241/1668057
    libxml_use_internal_errors(true);
    $xml->loadHTML($this->response['body']); 
    libxml_use_internal_errors(false);

    $base = new Net_URL2($this->url);

    foreach($xml->getElementsByTagName('a') as $link) { 
      $original_link = $link->getAttribute("href");

      // TODO: apply only to links starting with .,..,/, or any with just a path, like banana.png
      $abs = $base->resolve($original_link);
      $link->setAttribute('href', $abs);
    }

		$this->setResponseBody($xml->saveHtml());
  }

  public function detectEscapedSiteURLs($wp_site_environment, $overwrite_slug_targets) {
    // NOTE: this does return the expected http:\/\/172.18.0.3
    // but the PHP error log will escape again and show http:\\/\\/172.18.0.3
    $escaped_site_url = addcslashes(get_option('siteurl'), '/');

    if (strpos($this->response['body'], $escaped_site_url) !== false) {
      $this->rewriteEscapedURLs($wp_site_environment, $overwrite_slug_targets);
    }
  }

  public function rewriteEscapedURLs($wp_site_environment, $overwrite_slug_targets) {
    /* this function will be a bit more costly. To cover bases like:

<section  id="hero"  data-images="[&quot;https:\/\/mysite.example.com\/wp-content\/themes\/onepress\/assets\/images\/hero5.jpg&quot;]"             class="hero-slideshow-wrapper hero-slideshow-normal">

    from the onepress(?) theme, for example

    */

    $rewritten_source = str_replace(
      array(
        addcslashes($wp_site_environment['wp_active_theme'], '/'),
        addcslashes($wp_site_environment['wp_themes'], '/'),
        addcslashes($wp_site_environment['wp_uploads'], '/'),
        addcslashes($wp_site_environment['wp_plugins'], '/'),
        addcslashes($wp_site_environment['wp_content'], '/'),
        addcslashes($wp_site_environment['wp_inc'], '/'),
      ),
      array(
        addcslashes($overwrite_slug_targets['new_active_theme_path'], '/'),
        addcslashes($overwrite_slug_targets['new_themes_path'], '/'),
        addcslashes($overwrite_slug_targets['new_uploads_path'], '/'),
        addcslashes($overwrite_slug_targets['new_plugins_path'], '/'),
        addcslashes($overwrite_slug_targets['new_wp_content_path'], '/'),
        addcslashes($overwrite_slug_targets['new_wpinc_path'], '/'),
      ),
      $this->response['body']);

      $this->setResponseBody($rewritten_source);
  }

	public function rewriteWPPaths($wp_site_environment, $overwrite_slug_targets) {
    // NOTE: drier code but costlier memory usage
    foreach($this->xml_doc->getElementsByTagName('*') as $element) { 
      $attribute_to_change = '';
      $url_to_change = '';

      if ($element->hasAttribute('href')) {
        $attribute_to_change = 'href';
      } elseif ($element->hasAttribute('src')) {
        $attribute_to_change = 'src';
      // skip elements without href or src 
      } else {
        continue; 
      }

      $url_to_change = $element->getAttribute($attribute_to_change);

      if ($this->isInternalLink($url_to_change)) {
        // rewrite all the things, starting with longest paths down to shortest
        $rewritten_url = str_replace(
          array(
            $wp_site_environment['wp_active_theme'],
            $wp_site_environment['wp_themes'], 
            $wp_site_environment['wp_uploads'], 
            $wp_site_environment['wp_plugins'], 
            $wp_site_environment['wp_content'], 
            $wp_site_environment['wp_inc'], 
          ),
          array(
            $overwrite_slug_targets['new_active_theme_path'],
            $overwrite_slug_targets['new_themes_path'],
            $overwrite_slug_targets['new_uploads_path'],
            $overwrite_slug_targets['new_plugins_path'],
            $overwrite_slug_targets['new_wp_content_path'],
            $overwrite_slug_targets['new_wpinc_path'],
          ),
          $url_to_change);

        $element->setAttribute($attribute_to_change, $rewritten_url);
      }
    }
  }

  public function isInternalLink($link) {
    // check link is same host as $this->url and not a subdomain
    return parse_url($link, PHP_URL_HOST) == parse_url(get_option( 'siteurl' ), PHP_URL_HOST);
  }

  public function removeQueryStringsFromInternalLinks() {
    foreach($this->xml_doc->getElementsByTagName('a') as $link) { 
      $link_href = $link->getAttribute("href");

      // check if it's an internal link not a subdomain
      if ($this->isInternalLink($link_href)) {
        // strip anything from the ? onwards
        // https://stackoverflow.com/a/42476194/1668057 
        $link->setAttribute('href', strtok($link_href, '?'));
      } 
    }
  }

  public function stripWPMetaElements() {
    foreach($this->xml_doc->getElementsByTagName('meta') as $meta) { 
      $meta_name = $meta->getAttribute("name");

      if (strpos($meta_name, 'generator') !== false) {
        $meta->parentNode->removeChild($meta);
      }
    }

  }

  public function stripWPLinkElements() {
    $relativeLinksToRemove = array(
      'shortlink',
      'canonical',
      'pingback',
      'alternate',
      'EditURI',
      'wlwmanifest',
      'index',
      'profile',
      'prev',
      'next',
      'wlwmanifest',
    );

    foreach($this->xml_doc->getElementsByTagName('link') as $link) { 
      $link_rel = $link->getAttribute("rel");

      if (in_array($link_rel, $relativeLinksToRemove)) {
        $link->parentNode->removeChild($link);
      } elseif (strpos($link_rel, '.w.org') !== false) {
        $link->parentNode->removeChild($link);
      }
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

        // instantiate the XML body here 
        $this->xml_doc = new DOMDocument(); 
      
        // PERF: 70% of function time
        // prevent warnings, via https://stackoverflow.com/a/9149241/1668057
        libxml_use_internal_errors(true);
        $this->xml_doc->loadHTML($this->response['body']); 
        libxml_use_internal_errors(false);
        

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
        #    $this->response['body'],
        #    $allURLsInResponseBody);

        // looks only for urls starting with base name, also needs to search for relative links
        if (
            preg_match_all(
                '/' . str_replace('/', '\/', $baseUrl) . '[^"\'#\?); ]+/i',
                $this->response['body'], // in this
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
                $this->response['body'], // in this
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
            //error_log($this->url);
            //error_log($this->getContentType());
            //error_log('DIDNT FIND ANY LINKS IN RESPONSE BODY THAT WE WANT TO ADD TO ARCHIVE');
            return array();
        }
		
	}

	public function replaceBaseUrl($oldBaseUrl, $newBaseUrl, $allowOfflineUsage, $absolutePaths = false, $useBaseHref = true)
	{

		// TODO: don't rewrite mailto links unless specified, re #30
		if ($this->isRewritable())
		{

			$oldDomain = str_replace('https://', '', $oldBaseUrl);
			$oldDomain = str_replace('http://', '', $oldDomain);
			$oldDomain = str_replace('//', '', $oldDomain);
			$newDomain = str_replace('https://', '', $newBaseUrl);
			$newDomain = str_replace('http://', '', $newDomain);
			$newDomain = str_replace('//', '', $newDomain);

			$responseBody = $this->response['body'];

			if ($absolutePaths) {

				$responseBody = str_replace($oldDomain, $newDomain, $responseBody);
				$responseBody = str_replace('https://' . $newDomain . '/', '', $responseBody);
				$responseBody = str_replace('https://' . $newDomain, '', $responseBody);
				$responseBody = str_replace('http://' . $newDomain . '/', '', $responseBody);
				$responseBody = str_replace('http://' . $newDomain, '', $responseBody);
				$responseBody = str_replace('//' . $newDomain . '/', '', $responseBody);
				$responseBody = str_replace('//' . $newDomain, '', $responseBody);


				$responseBody = str_replace($newDomain, '', $responseBody);

		// TODO: use DOMDoc here
				if ($useBaseHref)
				{
					$responseBody = str_replace('<head>', "<head>\n<base href=\"" . esc_attr($newBaseUrl) . "/\" />\n", $responseBody);
				}
				else
				{
					$responseBody = str_replace('<head>', "<head>\n<base href=\"/\" />\n", $responseBody);
				}
			} elseif ($allowOfflineUsage) {
          // detect urls starting with our domain and append index.html to the end if they end in /
          $xml = new DOMDocument(); 
        
          // prevent warnings, via https://stackoverflow.com/a/9149241/1668057
          libxml_use_internal_errors(true);
          $xml->loadHTML($responseBody); 
          libxml_use_internal_errors(false);

          foreach($xml->getElementsByTagName('a') as $link) { 
             $original_link = $link->getAttribute("href");
             
              // process links from our site only 
              if (strpos($original_link, $oldDomain) !== false) {
              }

             $link->setAttribute('href', $original_link . 'index.html');
          }
          $responseBody =  $xml->saveHtml(); 

          $responseBody = str_replace('https://' . $oldDomain . '/', '', $responseBody);
          $responseBody = str_replace('https://' . $oldDomain . '', '', $responseBody);
          $responseBody = str_replace('http://' . $oldDomain . '/', '', $responseBody);
          $responseBody = str_replace('http://' . $oldDomain . '', '', $responseBody);
          $responseBody = str_replace('//' . $oldDomain . '/', '', $responseBody);
          $responseBody = str_replace('//' . $oldDomain . '', '', $responseBody);
          $responseBody = str_replace($oldDomain . '/', '', $responseBody);
          $responseBody = str_replace($oldDomain, '', $responseBody);
			} else {
          // note: as it's stripping urls first, the replacing, it will not keep the desired
          // url protocol if the old url is http and the new is https, for example 
          $responseBody = str_replace($oldDomain, $newDomain, $responseBody);

          // do another pass, detecting any incorrect protocols and correcting to the desired one
          $responseBody = str_replace('http://' . $newDomain, $newBaseUrl, $responseBody);
          $responseBody = str_replace('https://' . $newDomain, $newBaseUrl, $responseBody);

          // TODO: cater for protocol rel links

			}

			$this->setResponseBody($responseBody);
		}
	}
}
