<?php

error_log('standalone script called via AJAX');


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

    // trigger the crawl
    $this->crawl_site();
  }

  public function crawl_site($viaCLI = false) {

    // PERF: 1% of function time
    $initial_crawl_list_file = $this->uploads_path . '/WP-STATIC-INITIAL-CRAWL-LIST';
    $initial_crawl_list = file($initial_crawl_list_file, FILE_IGNORE_NEW_LINES);

    // PERF: 99% of function time
    if ( !empty($initial_crawl_list) ) {
      $this->crawlABitMore($viaCLI);
    } 
  }

  public function crawlABitMore($viaCLI = false) {
    require_once dirname(__FILE__) . '/../StaticHtmlOutput/WsLog.php';

    $archiveDir = file_get_contents($this->uploads_path . '/WP-STATIC-CURRENT-ARCHIVE');

    $initial_crawl_list_file = $this->uploads_path . '/WP-STATIC-INITIAL-CRAWL-LIST';
    $crawled_links_file = $this->uploads_path . '/WP-STATIC-CRAWLED-LINKS';
    $initial_crawl_list = file($initial_crawl_list_file, FILE_IGNORE_NEW_LINES);
    $crawled_links = file($crawled_links_file, FILE_IGNORE_NEW_LINES);

    $first_line = array_shift($initial_crawl_list);
    file_put_contents($initial_crawl_list_file, implode("\r\n", $initial_crawl_list));
    $currentUrl = $first_line;

    error_log('url: ' . $currentUrl);

    if (empty($currentUrl)){
      // skip this empty file

      $f = file($initial_crawl_list_file, FILE_IGNORE_NEW_LINES);
      $filesRemaining = count($f);
      if ($filesRemaining > 0) {
        echo $filesRemaining;
      } else {
        echo 'SUCCESS';
      }

      return;
    }

    $basicAuth = array(
        'useBasicAuth' => $this->useBasicAuth,
        'basicAuthUser' => $this->basicAuthUser,
        'basicAuthPassword' => $this->basicAuthPassword);

    require_once dirname(__FILE__) . '/../GuzzleHttp/autoloader.php';


    $client = new \GuzzleHttp\Client();
    // TODO: set basic auth and any other args
    $this->response = $client->request('GET', $currentUrl);

    // PERF: ~ 36% of function time when HTML content (50% when other)
    //$urlResponse = new StaticHtmlOutput_UrlRequest($currentUrl, $basicAuth);

    // PERF: ~ 36% of function time when HTML content (50% when other)
    //$urlResponseForFurtherExtraction = new StaticHtmlOutput_UrlRequest($currentUrl, $basicAuth);

    $successful_response_codes = array('200', '201', '301', '302', '304');
    if (! in_array($this->response->getStatusCode(),  $successful_response_codes)) {
      WsLog::l('FAILED TO CRAWL FILE: ' . $currentUrl);
    } else {
      file_put_contents($crawled_links_file, $currentUrl . PHP_EOL, FILE_APPEND | LOCK_EX);
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

    
    $this->detectFileType($currentUrl);

    // process based on filetype
    switch ($this->file_type) {
      case 'html':
        error_log('processing HTML');
        require_once dirname(__FILE__) . '/../StaticHtmlOutput/HTMLProcessor.php';
        $processor = new HTMLProcessor($this->response->getBody());

        $processor->normalizeURLs($currentUrl);

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
        error_log('processing CSS');
        require_once dirname(__FILE__) . '/../StaticHtmlOutput/CSSProcessor.php';
        $processor = new CSSProcessor($this->response->getBody());

        $processor->normalizeURLs($currentUrl);

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
    }

    // response body processing is complete, now time to save the file contents to the archive

    require_once dirname(__FILE__) . '/../StaticHtmlOutput/FileWriter.php';

    $file_writer = new FileWriter($currentUrl, $this->processed_file, $this->file_type);

    $file_writer->saveFile($archiveDir);


    // iteration complete, check if we will signal to the client to continue processing, continue ourself for CLI usage, or signal completetion to either

    // TODO: could avoid reading file again here as we should have it above
    $f = file($initial_crawl_list_file, FILE_IGNORE_NEW_LINES);
    $filesRemaining = count($f);
    if ($filesRemaining > 0) {
      echo $filesRemaining;
    } else {
      echo 'SUCCESS';
    }

    // if being called via the CLI, just keep crawling (TODO: until when?)
    if ($viaCLI) {
      $this->crawl_site($viaCLI);
    }

    // reclaim memory after each crawl
    $urlResponse = null;
    unset($urlResponse);
  }

	public function isRewritable($response) {
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
            //error_log($this->url);
            //error_log($this->getContentType());
            return true;
        }

        return false;
	}
  
  public function detectFileType($url) {
    // TODO: detect which processor to use here (HTML, CSS, IMAGE, other)
    $file_info = pathinfo($url);

    $file_extension = isset($file_info['extension']) ? $file_info['extension'] : false;


    if ($file_extension) {
          error_log('file extension detected as: ' . $file_extension);
          $this->file_type = $file_extension;
    } else {
      // further detect type based on content type
      $this->content_type = $this->response->getHeaderLine('content-type');
    
	    if (stripos($this->content_type, 'text/html') !== false) {
        $this->file_type = 'html';
      } else {
        error_log('couldnt get filetype from content-type header in response, all we got was:');
        error_log($this->response->getHeaderLine('content-type'));
      
      }
       

      error_log('file extension detected (via content type) as: ' . $this->file_type);
    }

  }
}

$site_crawler = new SiteCrawler();

