<?php

class Archive {

  public function __construct() {
    $this->path = '';
    $this->crawl_list = '';
    $this->export_log = '';
    $this->working_directory = isset($_POST['working_directory']) ? $_POST['working_directory'] : '';
  }

  public function addFiles() {
    // TODO: use within SiteCrawler
  }

  public function setToCurrentArchive() {
    // refreshes the archive properties in case called out of context
    
    // TODO: improve this by auto-detecting the current archive within the construct

    $this->path = file_get_contents($this->working_directory . '/WP-STATIC-CURRENT-ARCHIVE');
  }

  public function create() {
		$this->name = $this->working_directory . '/wp-static-html-output-' . time();

		$this->path = $this->name . '/';

    if (wp_mkdir_p($this->path)) {
      // saving the current archive name to file to persist across requests / functions
      file_put_contents($this->working_directory . '/WP-STATIC-CURRENT-ARCHIVE', $this->path);
    } else {
      error_log("Couldn't create the archive directory at " . $this->path);
    }
  }
}
