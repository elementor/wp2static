<?php
/**
 * @package WP Static Site Generator
 *
 * Copyright (c) 2011 Leon Stafford
 */

class StaticHtmlOutput_Controller {
	const VERSION = '5.9';
	const OPTIONS_KEY = 'wp-static-html-output-options';
	const HOOK = 'wp-static-html-output';

	protected static $_instance = null;
	protected function __construct() {}

	public static function getInstance() {
		if (null === self::$_instance) {
			self::$_instance = new self();
			self::$_instance->options = new StaticHtmlOutput_Options(self::OPTIONS_KEY);
			self::$_instance->view = new StaticHtmlOutput_View();
      $tmp_var_to_hold_return_array = wp_upload_dir();
      self::$_instance->uploadsPath = $tmp_var_to_hold_return_array['basedir'];
      self::$_instance->uploadsURL = $tmp_var_to_hold_return_array['baseurl'];
      self::$_instance->wp_site_path = ABSPATH;

      // load settings via Client or from DB if run from CLI
      if (null !== (filter_input(INPUT_POST, 'selected_deployment_option'))) {
        // export being triggered via GUI, set all options from filtered posts
        self::$_instance->selected_deployment_option = filter_input(INPUT_POST, 'selected_deployment_option');
        self::$_instance->baseUrl = untrailingslashit(filter_input(INPUT_POST, 'baseUrl', FILTER_SANITIZE_URL));
        self::$_instance->diffBasedDeploys = filter_input(INPUT_POST, 'diffBasedDeploys');
        self::$_instance->sendViaGithub = filter_input(INPUT_POST, 'sendViaGithub');
        self::$_instance->sendViaFTP = filter_input(INPUT_POST, 'sendViaFTP');
        self::$_instance->sendViaS3 = filter_input(INPUT_POST, 'sendViaS3');
        self::$_instance->sendViaNetlify = filter_input(INPUT_POST, 'sendViaNetlify');
        self::$_instance->sendViaDropbox = filter_input(INPUT_POST, 'sendViaDropbox');
        self::$_instance->additionalUrls = filter_input(INPUT_POST, 'additionalUrls');
        self::$_instance->outputDirectory = filter_input(INPUT_POST, 'outputDirectory');
        self::$_instance->targetFolder = filter_input(INPUT_POST, 'targetFolder');
        self::$_instance->githubRepo = filter_input(INPUT_POST, 'githubRepo');
        self::$_instance->githubPersonalAccessToken = filter_input(INPUT_POST, 'githubPersonalAccessToken');
        self::$_instance->githubBranch = filter_input(INPUT_POST, 'githubBranch');
        self::$_instance->githubPath = filter_input(INPUT_POST, 'githubPath');
        self::$_instance->rewriteWPCONTENT = filter_input(INPUT_POST, 'rewriteWPCONTENT');
        self::$_instance->rewriteTHEMEROOT = filter_input(INPUT_POST, 'rewriteTHEMEROOT');
        self::$_instance->rewriteTHEMEDIR = filter_input(INPUT_POST, 'rewriteTHEMEDIR');
        self::$_instance->rewriteUPLOADS = filter_input(INPUT_POST, 'rewriteUPLOADS');
        self::$_instance->rewritePLUGINDIR = filter_input(INPUT_POST, 'rewritePLUGINDIR');
        self::$_instance->rewriteWPINC = filter_input(INPUT_POST, 'rewriteWPINC');
				self::$_instance->useRelativeURLs = filter_input(INPUT_POST, 'useRelativeURLs');
				self::$_instance->useBaseHref = filter_input(INPUT_POST, 'useBaseHref');
        self::$_instance->useBasicAuth = filter_input(INPUT_POST, 'useBasicAuth');
        self::$_instance->basicAuthUser = filter_input(INPUT_POST, 'basicAuthUser');
        self::$_instance->basicAuthPassword = filter_input(INPUT_POST, 'basicAuthPassword');
        self::$_instance->bunnycdnPullZoneName = filter_input(INPUT_POST, 'bunnycdnPullZoneName');
        self::$_instance->bunnycdnAPIKey = filter_input(INPUT_POST, 'bunnycdnAPIKey');
        self::$_instance->bunnycdnRemotePath = filter_input(INPUT_POST, 'bunnycdnRemotePath');
        self::$_instance->cfDistributionId = filter_input(INPUT_POST, 'cfDistributionId');
        self::$_instance->s3Key = filter_input(INPUT_POST, 's3Key');
        self::$_instance->s3Secret = filter_input(INPUT_POST, 's3Secret');
        self::$_instance->s3Region = filter_input(INPUT_POST, 's3Region');
        self::$_instance->s3Bucket = filter_input(INPUT_POST, 's3Bucket');
        self::$_instance->s3RemotePath = filter_input(INPUT_POST, 's3RemotePath');
        self::$_instance->dropboxAccessToken = filter_input(INPUT_POST, 'dropboxAccessToken');
        self::$_instance->dropboxFolder = filter_input(INPUT_POST, 'dropboxFolder');
        self::$_instance->netlifySiteID = filter_input(INPUT_POST, 'netlifySiteID');
        self::$_instance->netlifyPersonalAccessToken = filter_input(INPUT_POST, 'netlifyPersonalAccessToken');
        self::$_instance->ftpServer = filter_input(INPUT_POST, 'ftpServer');
        self::$_instance->ftpUsername = filter_input(INPUT_POST, 'ftpUsername');
        self::$_instance->ftpPassword = filter_input(INPUT_POST, 'ftpPassword');
        self::$_instance->ftpRemotePath = filter_input(INPUT_POST, 'ftpRemotePath');
        self::$_instance->useActiveFTP = filter_input(INPUT_POST, 'useActiveFTP');
        self::$_instance->allowOfflineUsage = filter_input(INPUT_POST, 'allowOfflineUsage');
      } 
		}

		return self::$_instance;
	}

	public static function init($bootstrapFile) {
		$instance = self::getInstance();

		register_activation_hook($bootstrapFile, array($instance, 'activate'));

		if (is_admin()) {
			add_action('admin_menu', array($instance, 'registerOptionsPage'));
      add_filter('custom_menu_order', '__return_true' );
      add_filter('menu_order', array( $instance, 'set_menu_order'));
		}
 
		return $instance;
	}

  public function set_menu_order( $menu_order ) {
    $order = array();
    $file  = plugin_basename( __FILE__ );
    foreach ( $menu_order as $index => $item ) {
        if ( $item == 'index.php') {
            $order[] = $item;
        } 
    }

    $order = array(
      'index.php',
      'wp-static-html-output'
    );

    return $order;
  }

  public function activate_for_single_site() {
      if (null === $this->options->getOption('version')) {
        $this->options
          ->setOption('version', self::VERSION)
          ->setOption('static_export_settings', self::VERSION)
          ->save();
      }
  }

	public function activate($network_wide) {
    if ( $network_wide ) {
      global $wpdb;

      $site_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs WHERE site_id = $wpdb->siteid;" );

      foreach ( $site_ids as $site_id ) {
        switch_to_blog( $site_id );
        $this->activate_for_single_site();  
      }

      restore_current_blog();
    } else {
        $this->activate_for_single_site();  
    } 
	}

	public function registerOptionsPage() {
		$pluginDirUrl = plugin_dir_url(dirname(__FILE__));
		$page = add_menu_page(
			__('WP Static Site Generator', 'static-html-output-plugin'), 
			__('WP Static Site Generator', 'static-html-output-plugin'), 
			'manage_options', 
			self::HOOK, 
			array(self::$_instance, 'renderOptionsPage'),
			$pluginDirUrl . 'images/menu_icon_32x32.png'
		);

		add_action('admin_print_styles-' . $page, array($this, 'enqueueAdminStyles'));
	}

	public function enqueueAdminStyles() {
		$pluginDirUrl = plugin_dir_url(dirname(__FILE__));
		wp_enqueue_style(self::HOOK . '-admin', $pluginDirUrl . '/css/wp-static-html-output.css');
	}

  public function generate_initial_filelist() {
    // pre-generated the initial crawl list
    $initial_file_list_count = StaticHtmlOutput_FilesHelper::buildInitialFileList(
      true, // simulate viaCLI for debugging, will only be called via UI, but without response needed
      '', // simulate additional URLs for debugging, should not be any here yet
      //$this->getWorkingDirectory(),
      // NOTE: Working Dir not yet available, so we serve generate list under uploads dir
      $this->uploadsPath,
      $this->uploadsURL,
      $this->getWorkingDirectory(),
      self::HOOK
    );

    echo $initial_file_list_count;
  }

	public function renderOptionsPage() {
		// Check system requirements
		$uploadsFolderWritable = $this->uploadsPath && is_writable($this->uploadsPath);
		$supports_cURL = extension_loaded('curl');
		$permalinksStructureDefined = strlen(get_option('permalink_structure'));

		if (
			!$uploadsFolderWritable || 
			!$permalinksStructureDefined ||
		    !$supports_cURL
		) {
			$this->view
				->setTemplate('system-requirements')
				->assign('uploadsFolderWritable', $uploadsFolderWritable)
				->assign('supports_cURL', $supports_cURL)
				->assign('permalinksStructureDefined', $permalinksStructureDefined)
				->assign('uploadsPath', $this->uploadsPath)
				->render();
		} else {
			$this->view
				->setTemplate('options-page-js')
				->assign('basedir', $this->getWorkingDirectory())
				->assign('wpUploadsDir', $this->uploadsURL)
				->assign('options', $this->options)
				->assign('wp_site_path', $this->wp_site_path)
				->assign('wpPluginDir', plugins_url('/', __FILE__))
				->assign('onceAction', self::HOOK . '-options')
				->render();

			$this->view
				->setTemplate('options-page')
        ->assign('wp_uploads_path', $this->uploadsPath)
        ->assign('rewriteWPCONTENT', 
          $this->options->rewriteWPCONTENT ? $this->options->rewriteWPCONTENT : 'contents')
        ->assign('rewriteTHEMEDIR',
          $this->options->rewriteTHEMEDIR ? $this->options->rewriteTHEMEDIR : 'theme')
        ->assign('rewriteUPLOADS',
          $this->options->rewriteUPLOADS ? $this->options->rewriteUPLOADS : 'data')
        ->assign('rewriteTHEMEROOT',
          $this->options->rewriteTHEMEROOT ? $this->options->rewriteTHEMEROOT : 'ui')
        ->assign('rewritePLUGINDIR',
          $this->options->rewritePLUGINDIR ? $this->options->rewritePLUGINDIR : 'lib')
        ->assign('rewriteWPINC',
          $this->options->rewriteWPINC ? $this->options->rewriteWPINC : 'inc')
				->assign('wpUploadsDir', $this->uploadsURL)
				->assign('options', $this->options)
				->assign('wpPluginDir', plugins_url('/', __FILE__))
				->assign('onceAction', self::HOOK . '-options')
				->assign('wp_site_url', get_site_url())
				->assign('uploadsPath', $this->uploadsPath)
				->render();
		}
	}

  public function save_options () {
		if (!check_admin_referer(self::HOOK . '-options') || !current_user_can('manage_options')) {
			exit('You cannot change WP Static Site Generator Plugin options.');
		}

		$this->options->saveAllPostData();
  }

	public function getWorkingDirectory(){
		$outputDir = '';

		// priorities: from UI; from settings; fallback to WP uploads path
		if ( isset($this->outputDirectory) ) {
			$outputDir = $this->outputDirectory;
		} elseif ($this->options->outputDirectory) {
      $outputDir = $this->options->outputDirectory;
    } else {
      $outputDir = $this->uploadsPath;
    }

		if ( ! is_dir($outputDir) && ! wp_mkdir_p($outputDir)) {
      $outputDir = $this->uploadsPath;
      error_log('user defined outputPath does not exist and could not be created, reverting to ' . $outputDir);
    } 

		if ( empty($outputDir) || !is_writable($outputDir) ) {
			$outputDir = $this->uploadsPath;
      error_log('user defined outputPath is not writable, reverting to ' . $outputDir);
		}

		return $outputDir;
	}

  public function progressThroughExportTargets() {
    $exportTargetsFile = $this->getWorkingDirectory() . '/WP-STATIC-EXPORT-TARGETS';
    $exportTargets = file($exportTargetsFile, FILE_IGNORE_NEW_LINES);
    $filesRemaining = count($exportTargets) - 1;
    $first_line = array_shift($exportTargets);
    file_put_contents($exportTargetsFile, implode("\r\n", $exportTargets));
  }

	public function github_upload_blobs($viaCLI = false) {
    $github = new StaticHtmlOutput_GitHub(
      $this->githubRepo,
      $this->githubPersonalAccessToken,
      $this->githubBranch,
      $this->githubPath,
      $this->getWorkingDirectory()
    );

    $github->upload_blobs($viaCLI);
  }

  public function github_prepare_export() {
    $github = new StaticHtmlOutput_GitHub(
      $this->githubRepo,
      $this->githubPersonalAccessToken,
      $this->githubBranch,
      $this->githubPath,
      $this->getWorkingDirectory()
    );

    $github->prepare_deployment();
  }

  public function github_finalise_export() {
    $github = new StaticHtmlOutput_GitHub(
      $this->githubRepo,
      $this->githubPersonalAccessToken,
      $this->githubBranch,
      $this->githubPath,
      $this->getWorkingDirectory()
    );

    $github->commit_new_tree();
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

	public function pre_export_cleanup() {
		$files_to_clean = array(
			'/WP-STATIC-EXPORT-TARGETS',
			'/WP-STATIC-EXPORT-S3-FILES-TO-EXPORT',
			'/WP-STATIC-EXPORT-FTP-FILES-TO-EXPORT',
			'/WP-STATIC-EXPORT-GITHUB-FILES-TO-EXPORT',
			'/WP-STATIC-EXPORT-DROPBOX-FILES-TO-EXPORT',
			'/WP-STATIC-EXPORT-BUNNYCDN-FILES-TO-EXPORT',
			'/WP-STATIC-CRAWLED-LINKS',
			'/WP-STATIC-INITIAL-CRAWL-LIST',
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
			'/WP-STATIC-EXPORT-TARGETS',
			'/WP-STATIC-EXPORT-S3-FILES-TO-EXPORT',
			'/WP-STATIC-EXPORT-FTP-FILES-TO-EXPORT',
			'/WP-STATIC-EXPORT-GITHUB-FILES-TO-EXPORT',
			'/WP-STATIC-EXPORT-DROPBOX-FILES-TO-EXPORT',
			'/WP-STATIC-EXPORT-BUNNYCDN-FILES-TO-EXPORT',
			'/WP-STATIC-CRAWLED-LINKS',
			'/WP-STATIC-INITIAL-CRAWL-LIST',
			//'/WP-STATIC-CURRENT-ARCHIVE', // needed for zip download, diff deploys, etc
			//'WP-STATIC-EXPORT-LOG'
		);

		foreach ($files_to_clean as $file_to_clean) {
			if ( file_exists($this->getWorkingDirectory() . '/' . $file_to_clean) ) {
				unlink($this->getWorkingDirectory() . '/' . $file_to_clean);
			} 
		}

		echo 'SUCCESS';
	}

	public function start_export($viaCLI = false) {
		$this->pre_export_cleanup();
    $exportTargetsFile = $this->getWorkingDirectory() . '/WP-STATIC-EXPORT-TARGETS';

    if ($this->sendViaGithub == 1) {
        file_put_contents($exportTargetsFile, 'GITHUB' . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    if ($this->sendViaFTP == 1) {
        file_put_contents($exportTargetsFile, 'FTP' . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    if ($this->sendViaS3 == 1) {
        file_put_contents($exportTargetsFile, 'S3' . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    if ($this->sendViaNetlify == 1) {
        file_put_contents($exportTargetsFile, 'NETLIFY' . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    if ($this->sendViaDropbox == 1) {
        file_put_contents($exportTargetsFile, 'DROPBOX' . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    // initilise log with environmental info
    WsLog::l('STARTING EXPORT' . date("Y-m-d h:i:s") );
    WsLog::l('STARTING EXPORT: PHP VERSION ' . phpversion() );
    WsLog::l('STARTING EXPORT: PHP MAX EXECUTION TIME ' . ini_get('max_execution_time') );
    WsLog::l('STARTING EXPORT: OS VERSION ' . php_uname() );
    WsLog::l('STARTING EXPORT: WP VERSION ' . get_bloginfo('version') );
    WsLog::l('STARTING EXPORT: WP URL ' . get_bloginfo('url') );
    WsLog::l('STARTING EXPORT: WP SITEURL ' . get_option('siteurl') );
    WsLog::l('STARTING EXPORT: WP HOME ' . get_option('home') );
    WsLog::l('STARTING EXPORT: WP ADDRESS ' . get_bloginfo('wpurl') );
    WsLog::l('STARTING EXPORT: PLUGIN VERSION ' . $this::VERSION );
    WsLog::l('STARTING EXPORT: VIA CLI? ' . $viaCLI);
    WsLog::l('STARTING EXPORT: STATIC EXPORT URL ' . $this->baseUrl );

    $initial_file_list_count = StaticHtmlOutput_FilesHelper::buildInitialFileList(
      $viaCLI,
      $this->additionalUrls,
      $this->getWorkingDirectory(),
      $this->uploadsURL,
      $this->getWorkingDirectory(),
      self::HOOK
    );

    echo 'SUCCESS';
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
		if ( $this->selected_deployment_option == 'folder' ) {
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
						$archiveDir = untrailingslashit(file_get_contents($this->getWorkingDirectory() . '/WP-STATIC-CURRENT-ARCHIVE'));

						$this->recursive_copy($archiveDir, $publicFolderToCopyTo);	

					} else {
						error_log('Couldn\'t create target folder to copy files to');
					}
				} else {

					$archiveDir = untrailingslashit(file_get_contents($this->getWorkingDirectory() . '/WP-STATIC-CURRENT-ARCHIVE'));

					$this->recursive_copy($archiveDir, $publicFolderToCopyTo);	
				}
			}
		}
	}

  public function create_zip() {
    $archiveDir = file_get_contents($this->getWorkingDirectory() . '/WP-STATIC-CURRENT-ARCHIVE');
    $archiveName = rtrim($archiveDir, '/');
    $tempZip = $archiveName . '.tmp';
    $zipArchive = new ZipArchive();
    if ($zipArchive->open($tempZip, ZIPARCHIVE::CREATE) !== true) {
      return new WP_Error('Could not create archive');
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($archiveDir));
    foreach ($iterator as $fileName => $fileObject) {
      $baseName = basename($fileName);
      if($baseName != '.' && $baseName != '..') {
        if (!$zipArchive->addFile(realpath($fileName), str_replace($archiveDir, '', $fileName))) {
          return new WP_Error('Could not add file: ' . $fileName);
        }
      }
    }

    $zipArchive->close();
    $zipDownloadLink = $archiveName . '.zip';
    rename($tempZip, $zipDownloadLink); 
    $publicDownloadableZip = str_replace(ABSPATH, trailingslashit(home_url()), $archiveName . '.zip');

    echo 'SUCCESS';
  }

  public function ftp_prepare_export() {
    $ftp = new StaticHtmlOutput_FTP(
      $this->ftpServer,
      $this->ftpUsername,
      $this->ftpPassword,
      $this->ftpRemotePath,
      $this->useActiveFTP,
      $this->getWorkingDirectory()
    );

    $ftp->prepare_deployment();
  }

  public function ftp_transfer_files($viaCLI = false) {
    $ftp = new StaticHtmlOutput_FTP(
      $this->ftpServer,
      $this->ftpUsername,
      $this->ftpPassword,
      $this->ftpRemotePath,
      $this->useActiveFTP,
      $this->getWorkingDirectory()
    );

    $ftp->transfer_files($viaCLI);
  }

  public function bunnycdn_prepare_export() {
		if ( wpsho_fr()->is__premium_only() ) {
			$bunnyCDN = new StaticHtmlOutput_BunnyCDN(
				$this->bunnycdnPullZoneName,
				$this->bunnycdnAPIKey,
				$this->bunnycdnRemotePath,
				$this->getWorkingDirectory()
			);

			$bunnyCDN->prepare_export();
		}
  }

  public function bunnycdn_transfer_files($viaCLI = false) {
		if ( wpsho_fr()->is__premium_only() ) {

			$bunnyCDN = new StaticHtmlOutput_BunnyCDN(
				$this->bunnycdnPullZoneName,
				$this->bunnycdnAPIKey,
				$this->bunnycdnRemotePath,
				$this->getWorkingDirectory()
			);

			$bunnyCDN->transfer_files($viaCLI);
		}
  }

  public function bunnycdn_purge_cache() {
		if ( wpsho_fr()->is__premium_only() ) {

			$bunnyCDN = new StaticHtmlOutput_BunnyCDN(
				$this->bunnycdnPullZoneName,
				$this->bunnycdnAPIKey,
				$this->bunnycdnRemotePath,
				$this->getWorkingDirectory()
			);

			$bunnyCDN->purge_all_cache();
		}
  }

	public function prepare_file_list($export_target) {
    $file_list_path = $this->getWorkingDirectory() . '/WP-STATIC-EXPORT-' . $export_target . '-FILES-TO-EXPORT';

// zero file
    $f = @fopen($file_list_path, "r+");
    if ($f !== false) {
        ftruncate($f, 0);
        fclose($f);
    }

    $archiveDir = file_get_contents($this->getWorkingDirectory() . '/WP-STATIC-CURRENT-ARCHIVE');
    $archiveName = rtrim($archiveDir, '/');
    $siteroot = $archiveName . '/';

    error_log('preparing file list');

    StaticHtmlOutput_FilesHelper::recursively_scan_dir($siteroot, $siteroot, $file_list_path);
	}

  public function s3_prepare_export() {
		if ( wpsho_fr()->is__premium_only() ) {

			$s3 = new StaticHtmlOutput_S3(
				$this->s3Key,
				$this->s3Secret,
				$this->s3Region,
				$this->s3Bucket,
				$this->s3RemotePath,
				$this->getWorkingDirectory()
			);

			$s3->prepare_deployment();
		}	
  }

  public function s3_transfer_files($viaCLI = false) {
    if ( wpsho_fr()->is__premium_only() ) {

      $s3 = new StaticHtmlOutput_S3(
        $this->s3Key,
        $this->s3Secret,
        $this->s3Region,
        $this->s3Bucket,
        $this->s3RemotePath,
        $this->getWorkingDirectory()
      );

      $s3->transfer_files($viaCLI);
    }
  }

	public function cloudfront_invalidate_all_items() {
		if ( wpsho_fr()->is__premium_only() ) {
			require_once(__DIR__.'/CloudFront/CloudFront.php');
			$cloudfront_id = $this->cfDistributionId;

			if( !empty($cloudfront_id) ) {

				$cf = new CloudFront(
				$this->s3Key,
				$this->s3Secret,
					$cloudfront_id);

				$cf->invalidate('/*');
			
				if ( $cf->getResponseMessage() == 200 || $cf->getResponseMessage() == 201 )	{
					echo 'SUCCESS';
				} else {
					WsLog::l('CF ERROR: ' . $cf->getResponseMessage());
				}
			} else {
				echo 'SUCCESS';
			}
		}
	}

    public function dropbox_prepare_export() {
			$dropbox = new StaticHtmlOutput_Dropbox(
				$this->dropboxAccessToken,
				$this->dropboxFolder,
				$this->getWorkingDirectory()
			);

			$dropbox->prepare_export();
    }

    public function dropbox_do_export($viaCLI = false) {
			$dropbox = new StaticHtmlOutput_Dropbox(
				$this->dropboxAccessToken,
				$this->dropboxFolder,
				$this->getWorkingDirectory()
			);

			$dropbox->transfer_files($viaCLI);
    }

    public function netlify_do_export () {
			// will exclude the siteroot when copying
			$archiveDir = file_get_contents($this->getWorkingDirectory() . '/WP-STATIC-CURRENT-ARCHIVE');
			$archiveName = rtrim($archiveDir, '/') . '.zip';

			$netlify = new StaticHtmlOutput_Netlify(
				$this->netlifySiteID,
				$this->netlifyPersonalAccessToken
			);

			echo $netlify->deploy($archiveName);
    }

    public function deploy() {
      switch($this->selected_deployment_option) {
        case 'folder':
          $this->copyStaticSiteToPublicFolder();
        break;

        case 'github':
          $this->github_prepare_export();
          $this->github_upload_blobs(true);
          $this->github_finalise_export();
        break;

        case 'ftp':
          $this->ftp_prepare_export();
          $this->ftp_transfer_files(true);
        break;

        case 'netlify':
          $this->create_zip();
          $this->netlify_do_export();
        break;

        case 'zip':
          $this->create_zip();
        break;

        case 's3':
          $this->s3_prepare_export();
          $this->s3_transfer_files(true);
          $this->cloudfront_invalidate_all_items();
        break;

        case 'bunnycdn':
          $this->bunnycdn_prepare_export();
          $this->bunnycdn_transfer_files(true);
        break;

        case 'dropbox':
          $this->dropbox_prepare_export();
          $this->dropbox_do_export(true);
        break;
      }

      error_log('scheduled deploy complete');
      // TODO: email upon successful cron deploy
      // $current_user = wp_get_current_user();

      // $to = $current_user->user_email;
      // $subject = 'Static site deployment: ' . $site_title = get_bloginfo( 'name' );;
      // $body = 'Your WordPress site has been automatically deployed.';
      // $headers = array('Content-Type: text/html; charset=UTF-8');
      //  
      // wp_mail( $to, $subject, $body, $headers );
    }

    public function doExportWithoutGUI() {
      if ( wpsho_fr()->is_plan('professional_edition') ) {
    
        //$this->capture_last_deployment(); 
        $this->cleanup_leftover_archives(true);
        $this->start_export(true);
        $this->crawl_site(true);
        $this->post_process_archive_dir(true);
        $this->deploy();
        $this->post_export_teardown();

        //$this->create_zip();
      }
    }

	public function reset_default_settings() {
		$this->options
			->setOption('static-export-settings', '')
			->save();

		echo 'SUCCESS';

	}	

	public function detect_base_url() {
		$site_url = get_option( 'siteurl' );
		$home = get_option( 'home' );
    $this->subdirectory = '';

		// case for when WP is installed in a different place then being served
		if ( $site_url !== $home ) {
			$this->subdirectory = '/mysubdirectory';
		}

		$base_url = parse_url($site_url);

		if ( array_key_exists('path', $base_url ) && $base_url['path'] != '/' ) {
			$this->subdirectory = $base_url['path'];
		}
	}	

    public function post_process_archive_dir() {
      $this->create_symlink_to_latest_archive();

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

  public function remove_files_idential_to_previous_export() {
    $archiveDir = file_get_contents($this->getWorkingDirectory() . '/WP-STATIC-CURRENT-ARCHIVE');
    $dir_to_diff_against = $this->getWorkingDirectory() . '/previous-export';

    // iterate each file in current export, check the size and contents in previous, delete if match
    $objects = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
          $archiveDir, 
          RecursiveDirectoryIterator::SKIP_DOTS));

    foreach($objects as $current_file => $object){
        if (is_file($current_file)) {
          // get relative filename
          $filename = str_replace($archiveDir, '', $current_file);
   
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
    $files_to_be_deployed = exec("find $archiveDir -type f | wc -l"); 
 
    // copy the newly changed files back into the previous export dir, else will never capture changes

    // TODO: this works the first time, but will fail the diff on subsequent runs, alternating each time`
  }
  
  // default rename in PHP throws warnings if dir is populated
  public function rename_populated_directory($source, $target) {
    $this->recursive_copy($source, $target);

    StaticHtmlOutput_FilesHelper::delete_dir_with_files($source);
  }

	public function remove_symlink_to_latest_archive() {
    $archiveDir = file_get_contents($this->getWorkingDirectory() . '/WP-STATIC-CURRENT-ARCHIVE');

		if (is_link($this->getWorkingDirectory() . '/latest-export' )) {
			unlink($this->getWorkingDirectory() . '/latest-export'  );
		} 
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

  public function post_export_teardown() {
		$this->cleanup_working_files();
	}
}
