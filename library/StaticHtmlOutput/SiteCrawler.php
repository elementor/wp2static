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
    $this->basedir = $_POST['basedir'];

    // processing related settings
    $this->rewriteWPCONTENT = $_POST['rewriteWPCONTENT'];
    $this->rewriteTHEMEROOT = $_POST['rewriteTHEMEROOT'];
    $this->rewriteTHEMEDIR = $_POST['rewriteTHEMEDIR'];
    $this->rewriteUPLOADS = $_POST['rewriteUPLOADS'];
    $this->rewritePLUGINDIR = $_POST['rewritePLUGINDIR'];
    $this->rewriteWPINC = $_POST['rewriteWPINC'];

    // trigger the crawl
    $this->crawl_site();
  }

  public function crawl_site($viaCLI = false) {

    $this->uploads_path = '/var/www/html/wp-content/uploads';

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

    $initial_crawl_list_file = $this->uploads_path . '/WP-STATIC-INITIAL-CRAWL-LIST';
    $crawled_links_file = $this->uploads_path . '/WP-STATIC-CRAWLED-LINKS';
    $initial_crawl_list = file($initial_crawl_list_file, FILE_IGNORE_NEW_LINES);
    $crawled_links = file($crawled_links_file, FILE_IGNORE_NEW_LINES);

    $first_line = array_shift($initial_crawl_list);
    file_put_contents($initial_crawl_list_file, implode("\r\n", $initial_crawl_list));
    $currentUrl = $first_line;

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
    $response = $client->request('GET', $currentUrl);

    // PERF: ~ 36% of function time when HTML content (50% when other)
    //$urlResponse = new StaticHtmlOutput_UrlRequest($currentUrl, $basicAuth);

    // PERF: ~ 36% of function time when HTML content (50% when other)
    //$urlResponseForFurtherExtraction = new StaticHtmlOutput_UrlRequest($currentUrl, $basicAuth);

    $successful_response_codes = array('200', '201', '301', '302', '304');
    if (! in_array($response->getStatusCode(),  $successful_response_codes)) {
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
        'site_url' =>  get_site_url(),
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

    require_once dirname(__FILE__) . '/../StaticHtmlOutput/HTMLProcessor.php';

    // TODO: detect which processor to use here (HTML, CSS, IMAGE, other)
    $processor = new HTMLProcessor($response->getBody());

    $processor->normalizeURLs();

    // PERF: ~ 18% of function time
    $processor->cleanup(
        $wp_site_environment,
        $overwrite_slug_targets
        );

    $processor->replaceBaseUrl(
      $baseUrl,
      $this->baseUrl,
      $this->allowOfflineUsage,
      $this->useRelativeURLs,
      $this->useBaseHref);

    $processed_html = $processor->getHTML();

    $archiveDir = file_get_contents($this->uploads_path . '/WP-STATIC-CURRENT-ARCHIVE');

    require_once dirname(__FILE__) . '/../StaticHtmlOutput/FileWriter.php';

    $file_writer = new FileWriter($url, $processed_html);

    $file_wirter->saveFile();


// TODO: rethink this part    

//    // try extracting urls from a response that hasn't been changed yet...
//    // this seems to do it...
//    foreach ($urlResponseForFurtherExtraction->extractAllUrls($baseUrl) as $newUrl) {
//      $path = parse_url($newUrl, PHP_URL_PATH);
//      $extension = pathinfo($path, PATHINFO_EXTENSION);
//
//      if ($newUrl != $currentUrl && 
//          !in_array($newUrl, $crawled_links) && 
//          $extension != 'php' && 
//          !in_array($newUrl, $initial_crawl_list)
//         ) {
//
//        $urlResponse = new StaticHtmlOutput_UrlRequest($newUrl, $basicAuth);
//
//        if ($urlResponse->response == 'FAIL') {
//          WsLog::l('FAILED TO CRAWL FILE: ' . $newUrl);
//        } else {
//          file_put_contents($crawled_links_file, $newUrl . PHP_EOL, FILE_APPEND | LOCK_EX);
//          $crawled_links[] = $newUrl;
//        }
//
//        $urlResponse->cleanup(
//            $wp_site_environment,
//            $overwrite_slug_targets
//            );
//
//        $urlResponse->replaceBaseUrl($baseUrl, $this->baseUrl, $this->allowOfflineUsage, $this->useRelativeURLs, $this->useBaseHref);
//        $archiveDir = file_get_contents($this->uploads_path . '/WP-STATIC-CURRENT-ARCHIVE');
//        $this->saveUrlData($urlResponse, $archiveDir);
//      } 
//    }

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

}

$site_crawler = new SiteCrawler();

