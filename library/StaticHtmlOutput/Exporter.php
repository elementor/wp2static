<?php

class Exporter {

  public function __construct(){
    $this->diffBasedDeploys = '';
    $this->crawled_links_file = '';

    // WP env settings
    $this->baseUrl = $_POST['baseUrl'];
    $this->working_directory = $_POST['working_directory'];
    $this->wp_site_url = $_POST['wp_site_url']; 
    $this->wp_site_path = $_POST['wp_site_path']; 
    $this->wp_uploads_path = $_POST['wp_uploads_path'];
    $this->wp_uploads_url = $_POST['wp_uploads_url'];
  }

  public function capture_last_deployment() {
    // skip for first export state
    if (is_file($this->working_directory . '/WP-STATIC-CURRENT-ARCHIVE')) {
      $archiveDir = file_get_contents($this->working_directory . '/WP-STATIC-CURRENT-ARCHIVE');
      $previous_export = $archiveDir;
      $dir_to_diff_against = $this->working_directory . '/previous-export';

      if ($this->diffBasedDeploys) {
        $archiveDir = file_get_contents($this->working_directory . '/WP-STATIC-CURRENT-ARCHIVE');

        $previous_export = $archiveDir;
        $dir_to_diff_against = $this->working_directory . '/previous-export';

        if (is_dir($previous_export)) {
          shell_exec("rm -Rf $dir_to_diff_against && mkdir -p $dir_to_diff_against && cp -r $previous_export/* $dir_to_diff_against");

        } 
      } else {
          if(is_dir($dir_to_diff_against)) {
              StaticHtmlOutput_FilesHelper::delete_dir_with_files($dir_to_diff_against);
              StaticHtmlOutput_FilesHelper::delete_dir_with_files($archiveDir);
            }
      }
    }

		echo 'SUCCESS';
  }

	public function pre_export_cleanup() {
		$files_to_clean = array(
			'/WP-STATIC-EXPORT-S3-FILES-TO-EXPORT',
			'/WP-STATIC-EXPORT-FTP-FILES-TO-EXPORT',
			'/WP-STATIC-EXPORT-GITHUB-FILES-TO-EXPORT',
			'/WP-STATIC-EXPORT-DROPBOX-FILES-TO-EXPORT',
			'/WP-STATIC-EXPORT-BUNNYCDN-FILES-TO-EXPORT',
			'/WP-STATIC-CRAWLED-LINKS',
//			'/WP-STATIC-INITIAL-CRAWL-LIST',
//			'/WP-STATIC-CURRENT-ARCHIVE', // needed for zip download, diff deploys, etc
			'WP-STATIC-EXPORT-LOG'
		);

		foreach ($files_to_clean as $file_to_clean) {
			if ( file_exists($this->working_directory . '/' . $file_to_clean) ) {
				unlink($this->working_directory . '/' . $file_to_clean);
			} 
		}
	}

	public function cleanup_working_files() {
    error_log('cleanup_working_files()'); 
    // skip first explort state
    if (is_file($this->working_directory . '/WP-STATIC-CURRENT-ARCHIVE')) {
      $archiveDir = file_get_contents($this->working_directory . '/WP-STATIC-CURRENT-ARCHIVE');
      $dir_to_diff_against = $this->working_directory . '/previous-export';

      if(is_dir($dir_to_diff_against)) {
        // TODO: rewrite to php native in case of shared hosting 
        // delete archivedir and then recursively copy 
        shell_exec("cp -r $dir_to_diff_against/* $archiveDir/");
      }
    }

		$files_to_clean = array(
			'/WP-STATIC-EXPORT-S3-FILES-TO-EXPORT',
			'/WP-STATIC-EXPORT-FTP-FILES-TO-EXPORT',
			'/WP-STATIC-EXPORT-GITHUB-FILES-TO-EXPORT',
			'/WP-STATIC-EXPORT-DROPBOX-FILES-TO-EXPORT',
			'/WP-STATIC-EXPORT-BUNNYCDN-FILES-TO-EXPORT',
			'/WP-STATIC-CRAWLED-LINKS',
//			'/WP-STATIC-INITIAL-CRAWL-LIST',
			//'/WP-STATIC-CURRENT-ARCHIVE', // needed for zip download, diff deploys, etc
//			'WP-STATIC-EXPORT-LOG'
		);

		foreach ($files_to_clean as $file_to_clean) {
			if ( file_exists($this->working_directory . '/' . $file_to_clean) ) {
				unlink($this->working_directory . '/' . $file_to_clean);
			} 
		}
	}

  public function initialize_cache_files() {
    $this->crawled_links_file = $this->working_directory . '/WP-STATIC-CRAWLED-LINKS';
 
    $resource = fopen($this->crawled_links_file, 'w');
    fwrite($resource, '');
    fclose($resource);  
  }

	public function cleanup_leftover_archives() {
		$leftover_files = preg_grep('/^([^.])/', scandir($this->working_directory));

		foreach ($leftover_files as $fileName) {
			if( strpos($fileName, 'wp-static-html-output-') !== false ) {

				if (is_dir($this->working_directory . '/' . $fileName)) {
					StaticHtmlOutput_FilesHelper::delete_dir_with_files($this->working_directory . '/' . $fileName);
				} else {
					unlink($this->working_directory . '/' . $fileName);
				}
			}
		}

		echo 'SUCCESS';
	}	

  public function generateModifiedFileList() {
    // copy the preview crawl list within uploads dir to "modified list"
    copy($this->wp_uploads_path . '/WP-STATIC-INITIAL-CRAWL-LIST', $this->wp_uploads_path . '/WP-STATIC-MODIFIED-CRAWL-LIST');

//    // process the modified list and make available for previewing from UI
//    $initial_file_list_count = StaticHtmlOutput_FilesHelper::buildFinalFileList(
//      $viaCLI,
//      $this->additionalUrls,
//      $this->getWorkingDirectory(),
//      $this->uploadsURL,
//      $this->getWorkingDirectory(),
//      self::HOOK
//    );

    // copy the modified list to the working dir "finalized crawl list"
    copy($this->wp_uploads_path . '/WP-STATIC-MODIFIED-CRAWL-LIST', $this->working_directory . '/WP-STATIC-FINAL-CRAWL-LIST');

    // use finalized crawl list from working dir to start the export 

    // if list has been (re)generated in the frontend, use it, else generate again at export time
  }
}

