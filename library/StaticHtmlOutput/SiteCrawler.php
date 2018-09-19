<?php

use GuzzleHttp\Client;

class SiteCrawler {

  public function __construct(){
    // TODO: security check that this is being called from same server
    
    // basic auth
    $this->useBasicAuth = isset($_POST['sendViaBasic']) ?  $_POST['sendViaBasic'] :  false;
    $this->basicAuthUser = $_POST['basicAuthUser'];
    $this->basicAuthPassword = $_POST['basicAuthPassword'];

    // WP env settings
    $this->baseUrl = $_POST['baseUrl'];
    $this->basedir = $_POST['basedir']; // // TODO: location of uploads dir?
    $this->wp_site_url = $_POST['wp_site_url']; 
    $this->wp_site_path = $_POST['wp_site_path']; 
    $this->uploads_path = $_POST['wp_uploads_path'];

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

    // internal pointers
    $this->processed_file = '';
    $this->file_type = '';
    $this->response = '';
    $this->content_type = '';
    $this->url = '';
    $this->extension = '';
    $this->archive_dir = '';
    $this->initial_crawl_list_file = '';

    $this->viaCLI = false; 

    // trigger the crawl
    $this->crawl_site();
  }

  public function crawl_site($viaCLI = false) {
    $this->initial_crawl_list_file = $this->uploads_path . '/WP-STATIC-INITIAL-CRAWL-LIST';
    $this->initial_crawl_list = file($this->initial_crawl_list_file, FILE_IGNORE_NEW_LINES);

    if ( !empty($this->initial_crawl_list) ) {
      $this->crawlABitMore($this->viaCLI);
    } 
  }

  public function crawlABitMore($viaCLI = false) {
    require_once dirname(__FILE__) . '/../StaticHtmlOutput/WsLog.php';

    $this->archive_dir = file_get_contents($this->uploads_path . '/WP-STATIC-CURRENT-ARCHIVE');
    $this->initial_crawl_list_file = $this->uploads_path . '/WP-STATIC-INITIAL-CRAWL-LIST';
    $this->crawled_links_file = $this->uploads_path . '/WP-STATIC-CRAWLED-LINKS';
    $this->initial_crawl_list = file($this->initial_crawl_list_file, FILE_IGNORE_NEW_LINES);
    $crawled_links = file($this->crawled_links_file, FILE_IGNORE_NEW_LINES);
    $first_line = array_shift($this->initial_crawl_list);
    file_put_contents($this->initial_crawl_list_file, implode("\r\n", $this->initial_crawl_list));
    $this->url = $first_line;

    if (empty($this->url)){
      // skip this empty file, check for more
      $f = file($this->initial_crawl_list_file, FILE_IGNORE_NEW_LINES);
      $filesRemaining = count($f);
      if ($filesRemaining > 0) {
        echo $filesRemaining;
      } else {
        echo 'SUCCESS';
      }

      return;
    }

    // detect and set file_extension
    $this->file_extension = $this->getExtensionFromURL();

    // TODO: if not a rewriteable file and exists on server, copy it into archive without reading
    if ($this->canFileBeCopiedWithoutProcessing()) {
      $this->copyFile();
    } else {
      $this->loadFileForProcessing();
      $this->saveFile();
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

    // PERF: ~ 36% of function time when HTML content (50% when other)
    //$urlResponse = new StaticHtmlOutput_UrlRequest($this->url, $basicAuth);

    // PERF: ~ 36% of function time when HTML content (50% when other)
    //$urlResponseForFurtherExtraction = new StaticHtmlOutput_UrlRequest($this->url, $basicAuth);

    $successful_response_codes = array('200', '201', '301', '302', '304');
    $status_code = $this->response->getStatusCode();
    if (! in_array($status_code,  $successful_response_codes)) {
      error_log('BAD RESPONSE STATUS (' . $status_code . '): ' . $this->url);
      WsLog::l('FAILED TO CRAWL FILE: ' . $this->url);
    } else {
      file_put_contents($this->crawled_links_file, $this->url . PHP_EOL, FILE_APPEND | LOCK_EX);
    }


    // TODO: what's the difference between this and $this->baseUrl in original code?
    $baseUrl = $this->baseUrl;

    $wp_site_environment = array(
        'wp_inc' =>  '/' . WPINC,	
        'wp_content' => '/wp-content', // TODO: check if this has been modified/use constant
        'wp_uploads' =>  str_replace(ABSPATH, '/', $this->basedir),	
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

      case 'css':
        require_once dirname(__FILE__) . '/../StaticHtmlOutput/CSSProcessor.php';
        $processor = new CSSProcessor($this->response->getBody(), $this->wp_site_url);

        $processor->normalizeURLs($this->url);
//
//        $processor->cleanup(
//            $wp_site_environment,
//            $overwrite_slug_targets
//            );
//
//        $processor->replaceBaseUrl(
//          $this->wp_site_url,
//          $this->baseUrl,
//          $this->allowOfflineUsage,
//          $this->useRelativeURLs,
//          $this->useBaseHref);
//
        $this->processed_file = $processor->getCSS();

      break;
    }
  }
  
  public function checkIfMoreCrawlingNeeded() {
    // iteration complete, check if we will signal to the client to continue processing, continue ourself for CLI usage, or signal completetion to either

    // TODO: could avoid reading file again here as we should have it above
    $f = file($this->initial_crawl_list_file, FILE_IGNORE_NEW_LINES);
    $filesRemaining = count($f);
    if ($filesRemaining > 0) {
      echo $filesRemaining;
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

