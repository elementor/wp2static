<?php

class ArchiveProcessor {

  public function __construct(){
    // TODO: prepare_export func to return archive name to client, then we use that directly here
    require_once dirname(__FILE__) . '/../StaticHtmlOutput/Archive.php';
    $this->archive = new Archive();

    $this->working_directory = isset($_POST['working_directory']) ? $_POST['working_directory'] : '';

    $this->rewriteWPCONTENT = filter_input(INPUT_POST, 'rewriteWPCONTENT');
    $this->rewriteTHEMEROOT = filter_input(INPUT_POST, 'rewriteTHEMEROOT');
    $this->rewriteTHEMEDIR = filter_input(INPUT_POST, 'rewriteTHEMEDIR');
    $this->rewriteUPLOADS = filter_input(INPUT_POST, 'rewriteUPLOADS');
    $this->rewritePLUGINDIR = filter_input(INPUT_POST, 'rewritePLUGINDIR');
    $this->rewriteWPINC = filter_input(INPUT_POST, 'rewriteWPINC');

    $this->allowOfflineUsage = filter_input(INPUT_POST, 'allowOfflineUsage');
    $this->targetFolder = filter_input(INPUT_POST, 'targetFolder');

    $this->selected_deployment_option = isset($_POST['selected_deployment_option']) ? $_POST['selected_deployment_option'] : '';
    $this->diffBasedDeploys = isset($_POST['diffBasedDeploys']) ? $_POST['diffBasedDeploys'] : false;

		$this->wp_site_url = '';
		$this->wp_site_subdir = '';
    $this->wp_uploads_path = $_POST['wp_uploads_path'];
    $this->working_directory = isset($_POST['workingDirectory']) ? $_POST['workingDirectory'] : $this->wp_uploads_path;
  }

	public function create_symlink_to_latest_archive() {
    $this->archive->setToCurrentArchive();

    if (is_dir($this->archive->path)) {
      $this->remove_symlink_to_latest_archive();
      symlink($this->archive->path, $this->wp_uploads_path . '/latest-export' );
    } else {
      error_log('failed to symlink latest export directory');
    }
	}	

	public function remove_symlink_to_latest_archive() {
		if (is_link($this->wp_uploads_path . '/latest-export' )) {
			unlink($this->wp_uploads_path . '/latest-export'  );
		} 
	}	

  public function remove_files_idential_to_previous_export() {
    $dir_to_diff_against = $this->wp_uploads_path . '/previous-export';

    // iterate each file in current export, check the size and contents in previous, delete if match
    $objects = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
          $this->archive->path, 
          RecursiveDirectoryIterator::SKIP_DOTS));

    foreach($objects as $current_file => $object){
        if (is_file($current_file)) {
          // get relative filename
          $filename = str_replace($this->archive->path, '', $current_file);
   
          $previously_exported_file = $dir_to_diff_against . '/' . $filename;

          // if file doesn't exist at all in previous export:
          if (is_file($previously_exported_file)) {
            if ( $this->files_are_equal($current_file, $previously_exported_file)) {
              unlink($current_file);
            } 
          } 
        }
    }

    // TODO: cleanup empty dirs in archiveDir to prevent them being attempted to export

    $files_in_previous_export = exec("find $dir_to_diff_against -type f | wc -l"); 
    $files_to_be_deployed = exec("find $this->archive->path -type f | wc -l"); 
 
    // copy the newly changed files back into the previous export dir, else will never capture changes

    // TODO: this works the first time, but will fail the diff on subsequent runs, alternating each time`
  }

  // default rename in PHP throws warnings if dir is populated
  public function rename_populated_directory($source, $target) {
    $this->recursive_copy($source, $target);

    StaticHtmlOutput_FilesHelper::delete_dir_with_files($source);
  }


  public function files_are_equal($a, $b) {
    // if image, use sha, if html, use something else
    $pathinfo = pathinfo($a);
    if (isset($pathinfo['extension']) && in_array($pathinfo['extension'], array('jpg', 'png', 'gif', 'jpeg'))) {
      return sha1_file($a) === sha1_file($b);
    }

    $diff = exec("diff $a $b");
    $result = $diff === '';

    return $result;
  }


  public function recursive_copy($srcdir, $dstdir) {
    $dir = opendir($srcdir);
    @mkdir($dstdir);
    while ($file = readdir($dir)) {
      if ($file != '.'  && $file != '..') {
        $src = $srcdir . '/' . $file;
        $dst = $dstdir . '/' . $file;
        if (is_dir($src)) { 
            $this->recursive_copy($src, $dst); 
        } else { 
          copy($src, $dst); 
        }
      }
    }
    closedir($dir);
  }


	public function copyStaticSiteToPublicFolder() {
		if ( $this->selected_deployment_option === 'folder' ) {
			$publicFolderToCopyTo = trim($this->targetFolder);

			if ( ! empty($publicFolderToCopyTo) ) {
				// if folder isn't empty and current deployment option is "folder"
				$publicFolderToCopyTo = ABSPATH . $publicFolderToCopyTo;

				// mkdir for the new dir
				if (! file_exists($publicFolderToCopyTo)) {
					if (wp_mkdir_p($publicFolderToCopyTo)) {
						// file permissions to allow public viewing of files within
						chmod($publicFolderToCopyTo, 0755);

						// copy the contents of the current archive to the targetFolder
						$this->recursive_copy($this->archive->path, $publicFolderToCopyTo);	

					} else {
						error_log('Couldn\'t create target folder to copy files to');
					}
				} else {


					$this->recursive_copy($this->archive->path, $publicFolderToCopyTo);	
				}
			}
		}
	}

  public function create_zip() {
    $archiveName = rtrim($this->archive->path, '/');
    $tempZip = $archiveName . '.tmp';
    $zipArchive = new ZipArchive();
    if ($zipArchive->open($tempZip, ZIPARCHIVE::CREATE) !== true) {
      return new WP_Error('Could not create archive');
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->archive->path));
    foreach ($iterator as $fileName => $fileObject) {
      $baseName = basename($fileName);
      if($baseName != '.' && $baseName != '..') {
        if (!$zipArchive->addFile(realpath($fileName), str_replace($this->archive->path, '', $fileName))) {
          return new WP_Error('Could not add file: ' . $fileName);
        }
      }
    }

    $zipArchive->close();
    $zipDownloadLink = $archiveName . '.zip';
    rename($tempZip, $zipDownloadLink); 

    // TODO: need to make sure this is WP independent and saves to uploads vs working dir?
    $publicDownloadableZip = str_replace(ABSPATH, trailingslashit(home_url()), $archiveName . '.zip');

    echo 'SUCCESS';
  }

  public function renameWPDirectories() {
		// rename dirs (in reverse order than when doing in responsebody)
		// rewrite wp-content  dir
		$original_wp_content = $this->archive->path . '/wp-content'; // TODO: check if this has been modified/use constant

		// rename the theme theme root before the nested theme dir
		// rename the theme directory 
    $new_wp_content = $this->archive->path .'/' . $this->rewriteWPCONTENT;
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
		$original_wp_includes = $this->archive->path . WPINC;
		$new_wp_includes = $this->archive->path . $this->rewriteWPINC;


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
    } else {
        error_log('original INC not found' . $original_wp_includes);
        }

		// rm other left over WP identifying files

		if( file_exists($this->archive->path . '/xmlrpc.php') ) {
			unlink($this->archive->path . '/xmlrpc.php');
		}

		if( file_exists($this->archive->path . '/wp-login.php') ) {
			unlink($this->archive->path . '/wp-login.php');
		}

		StaticHtmlOutput_FilesHelper::delete_dir_with_files($this->archive->path . '/wp-json/');
		
		// TODO: remove all text files from theme dir 

    if ($this->diffBasedDeploys) {
      $this->remove_files_idential_to_previous_export();
    } 

		$this->copyStaticSiteToPublicFolder();


		echo 'SUCCESS';
  }
}

