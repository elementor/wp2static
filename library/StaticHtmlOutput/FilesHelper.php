<?php
/**
 * @package WP Static HTML Output
 *
 * Copyright (c) 2011 Leon Stafford
 */

class StaticHtmlOutput_FilesHelper
{
	protected $_directory;
	
	public function __construct() {
		$this->_directory = '';
	}

	public static function delete_dir_with_files($dir) { 
    if (is_dir($dir)) {
      $files = array_diff(scandir($dir), array('.','..')); 

      foreach ($files as $file) { 
        (is_dir("$dir/$file")) ? self::delete_dir_with_files("$dir/$file") : unlink("$dir/$file"); 
      } 

      return rmdir($dir); 
    }
	} 

	public static function recursively_scan_dir($dir, $siteroot, $file_list_path){
		// rm duplicate slashes in path (TODO: fix cause)
		$dir = str_replace('//', '/', $dir);
		$files = scandir($dir);
   
		foreach($files as $item){
			if($item != '.' && $item != '..' && $item != '.git'){
				if(is_dir($dir.'/'.$item)) {
					self::recursively_scan_dir($dir.'/'.$item, $siteroot, $file_list_path);
				} else if(is_file($dir.'/'.$item)) {
					$subdir = str_replace('/wp-admin/admin-ajax.php', '', $_SERVER['REQUEST_URI']);
					$subdir = ltrim($subdir, '/');
					$clean_dir = str_replace($siteroot . '/', '', $dir.'/');
					$clean_dir = str_replace($subdir, '', $clean_dir);
					$filename = $dir .'/' . $item . "\n";
					$filename = str_replace('//', '/', $filename);
					//$this->wsLog('FILE TO ADD:');
					//$this->wsLog($filename);
					file_put_contents($file_list_path, $filename, FILE_APPEND | LOCK_EX);
				} 
			}
		}
	}

  public static function getListOfLocalFilesByUrl(array $urls) {
    $files = array();

    foreach ($urls as $url) {
      $directory = str_replace(home_url('/'), ABSPATH, $url);

      // TODO:  exclude previous export used for diff
      //if ( ! strpos($url, 'previous-export') === false ) {
        if (stripos($url, home_url('/')) === 0 && is_dir($directory)) {
          $iterator = new RecursiveIteratorIterator(
              new RecursiveDirectoryIterator(
                $directory, 
                RecursiveDirectoryIterator::SKIP_DOTS));

          foreach ($iterator as $fileName => $fileObject) {
            if (is_file($fileName)) {
              $pathinfo = pathinfo($fileName);
              if (isset($pathinfo['extension']) && !in_array($pathinfo['extension'], array('php', 'phtml', 'tpl'))) {
                array_push($files, home_url(str_replace(ABSPATH, '', $fileName)));
              }
            } 
          }
        } else {
          if ($url != '') {
            array_push($files, $url);
          }
        }
    }


    // TODO: remove any dot files, like .gitignore here, only rm'd from dirs above

    return $files;
  }

	public function buildInitialFileList(
		$viaCLI = false, 
		$additionalUrls, 
		$uploadsPath, 
		$uploadsURL, 
		$workingDirectory, 
		$pluginHook) {

    // TODO: how useful is this?
		set_time_limit(0);

		$exporter = wp_get_current_user();

		// setting path to store the archive dir path

		$archiveName = $workingDirectory . '/' . $pluginHook . '-' . time();

		// append username if done via UI
		if ( $exporter->user_login ) {
			$archiveName .= '-' . $exporter->user_login;
		}

		$archiveDir = $archiveName . '/';

		// saving the current archive name to file to persist across requests / functions
        file_put_contents($uploadsPath . '/WP-STATIC-CURRENT-ARCHIVE', $archiveDir);

		if (!file_exists($archiveDir)) {
			wp_mkdir_p($archiveDir);
		}

		$baseUrl = untrailingslashit(home_url());
			
		$urlsQueue = array_unique(array_merge(
					array(trailingslashit($baseUrl)),
					self::getListOfLocalFilesByUrl(array(get_template_directory_uri())),
                    self::getAllWPPostURLs($baseUrl),
					explode("\n", $additionalUrls)
					));

      // TODO: shift this as an option to exclusions area
			$urlsQueue = array_unique(array_merge(
					$urlsQueue,
					self::getListOfLocalFilesByUrl(array($uploadsURL))
			));

      $str = implode("\n", $urlsQueue);
      file_put_contents($uploadsPath . '/WP-STATIC-INITIAL-CRAWL-LIST', $str);
      file_put_contents($uploadsPath . '/WP-STATIC-CRAWLED-LINKS', '');

      return count($urlsQueue);
    }

    public function getAllWPPostURLs($wp_site_url){
        global $wpdb;

        // NOTE: re using $wpdb->ret_results vs WP_Query
        // https://wordpress.stackexchange.com/a/151843/20982
        // get_results may be faster, but more error prone
        // TODO: benchmark the difference and use WP_Query if not noticably slower
        // NOTE: inheret post_status allows unlinked attachment pages to be created

        $posts = $wpdb->get_results("
            SELECT ID,post_type
            FROM {$wpdb->posts}
            WHERE post_status = 'publish' AND post_type NOT IN ('revision','nav_menu_item')
        ");

        $postURLs = array();

        foreach($posts as $post) {
            switch ($post->post_type) {
                case 'page':
                    $permalink = get_page_link($post->ID);
                    break;
                case 'post':
                    $permalink = get_permalink($post->ID);
                    break;
                case 'attachment':
                    $permalink = get_attachment_link($post->ID);
                    break;
                default:
                    $permalink = get_post_permalink($post->ID);
                    break;
            }

            /* get the post's URL and each sub-chunk of the path as a URL

              ie http://domain.com/2018/01/01/my-post/ to yield:

                http://domain.com/2018/01/01/my-post/
                http://domain.com/2018/01/01/
                http://domain.com/2018/01/
                http://domain.com/2018/
            */ 

            $parsed_link = parse_url($permalink);
            $link_host = $wp_site_url . '/'; // rely on WP's site URL vs reconstructing from parsed
            $link_path = $parsed_link['path'];

            // TODO: Windows filepath support?
            $path_segments = explode('/', $link_path);

            // remove first and last empty elements 
            array_shift($path_segments);
            array_pop($path_segments);

            $number_of_segments = sizeOf($path_segments);
      
            // build each URL
            for($i = 0; $i < $number_of_segments; $i += 1) {
              $full_url = $link_host;

              for ($x = 0; $x <= $i; $x += 1) {
                $full_url .= $path_segments[$x] . '/';
              }
              $postURLs[] = $full_url;
            }
        }

        // gets all category page links
        $args = array(
          'public'   => true
        );

        $taxonomies = get_taxonomies($args, 'objects');
        foreach ($taxonomies as $taxonomy){
          $terms = get_terms(
            $taxonomy->name,
            array(
              'hide_empty' => true,
            )
          );

          foreach ($terms as $term) {
            $permalink = get_term_link($term);

            $postURLs[] = trim($permalink);
          }
        }

        // de-duplicate the array 
        return array_unique($postURLs);
    }
}
