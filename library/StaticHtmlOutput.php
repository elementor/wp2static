<?php
/**
 * @package WP Static HTML Output
 *
 * Copyright (c) 2011 Leon Stafford
 */

class StaticHtmlOutput_Controller {
	const VERSION = '4.4';
	const OPTIONS_KEY = 'wp-static-html-output-options';
	const HOOK = 'wp-static-html-output';

	protected static $_instance = null;
	protected $_options = null;
	protected $_view = null;
	protected $_uploadsPath;
	protected $_uploadsURL;
	protected function __construct() {}
	protected function __clone() {}

	public static function getInstance() {
		if (null === self::$_instance) {
			self::$_instance = new self();
			self::$_instance->_options = new StaticHtmlOutput_Options(self::OPTIONS_KEY);
			self::$_instance->_view = new StaticHtmlOutput_View();
		}

		return self::$_instance;
	}

	public static function init($bootstrapFile) {
		$instance = self::getInstance();
        $tmp_var_to_hold_return_array = wp_upload_dir();
		$instance->_uploadsPath = $tmp_var_to_hold_return_array['basedir'];
		$instance->_uploadsURL = $tmp_var_to_hold_return_array['baseurl'];

		register_activation_hook($bootstrapFile, array($instance, 'activate'));

		if (is_admin()) {
			add_action('admin_menu', array($instance, 'registerOptionsPage'));
			add_action(self::HOOK . '-saveOptions', array($instance, 'saveOptions'));
		}

		return $instance;
	}

	public function saveOptions() {
    // required
    }

	public function activate() {
		if (null === $this->_options->getOption('version')) {
			$this->_options
				->setOption('version', self::VERSION)
				->setOption('static_export_settings', self::VERSION)
				->save();
		}
	}

	public function registerOptionsPage() {
		$page = add_submenu_page('tools.php', __('WP Static HTML Output', 'static-html-output-plugin'), __('WP Static HTML Output', 'static-html-output-plugin'), 'manage_options', self::HOOK . '-options', array($this, 'renderOptionsPage'));
		add_action('admin_print_styles-' . $page, array($this, 'enqueueAdminStyles'));
	}

	public function enqueueAdminStyles() {
		$pluginDirUrl = plugin_dir_url(dirname(__FILE__));
		wp_enqueue_style(self::HOOK . '-admin', $pluginDirUrl . '/css/wp-static-html-output.css');
	}

	public function renderOptionsPage() {
		// Check system requirements
		$uploadsFolderWritable = $this->_uploadsPath && is_writable($this->_uploadsPath);
		$supportsZipArchives = extension_loaded('zip');
		$supports_cURL = extension_loaded('curl');
		$permalinksStructureDefined = strlen(get_option('permalink_structure'));

		if (
			!$uploadsFolderWritable || 
			!$supportsZipArchives || 
			!$permalinksStructureDefined ||
		    !$supports_cURL
		) {
			$this->_view
				->setTemplate('system-requirements')
				->assign('uploadsFolderWritable', $uploadsFolderWritable)
				->assign('supportsZipArchives', $supportsZipArchives)
				->assign('supports_cURL', $supports_cURL)
				->assign('permalinksStructureDefined', $permalinksStructureDefined)
				->assign('uploadsPath', $this->_uploadsPath)
				->render();
		} else {
			do_action(self::HOOK . '-saveOptions');
			$wp_upload_dir = wp_upload_dir();

			$this->_view
				->setTemplate('options-page-js')
				->assign('staticExportSettings', $this->_options->getOption('static-export-settings'))
				->assign('wpUploadsDir', $this->_uploadsURL)
				->assign('wpPluginDir', plugins_url('/', __FILE__))
				->assign('onceAction', self::HOOK . '-options')
				->render();

			$this->_view
				->setTemplate('options-page')
				->assign('staticExportSettings', $this->_options->getOption('static-export-settings'))
				->assign('wpUploadsDir', $this->_uploadsURL)
				->assign('wpPluginDir', plugins_url('/', __FILE__))
				->assign('onceAction', self::HOOK . '-options')
				->assign('uploadsPath', $this->_uploadsPath)
				->render();
		}
	}

    public function save_options () {
		if (!check_admin_referer(self::HOOK . '-options') || !current_user_can('manage_options')) {
			exit('You cannot change WP Static HTML Output Plugin options.');
		}

		$this->_options
			->setOption('static-export-settings', filter_input(INPUT_POST, 'staticExportSettings', FILTER_SANITIZE_URL))
			->save();
    }

	public function outputPath(){
		// TODO: a costly function, think about optimisations, we don't want this running for each request if possible

		// set default uploads path as output path
		$outputDir = $this->_uploadsPath;

		// check for outputDir set in saved options
		parse_str($this->_options->getOption('static-export-settings'), $pluginOptions);
		if ( array_key_exists('outputDirectory', $pluginOptions )) {
			if ( !empty($pluginOptions['outputDirectory']) ) {
				$outputDir = $pluginOptions['outputDirectory'];
			}
		} 

		// override if user has specified it in the UI
		if ( ! filter_input(INPUT_POST, 'outputDirectory' ) ) {
			$outputDir = filter_input(INPUT_POST, 'outputDirectory');
		} 

		if ( !is_dir($outputDir) ) {
			// reverting back to default uploads path	
			$outputDir = $this->_uploadsPath;
		}

		// if path is not writeable, revert back to default	
		if ( empty($outputDir) || !is_writable($outputDir) ) {
			$outputDir = $this->_uploadsPath;
		}

		return $outputDir;
	}

    public function progressThroughExportTargets() {
        $exportTargetsFile = $this->_uploadsPath . '/WP-STATIC-EXPORT-TARGETS';

        // remove first line from file (disabled while testing)
        $exportTargets = file($exportTargetsFile, FILE_IGNORE_NEW_LINES);
        $filesRemaining = count($exportTargets) - 1;
        $first_line = array_shift($exportTargets);
        file_put_contents($exportTargetsFile, implode("\r\n", $exportTargets));
        
        WsLog::l('PROGRESS: Starting export type:' . $target . PHP_EOL);
    }

	public function github_upload_blobs() {
		if ( wpsho_fr()->is__premium_only() ) {
			WsLog::l('GITHUB EXPORT: Uploading file blobs...');
			$github = new StaticHtmlOutput_GitHub(
				filter_input(INPUT_POST, 'githubRepo'),
				filter_input(INPUT_POST, 'githubPersonalAccessToken'),
				filter_input(INPUT_POST, 'githubBranch'),
				filter_input(INPUT_POST, 'githubPath'),
				$this->_uploadsPath
			);

			$github->upload_blobs();
		}
    }

    public function github_prepare_export () {
		if ( wpsho_fr()->is__premium_only() ) {
			WsLog::l('GITHUB EXPORT: Preparing files for deployment...');

			$github = new StaticHtmlOutput_GitHub(
				filter_input(INPUT_POST, 'githubRepo'),
				filter_input(INPUT_POST, 'githubPersonalAccessToken'),
				filter_input(INPUT_POST, 'githubBranch'),
				filter_input(INPUT_POST, 'githubPath'),
				$this->_uploadsPath
			);

			$github->prepare_deployment();
		}
    }

    public function github_finalise_export() {
		if ( wpsho_fr()->is__premium_only() ) {
			WsLog::l('GITHUB EXPORT: Finalising deployment...');

			$github = new StaticHtmlOutput_GitHub(
				filter_input(INPUT_POST, 'githubRepo'),
				filter_input(INPUT_POST, 'githubPersonalAccessToken'),
				filter_input(INPUT_POST, 'githubBranch'),
				filter_input(INPUT_POST, 'githubPath'),
				$this->_uploadsPath
			);

			$github->commit_new_tree();
		}
    }

	public function cleanup_leftover_archives() {
		WsLog::l('CLEANUP LEFTOVER ARCHIVES: ' . $this->_uploadsPath);
		$leftover_files = preg_grep('/^([^.])/', scandir($this->_uploadsPath));

		foreach ($leftover_files as $fileName) {
			if( strpos($fileName, 'wp-static-html-output-') !== false ) {
				WsLog::l('cleaning up a previous export dir or zip: ' . $fileName);

				if (is_dir($this->_uploadsPath . '/' . $fileName)) {
					StaticHtmlOutput_FilesHelper::delete_dir_with_files($this->_uploadsPath . '/' . $fileName);
				} else {
					unlink($this->_uploadsPath . '/' . $fileName);
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
			if ( file_exists($this->_uploadsPath . '/' . $file_to_clean) ) {
				unlink($this->_uploadsPath . '/' . $file_to_clean);
			} 
		}
		
	}

	// clean up files possibly left behind by a partial export
	public function cleanup_working_files() {
		WsLog::l('CLEANING WORKING FILES:');

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
			if ( file_exists($this->_uploadsPath . '/' . $file_to_clean) ) {
				unlink($this->_uploadsPath . '/' . $file_to_clean);
			} 
		}

		echo 'SUCCESS';
	}

	public function start_export($viaCLI = false) {
		$this->pre_export_cleanup();

        // set options from GUI or override via CLI
        $sendViaGithub = filter_input(INPUT_POST, 'sendViaGithub');
        $sendViaFTP = filter_input(INPUT_POST, 'sendViaFTP');
        $sendViaS3 = filter_input(INPUT_POST, 'sendViaS3');
        $sendViaNetlify = filter_input(INPUT_POST, 'sendViaNetlify');
        $sendViaDropbox = filter_input(INPUT_POST, 'sendViaDropbox');

        if ($viaCLI) {
            parse_str($this->_options->getOption('static-export-settings'), $pluginOptions);

            $sendViaGithub = $pluginOptions['sendViaGithub'];
            $sendViaFTP = $pluginOptions['sendViaFTP'];
            $sendViaS3 = $pluginOptions['sendViaS3'];
            $sendViaNetlify = $pluginOptions['sendViaNetlify'];
            $sendViaDropbox = $pluginOptions['sendViaDropbox'];
        }

        $exportTargetsFile = $this->_uploadsPath . '/WP-STATIC-EXPORT-TARGETS';

        // add each export target to file
        if ($sendViaGithub == 1) {
            file_put_contents($exportTargetsFile, 'GITHUB' . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
        if ($sendViaFTP == 1) {
            file_put_contents($exportTargetsFile, 'FTP' . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
        if ($sendViaS3 == 1) {
            file_put_contents($exportTargetsFile, 'S3' . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
        if ($sendViaNetlify == 1) {
            file_put_contents($exportTargetsFile, 'NETLIFY' . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
        if ($sendViaDropbox == 1) {
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
        WsLog::l('STARTING EXPORT: STATIC EXPORT URL ' . filter_input(INPUT_POST, 'baseUrl') );


        // set options from GUI or CLI
        $additionalUrls = filter_input(INPUT_POST, 'additionalUrls');

        if ($viaCLI) {
            // read options from DB as array
            parse_str($this->_options->getOption('static-export-settings'), $pluginOptions);

            $newBaseURL = $pluginOptions['baseUrl'];
            $additionalUrls = $pluginOptions['additionalUrls'];
        }

        $initial_file_list_count = StaticHtmlOutput_FilesHelper::buildInitialFileList(
			$viaCLI,
			$additionalUrls,
			$this->_uploadsPath,
			$this->_uploadsURL,
			$this->outputPath(),
			self::HOOK,
			! filter_input(INPUT_POST, 'dontIncludeAllUploadFiles') // TODO: neg neg here inelegant
		);

        WsLog::l('STARTING EXPORT: initial crawl list contains ' . $initial_file_list_count . ' files');

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
		// TODO: switch for CLI driven

		error_log('copying folder to public');

        $publicFolderToCopyTo = filter_input(INPUT_POST, 'targetFolder');

		if ( ! empty(trim($publicFolderToCopyTo)) ) {
			// if folder isn't empty and current deployment option is "folder"
			$publicFolderToCopyTo = ABSPATH . $publicFolderToCopyTo;

			WsLog::l('DEPLOYING TO PUBLIC URL: ' . $publicFolderToCopyTo);

			// mkdir for the new dir
			if (!file_exists($publicFolderToCopyTo)) {
				if (wp_mkdir_p($publicFolderToCopyTo)) {
					// copy the contents of the current archive to the targetFolder
					$archiveDir = untrailingslashit(file_get_contents($this->_uploadsPath . '/WP-STATIC-CURRENT-ARCHIVE'));

					$this->recursive_copy($archiveDir, $publicFolderToCopyTo);	

				} else {
					error_log('Couldn\'t create target folder to copy files to');
				}
			} else {

				$archiveDir = untrailingslashit(file_get_contents($this->_uploadsPath . '/WP-STATIC-CURRENT-ARCHIVE'));

				$this->recursive_copy($archiveDir, $publicFolderToCopyTo);	
			}

		}
	}

	public function crawlABitMore($viaCLI = false) {
		$initial_crawl_list_file = $this->_uploadsPath . '/WP-STATIC-INITIAL-CRAWL-LIST';
        $crawled_links_file = $this->_uploadsPath . '/WP-STATIC-CRAWLED-LINKS';
        $initial_crawl_list = file($initial_crawl_list_file, FILE_IGNORE_NEW_LINES);
        $crawled_links = file($crawled_links_file, FILE_IGNORE_NEW_LINES);

        $first_line = array_shift($initial_crawl_list);
        file_put_contents($initial_crawl_list_file, implode("\r\n", $initial_crawl_list));
        $currentUrl = $first_line;
        WsLog::l('CRAWLING URL: ' . $currentUrl);

        $newBaseUrl = untrailingslashit(filter_input(INPUT_POST, 'baseUrl', FILTER_SANITIZE_URL));

        // override options if running via CLI
        if ($viaCLI) {
            parse_str($this->_options->getOption('static-export-settings'), $pluginOptions);

            $newBaseUrl = $pluginOptions['baseUrl'];
        }

        if (empty($currentUrl)){
            WsLog::l('EMPTY FILE ENCOUNTERED');

			// skip this empty file

			$f = file($initial_crawl_list_file, FILE_IGNORE_NEW_LINES);
			$filesRemaining = count($f);
			WsLog::l('CRAWLING SITE: ' . $filesRemaining . ' files remaining');
			if ($filesRemaining > 0) {
				echo $filesRemaining;
			} else {
				echo 'SUCCESS';
			}
			
			return;
        }

		$basicAuth = array(
			'useBasicAuth' => filter_input(INPUT_POST, 'sendViaBasic'),
			'basicAuthUser' => filter_input(INPUT_POST, 'basicAuthUser'),
			'basicAuthPassword' => filter_input(INPUT_POST, 'basicAuthPassword')
		);

        $urlResponse = new StaticHtmlOutput_UrlRequest($currentUrl, $basicAuth);
        $urlResponseForFurtherExtraction = new StaticHtmlOutput_UrlRequest($currentUrl, $basicAuth);

        if ($urlResponse->checkResponse() == 'FAIL') {
            WsLog::l('FAILED TO CRAWL FILE: ' . $currentUrl);
        } else {
            file_put_contents($crawled_links_file, $currentUrl . PHP_EOL, FILE_APPEND | LOCK_EX);
            WsLog::l('CRAWLED FILE: ' . $currentUrl);
        }

        $baseUrl = untrailingslashit(home_url());

		$tmp_upload_dir_var = wp_upload_dir(); // need to store as var first

		$wp_site_environment = array(
			'wp_inc' =>  '/' . WPINC,	
			'wp_content' => '/wp-content', // TODO: check if this has been modified/use constant
			'wp_uploads' =>  str_replace(ABSPATH, '/', $tmp_upload_dir_var['basedir']),	
			'wp_plugins' =>  str_replace(ABSPATH, '/', WP_PLUGIN_DIR),	
			'wp_themes' =>  str_replace(ABSPATH, '/', get_theme_root()),	
			'wp_active_theme' =>  str_replace(home_url(), '', get_template_directory_uri()),	
			'site_url' =>  get_site_url(),
		);

        $new_wp_content = '/' . filter_input(INPUT_POST, 'rewriteWPCONTENT');
        $new_theme_root = $new_wp_content . '/' . filter_input(INPUT_POST, 'rewriteTHEMEROOT');
        $new_theme_dir = $new_theme_root . '/' . filter_input(INPUT_POST, 'rewriteTHEMEDIR');
		$new_uploads_dir = $new_wp_content . '/' . filter_input(INPUT_POST, 'rewriteUPLOADS');
		$new_plugins_dir = $new_wp_content . '/' . filter_input(INPUT_POST, 'rewritePLUGINDIR');

		$overwrite_slug_targets = array(
			'new_wp_content_path' => $new_wp_content,
			'new_themes_path' => $new_theme_root,
			'new_active_theme_path' => $new_theme_dir,
			'new_uploads_path' => $new_uploads_dir,
			'new_plugins_path' => $new_plugins_dir,
			'new_wpinc_path' => '/' . filter_input(INPUT_POST, 'rewriteWPINC'),
		);

        $urlResponse->cleanup(
			$wp_site_environment,
			$overwrite_slug_targets
		);


		$useRelativeURLs = filter_input(INPUT_POST, 'useRelativeURLs');

		// TODO: if it replaces baseurl here, it will be searching links starting with that...
		// TODO: shouldn't be doing this here...
        $urlResponse->replaceBaseUrl($baseUrl, $newBaseUrl, $useRelativeURLs);
        $archiveDir = file_get_contents($this->_uploadsPath . '/WP-STATIC-CURRENT-ARCHIVE');
        $this->_saveUrlData($urlResponse, $archiveDir);

		// try extracting urls from a response that hasn't been changed yet...
		// this seems to do it...
        foreach ($urlResponseForFurtherExtraction->extractAllUrls($baseUrl) as $newUrl) {
			$path = parse_url($newUrl, PHP_URL_PATH);
			$extension = pathinfo($path, PATHINFO_EXTENSION);

            if ($newUrl != $currentUrl && 
				!in_array($newUrl, $crawled_links) && 
				$extension != 'php' && 
				!in_array($newUrl, $initial_crawl_list)
			) {
                WsLog::l('DISCOVERED NEW FILE: ' . $newUrl);
                
                $urlResponse = new StaticHtmlOutput_UrlRequest($newUrl, $basicAuth);

                if ($urlResponse->checkResponse() == 'FAIL') {
                    WsLog::l('FAILED TO CRAWL FILE: ' . $newUrl);
                } else {
                    file_put_contents($crawled_links_file, $newUrl . PHP_EOL, FILE_APPEND | LOCK_EX);
                    $crawled_links[] = $newUrl;
                    WsLog::l('CRAWLED FILE: ' . $newUrl);
                }

				$urlResponse->cleanup(
					$wp_site_environment,
					$overwrite_slug_targets
				);

                $urlResponse->replaceBaseUrl($baseUrl, $newBaseUrl, $useRelativeURLs);
                $archiveDir = file_get_contents($this->_uploadsPath . '/WP-STATIC-CURRENT-ARCHIVE');
                $this->_saveUrlData($urlResponse, $archiveDir);
            } 
        }
		
		// TODO: could avoid reading file again here as we should have it above
        $f = file($initial_crawl_list_file, FILE_IGNORE_NEW_LINES);
        $filesRemaining = count($f);
		WsLog::l('CRAWLING SITE: ' . $filesRemaining . ' files remaining');
		if ($filesRemaining > 0) {
			echo $filesRemaining;
		} else {
			echo 'SUCCESS';
		}
	
        // if being called via the CLI, just keep crawling (TODO: until when?)
        if ($viaCLI) {
            $this->crawl_site($viaCLI);
        }
    }

	public function crawl_site($viaCLI = false) {
		$initial_crawl_list_file = $this->_uploadsPath . '/WP-STATIC-INITIAL-CRAWL-LIST';
        $initial_crawl_list = file($initial_crawl_list_file, FILE_IGNORE_NEW_LINES);

		if ( !empty($initial_crawl_list) ) {
            $this->crawlABitMore($viaCLI);
		} 
    }

    public function create_zip() {
        WsLog::l('CREATING ZIP FILE...');
        $archiveDir = file_get_contents($this->_uploadsPath . '/WP-STATIC-CURRENT-ARCHIVE');
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
        WsLog::l('ZIP CREATED: Download at ' . $publicDownloadableZip);

		echo 'SUCCESS';
		// TODO: put the zip url somewhere in the interface
        //echo $publicDownloadableZip;
    }

    public function ftp_prepare_export() {
		if ( wpsho_fr()->is__premium_only() ) {
			WsLog::l('FTP EXPORT: Checking credentials..:');

			$ftp = new StaticHtmlOutput_FTP(
				filter_input(INPUT_POST, 'ftpServer'),
				filter_input(INPUT_POST, 'ftpUsername'),
				filter_input(INPUT_POST, 'ftpPassword'),
				filter_input(INPUT_POST, 'ftpRemotePath'),
				filter_input(INPUT_POST, 'useActiveFTP'),
				$this->_uploadsPath
			);

			$ftp->prepare_deployment();
		}
    }

    public function ftp_transfer_files($batch_size = 5) {
		if ( wpsho_fr()->is__premium_only() ) {

			$ftp = new StaticHtmlOutput_FTP(
				filter_input(INPUT_POST, 'ftpServer'),
				filter_input(INPUT_POST, 'ftpUsername'),
				filter_input(INPUT_POST, 'ftpPassword'),
				filter_input(INPUT_POST, 'ftpRemotePath'),
				filter_input(INPUT_POST, 'useActiveFTP'),
				$this->_uploadsPath
			);

			$ftp->transfer_files();
		}
    }

    public function bunnycdn_prepare_export() {
		if ( wpsho_fr()->is__premium_only() ) {
			WsLog::l('BUNNYCDN EXPORT: Preparing export..:');

			$bunnyCDN = new StaticHtmlOutput_BunnyCDN(
				filter_input(INPUT_POST, 'bunnycdnPullZoneName'),
				filter_input(INPUT_POST, 'bunnycdnAPIKey'),
				filter_input(INPUT_POST, 'bunnycdnRemotePath'),
				$this->_uploadsPath
			);

			$bunnyCDN->prepare_export();
		}
    }

    public function bunnycdn_transfer_files() {
		if ( wpsho_fr()->is__premium_only() ) {

			$bunnyCDN = new StaticHtmlOutput_BunnyCDN(
				filter_input(INPUT_POST, 'bunnycdnPullZoneName'),
				filter_input(INPUT_POST, 'bunnycdnAPIKey'),
				filter_input(INPUT_POST, 'bunnycdnRemotePath'),
				$this->_uploadsPath
			);

			$bunnyCDN->transfer_files();
		}
    }

    public function bunnycdn_purge_cache() {
		if ( wpsho_fr()->is__premium_only() ) {
			WsLog::l('BUNNYCDN EXPORT: purging cache'); 

			$bunnyCDN = new StaticHtmlOutput_BunnyCDN(
				filter_input(INPUT_POST, 'bunnycdnPullZoneName'),
				filter_input(INPUT_POST, 'bunnycdnAPIKey'),
				filter_input(INPUT_POST, 'bunnycdnRemotePath'),
				$this->_uploadsPath
			);

			$bunnyCDN->purge_all_cache();
		}
    }

	public function prepare_file_list($export_target) {
        WsLog::l($export_target . ' EXPORT: Preparing list of files to export');

         $file_list_path = $this->_uploadsPath . '/WP-STATIC-EXPORT-' . $export_target . '-FILES-TO-EXPORT';

		// zero file
        $f = @fopen($file_list_path, "r+");
        if ($f !== false) {
            ftruncate($f, 0);
            fclose($f);
        }

        $archiveDir = file_get_contents($this->_uploadsPath . '/WP-STATIC-CURRENT-ARCHIVE');
        $archiveName = rtrim($archiveDir, '/');
        $siteroot = $archiveName . '/';

        StaticHtmlOutput_FilesHelper::recursively_scan_dir($siteroot, $siteroot, $file_list_path);
        WsLog::l('GENERIC EXPORT: File list prepared');
	}

    public function s3_prepare_export() {
		if ( wpsho_fr()->is__premium_only() ) {
			WsLog::l('S3 EXPORT: preparing export...');

			$s3 = new StaticHtmlOutput_S3(
				filter_input(INPUT_POST, 's3Key'),
				filter_input(INPUT_POST, 's3Secret'),
				filter_input(INPUT_POST, 's3Region'),
				filter_input(INPUT_POST, 's3Bucket'),
				filter_input(INPUT_POST, 's3RemotePath'),
				$this->_uploadsPath
			);

			$s3->prepare_deployment();
		}	
    }

    public function s3_transfer_files() {
		if ( wpsho_fr()->is__premium_only() ) {

			$s3 = new StaticHtmlOutput_S3(
				filter_input(INPUT_POST, 's3Key'),
				filter_input(INPUT_POST, 's3Secret'),
				filter_input(INPUT_POST, 's3Region'),
				filter_input(INPUT_POST, 's3Bucket'),
				filter_input(INPUT_POST, 's3RemotePath'),
				$this->_uploadsPath
			);

			$s3->transfer_files();
		}
    }

	public function cloudfront_invalidate_all_items() {
		if ( wpsho_fr()->is__premium_only() ) {
			WsLog::l('S3 EXPORT: Checking whether to invalidate CF cache');
			require_once(__DIR__.'/CloudFront/CloudFront.php');
			$cloudfront_id = filter_input(INPUT_POST, 'cfDistributionId');

			if( !empty($cloudfront_id) ) {
				WsLog::l('CLOUDFRONT INVALIDATING CACHE...');

				$cf = new CloudFront(
					filter_input(INPUT_POST, 's3Key'), 
					filter_input(INPUT_POST, 's3Secret'),
					$cloudfront_id);

				$cf->invalidate('/*');
			
				if ( $cf->getResponseMessage() == 200 || $cf->getResponseMessage() == 201 )	{
					echo 'SUCCESS';
				} else {
					WsLog::l('CF ERROR: ' . $cf->getResponseMessage());
				}
			} else {
				WsLog::l('S3 EXPORT: Skipping CF cache invalidation');
				echo 'SUCCESS';
			}
		}
	}

    public function dropbox_prepare_export() {
		if ( wpsho_fr()->is__premium_only() ) {
			WsLog::l('DROPBOX EXPORT: preparing export');

			$dropbox = new StaticHtmlOutput_Dropbox(
				filter_input(INPUT_POST, 'dropboxAccessToken'),
				filter_input(INPUT_POST, 'dropboxFolder'),
				$this->_uploadsPath
			);

			$dropbox->prepare_export();
		}
    }

    public function dropbox_do_export() {
		if ( wpsho_fr()->is__premium_only() ) {

			$dropbox = new StaticHtmlOutput_Dropbox(
				filter_input(INPUT_POST, 'dropboxAccessToken'),
				filter_input(INPUT_POST, 'dropboxFolder'),
				$this->_uploadsPath
			);

			$dropbox->transfer_files();
		}
    }


    public function netlify_do_export () {
		if ( wpsho_fr()->is__premium_only() ) {

			WsLog::l('NETLIFY EXPORT: starting to deploy ZIP file');

			// will exclude the siteroot when copying
			$archiveDir = file_get_contents($this->_uploadsPath . '/WP-STATIC-CURRENT-ARCHIVE');
			$archiveName = rtrim($archiveDir, '/') . '.zip';

			$netlify = new StaticHtmlOutput_Netlify(
				filter_input(INPUT_POST, 'netlifySiteID'),
				filter_input(INPUT_POST, 'netlifyPersonalAccessToken')
			);

			echo $netlify->deploy($archiveName);
		}
    }

    public function doExportWithoutGUI() {
		if ( wpsho_fr()->is_plan('professional_edition') ) {
			// TODO: get parity with UI export options
			

			// start export, including build initial file list
			$this->start_export(true);

			// do the crawl
			$this->crawl_site(true);

			// create zip
			$this->create_zip();

			// TODO: run any other enabled exports
		}
    }

	public function get_number_of_successes($viaCLI = false) {
		global $wpdb;

		$successes = $wpdb->get_var( 'SELECT `value` FROM '.$wpdb->base_prefix.'wpstatichtmloutput_meta WHERE name = \'successful_export_count\' ');

		if ($successes > 0) {

			echo $successes;
		} else {
			echo '';
		}
	}

	public function record_successful_export($viaCLI = false) {
		// increment a value in the DB 
		global $wpdb;
		// create meta table if not exists
		$wpdb->query('CREATE TABLE IF NOT EXISTS '.$wpdb->base_prefix.'wpstatichtmloutput_meta (`id` int(11) NOT NULL auto_increment, `name` varchar(255) NOT NULL, `value` varchar(255) NOT NULL, PRIMARY KEY (id))');

		// check for successful_export_count
		if ( $wpdb->get_var( 'SELECT `value` FROM '.$wpdb->base_prefix.'wpstatichtmloutput_meta WHERE name = \'successful_export_count\' ') ) {
			// if exists, increase by one
			$wpdb->get_var( 'UPDATE '.$wpdb->base_prefix.'wpstatichtmloutput_meta SET `value` = `value` + 1 WHERE `name` = \'successful_export_count\' ') ;

		} else {
			// else insert the first success	
			$wpdb->query('INSERT INTO '.$wpdb->base_prefix.'wpstatichtmloutput_meta SET `value` = 1 , `name` = \'successful_export_count\' ');
		}

		echo 'SUCCESS';

	}	

	public function reset_default_settings() {
		$this->_options
			->setOption('static-export-settings', '')
			->save();

		echo 'SUCCESS';

	}	

	
    public function post_process_archive_dir() {
        WsLog::l('POST PROCESSING ARCHIVE DIR: ...');


        $archiveDir = untrailingslashit(file_get_contents($this->_uploadsPath . '/WP-STATIC-CURRENT-ARCHIVE'));

		// rename dirs (in reverse order than when doing in responsebody)
		// rewrite wp-content  dir
		$original_wp_content = $archiveDir . '/wp-content'; // TODO: check if this has been modified/use constant

		// rename the theme theme root before the nested theme dir
		// rename the theme directory 
        $new_wp_content = $archiveDir .'/' . filter_input(INPUT_POST, 'rewriteWPCONTENT');
        $new_theme_root = $new_wp_content . '/' . filter_input(INPUT_POST, 'rewriteTHEMEROOT');
        $new_theme_dir =  $new_theme_root . '/' . filter_input(INPUT_POST, 'rewriteTHEMEDIR');

		// rewrite uploads dir
		$default_upload_dir = wp_upload_dir(); // need to store as var first
		$updated_uploads_dir =  str_replace(ABSPATH, '', $default_upload_dir['basedir']);
		
		$updated_uploads_dir =  str_replace('wp-content/', '', $updated_uploads_dir);
		$updated_uploads_dir = $new_wp_content . '/' . $updated_uploads_dir;
		$new_uploads_dir = $new_wp_content . '/' . filter_input(INPUT_POST, 'rewriteUPLOADS');


		$updated_theme_root = str_replace(ABSPATH, '/', get_theme_root());
		$updated_theme_root = $new_wp_content . str_replace('wp-content', '/', $updated_theme_root);

		$updated_theme_dir = $new_theme_root . '/' . basename(get_template_directory_uri());
		$updated_theme_dir = str_replace('\/\/', '', $updated_theme_dir);

		// rewrite plugins dir
		$updated_plugins_dir = str_replace(ABSPATH, '/', WP_PLUGIN_DIR);
		$updated_plugins_dir = str_replace('wp-content/', '', $updated_plugins_dir);
		$updated_plugins_dir = $new_wp_content . $updated_plugins_dir;
		$new_plugins_dir = $new_wp_content . '/' . filter_input(INPUT_POST, 'rewritePLUGINDIR');

		// rewrite wp-includes  dir
		$original_wp_includes = $archiveDir . '/' . WPINC;
		$new_wp_includes = $archiveDir . '/' . filter_input(INPUT_POST, 'rewriteWPINC');


		// TODO: subdir installations are not being correctly detected here
		// 
		if (! rename($original_wp_content, $new_wp_content)) {
			WsLog::l('POST PROCESSING ARCHIVE DIR: Failed to rename ' . $original_wp_content);
			echo 'FAIL';
		}

		if (file_exists($updated_uploads_dir)) {
			rename($updated_uploads_dir, $new_uploads_dir);
		}

		rename($updated_theme_root, $new_theme_root);
		rename($updated_theme_dir, $new_theme_dir);

		if( file_exists($updated_plugins_dir) ) {
			rename($updated_plugins_dir, $new_plugins_dir);

		}
		rename($original_wp_includes, $new_wp_includes);

		// rm other left over WP identifying files

		if( file_exists($archiveDir . '/xmlrpc.php') ) {
			unlink($archiveDir . '/xmlrpc.php');
		}

		if( file_exists($archiveDir . '/wp-login.php') ) {
			unlink($archiveDir . '/wp-login.php');
		}

		StaticHtmlOutput_FilesHelper::delete_dir_with_files($archiveDir . '/wp-json/');
		
		// TODO: remove all text files from theme dir 


		$this->copyStaticSiteToPublicFolder();


		echo 'SUCCESS';
	}


	public function remove_symlink_to_latest_archive() {
        global $blog_id;
        $archiveDir = file_get_contents($this->_uploadsPath . '/WP-STATIC-CURRENT-ARCHIVE');

		if (is_link($this->outputPath() . '/latest-' . $blog_id)) {
			WsLog::l('REMOVING SYMLINK: '. $this->outputPath() . '/latest-' . $blog_id);
			unlink($this->outputPath() . '/latest-' . $blog_id );
		} else {
			WsLog::l('REMOVING SYMLINK: NO LINK FOUND AT'. $this->outputPath() . '/latest-' . $blog_id);
		}
	}	

	public function create_symlink_to_latest_archive() {
        global $blog_id;
        $archiveDir = file_get_contents($this->_uploadsPath . '/WP-STATIC-CURRENT-ARCHIVE');

		// rm and recreate
		$this->remove_symlink_to_latest_archive();

        WsLog::l('(RE)CREATING SYMLINK TO LATEST EXPORT FOLDER: '. $this->outputPath() . '/latest-' . $blog_id);

        symlink($archiveDir, $this->outputPath() . '/latest-' . $blog_id );

		echo 'SUCCESS';
	}	


    public function post_export_teardown() {
        WsLog::l('POST EXPORT CLEANUP: starting...');


		$this->cleanup_working_files();

		WsLog::l('POST EXPORT CLEANUP: complete');

		// has SUCCESS returned already from cleanup working files..
	}

	protected function _saveUrlData(StaticHtmlOutput_UrlRequest $url, $archiveDir) {
		$urlInfo = parse_url($url->getUrl());
		$pathInfo = array();

		//WsLog::l('urlInfo :' . $urlInfo['path']);
		/* will look like
			
			(homepage)

			[scheme] => http
			[host] => 172.17.0.3
			[path] => /

			(closed url segment)

			[scheme] => http
			[host] => 172.17.0.3
			[path] => /feed/

			(file with extension)

			[scheme] => http
			[host] => 172.17.0.3
			[path] => /wp-content/themes/twentyseventeen/assets/css/ie8.css

		*/

		// TODO: here we can allow certain external host files to be crawled

		// validate our inputs
		if ( !isset($urlInfo['path']) ) {
			WsLog::l('PREPARING URL: Invalid URL given, aborting');
			return false;
		}

		// set what the new path will be based on the given url
		if( $urlInfo['path'] != '/' ) {
			$pathInfo = pathinfo($urlInfo['path']);
		} else {
			$pathInfo = pathinfo('index.html');
		}

		// set fileDir to the directory name else empty	
		$fileDir = $archiveDir . (isset($pathInfo['dirname']) ? $pathInfo['dirname'] : '');

		// set filename to index if there is no extension and basename and filename are the same
		if (empty($pathInfo['extension']) && $pathInfo['basename'] == $pathInfo['filename']) {
			$fileDir .= '/' . $pathInfo['basename'];
			$pathInfo['filename'] = 'index';
		}

		//$fileDir = preg_replace('/(\/+)/', '/', $fileDir);

		if (!file_exists($fileDir)) {
			wp_mkdir_p($fileDir);
		}

		$fileExtension = ''; 

		// TODO: was isHtml() method modified to include more than just html
		// if there's no extension set or content type matches html, set it to html
		// TODO: seems to be flawed for say /feed/ urls, which would not be xml content type..
		if(  isset($pathInfo['extension'])) {
			$fileExtension = $pathInfo['extension']; 
		} else if( $url->isHtml() ) {
			$fileExtension = 'html'; 
		} else {
			// guess mime type
			
			$fileExtension = $url->getExtensionFromContentType(); 
		}

		$fileName = '';

		// set path for homepage to index.html, else build filename
		if ($urlInfo['path'] == '/') {
			$fileName = $fileDir . 'index.html';
		} else {
			$fileName = $fileDir . '/' . $pathInfo['filename'] . '.' . $fileExtension;
		}
		
		// TODO: find where this extra . is coming from (current dir indicator?)
		$fileName = str_replace('.index.html', 'index.html', $fileName);
		// remove 2 or more slashes from paths
		$fileName = preg_replace('/(\/+)/', '/', $fileName);


		$fileContents = $url->getResponseBody();
		
		WsLog::l('SAVING URL: ' . $urlInfo['path'] . ' to new path' . $fileName);
		// TODO: what was the 'F' check for?1? Comments exist for a reason
		if ($fileContents != '' && $fileContents != 'F') {
			file_put_contents($fileName, $fileContents);
		} else {
			WsLog::l('SAVING URL: UNABLE TO SAVE FOR SOME REASON');
		}
	}
}
