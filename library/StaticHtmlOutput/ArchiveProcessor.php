<?php

class ArchiveProcessor {

  public function __construct($html_document, $wp_site_url){
    // TODO: prepare_export func to return archive name to client, then we use that directly here

    $this->archive_path = isset($_POST['archive_path']) ? $_POST['archive_path'] : '';
    $this->working_directory = isset($_POST['working_directory']) ? $_POST['working_directory'] : '';
  }

	public function create_symlink_to_latest_archive() {
    if (is_file(($this->getWorkingDirectory() . '/WP-STATIC-CURRENT-ARCHIVE'))) {
      $archiveDir = file_get_contents($this->getWorkingDirectory() . '/WP-STATIC-CURRENT-ARCHIVE');

      $this->remove_symlink_to_latest_archive();
      symlink($archiveDir, $this->getWorkingDirectory() . '/latest-export' );
    } else {
      error_log('failed to symlink latest export directory');
    }
	}	

	public function remove_symlink_to_latest_archive() {
    $archiveDir = file_get_contents($this->getWorkingDirectory() . '/WP-STATIC-CURRENT-ARCHIVE');

		if (is_link($this->getWorkingDirectory() . '/latest-export' )) {
			unlink($this->getWorkingDirectory() . '/latest-export'  );
		} 
	}	



      $archiveDir = untrailingslashit(file_get_contents($this->getWorkingDirectory() . '/WP-STATIC-CURRENT-ARCHIVE'));

		$this->detect_base_url();

		$archiveDir .= $this->subdirectory;

		// rename dirs (in reverse order than when doing in responsebody)
		// rewrite wp-content  dir
		$original_wp_content = $archiveDir . '/wp-content'; // TODO: check if this has been modified/use constant

		// rename the theme theme root before the nested theme dir
		// rename the theme directory 
    $new_wp_content = $archiveDir .'/' . $this->rewriteWPCONTENT;
    $new_theme_root = $new_wp_content . '/' . $this->rewriteTHEMEROOT;
    $new_theme_dir =  $new_theme_root . '/' . $this->rewriteTHEMEDIR;

		// rewrite uploads dir
		$default_upload_dir = wp_upload_dir(); // need to store as var first
		$updated_uploads_dir =  str_replace(ABSPATH, '', $default_upload_dir['basedir']);
		
		$updated_uploads_dir =  str_replace('wp-content/', '', $updated_uploads_dir);
		$updated_uploads_dir = $new_wp_content . '/' . $updated_uploads_dir;
		$new_uploads_dir = $new_wp_content . '/' . $this->rewriteUPLOADS;


		$updated_theme_root = str_replace(ABSPATH, '/', get_theme_root());
		$updated_theme_root = $new_wp_content . str_replace('wp-content', '/', $updated_theme_root);

		$updated_theme_dir = $new_theme_root . '/' . basename(get_template_directory_uri());
		$updated_theme_dir = str_replace('\/\/', '', $updated_theme_dir);

		// rewrite plugins dir
		$updated_plugins_dir = str_replace(ABSPATH, '/', WP_PLUGIN_DIR);
		$updated_plugins_dir = str_replace('wp-content/', '', $updated_plugins_dir);
		$updated_plugins_dir = $new_wp_content . $updated_plugins_dir;
		$new_plugins_dir = $new_wp_content . '/' . $this->rewritePLUGINDIR;

		// rewrite wp-includes  dir
		$original_wp_includes = $archiveDir . '/' . WPINC;
		$new_wp_includes = $archiveDir . '/' . $this->rewriteWPINC;


		if (file_exists($original_wp_content)) {
      $this->rename_populated_directory($original_wp_content, $new_wp_content);
    }

		if (file_exists($updated_uploads_dir)) {
			$this->rename_populated_directory($updated_uploads_dir, $new_uploads_dir);
		}

		if (file_exists($updated_theme_root)) {
      $this->rename_populated_directory($updated_theme_root, $new_theme_root);
    }

		if (file_exists($updated_theme_dir)) {
      $this->rename_populated_directory($updated_theme_dir, $new_theme_dir);
    }

		if( file_exists($updated_plugins_dir) ) {
			$this->rename_populated_directory($updated_plugins_dir, $new_plugins_dir);

		}

		if (file_exists($original_wp_includes)) {
      $this->rename_populated_directory($original_wp_includes, $new_wp_includes);
    }

		// rm other left over WP identifying files

		if( file_exists($archiveDir . '/xmlrpc.php') ) {
			unlink($archiveDir . '/xmlrpc.php');
		}

		if( file_exists($archiveDir . '/wp-login.php') ) {
			unlink($archiveDir . '/wp-login.php');
		}

		StaticHtmlOutput_FilesHelper::delete_dir_with_files($archiveDir . '/wp-json/');
		
		// TODO: remove all text files from theme dir 

    if ($this->diffBasedDeploys) {
      $this->remove_files_idential_to_previous_export();
    } 

		$this->copyStaticSiteToPublicFolder();


		echo 'SUCCESS';
}

