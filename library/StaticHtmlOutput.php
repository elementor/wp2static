<?php
/**
 * @package WP Static HTML Output
 *
 * Copyright (c) 2011 Leon Stafford
 */

class StaticHtmlOutput_Controller {
	const VERSION = '3.1';
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
        
        $this->wsLog('PROGRESS: Starting export type:' . $target . PHP_EOL);
    }

	public function github_upload_blobs() {
		if ( wpsho_fr()->is__premium_only() ) {
			$this->wsLog('GITHUB EXPORT: Uploading file blobs...');
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
			$this->wsLog('GITHUB EXPORT: Preparing files for deployment...');

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
			$this->wsLog('GITHUB EXPORT: Finalising deployment...');

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
		$this->wsLog('CLEANUP LEFTOVER ARCHIVES: ' . $this->_uploadsPath);
		$leftover_files = preg_grep('/^([^.])/', scandir($this->_uploadsPath));

		foreach ($leftover_files as $fileName) {
			if( strpos($fileName, 'wp-static-html-output-') !== false ) {
				$this->wsLog('cleaning up a previous export dir or zip: ' . $fileName);

				if (is_dir($this->_uploadsPath . '/' . $fileName)) {
					StaticHtmlOutput_FilesHelper::delete_dir_with_files($this->_uploadsPath . '/' . $fileName);
				} else {
					unlink($this->_uploadsPath . '/' . $fileName);
				}
			}
		}

		echo 'SUCCESS';
	}	

	// clean up files possibly left behind by a partial export
	public function cleanup_working_files() {
		$this->wsLog('CLEANING WORKING FILES:');

		$files_to_clean = array(
			'/WP-STATIC-EXPORT-TARGETS',
			'/WP-STATIC-EXPORT-S3-FILES-TO-EXPORT',
			'/WP-STATIC-EXPORT-FTP-FILES-TO-EXPORT',
			'/WP-STATIC-EXPORT-GITHUB-FILES-TO-EXPORT',
			'/WP-STATIC-EXPORT-DROPBOX-FILES-TO-EXPORT',
			'/WP-STATIC-EXPORT-BUNNYCDN-FILES-TO-EXPORT',
			'/WP-STATIC-CRAWLED-LINKS',
			'/WP-STATIC-INITIAL-CRAWL-LIST',
			'/WP-STATIC-CURRENT-ARCHIVE',
		);

		foreach ($files_to_clean as $file_to_clean) {
			if ( file_exists($this->_uploadsPath . $file_to_clean) ) {
				unlink($this->_uploadsPath . $file_to_clean);
			}
		}

		echo 'SUCCESS';
	}

	public function start_export($viaCLI = false) {
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

        $archiveUrl = $this->_prepareInitialFileList($viaCLI);

        echo 'SUCCESS';
	}

	protected function _prepareInitialFileList($viaCLI = false) {
		global $blog_id;
		set_time_limit(0);

        // set options from GUI or CLI
        $newBaseUrl = untrailingslashit(filter_input(INPUT_POST, 'baseUrl', FILTER_SANITIZE_URL));

        $additionalUrls = filter_input(INPUT_POST, 'additionalUrls');

        if ($viaCLI) {
            // read options from DB as array
            parse_str($this->_options->getOption('static-export-settings'), $pluginOptions);

            $newBaseURL = $pluginOptions['baseUrl'];
            $additionalUrls = $pluginOptions['additionalUrls'];
        }


		$exporter = wp_get_current_user();
		// setting path to store the archive dir path
		$_SERVER['currentArchive'] = $this->_uploadsPath . '/WP-STATIC-CURRENT-ARCHIVE';
		$_SERVER['exportLog'] = $this->_uploadsPath . '/WP-STATIC-EXPORT-LOG';
		$_SERVER['githubFilesToExport'] = $this->_uploadsPath . '/WP-STATIC-EXPORT-GITHUB-FILES-TO-EXPORT';

		$archiveName = $this->outputPath() . '/' . self::HOOK . '-' . $blog_id . '-' . time();
		// append username if done via UI
		if ( $exporter->user_login ) {
			$archiveName .= '-' . $exporter->user_login;
		}

		$archiveDir = $archiveName . '/';

		// writing the archive dir into the `$this->_uploadsPath . '/WP-STATIC-CURRENT-ARCHIVE'` path
        file_put_contents($_SERVER['currentArchive'], $archiveDir);

		if (!file_exists($archiveDir)) {
			wp_mkdir_p($archiveDir);
		}

		if (file_exists($_SERVER['exportLog'])) {
			unlink($_SERVER['exportLog']);
		}

        file_put_contents($_SERVER['exportLog'], date("Y-m-d h:i:s") . ' STARTING EXPORT', FILE_APPEND | LOCK_EX);

        $this->wsLog('STARTING EXPORT: PHP VERSION ' . phpversion() );
        $this->wsLog('STARTING EXPORT: PHP MAX EXECUTION TIME ' . ini_get('max_execution_time') );
        $this->wsLog('STARTING EXPORT: OS VERSION ' . php_uname() );
        $this->wsLog('STARTING EXPORT: WP VERSION ' . get_bloginfo('version') );
        $this->wsLog('STARTING EXPORT: WP URL ' . get_bloginfo('url') );
        $this->wsLog('STARTING EXPORT: WP ADDRESS ' . get_bloginfo('wpurl') );
        $this->wsLog('STARTING EXPORT: VIA CLI? ' . $viaCLI);
        $this->wsLog('STARTING EXPORT: STATIC EXPORT URL ' . filter_input(INPUT_POST, 'baseUrl') );

		$baseUrl = untrailingslashit(home_url());
		
		$urlsQueue = array_unique(array_merge(
					array(trailingslashit($baseUrl)),
					StaticHtmlOutput_FilesHelper::getListOfLocalFilesByUrl(array(get_template_directory_uri())),
                    $this->_getAllWPPostURLs(),
					explode("\n", $additionalUrls)
					));


        $dontIncludeAllUploadFiles = filter_input(INPUT_POST, 'dontIncludeAllUploadFiles');

		if (!$dontIncludeAllUploadFiles) {
            $this->wsLog('NOT INCLUDING ALL FILES FROM UPLOADS DIR');
			$urlsQueue = array_unique(array_merge(
					$urlsQueue,
					StaticHtmlOutput_FilesHelper::getListOfLocalFilesByUrl(array($this->_uploadsURL))
			));
		}

        $this->wsLog('INITIAL CRAWL LIST CONTAINS ' . count($urlsQueue) . ' FILES');

        $str = implode("\n", $urlsQueue);
        file_put_contents($this->_uploadsPath . '/WP-STATIC-INITIAL-CRAWL-LIST', $str);
        file_put_contents($this->_uploadsPath . '/WP-STATIC-CRAWLED-LINKS', '');

        return 'initial crawl list ready';
    }

	public function crawlABitMore($viaCLI = false) {
		$initial_crawl_list_file = $this->_uploadsPath . '/WP-STATIC-INITIAL-CRAWL-LIST';
        $crawled_links_file = $this->_uploadsPath . '/WP-STATIC-CRAWLED-LINKS';
        $initial_crawl_list = file($initial_crawl_list_file, FILE_IGNORE_NEW_LINES);
        $crawled_links = file($crawled_links_file, FILE_IGNORE_NEW_LINES);

        $first_line = array_shift($initial_crawl_list);
        file_put_contents($initial_crawl_list_file, implode("\r\n", $initial_crawl_list));
        $currentUrl = $first_line;
        $this->wsLog('CRAWLING URL: ' . $currentUrl);

        $newBaseUrl = untrailingslashit(filter_input(INPUT_POST, 'baseUrl', FILTER_SANITIZE_URL));

        // override options if running via CLI
        if ($viaCLI) {
            parse_str($this->_options->getOption('static-export-settings'), $pluginOptions);

            $newBaseUrl = $pluginOptions['baseUrl'];
        }

        if (empty($currentUrl)){
            $this->wsLog('EMPTY FILE ENCOUNTERED');
			// skip this empty file
			echo 'SUCCESS';
			return;
        }

        $urlResponse = new StaticHtmlOutput_UrlRequest($currentUrl);
        $urlResponseForFurtherExtraction = new StaticHtmlOutput_UrlRequest($currentUrl);

        if ($urlResponse->checkResponse() == 'FAIL') {
            $this->wsLog('FAILED TO CRAWL FILE: ' . $currentUrl);
        } else {
            file_put_contents($crawled_links_file, $currentUrl . PHP_EOL, FILE_APPEND | LOCK_EX);
            $this->wsLog('CRAWLED FILE: ' . $currentUrl);
        }

        $baseUrl = untrailingslashit(home_url());
        $urlResponse->cleanup();
		// TODO: if it replaces baseurl here, it will be searching links starting with that...
		// TODO: shouldn't be doing this here...
        $urlResponse->replaceBaseUrl($baseUrl, $newBaseUrl);
        $archiveDir = file_get_contents($this->_uploadsPath . '/WP-STATIC-CURRENT-ARCHIVE');
        $this->_saveUrlData($urlResponse, $archiveDir);

		// try extracting urls from a response that hasn't been changed yet...
		// this seems to do it...
        foreach ($urlResponseForFurtherExtraction->extractAllUrls($baseUrl) as $newUrl) {
            if ($newUrl != $currentUrl && !in_array($newUrl, $crawled_links) && !in_array($newUrl, $initial_crawl_list)) {
                $this->wsLog('DISCOVERED NEW FILE: ' . $newUrl);
                
                $urlResponse = new StaticHtmlOutput_UrlRequest($newUrl);

                if ($urlResponse->checkResponse() == 'FAIL') {
                    $this->wsLog('FAILED TO CRAWL FILE: ' . $newUrl);
                } else {
                    file_put_contents($crawled_links_file, $newUrl . PHP_EOL, FILE_APPEND | LOCK_EX);
                    $crawled_links[] = $newUrl;
                    $this->wsLog('CRAWLED FILE: ' . $newUrl);
                }

                $urlResponse->cleanup();
                $urlResponse->replaceBaseUrl($baseUrl, $newBaseUrl);
                $archiveDir = file_get_contents($this->_uploadsPath . '/WP-STATIC-CURRENT-ARCHIVE');
                $this->_saveUrlData($urlResponse, $archiveDir);
            } 
        }
		
		// TODO: could avoid reading file again here as we should have it above
        $f = file($initial_crawl_list_file, FILE_IGNORE_NEW_LINES);
        $filesRemaining = count($f);
		$this->wsLog('CRAWLING SITE: ' . $filesRemaining . ' files remaining');
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
        $this->wsLog('CREATING ZIP FILE...');
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
        $this->wsLog('ZIP CREATED: Download at ' . $publicDownloadableZip);

		echo 'SUCCESS';
		// TODO: put the zip url somewhere in the interface
        //echo $publicDownloadableZip;
    }

    public function ftp_prepare_export() {
		if ( wpsho_fr()->is__premium_only() ) {
			$this->wsLog('FTP EXPORT: Checking credentials..:');

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
			$this->wsLog('FTP EXPORT: transferring files..:');

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
			$this->wsLog('BUNNYCDN EXPORT: Preparing export..:');

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
			$this->wsLog('BUNNYCDN EXPORT: transferring files'); 

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
			$this->wsLog('BUNNYCDN EXPORT: purging cache'); 

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
        $this->wsLog($export_target . ' EXPORT: Preparing list of files to export');

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
        $this->wsLog('GENERIC EXPORT: File list prepared');
	}

    public function s3_prepare_export() {
		if ( wpsho_fr()->is__premium_only() ) {
			$this->wsLog('S3 EXPORT: preparing export...');
			$this->prepare_file_list('S3');

			echo 'SUCCESS';
		}	
    }

	public function s3_put_object($Bucket, $Key, $Data, $ContentType = "text/plain", $pluginInstance) {
		if ( wpsho_fr()->is__premium_only() ) {
		
			require_once(__DIR__.'/S3/S3.php');

			$client = new S3(
				filter_input(INPUT_POST, 's3Key'),
				filter_input(INPUT_POST, 's3Secret'),
				's3.' . filter_input(INPUT_POST, 's3Region') .  '.amazonaws.com'
			);

			// [OPTIONAL] Specify different curl options
			$client->useCurlOpts(array(
				CURLOPT_MAX_RECV_SPEED_LARGE => 1048576,
				CURLOPT_CONNECTTIMEOUT => 10
			));

			$response = $client->putObject(
				$Bucket, // bucket name without s3.amazonaws.com
				$Key, // path to create in bucket
				$Data, // file contents - path to stream or fopen result
				array(
					'Content-Type' => $ContentType,
					'x-amz-acl' => 'public-read', // public read for static site
				)
			);

			if ($response->code == 200) {
				return true;
			} else {
				$pluginInstance->wsLog('S3 EXPORT: following error returned from S3:');
				$pluginInstance->wsLog(print_r($response, true));
				error_log(print_r($response, true));
				return false;
			}
		}
	}

	// TODO: make this a generic func, calling vendor specific files
    public function s3_transfer_files() {
		if ( wpsho_fr()->is__premium_only() ) {
			$this->wsLog('S3 EXPORT: Transferring files...');
			$archiveDir = file_get_contents($this->_uploadsPath . '/WP-STATIC-CURRENT-ARCHIVE');
			$archiveName = rtrim($archiveDir, '/');
			$siteroot = $archiveName . '/';
			$file_list_path = $this->_uploadsPath . '/WP-STATIC-EXPORT-S3-FILES-TO-EXPORT';
			$contents = file($file_list_path, FILE_IGNORE_NEW_LINES);
			$filesRemaining = count($contents) - 1;

			if ($filesRemaining < 0) {
				echo $filesRemaining;die();
			}

			$filename = array_shift($contents);
			$file_body = file_get_contents($filename);
			// rewrite file without first line
			file_put_contents($file_list_path, implode("\r\n", $contents));

			$target_path = str_replace($siteroot, '', $filename);
			$this->wsLog('S3 EXPORT: transferring ' . 
				basename($filename) . ' TO ' . $target_path);
		  
			require_once(__DIR__.'/StaticHtmlOutput/MimeTypes.php'); 

			if( $this->s3_put_object(
				filter_input(INPUT_POST, 's3Bucket'),
				$target_path,
				$file_body,
				GuessMimeType($filename),
				$this) ) 
			{
				$this->wsLog('S3 EXPORT: ' . $filesRemaining . ' files remaining to transfer');

				if ( $filesRemaining > 0 ) {
					echo $filesRemaining;
				} else {
					echo 'SUCCESS';
				}
			} else {
					echo 'FAIL';
			}
		}
    }

	public function cloudfront_invalidate_all_items() {
		if ( wpsho_fr()->is__premium_only() ) {
			$this->wsLog('S3 EXPORT: Checking whether to invalidate CF cache');
			require_once(__DIR__.'/CloudFront/CloudFront.php');
			$cloudfront_id = filter_input(INPUT_POST, 'cfDistributionId');

			if( !empty($cloudfront_id) ) {
				$this->wsLog('CLOUDFRONT INVALIDATING CACHE...');

				$cf = new CloudFront(
					filter_input(INPUT_POST, 's3Key'), 
					filter_input(INPUT_POST, 's3Secret'),
					$cloudfront_id);

				$cf->invalidate('/*');
			
				if ( $cf->getResponseMessage() == 200 || $cf->getResponseMessage() == 201 )	{
					echo 'SUCCESS';
				} else {
					$this->wsLog('CF ERROR: ' . $cf->getResponseMessage());
				}
			} else {
				$this->wsLog('S3 EXPORT: Skipping CF cache invalidation');
				echo 'SUCCESS';
			}
		}
	}

    public function dropbox_prepare_export() {
		if ( wpsho_fr()->is__premium_only() ) {
			$this->wsLog('DROPBOX EXPORT: preparing export');

			$dropbox = new StaticHtmlOutput_Dropbox(
				filter_input(INPUT_POST, 'dropboxAccessToken'),
				filter_input(INPUT_POST, 'dropboxFolder'),
				$this->_uploadsPath
			);

			echo $dropbox->prepare_export();
		}
    }

    public function dropbox_do_export() {
		if ( wpsho_fr()->is__premium_only() ) {
			$this->wsLog('DROPBOX EXPORT: Transferring files...');

			$dropbox = new StaticHtmlOutput_Dropbox(
				filter_input(INPUT_POST, 'dropboxAccessToken'),
				filter_input(INPUT_POST, 'dropboxFolder'),
				$this->_uploadsPath
			);

			echo $dropbox->transfer_files();
		}
    }


    public function netlify_do_export () {
		if ( wpsho_fr()->is__premium_only() ) {

			$this->wsLog('NETLIFY EXPORT: starting to deploy ZIP file');

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

	
    public function post_process_archive_dir() {
        $this->wsLog('POST PROCESSING ARCHIVE DIR: ...');


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


		rename($original_wp_content, $new_wp_content);

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

		echo 'SUCCESS';
	}


	public function remove_symlink_to_latest_archive() {
        global $blog_id;
        $archiveDir = file_get_contents($this->_uploadsPath . '/WP-STATIC-CURRENT-ARCHIVE');

		if (is_link($this->outputPath() . '/latest-' . $blog_id)) {
			$this->wsLog('REMOVING SYMLINK: '. $this->outputPath() . '/latest-' . $blog_id);
			unlink($this->outputPath() . '/latest-' . $blog_id );
		} else {
			$this->wsLog('REMOVING SYMLINK: NO LINK FOUND AT'. $this->outputPath() . '/latest-' . $blog_id);
		}
	}	

	public function create_symlink_to_latest_archive() {
        global $blog_id;
        $archiveDir = file_get_contents($this->_uploadsPath . '/WP-STATIC-CURRENT-ARCHIVE');

		// rm and recreate
		$this->remove_symlink_to_latest_archive();

        $this->wsLog('(RE)CREATING SYMLINK TO LATEST EXPORT FOLDER: '. $this->outputPath() . '/latest-' . $blog_id);

        symlink($archiveDir, $this->outputPath() . '/latest-' . $blog_id );

		echo 'SUCCESS';
	}	

    public function post_export_teardown() {
        $this->wsLog('POST EXPORT CLEANUP: starting...');

        $archiveDir = file_get_contents($this->_uploadsPath . '/WP-STATIC-CURRENT-ARCHIVE');

		$retainStaticFiles = filter_input(INPUT_POST, 'retainStaticFiles');
		$retainZipFile = filter_input(INPUT_POST, 'retainZipFile');

        // Remove temporary files unless user requested to keep or needed for FTP transfer
        if ($retainStaticFiles != 1) {
			$this->remove_symlink_to_latest_archive();

			$this->wsLog('POST EXPORT CLEANUP: removing dir: ' . $archiveDir);
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($archiveDir), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($iterator as $fileName => $fileObject) {

                // Remove file
                if ($fileObject->isDir()) {
                    // Ignore special dirs
                    $dirName = basename($fileName);
					
                    if($dirName != '.' && $dirName != '..') {
                        rmdir($fileName);
                    }
                } else {
                    unlink($fileName);
                }
            }
            rmdir($archiveDir);
        }	

        if ($retainZipFile != 1) {
			$archiveName = rtrim($archiveDir, '/');
			$zipFile = $archiveName . '.zip';
			if( file_exists($zipFile) ) {
				$this->wsLog('POST EXPORT CLEANUP: removing zip: ' . $zipFile);
				unlink($zipFile);
			}
		}

		
		$this->cleanup_working_files();

		$this->wsLog('POST EXPORT CLEANUP: complete');

		// has SUCCESS returned already from cleanup working files..
	}

    protected function _getAllWPPostURLs(){
        global $wpdb;
        $posts = $wpdb->get_results("
            SELECT ID,post_type,post_title
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
            }
            
            $postURLs[] = $permalink;
        }

        return $postURLs;
    }

    public function wsLog($text) {
        $exportLog = $this->_uploadsPath . '/WP-STATIC-EXPORT-LOG';
        
        $src = fopen($exportLog, 'r+');
        $dest = fopen('php://temp', 'w');
        fwrite($dest,  date("Y-m-d h:i:s") . ' ' . $text . PHP_EOL);
        stream_copy_to_stream($src, $dest);
        rewind($dest);
        rewind($src);
        stream_copy_to_stream($dest, $src);
        fclose($src);
        fclose($dest);
    }

	protected function _saveUrlData(StaticHtmlOutput_UrlRequest $url, $archiveDir) {
		$urlInfo = parse_url($url->getUrl());
		$pathInfo = array();

		//$this->wsLog('urlInfo :' . $urlInfo['path']);
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
			$this->wsLog('PREPARING URL: Invalid URL given, aborting');
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
		
		$this->wsLog('SAVING URL: ' . $urlInfo['path'] . ' to new path' . $fileName);
		// TODO: what was the 'F' check for?1? Comments exist for a reason
		if ($fileContents != '' && $fileContents != 'F') {
			file_put_contents($fileName, $fileContents);
		} else {
			$this->wsLog('SAVING URL: UNABLE TO SAVE FOR SOME REASON');
		}
	}
}
