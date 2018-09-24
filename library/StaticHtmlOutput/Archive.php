<?php

class Archive {

  public function __construct() {
    $this->path = '';
    $this->crawl_list = '';
    $this->export_log = '';
    $this->working_directory = isset($_POST['working_directory']) ? $_POST['working_directory'] : '';
  }

  public function addFiles() {

  }

  public function create() {
		$this->archive_name = $this->working_directory . '/wp-static-html-output-' . time();

		$this->archive_directory = $this->archive_name . '/';

    if (wp_mkdir_p($this->archive_directory)) {
      // saving the current archive name to file to persist across requests / functions
      file_put_contents($this->working_directory . '/WP-STATIC-CURRENT-ARCHIVE', $this->archive_directory);
    } else {
      error_log("Couldn't create the archive directory at " . $this->archive_directory);
    }
  }
}
