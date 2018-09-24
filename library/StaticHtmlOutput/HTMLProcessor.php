<?php
/**
 * HTMLProcessor
 *
 * @package WP2Static
 */

// TODO: deal with inline CSS blocks or style attributes on tags

// TODO: don't rewrite mailto links unless specified, re #30

class HTMLProcessor {

  public function __construct($html_document, $wp_site_url){
    $this->wp_site_url = $wp_site_url;

    // instantiate the XML body here
    $this->xml_doc = new DOMDocument();
    $this->raw_html = $html_document;
  
    // PERF: 70% of function time
    // prevent warnings, via https://stackoverflow.com/a/9149241/1668057
    libxml_use_internal_errors(true);
    $this->xml_doc->loadHTML($html_document);
    libxml_use_internal_errors(false);
  }

  // make all links absolute and relative to the current document
	public function normalizeURLs($url) {
    require_once dirname(__FILE__) . '/../URL2/URL2.php';
    $base = new Net_URL2($url);

    foreach($this->xml_doc->getElementsByTagName('a') as $link) {
      $original_link = $link->getAttribute("href");

      if ($this->isInternalLink($original_link)) {
        // TODO: apply only to links starting with .,..,/, or any with just a path, like banana.png
        $abs = $base->resolve($original_link);
        $link->setAttribute('href', $abs);
      }
    }
  }

	public function cleanup($wp_site_environment, $overwrite_slug_targets) {
    $this->stripWPMetaElements();
    $this->stripWPLinkElements();
    $this->removeQueryStringsFromInternalLinks();
    $this->rewriteWPPaths($wp_site_environment, $overwrite_slug_targets);
    $this->detectEscapedSiteURLs($wp_site_environment, $overwrite_slug_targets);
	}

  public function isInternalLink($link) {
    // check link is same host as $this->url and not a subdomain
    return parse_url($link, PHP_URL_HOST) === parse_url($this->wp_site_url, PHP_URL_HOST);
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
    $metas = iterator_to_array($this->xml_doc->getElementsByTagName('meta'));

    foreach ($metas as $meta) {
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

    $links = iterator_to_array($this->xml_doc->getElementsByTagName('link'));

    foreach ($links as $link) {
      $link_rel = $link->getAttribute("rel");
      if (in_array($link_rel, $relativeLinksToRemove)) {
        $link->parentNode->removeChild($link);
      } elseif (strpos($link_rel, '.w.org') !== false) {
        $link->parentNode->removeChild($link);
      }
    }
  }

  public function detectEscapedSiteURLs($wp_site_environment, $overwrite_slug_targets) {
    // NOTE: this does return the expected http:\/\/172.18.0.3
    // but the PHP error log will escape again and show http:\\/\\/172.18.0.3
    $escaped_site_url = addcslashes(get_option('siteurl'), '/');

    if (strpos($this->raw_html, $escaped_site_url) !== false) {
      // TODO: renable this function being called. needs to be on raw HTML, so ideally after the Processor has done all other XML things, so no need to parse again
      // suggest adding a processRawHTML function, that includes this stuff.. and call it within the getHTML function or finalizeProcessing or such....
      // error_log('skipping rewriting of escaped URLs');// leave this error log in until fixed
      //$this->rewriteEscapedURLs($wp_site_environment, $overwrite_slug_targets);
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

  public function getHTML() {
    return $this->xml_doc->saveHtml();

  }

  // TODO: damn, this is going to take a while to cleanup...
  // TODO: some optimization to be gained by doing this in same loop as rewriteWPPaths loop
	public function replaceBaseUrl($oldBaseUrl, $newBaseUrl, $allowOfflineUsage, $absolutePaths = false, $useBaseHref = true) {

    // error_log('old base url: ' . $oldBaseUrl);
    // error_log('new base url: ' . $newBaseUrl);

    foreach($this->xml_doc->getElementsByTagName('*') as $element) {
      if ($element->hasAttribute('href')) {
        $attribute_to_change = 'href';
      } elseif ($element->hasAttribute('src')) {
        $attribute_to_change = 'src';
      // skip elements without href or src
      } else {
        continue;
      }

      $url_to_change = $element->getAttribute($attribute_to_change);

      // check it actually needs to be changed
      if (strpos($url_to_change, $oldBaseUrl) !== false) {
        $rewritten_url = str_replace($oldBaseUrl, $newBaseUrl, $url_to_change);

        $element->setAttribute($attribute_to_change, $rewritten_url);
      }
    }


    if ($absolutePaths) {

      // TODO: re-implement as separate function as another processing layer

      // error_log('SKIPPING absolute path rewriting');

      if ($useBaseHref) {
        $responseBody = str_replace('<head>', "<head>\n<base href=\"" . esc_attr($newBaseUrl) . "/\" />\n", $responseBody);
      } else {
        $responseBody = str_replace('<head>', "<head>\n<base href=\"/\" />\n", $responseBody);
      }

    } elseif ($allowOfflineUsage) {
        // detect urls starting with our domain and append index.html to the end if they end in /

      // TODO: re-implement as separate function as another processing layer

      // error_log('SKIPPING offline usage rewriting');


//        foreach($xml->getElementsByTagName('a') as $link) {
//           $original_link = $link->getAttribute("href");
//
//            // process links from our site only
//            if (strpos($original_link, $oldDomain) !== false) {
//
//            }
//
//           $link->setAttribute('href', $original_link . 'index.html');
//        }
//
    }

    // TODO: should we check for incorrectly linked references? like an http link on a WP site served from https? - probably not

	}
}

