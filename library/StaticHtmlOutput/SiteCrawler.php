<?php

use GuzzleHttp\Client;

class SiteCrawler {

  public function __construct(){
    // TODO: security check that this is being called from same server

    // TODO: move to actual Crawler class, rename this to SiteScraper
    //$crawled_links = file($this->crawled_links_file, FILE_IGNORE_NEW_LINES);
    
    // basic auth
    $this->useBasicAuth = isset($_POST['useBasicAuth']) ?  $_POST['useBasicAuth'] :  false;
    $this->basicAuthUser = isset($_POST['basicAuthUser']) ?  $_POST['basicAuthUser'] :  false;
    $this->basicAuthPassword = isset($_POST['basicAuthPassword']) ?  $_POST['basicAuthPassword'] :  false;

    // WP env settings
    $this->baseUrl = $_POST['baseUrl'];
    $this->wp_site_url = $_POST['wp_site_url']; 
    $this->wp_site_path = $_POST['wp_site_path']; 
    $this->wp_uploads_path = $_POST['wp_uploads_path'];
    $this->working_directory = isset($_POST['workingDirectory']) ? $_POST['workingDirectory'] : $this->wp_uploads_path;
    $this->wp_uploads_url = $_POST['wp_uploads_url'];

    // processing related settings
    $this->rewriteWPCONTENT = $_POST['rewriteWPCONTENT'];
    $this->rewriteTHEMEROOT = $_POST['rewriteTHEMEROOT'];
    $this->rewriteTHEMEDIR = $_POST['rewriteTHEMEDIR'];
    $this->rewriteUPLOADS = $_POST['rewriteUPLOADS'];
    $this->rewritePLUGINDIR = $_POST['rewritePLUGINDIR'];
    $this->rewriteWPINC = $_POST['rewriteWPINC'];

    $this->allowOfflineUsage = isset($_POST['allowOfflineUsage']) ?  $_POST['allowOfflineUsage'] :  false;
    $this->useRelativeURLs = isset($_POST['useRelativeURLs']) ?  $_POST['useRelativeURLs'] :  false;
    $this->useBaseHref = isset($_POST['useBaseHref']) ?  $_POST['useBaseHref'] :  false;
    $this->crawl_increment = (int) $_POST['crawl_increment'];
    $this->additionalUrls = filter_input(INPUT_POST, 'additionalUrls');

    // internal pointers
    $this->processed_file = '';
    $this->file_type = '';
    $this->response = '';
    $this->content_type = '';
    $this->url = '';
    $this->extension = '';
    $this->archive_dir = '';
    $this->list_of_urls_to_crawl_path = '';
    $this->urls_to_crawl = '';

    $this->viaCLI = false; 

    $this->crawl_site();
  }

  public function crawl_site($viaCLI = false) {
    $this->list_of_urls_to_crawl_path = $this->working_directory . '/WP-STATIC-FINAL-CRAWL-LIST';

    if (! is_file($this->list_of_urls_to_crawl_path)) {
      error_log('could not find list of files to crawl');
      require_once dirname(__FILE__) . '/../StaticHtmlOutput/WsLog.php';
      WsLog::l('ERROR: LIST OF URLS TO CRAWL NOT FOUND AT: ' . $this->list_of_urls_to_crawl_path);
      die();
    } else {
      // TODO: check when this is actually hitting empty state, not leaving behind some chars..
      if (filesize($this->list_of_urls_to_crawl_path)) {
        $this->crawlABitMore($this->viaCLI);
      } 
    }
  }

  public function crawlABitMore($viaCLI = false) {
    $batch_of_links_to_crawl = array();

    $this->list_of_urls_to_crawl_path = $this->working_directory . '/WP-STATIC-FINAL-CRAWL-LIST';

    $this->urls_to_crawl = file($this->list_of_urls_to_crawl_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    $total_links = count($this->urls_to_crawl);

    if ($total_links < 1) {
      error_log('list of URLs to crawl not found at ' . $this->list_of_urls_to_crawl_path);
      require_once dirname(__FILE__) . '/../StaticHtmlOutput/WsLog.php';
      WsLog::l('ERROR: LIST OF URLS TO CRAWL NOT FOUND AT: ' . $this->list_of_urls_to_crawl_path);
      die();
    }

    if ($this->crawl_increment > $total_links) {
      $this->crawl_increment = $total_links;
    }

    for($i = 0; $i < $this->crawl_increment; $i += 1) {
      $link_from_crawl_list = array_shift($this->urls_to_crawl);

      if ($link_from_crawl_list) {
        $batch_of_links_to_crawl[] = $link_from_crawl_list;
      }
    }

    $this->remaining_urls_to_crawl = count($this->urls_to_crawl);

    // resave crawl list file, minus those from this batch
    file_put_contents($this->list_of_urls_to_crawl_path, implode("\r\n", $this->urls_to_crawl));

    // TODO: required in saving/copying, but not here? optimize...
    $handle = fopen($this->wp_uploads_path . '/WP-STATIC-CURRENT-ARCHIVE', 'r');
    $this->archive_dir = stream_get_line($handle, 0);

    // for each link in batch, do the crawl and save
    foreach($batch_of_links_to_crawl as $link_to_crawl) {
      $this->url = $link_to_crawl;

      $this->file_extension = $this->getExtensionFromURL();

      if ($this->canFileBeCopiedWithoutProcessing()) {
        $this->copyFile();
      } else {
        $this->loadFileForProcessing();
        $this->saveFile();
      }
    }

    $this->checkIfMoreCrawlingNeeded();

    // reclaim memory after each crawl 
    $urlResponse = null;
    unset($urlResponse);
  }

  public function loadFileForProcessing() {
    require_once dirname(__FILE__) . '/../GuzzleHttp/autoloader.php';

    $client = new \GuzzleHttp\Client();

    $request_options = array(
      'http_errors' => false,
    );

    if ($this->useBasicAuth) {
      $request_options['auth'] = array(
         $this->basicAuthUser, $this->basicAuthPassword
      );
    }

    $this->response = $client->request('GET', $this->url, $request_options);
    $this->crawled_links_file = $this->working_directory . '/WP-STATIC-CRAWLED-LINKS';

    $successful_response_codes = array('200', '201', '301', '302', '304');
    $status_code = $this->response->getStatusCode();

    if (! in_array($status_code,  $successful_response_codes)) {
      require_once dirname(__FILE__) . '/../StaticHtmlOutput/WsLog.php';
      WsLog::l('BAD RESPONSE STATUS (' . $status_code . '): ' . $this->url);
    } else {
      file_put_contents($this->crawled_links_file, $this->url . PHP_EOL, FILE_APPEND | LOCK_EX);
    }


    // TODO: what's the difference between this and $this->baseUrl in original code?
    $baseUrl = $this->baseUrl;

    $wp_site_environment = array(
        'wp_inc' =>  '/' . WPINC,	
        'wp_content' => '/wp-content', // TODO: check if this has been modified/use constant
        'wp_uploads' =>  str_replace(ABSPATH, '/', $this->wp_uploads_path),	
        'wp_plugins' =>  str_replace(ABSPATH, '/', WP_PLUGIN_DIR),	
        'wp_themes' =>  str_replace(ABSPATH, '/', get_theme_root()),	
        'wp_active_theme' =>  str_replace(home_url(), '', get_template_directory_uri()),	
        'site_url' =>  $this->wp_site_url,
        );

    $new_wp_content = '/' . $this->rewriteWPCONTENT;
    $new_theme_root = $new_wp_content . '/' . $this->rewriteTHEMEROOT;
    $new_theme_dir = $new_theme_root . '/' . $this->rewriteTHEMEDIR;
    $new_uploads_dir = $new_wp_content . '/' . $this->rewriteUPLOADS;
    $new_plugins_dir = $new_wp_content . '/' . $this->rewritePLUGINDIR;

    $overwrite_slug_targets = array(
        'new_wp_content_path' => $new_wp_content,
        'new_themes_path' => $new_theme_root,
        'new_active_theme_path' => $new_theme_dir,
        'new_uploads_path' => $new_uploads_dir,
        'new_plugins_path' => $new_plugins_dir,
        'new_wpinc_path' => '/' . $this->rewriteWPINC,
        );

    
    $this->detectFileType($this->url);

    // process based on filetype
    switch ($this->file_type) {
      case 'html':
        require_once dirname(__FILE__) . '/../StaticHtmlOutput/HTMLProcessor.php';
        $processor = new HTMLProcessor($this->response->getBody(), $this->wp_site_url);

        $processor->normalizeURLs($this->url);

        $processor->cleanup(
            $wp_site_environment,
            $overwrite_slug_targets
            );

        $processor->replaceBaseUrl(
          $this->wp_site_url,
          $this->baseUrl,
          $this->allowOfflineUsage,
          $this->useRelativeURLs,
          $this->useBaseHref);

        $this->processed_file = $processor->getHTML();

      break;

      // TODO: apply other replacement functions to all processors
      case 'css':
        require_once dirname(__FILE__) . '/../StaticHtmlOutput/CSSProcessor.php';
        $processor = new CSSProcessor($this->response->getBody(), $this->wp_site_url);

        $processor->normalizeURLs($this->url);

        $this->processed_file = $processor->getCSS();

      case 'js':
        require_once dirname(__FILE__) . '/../StaticHtmlOutput/JSProcessor.php';
        $processor = new JSProcessor($this->response->getBody(), $this->wp_site_url);

        $processor->normalizeURLs($this->url);

        $this->processed_file = $processor->getJS();

      break;

      case 'txt':
        require_once dirname(__FILE__) . '/../StaticHtmlOutput/TXTProcessor.php';
        $processor = new TXTProcessor($this->response->getBody(), $this->wp_site_url);

        $processor->normalizeURLs($this->url);

        $this->processed_file = $processor->getTXT();

      break;
    
      default:
        require_once dirname(__FILE__) . '/../StaticHtmlOutput/WsLog.php';
        WsLog::l('WARNING: ENCOUNTERED FILE WITH NO PROCESSOR: ' . $this->url);
      break;
    }
  }
  
  public function checkIfMoreCrawlingNeeded() {
    // iteration complete, check if we will signal to the client to continue processing, continue ourself for CLI usage, or signal completetion to either

    if ($this->remaining_urls_to_crawl > 0) {
      echo $this->remaining_urls_to_crawl;
    } else {
      echo 'SUCCESS';
    }

    // if being called via the CLI, just keep crawling (TODO: until when?)
    if ($this->viaCLI) {
      $this->crawl_site($this->viaCLI);
    }
  }


  public function saveFile() {
    // response body processing is complete, now time to save the file contents to the archive
    require_once dirname(__FILE__) . '/../StaticHtmlOutput/FileWriter.php';

    $file_writer = new FileWriter($this->url, $this->processed_file, $this->file_type);
    $file_writer->saveFile($this->archive_dir);
  }

  public function copyFile() {
    require_once dirname(__FILE__) . '/../StaticHtmlOutput/FileCopier.php';

    $file_copier = new FileCopier($this->url, $this->wp_site_url, $this->wp_site_path);
    $file_copier->copyFile($this->archive_dir);
  }

  public function getExtensionFromURL() {
    $url_path = parse_url($this->url, PHP_URL_PATH);

    $extension = pathinfo($url_path, PATHINFO_EXTENSION);   

    if (! $extension) {
      return '';
    }
 
    return $extension;
  }

  public function canFileBeCopiedWithoutProcessing() {
    // whitelisted extensions, so as not catch html/xml/json served at domain.com/path/  
    $extensions_to_skip = array(
      'jpg', 'jpeg', 'pdf', 'png', 'gif', 'svg'
    );
 
    if ( $this->file_extension && in_array($this->file_extension, $extensions_to_skip)) {
      return true;
    }

    return false;
  }


	public function isRewritable($url) {
		$contentType = $this->response->getHeaderLine('content-type');

		return (stripos($contentType, 'html') !== false) || (stripos($contentType, 'text') !== false);
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
            return true;
        }

        return false;
	}
  
  public function detectFileType() {
    if ($this->file_extension) {
          $this->file_type = $this->file_extension;
    } else {
      // further detect type based on content type
      $this->content_type = $this->response->getHeaderLine('content-type');

	    if (stripos($this->content_type, 'text/html') !== false) {
        $this->file_type = 'html';
      } else {
        error_log('couldnt get filetype from content-type header in response, all we got was:');
        error_log($this->response->getHeaderLine('content-type'));
      }
    }
  }
}

$site_crawler = new SiteCrawler();

