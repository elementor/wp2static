<?php

class Exporter {

  public function __construct(){

  }

  public function capture_last_deployment() {
      // skip for first export state
      if (is_file($this->getWorkingDirectory() . '/WP-STATIC-CURRENT-ARCHIVE')) {
        $archiveDir = file_get_contents($this->getWorkingDirectory() . '/WP-STATIC-CURRENT-ARCHIVE');
        $previous_export = $archiveDir;
        $dir_to_diff_against = $this->getWorkingDirectory() . '/previous-export';

        if ($this->diffBasedDeploys) {
          $archiveDir = file_get_contents($this->getWorkingDirectory() . '/WP-STATIC-CURRENT-ARCHIVE');

          $previous_export = $archiveDir;
          $dir_to_diff_against = $this->getWorkingDirectory() . '/previous-export';

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
    error_log('cleaning up leftover exports');
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
			if ( file_exists($this->getWorkingDirectory() . '/' . $file_to_clean) ) {
				unlink($this->getWorkingDirectory() . '/' . $file_to_clean);
			} 
		}
	}

	public function cleanup_working_files() {
    error_log('cleanup_working_files()'); 
    // skip first explort state
    if (is_file($this->getWorkingDirectory() . '/WP-STATIC-CURRENT-ARCHIVE')) {
      $archiveDir = file_get_contents($this->getWorkingDirectory() . '/WP-STATIC-CURRENT-ARCHIVE');
      $dir_to_diff_against = $this->getWorkingDirectory() . '/previous-export';

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
			if ( file_exists($this->getWorkingDirectory() . '/' . $file_to_clean) ) {
				unlink($this->getWorkingDirectory() . '/' . $file_to_clean);
			} 
		}
	}

  public function initialize_cache_files() {
    $this->crawled_links_file = $this->getWorkingDirectory() . '/WP-STATIC-CRAWLED-LINKS';
 
    $resource = fopen($this->crawled_links_file, 'w');
    fwrite($resource, '');
    fclose($resource);  
  }

	public function cleanup_leftover_archives() {
		$leftover_files = preg_grep('/^([^.])/', scandir($this->getWorkingDirectory()));

		foreach ($leftover_files as $fileName) {
			if( strpos($fileName, 'wp-static-html-output-') !== false ) {

				if (is_dir($this->getWorkingDirectory() . '/' . $fileName)) {
					StaticHtmlOutput_FilesHelper::delete_dir_with_files($this->getWorkingDirectory() . '/' . $fileName);
				} else {
					unlink($this->getWorkingDirectory() . '/' . $fileName);
				}
			}
		}

		echo 'SUCCESS';
	}	
}

