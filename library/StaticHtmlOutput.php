<?php
/**
 * @package WP Static HTML Output
 *
 * Copyright (c) 2011 Leon Stafford
 */

use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\DropboxFile;
use Kunnu\Dropbox\Exceptions\DropboxClientException;
use GuzzleHttp\Client;

class StaticHtmlOutput {
	const VERSION = '2.5';
	const OPTIONS_KEY = 'wp-static-html-output-options';
	const HOOK = 'wp-static-html-output';

	protected static $_instance = null;
	protected $_options = null;
	protected $_view = null;
	protected function __construct() {}
	protected function __clone() {}

    // gets the full path on server to the wp uploads dir
    // not to be confued with uploads public URL 
	public function getUploadsDirBaseDIR() {
        $tmp_var_to_hold_return_array = wp_upload_dir();

		return $tmp_var_to_hold_return_array['basedir'];
	}

	public function getUploadsDirBaseURL() {
        $tmp_var_to_hold_return_array = wp_upload_dir();

		return $tmp_var_to_hold_return_array['baseurl'];
	}

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
		$uploadDir = $this->get_write_directory();
		$uploadsFolderWritable = $uploadDir && is_writable($uploadDir);
		$supportsZipArchives = extension_loaded('zip');
		$permalinksStructureDefined = strlen(get_option('permalink_structure'));

		if (!$uploadsFolderWritable || !$supportsZipArchives || !$permalinksStructureDefined) {
			$this->_view
				->setTemplate('system-requirements')
				->assign('uploadsFolderWritable', $uploadsFolderWritable)
				->assign('supportsZipArchives', $supportsZipArchives)
				->assign('permalinksStructureDefined', $permalinksStructureDefined)
				->assign('uploadsFolder', $uploadDir)
				->render();
		} else {
			do_action(self::HOOK . '-saveOptions');
			$wp_upload_dir = wp_upload_dir();

			$this->_view
				->setTemplate('options-page')
				->assign('staticExportSettings', $this->_options->getOption('static-export-settings'))
				->assign('wpUploadsDir', $this->getUploadsDirBaseURL())
				->assign('wpPluginDir', plugins_url('/', __FILE__))
				->assign('onceAction', self::HOOK . '-options')
				->render();
		}
	}

    public function save_options () {
		if (!check_admin_referer(self::HOOK . '-options') || !current_user_can('manage_options')) {
			//error_log('user didnt have permissions to change options');
			exit('You cannot change WP Static HTML Output Plugin options.');
		}

		$this->_options
			->setOption('static-export-settings', filter_input(INPUT_POST, 'staticExportSettings', FILTER_SANITIZE_URL))
			->save();
    }

	public function get_write_directory(){
		// read outputDirectory setting from post or from plugin options if set
		$outputDir = '';

		// if post is set from an AJAX call
		if ( filter_input(INPUT_POST, 'outputDirectory') ) {
			$outputDir = filter_input(INPUT_POST, 'outputDirectory');
		} else {
            parse_str($this->_options->getOption('static-export-settings'), $pluginOptions);
            if ( array_key_exists('outputDirectory', $pluginOptions )) {
				$outputDir = $pluginOptions['outputDirectory'];
			}
		}

		if ( $outputDir && is_dir($outputDir)) {
			if( is_writable( $outputDir ) ){
				return $outputDir;
			}
		}

		return $this->getUploadsDirBaseDIR();
	}

    public function progressThroughExportTargets() {
        $exportTargetsFile = $this->getUploadsDirBaseDIR() . '/WP-STATIC-EXPORT-TARGETS';

        // remove first line from file (disabled while testing)
        $exportTargets = file($exportTargetsFile, FILE_IGNORE_NEW_LINES);
        $filesRemaining = count($exportTargets) - 1;
        $first_line = array_shift($exportTargets);
        file_put_contents($exportTargetsFile, implode("\r\n", $exportTargets));

        
        $this->_prependExportLog('PROGRESS: Starting export type:' . $target . PHP_EOL);
    }

    public function github_finalise_export() {
        $client = new \Github\Client();
        $githubRepo = filter_input(INPUT_POST, 'githubRepo');
        $githubBranch = filter_input(INPUT_POST, 'githubBranch');
        $githubPersonalAccessToken = filter_input(INPUT_POST, 'githubPersonalAccessToken');

        list($githubUser, $githubRepo) = explode('/', $githubRepo);

        $client->authenticate($githubPersonalAccessToken, Github\Client::AUTH_HTTP_TOKEN);
        $reference = $client->api('gitData')->references()->show($githubUser, $githubRepo, 'heads/' . $githubBranch);
        $commit = $client->api('gitData')->commits()->show($githubUser, $githubRepo, $reference['object']['sha']);
        $commitSHA = $commit['sha'];
        $treeSHA = $commit['tree']['sha'];
        $treeURL = $commit['tree']['url'];
        $treeContents = array();
        $githubGlobHashesAndPaths = $this->getUploadsDirBaseDIR() . '/WP-STATIC-EXPORT-GITHUB-GLOBS-PATHS';
        $contents = file($githubGlobHashesAndPaths);

        foreach($contents as $line) {
            list($blobHash, $targetPath) = explode(',', $line);

            $treeContents[] = array(
                'path' => trim($targetPath),
                'mode' => '100644',
                'type' => 'blob',
                'sha' => $blobHash
            );
        }

        $treeData = array(
            'base_tree' => $treeSHA,
            'tree' => $treeContents
        );

        $this->_prependExportLog('GITHUB: Creating tree ...' . PHP_EOL);
        $this->_prependExportLog('GITHUB: tree data: '. PHP_EOL);
        #$this->_prependExportLog(print_r($treeData, true) . PHP_EOL);
        $newTree = $client->api('gitData')->trees()->create($githubUser, $githubRepo, $treeData);
        $this->_prependExportLog('GITHUB: Tree created');
        
        $commitData = array('message' => 'WP Static HTML Export Plugin on ' . date("Y-m-d h:i:s"), 'tree' => $newTree['sha'], 'parents' => array($commitSHA));
        $this->_prependExportLog('GITHUB: Creating commit ...');
        $commit = $client->api('gitData')->commits()->create($githubUser, $githubRepo, $commitData);
        $this->_prependExportLog('GITHUB: Updating head to reference commit ...');
        $referenceData = array('sha' => $commit['sha'], 'force' => true); //Force is default false
        try {
            $reference = $client->api('gitData')->references()->update(
                    $githubUser,
                    $githubRepo,
                    'heads/' . $githubBranch,
                    $referenceData);
        } catch (Exception $e) {
            $this->_prependExportLog($e);
            throw new Exception($e);
        }

		echo 'SUCCESS';

    }

	public function github_upload_blobs() {
        $client = new \Github\Client();
        $githubRepo = filter_input(INPUT_POST, 'githubRepo');
        $githubPersonalAccessToken = filter_input(INPUT_POST, 'githubPersonalAccessToken');
        list($githubUser, $githubRepo) = explode('/', $githubRepo);

        $client->authenticate($githubPersonalAccessToken, Github\Client::AUTH_HTTP_TOKEN);

        $_SERVER['githubFilesToExport'] = $this->getUploadsDirBaseDIR() . '/WP-STATIC-EXPORT-GITHUB-FILES-TO-EXPORT';

        // grab first line from filelist
        $githubFilesToExport = $_SERVER['githubFilesToExport'];
        $f = fopen($githubFilesToExport, 'r');
        $line = fgets($f);
        fclose($f);

        // TODO: look at these funcs above and below, seems redundant...

        // remove first line from file (disabled while testing)
        $contents = file($githubFilesToExport, FILE_IGNORE_NEW_LINES);
        $filesRemaining = count($contents) - 1;
        $first_line = array_shift($contents);
        file_put_contents($githubFilesToExport, implode("\r\n", $contents));

        // create the blob
        // first part of line is file to read, second is target path in GH:
        list($fileToExport, $targetPath) = explode(',', $line);
        
        $this->_prependExportLog('GITHUB: Creating blob for ' . rtrim($targetPath));

        $encodedFile = chunk_split(base64_encode(file_get_contents($fileToExport)));

        $globHash = $client->api('gitData')->blobs()->create(
                $githubUser, 
                $githubRepo, 
                array('content' => $encodedFile, 'encoding' => 'base64')
                ); # utf-8 or base64

        $githubGlobHashesAndPaths = $this->getUploadsDirBaseDIR() . '/WP-STATIC-EXPORT-GITHUB-GLOBS-PATHS';

        $globHashPathLine = $globHash['sha'] . ',' . $targetPath;
        file_put_contents($githubGlobHashesAndPaths, $globHashPathLine, FILE_APPEND | LOCK_EX);


        $this->_prependExportLog('GITHUB: ' . $filesRemaining . ' blobs remaining to create');
        
		if ($filesRemaining > 0) {
			echo $filesRemaining;
		} else {
			echo 'SUCCESS';
		}
    }

	public function start_export($viaCLI = false) {
        $this->_prependExportLog('STARTING EXPORT: via CLI = ' . $viaCLI);
        //error_log('STARTING EXPORT: via CLI = ' . $viaCLI);
        // prepare export targets
        $exportTargetsFile = $this->getUploadsDirBaseDIR() . '/WP-STATIC-EXPORT-TARGETS';

		if ( file_exists($exportTargetsFile) ) {
			unlink($this->getUploadsDirBaseDIR() . '/WP-STATIC-EXPORT-TARGETS');
		}


        // set options from GUI or override via CLI
        $sendViaGithub = filter_input(INPUT_POST, 'sendViaGithub');
        $sendViaFTP = filter_input(INPUT_POST, 'sendViaFTP');
        $sendViaS3 = filter_input(INPUT_POST, 'sendViaS3');
        $sendViaNetlify = filter_input(INPUT_POST, 'sendViaNetlify');
        $sendViaDropbox = filter_input(INPUT_POST, 'sendViaDropbox');

        if ($viaCLI) {
            //error_log('DOING EXPORT VIA CLI');
            parse_str($this->_options->getOption('static-export-settings'), $pluginOptions);

            $sendViaGithub = $pluginOptions['sendViaGithub'];
            $sendViaFTP = $pluginOptions['sendViaFTP'];
            $sendViaS3 = $pluginOptions['sendViaS3'];
            $sendViaNetlify = $pluginOptions['sendViaNetlify'];
            $sendViaDropbox = $pluginOptions['sendViaDropbox'];
        }

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

        if ($archiveUrl = 'initial crawl list ready') {
            $this->_prependExportLog('Initial list of files to include is prepared. Now crawling these to extract more URLs.');

        } elseif (is_wp_error($archiveUrl)) {
			$message = 'Error: ' . $archiveUrl->get_error_code;
		} else {
            $this->_prependExportLog('ZIP CREATED: Download a ZIP of your static site from: ' . $archiveUrl);
			$message = sprintf('Archive created successfully: <a href="%s">Download archive</a>', $archiveUrl);
			if ($this->_options->getOption('retainStaticFiles') == 1) {
				$message .= sprintf('<br />Static files retained at: %s/', str_replace(home_url(),'',substr($archiveUrl,0,-4)));
			}
        }

        echo 'Archive has been generated';

        global $blog_id;
        $archiveDir = file_get_contents($this->getUploadsDirBaseDIR() . '/WP-STATIC-CURRENT-ARCHIVE');
		$uploadDir = $this->getUploadsDirBaseDIR();
        unlink($uploadDir . '/latest-' . $blog_id );
        symlink($archiveDir, $uploadDir . '/latest-' . $blog_id );

        echo 'LOCALDIR SYMLINK UPDATED: '. $uploadDir . '/latest-' . $blog_id;
	}

	protected function _prepareInitialFileList($viaCLI = false) {
		global $blog_id;
		set_time_limit(0);

        // set options from GUI or CLI
        $newBaseUrl = untrailingslashit(filter_input(INPUT_POST, 'baseUrl', FILTER_SANITIZE_URL));

        $additionalUrls = filter_input(INPUT_POST, 'additionalUrls');

		// location for acrhive folder and zip to be created	

		$uploadDir = $this->get_write_directory();
        if ($viaCLI) {
            // read options from DB as array
            parse_str($this->_options->getOption('static-export-settings'), $pluginOptions);

            $newBaseURL = $pluginOptions['baseUrl'];
            $additionalUrls = $pluginOptions['additionalUrls'];
        }


		$exporter = wp_get_current_user();
		$_SERVER['urlsQueue'] = $this->getUploadsDirBaseDIR() . '/WP-STATIC-INITIAL-CRAWL-LIST';
		$_SERVER['currentArchive'] = $this->getUploadsDirBaseDIR() . '/WP-STATIC-CURRENT-ARCHIVE';
		$_SERVER['exportLog'] = $this->getUploadsDirBaseDIR() . '/WP-STATIC-EXPORT-LOG';
		$_SERVER['githubFilesToExport'] = $this->getUploadsDirBaseDIR() . '/WP-STATIC-EXPORT-GITHUB-FILES-TO-EXPORT';


		$archiveName = $uploadDir . '/' . self::HOOK . '-' . $blog_id . '-' . time();
		// append username if done via UI
		if ( $exporter->user_login ) {
			$archiveName .= '-' . $exporter->user_login;
		}

		$archiveDir = $archiveName . '/';

        file_put_contents($_SERVER['currentArchive'], $archiveDir);

		if (!file_exists($archiveDir)) {
			wp_mkdir_p($archiveDir);
		}

		unlink($_SERVER['exportLog']);
		unlink($_SERVER['urlsQueue']);

        file_put_contents($_SERVER['exportLog'], date("Y-m-d h:i:s") . ' STARTING EXPORT', FILE_APPEND | LOCK_EX);

		$baseUrl = untrailingslashit(home_url());
		
		$urlsQueue = array_unique(array_merge(
					array(trailingslashit($baseUrl)),
					$this->_getListOfLocalFilesByUrl(array(get_template_directory_uri())),
                    $this->_getAllWPPostURLs(),
					explode("\n", $additionalUrls)
					));


        $dontIncludeAllUploadFiles = filter_input(INPUT_POST, 'dontIncludeAllUploadFiles');

		if (!$dontIncludeAllUploadFiles) {
            $this->_prependExportLog('NOT INCLUDING ALL FILES FROM UPLOADS DIR');
			$urlsQueue = array_unique(array_merge(
					$urlsQueue,
					$this->_getListOfLocalFilesByUrl(array($this->getUploadsDirBaseURL()))
			));
		}

        $this->_prependExportLog('INITIAL CRAWL LIST CONTAINS ' . count($urlsQueue) . ' FILES');

        $str = implode("\n", $urlsQueue);
        file_put_contents($_SERVER['urlsQueue'], $str);
        file_put_contents($this->getUploadsDirBaseDIR() . '/WP-STATIC-CRAWLED-LINKS', '');

        return 'initial crawl list ready';
    }

	public function crawlABitMore($viaCLI = false) {
		$initial_crawl_list_file = $this->getUploadsDirBaseDIR() . '/WP-STATIC-INITIAL-CRAWL-LIST';
        $crawled_links_file = $this->getUploadsDirBaseDIR() . '/WP-STATIC-CRAWLED-LINKS';
        $initial_crawl_list = file($initial_crawl_list_file, FILE_IGNORE_NEW_LINES);
        $crawled_links = file($crawled_links_file, FILE_IGNORE_NEW_LINES);

        $first_line = array_shift($initial_crawl_list);
        file_put_contents($initial_crawl_list_file, implode("\r\n", $initial_crawl_list));
        $currentUrl = $first_line;
        $this->_prependExportLog('CRAWLING URL: ' . $currentUrl);

        $newBaseUrl = untrailingslashit(filter_input(INPUT_POST, 'baseUrl', FILTER_SANITIZE_URL));

        // override options if running via CLI
        if ($viaCLI) {
            parse_str($this->_options->getOption('static-export-settings'), $pluginOptions);

            $newBaseUrl = $pluginOptions['baseUrl'];
        }

        if (empty($currentUrl)){
            $this->_prependExportLog('EMPTY FILE ENCOUNTERED');
        }

        $urlResponse = new StaticHtmlOutput_UrlRequest($currentUrl);
        $urlResponseForFurtherExtraction = new StaticHtmlOutput_UrlRequest($currentUrl);

        if ($urlResponse->checkResponse() == 'FAIL') {
            $this->_prependExportLog('FAILED TO CRAWL FILE: ' . $currentUrl);
        } else {
            file_put_contents($crawled_links_file, $currentUrl . PHP_EOL, FILE_APPEND | LOCK_EX);
            $this->_prependExportLog('CRAWLED FILE: ' . $currentUrl);
        }

        $baseUrl = untrailingslashit(home_url());
        $urlResponse->cleanup();
		// TODO: if it replaces baseurl here, it will be searching links starting with that...
		// TODO: shouldn't be doing this here...
        $urlResponse->replaceBaseUrl($baseUrl, $newBaseUrl);
        $archiveDir = file_get_contents($this->getUploadsDirBaseDIR() . '/WP-STATIC-CURRENT-ARCHIVE');
        $this->_saveUrlData($urlResponse, $archiveDir);

		// try extracting urls from a response that hasn't been changed yet...
		// this seems to do it...
        foreach ($urlResponseForFurtherExtraction->extractAllUrls($baseUrl) as $newUrl) {
            if ($newUrl != $currentUrl && !in_array($newUrl, $crawled_links) && !in_array($newUrl, $initial_crawl_list)) {
                $this->_prependExportLog('DISCOVERED NEW FILE: ' . $newUrl);
                
                $urlResponse = new StaticHtmlOutput_UrlRequest($newUrl);

                if ($urlResponse->checkResponse() == 'FAIL') {
                    $this->_prependExportLog('FAILED TO CRAWL FILE: ' . $newUrl);
                } else {
                    file_put_contents($crawled_links_file, $newUrl . PHP_EOL, FILE_APPEND | LOCK_EX);
                    $crawled_links[] = $newUrl;
                    $this->_prependExportLog('CRAWLED FILE: ' . $newUrl);
                }

                $urlResponse->cleanup();
                $urlResponse->replaceBaseUrl($baseUrl, $newBaseUrl);
                $archiveDir = file_get_contents($this->getUploadsDirBaseDIR() . '/WP-STATIC-CURRENT-ARCHIVE');
                $this->_saveUrlData($urlResponse, $archiveDir);
            } 
        }
		
		// TODO: could avoid reading file again here as we should have it above
        $f = file($initial_crawl_list_file, FILE_IGNORE_NEW_LINES);
        $filesRemaining = count($f);
		$this->_prependExportLog('CRAWLING SITE: ' . $filesRemaining . ' files remaining');
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
		$initial_crawl_list_file = $this->getUploadsDirBaseDIR() . '/WP-STATIC-INITIAL-CRAWL-LIST';
        $initial_crawl_list = file($initial_crawl_list_file, FILE_IGNORE_NEW_LINES);

		if ( !empty($initial_crawl_list) ) {
            $this->crawlABitMore($viaCLI);
		} 
    }

    public function create_zip() {
        $this->_prependExportLog('CREATING ZIP FILE...');
        $archiveDir = file_get_contents($this->getUploadsDirBaseDIR() . '/WP-STATIC-CURRENT-ARCHIVE');
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
        $this->_prependExportLog('ZIP CREATED: Download at ' . $publicDownloadableZip);

		echo 'SUCCESS';
		// TODO: put the zip url somewhere in the interface
        //echo $publicDownloadableZip;
    }

    public function ftp_prepare_export() {
        $this->_prependExportLog('FTP EXPORT: Checking credentials..:');

        require_once(__DIR__.'/FTP/FtpClient.php');
        require_once(__DIR__.'/FTP/FtpException.php');
        require_once(__DIR__.'/FTP/FtpWrapper.php');

        $ftp = new \FtpClient\FtpClient();
        
        try {
			$ftp->connect(filter_input(INPUT_POST, 'ftpServer'));
			$ftp->login(filter_input(INPUT_POST, 'ftpUsername'), filter_input(INPUT_POST, 'ftpPassword'));
        } catch (Exception $e) {
			$this->_prependExportLog('FTP EXPORT: error encountered');
			$this->_prependExportLog($e);
            throw new Exception($e);
        }

        if ($ftp->isdir(filter_input(INPUT_POST, 'ftpRemotePath'))) {
            $this->_prependExportLog('FTP EXPORT: Remote dir exists');
        } else {
            $this->_prependExportLog('FTP EXPORT: Creating remote dir');
            $ftp->mkdir(filter_input(INPUT_POST, 'ftpRemotePath'), true);
        }

        unset($ftp);

        $this->_prependExportLog('FTP EXPORT: Preparing list of files to transfer');

        // prepare file list
        $_SERVER['ftpFilesToExport'] = $this->getUploadsDirBaseDIR() . '/WP-STATIC-EXPORT-FTP-FILES-TO-EXPORT';

        $f = @fopen($_SERVER['ftpFilesToExport'], "r+");
        if ($f !== false) {
            ftruncate($f, 0);
            fclose($f);
        }

        $ftpTargetPath = filter_input(INPUT_POST, 'ftpRemotePath');
        $archiveDir = file_get_contents($this->getUploadsDirBaseDIR() . '/WP-STATIC-CURRENT-ARCHIVE');
        $archiveName = rtrim($archiveDir, '/');
        $siteroot = $archiveName . '/';

        function FolderToFTP($dir, $siteroot, $ftpTargetPath){
            $files = scandir($dir);
            foreach($files as $item){
                if($item != '.' && $item != '..' && $item != '.git'){
                    if(is_dir($dir.'/'.$item)) {
                        FolderToFTP($dir.'/'.$item, $siteroot, $ftpTargetPath);
                    } else if(is_file($dir.'/'.$item)) {
                        $subdir = str_replace('/wp-admin/admin-ajax.php', '', $_SERVER['REQUEST_URI']);
                        $subdir = ltrim($subdir, '/');
                        //$clean_dir = str_replace($siteroot . '/', '', $dir.'/'.$item);
                        $clean_dir = str_replace($siteroot . '/', '', $dir.'/');
                        $clean_dir = str_replace($subdir, '', $clean_dir);
                        $targetPath =  $ftpTargetPath . $clean_dir;
                        $targetPath = ltrim($targetPath, '/');
                        $ftpExportLine = $dir .'/' . $item . ',' . $targetPath . "\n";
                        file_put_contents($_SERVER['ftpFilesToExport'], $ftpExportLine, FILE_APPEND | LOCK_EX);
                    } 
                }
            }
        }

        FolderToFTP($siteroot, $siteroot, $ftpTargetPath);

        echo 'SUCCESS';
    }

    public function ftp_transfer_files($batch_size = 5) {
        $archiveDir = file_get_contents($this->getUploadsDirBaseDIR() . '/WP-STATIC-CURRENT-ARCHIVE');
        $archiveName = rtrim($archiveDir, '/');

        require_once(__DIR__.'/FTP/FtpClient.php');
        require_once(__DIR__.'/FTP/FtpException.php');
        require_once(__DIR__.'/FTP/FtpWrapper.php');

        $ftp = new \FtpClient\FtpClient();
        
        $ftp->connect(filter_input(INPUT_POST, 'ftpServer'));
        $ftp->login(filter_input(INPUT_POST, 'ftpUsername'), filter_input(INPUT_POST, 'ftpPassword'));

        $ftp->pasv(true);
        
        $_SERVER['ftpFilesToExport'] = $this->getUploadsDirBaseDIR() . '/WP-STATIC-EXPORT-FTP-FILES-TO-EXPORT';

        // grab first line from filelist
        $ftpFilesToExport = $_SERVER['ftpFilesToExport'];
        $f = fopen($ftpFilesToExport, 'r');
        $line = fgets($f);
        fclose($f);

        // TODO: look at these funcs above and below, seems redundant...

        // TODO: refactor like the crawling function, first_line unused
        $contents = file($ftpFilesToExport, FILE_IGNORE_NEW_LINES);
        $filesRemaining = count($contents) - 1;

        if ($filesRemaining < 0) {
            echo $filesRemaining;die();
        }

        $first_line = array_shift($contents);
        file_put_contents($ftpFilesToExport, implode("\r\n", $contents));

        list($fileToTransfer, $targetPath) = explode(',', $line);

        // TODO: check other funcs using similar, was causing issues without trimming CR's
        $targetPath = rtrim($targetPath);

        $this->_prependExportLog('FTP EXPORT: transferring ' . 
            basename($fileToTransfer) . ' TO ' . $targetPath);
       
        if ($ftp->isdir($targetPath)) {
            //$this->_prependExportLog('FTP EXPORT: Remote dir exists');
        } else {
            $this->_prependExportLog('FTP EXPORT: Creating remote dir');
            $mkdir_result = $ftp->mkdir($targetPath, true); // true = recursive creation
        }

        $ftp->chdir($targetPath);
        $ftp->putFromPath($fileToTransfer);

        $this->_prependExportLog('FTP EXPORT: ' . $filesRemaining . ' files remaining to transfer');

        // TODO: error handling when not connected/unable to put, etc
        unset($ftp);

		if ( $filesRemaining > 0 ) {
			echo $filesRemaining;
		} else {
			echo 'SUCCESS';
		}
    }

    public function bunnycdn_prepare_export() {
        $this->_prependExportLog('BUNNYCDN EXPORT: Preparing export..:');

        // prepare file list
        $_SERVER['bunnycdnFilesToExport'] = $this->getUploadsDirBaseDIR() . '/WP-STATIC-EXPORT-BUNNYCDN-FILES-TO-EXPORT';

        $f = @fopen($_SERVER['bunnycdnFilesToExport'], "r+");
        if ($f !== false) {
            ftruncate($f, 0);
            fclose($f);
        }

        $bunnycdnTargetPath = filter_input(INPUT_POST, 'bunnycdnRemotePath');
        $archiveDir = file_get_contents($this->getUploadsDirBaseDIR() . '/WP-STATIC-CURRENT-ARCHIVE');
        $archiveName = rtrim($archiveDir, '/');
        $siteroot = $archiveName;

        function AddToBunnyCDNExportList($dir, $siteroot, $bunnycdnTargetPath){
            $files = scandir($dir);
            foreach($files as $item){
                if($item != '.' && $item != '..' && $item != '.git'){
                    if(is_dir($dir.'/'.$item)) {
                        AddToBunnyCDNExportList($dir.'/'.$item, $siteroot, $bunnycdnTargetPath);
                    } else if(is_file($dir.'/'.$item)) {
                        $subdir = str_replace('/wp-admin/admin-ajax.php', '', $_SERVER['REQUEST_URI']);
                        $subdir = ltrim($subdir, '/');
                        //$clean_dir = str_replace($siteroot . '/', '', $dir.'/'.$item);
                        $clean_dir = str_replace($siteroot . '/', '', $dir.'/');
                        $clean_dir = str_replace($subdir, '', $clean_dir);
                        $targetPath =  $bunnycdnTargetPath . $clean_dir;
                        $targetPath = ltrim($targetPath, '/');
                        $bunnycdnExportLine = $dir .'/' . $item . ',' . $targetPath . "\n";
                        file_put_contents($_SERVER['bunnycdnFilesToExport'], $bunnycdnExportLine, FILE_APPEND | LOCK_EX);
                    } 
                }
            }
        }

        AddToBunnyCDNExportList($siteroot, $siteroot, $bunnycdnTargetPath);

        echo 'SUCCESS';
    }

    public function bunnycdn_transfer_files($batch_size = 5) {
        $bunnycdnAPIKey = filter_input(INPUT_POST, 'bunnycdnAPIKey');
        $bunnycdnPullZoneName = filter_input(INPUT_POST, 'bunnycdnPullZoneName');
        $archiveDir = file_get_contents($this->getUploadsDirBaseDIR() . '/WP-STATIC-CURRENT-ARCHIVE');
        $archiveName = rtrim($archiveDir, '/');


        $_SERVER['bunnycdnFilesToExport'] = $this->getUploadsDirBaseDIR() . '/WP-STATIC-EXPORT-BUNNYCDN-FILES-TO-EXPORT';

        // grab first line from filelist
        $bunnycdnFilesToExport = $_SERVER['bunnycdnFilesToExport'];
        $f = fopen($bunnycdnFilesToExport, 'r');
        $line = fgets($f);
        fclose($f);


        $contents = file($bunnycdnFilesToExport, FILE_IGNORE_NEW_LINES);
        $filesRemaining = count($contents) - 1;

        if ($filesRemaining < 0) {
            echo $filesRemaining;die();
        }

        $first_line = array_shift($contents);
        file_put_contents($bunnycdnFilesToExport, implode("\r\n", $contents));

        list($fileToTransfer, $targetPath) = explode(',', $line);

        $targetPath = rtrim($targetPath);

        $this->_prependExportLog('BUNNYCDN EXPORT: transferring ' . 
            basename($fileToTransfer) . ' TO ' . $targetPath);
       
		// do the bunny export
        $client = new Client(array(
                'base_uri' => 'https://storage.bunnycdn.com'
        ));	

        try {
            $response = $client->request('PUT', '/' . $bunnycdnPullZoneName . '/' . $targetPath . basename($fileToTransfer), array(
                    'headers'  => array(
                        'AccessKey' => ' ' . $bunnycdnAPIKey
                    ),
                    'body' => fopen($fileToTransfer, 'rb')
            ));
        } catch (Exception $e) {
			//error_log($bunnycdnAPIKey);
			$this->_prependExportLog('BUNNYCDN EXPORT: error encountered');
			$this->_prependExportLog($e);
            throw new Exception($e);
        }

        $this->_prependExportLog('BUNNYCDN EXPORT: ' . $filesRemaining . ' files remaining to transfer');

		if ( $filesRemaining > 0 ) {
			echo $filesRemaining;
		} else {
			echo 'SUCCESS';
		}
    }

	public function recursively_scan_dir($dir, $siteroot, $file_list_path){
		// rm duplicate slashes in path (TODO: fix cause)
		$dir = str_replace('//', '/', $dir);
		$files = scandir($dir);

		foreach($files as $item){
			if($item != '.' && $item != '..' && $item != '.git'){
				if(is_dir($dir.'/'.$item)) {
					$this->recursively_scan_dir($dir.'/'.$item, $siteroot, $file_list_path);
				} else if(is_file($dir.'/'.$item)) {
					$subdir = str_replace('/wp-admin/admin-ajax.php', '', $_SERVER['REQUEST_URI']);
					$subdir = ltrim($subdir, '/');
					$clean_dir = str_replace($siteroot . '/', '', $dir.'/');
					$clean_dir = str_replace($subdir, '', $clean_dir);
					$filename = $dir .'/' . $item . "\n";
					$filename = str_replace('//', '/', $filename);
					$this->_prependExportLog('FILE TO ADD:');
					$this->_prependExportLog($filename);
					$this->add_file_to_list($filename, $file_list_path);
				} 
			}
		}
	}

	public function add_file_to_list( $filename, $file_list_path) {
		file_put_contents($file_list_path, $filename, FILE_APPEND | LOCK_EX);
	}

	public function prepare_file_list($export_target) {
        $this->_prependExportLog($export_target . ' EXPORT: Preparing list of files to export');

         $file_list_path = $this->getUploadsDirBaseDIR() . '/WP-STATIC-EXPORT-' . $export_target . '-FILES-TO-EXPORT';

		// zero file
        $f = @fopen($file_list_path, "r+");
        if ($f !== false) {
            ftruncate($f, 0);
            fclose($f);
        }

        $archiveDir = file_get_contents($this->getUploadsDirBaseDIR() . '/WP-STATIC-CURRENT-ARCHIVE');
        $archiveName = rtrim($archiveDir, '/');
        $siteroot = $archiveName . '/';

        $this->recursively_scan_dir($siteroot, $siteroot, $file_list_path);
        $this->_prependExportLog('GENERIC EXPORT: File list prepared');
	}

    public function s3_prepare_export() {
        $this->_prependExportLog('S3 EXPORT: preparing export...');
		$this->prepare_file_list('S3');

        echo 'SUCCESS';
    }

	public function s3_put_object($S3, $Bucket, $Key, $Data, $ACL, $ContentType = "text/plain") {
		try {
			$Model = $S3->PutObject(array('Bucket'      => $Bucket,
						'Key'         => $Key,
						'Body'        => $Data,
						'ACL'         => $ACL,
						'ContentType' => $ContentType));
			return true;
		}
		catch (Exception $e) {
			$pluginInstance->_prependExportLog('S3 EXPORT: following error returned from Dropbox:');
			$pluginInstance->_prependExportLog($e);
			throw new Exception($e);
		}
	}

	// TODO: make this a generic func, calling vendor specific files
    public function s3_transfer_files() {
        $this->_prependExportLog('S3 EXPORT: Transferring files...');
        $archiveDir = file_get_contents($this->getUploadsDirBaseDIR() . '/WP-STATIC-CURRENT-ARCHIVE');
        $archiveName = rtrim($archiveDir, '/');
        $siteroot = $archiveName . '/';
        $file_list_path = $this->getUploadsDirBaseDIR() . '/WP-STATIC-EXPORT-S3-FILES-TO-EXPORT';
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
        $this->_prependExportLog('S3 EXPORT: transferring ' . 
            basename($filename) . ' TO ' . $target_path);
       
		// do the vendor specific export:
		require_once(__DIR__.'/aws/aws-autoloader.php');
        require_once(__DIR__.'/StaticHtmlOutput/MimeTypes.php');

		# goes in transfer step
        $S3 = Aws\S3\S3Client::factory(array(
            'version'=> '2006-03-01',
            'key'    => filter_input(INPUT_POST, 's3Key'),
            'secret' => filter_input(INPUT_POST, 's3Secret'),
            'region' => filter_input(INPUT_POST, 's3Region')
            )
        );

        $Bucket = filter_input(INPUT_POST, 's3Bucket');

		$this->s3_put_object($S3, $Bucket, $target_path, $file_body, 'public-read', GuessMimeType($filename));

        $this->_prependExportLog('S3 EXPORT: ' . $filesRemaining . ' files remaining to transfer');

		if ( $filesRemaining > 0 ) {
			echo $filesRemaining;
		} else {
			echo 'SUCCESS';
		}
    }

	public function cloudfront_invalidate_all_items() {
        if(strlen(filter_input(INPUT_POST, 'cfDistributionId'))>12) {
			$this->_prependExportLog('CLOUDFRONT INVALIDATING CACHE...');
			$CF = Aws\CloudFront\CloudFrontClient::factory(array(
				'version'		=> '2016-01-28',
				'key'           => filter_input(INPUT_POST, 's3Key'),
				'secret'        => filter_input(INPUT_POST, 's3Secret'),
				));

			$result = $CF->createInvalidation(array(
				'DistributionId' => filter_input(INPUT_POST, 'cfDistributionId'),
				'Paths' => array (
					'Quantity' => 1, 'Items' => array('/*')),
					'CallerReference' => time()));
        }

		echo 'SUCCESS';
	}

	// TODO: this is being called twice, check export targets flow in FE/BE
	// TODO: convert this to an incremental export
    public function dropbox_do_export() {
        $archiveDir = file_get_contents($this->getUploadsDirBaseDIR() . '/WP-STATIC-CURRENT-ARCHIVE');
        $archiveName = rtrim($archiveDir, '/');
        $siteroot = $archiveName . '/';
        $dropboxAppKey = filter_input(INPUT_POST, 'dropboxAppKey');
        $dropboxAppSecret = filter_input(INPUT_POST, 'dropboxAppSecret');
        $dropboxAccessToken = filter_input(INPUT_POST, 'dropboxAccessToken');
        $dropboxFolder = filter_input(INPUT_POST, 'dropboxFolder');

        $this->_prependExportLog('DROPBOX EXPORT: Doing one synchronous export to your ' . $dropboxFolder . ' directory');

        $app = new DropboxApp($dropboxAppKey, $dropboxAppSecret, $dropboxAccessToken);
        $dbxClient = new Dropbox($app);

        function FolderToDropbox($dir, $dbxClient, $siteroot, $dropboxFolder, $pluginInstance){
            $files = scandir($dir);
            foreach($files as $item){
                if($item != '.' && $item != '..' && $item != '.git'){
                    if(is_dir($dir.'/'.$item)) {
                        FolderToDropbox($dir.'/'.$item, $dbxClient, $siteroot, $dropboxFolder, $pluginInstance);
                    } else if(is_file($dir.'/'.$item)) {
                        $clean_dir = str_replace($siteroot, '', $dir.'/'.$item);
                        $targetPath =  $dropboxFolder . $clean_dir;

						$pluginInstance->_prependExportLog('DROPBOX EXPORT: transferring:' . $targetPath);
                        try {
                            $dropboxFile = new DropboxFile($dir.'/'.$item);
                            $uploadedFile = $dbxClient->upload($dropboxFile, $targetPath, array('autorename' => false, 'mode' => 'overwrite'));
                        } catch (Exception $e) {
							$pluginInstance->_prependExportLog('DROPBOX EXPORT: following error returned from Dropbox:');
							$pluginInstance->_prependExportLog($e);
                            throw new Exception($e);

                        }
                    } 
                }
            }

        }

        FolderToDropbox($siteroot, $dbxClient, $siteroot, $dropboxFolder, $this);

		echo 'SUCCESS';
    }

    public function github_prepare_export () {
        // empty the list of GH export files in preparation
		$_SERVER['githubFilesToExport'] = $this->getUploadsDirBaseDIR() . '/WP-STATIC-EXPORT-GITHUB-FILES-TO-EXPORT';
        $f = @fopen($_SERVER['githubFilesToExport'], "r+");
        if ($f !== false) {
            ftruncate($f, 0);
            fclose($f);
        }

        $githubGlobHashesAndPaths = $this->getUploadsDirBaseDIR() . '/WP-STATIC-EXPORT-GITHUB-GLOBS-PATHS';
        $f = @fopen($githubGlobHashesAndPaths, "r+");
        if ($f !== false) {
            ftruncate($f, 0);
            fclose($f);
        }
            
        // optional path within GH repo
        $githubPath = filter_input(INPUT_POST, 'githubPath');

        $archiveDir = file_get_contents($this->getUploadsDirBaseDIR() . '/WP-STATIC-CURRENT-ARCHIVE');
        $archiveName = rtrim($archiveDir, '/');

        $siteroot = $archiveName . '/';

        function FolderToGithub($dir, $siteroot, $githubPath){
            $files = scandir($dir);
            foreach($files as $item){
                if($item != '.' && $item != '..' && $item != '.git'){
                    if(is_dir($dir.'/'.$item)) {
                        FolderToGithub($dir.'/'.$item, $siteroot, $githubPath);
                    } else if(is_file($dir.'/'.$item)) {
                        $subdir = str_replace('/wp-admin/admin-ajax.php', '', $_SERVER['REQUEST_URI']);
                        $subdir = ltrim($subdir, '/');
                        $clean_dir = str_replace($siteroot . '/', '', $dir.'/'.$item);
                        $clean_dir = str_replace($subdir, '', $clean_dir);
                        $targetPath =  $githubPath . $clean_dir;
                        $targetPath = ltrim($targetPath, '/');
                        $githubExportLine = $dir .'/' . $item . ',' . $targetPath . "\n";
                        file_put_contents($_SERVER['githubFilesToExport'], $githubExportLine, FILE_APPEND | LOCK_EX);
                    } 
                }
            }
        }


        FolderToGithub($siteroot, $siteroot, $githubPath);

		echo 'SUCCESS';
    }

    public function netlify_do_export () {
        $this->_prependExportLog('NETLIFY EXPORT: starting to deploy ZIP file');
        // will exclude the siteroot when copying
        $archiveDir = file_get_contents($this->getUploadsDirBaseDIR() . '/WP-STATIC-CURRENT-ARCHIVE');
        $archiveName = rtrim($archiveDir, '/');
        $siteroot = $archiveName . '/';
        $netlifySiteID = filter_input(INPUT_POST, 'netlifySiteID');
        $netlifyPersonalAccessToken = filter_input(INPUT_POST, 'netlifyPersonalAccessToken');

        $client = new Client(array(
                // Base URI is used with relative requests
                'base_uri' => 'https://api.netlify.com'
        ));	

        try {
            $response = $client->request('POST', '/api/v1/sites/' . $netlifySiteID . '.netlify.com/deploys', array(
                    'headers'  => array(
                        'Content-Type' => 'application/zip',
                        'Authorization' => 'Bearer ' . $netlifyPersonalAccessToken
                    ),
                    'body' => fopen($archiveName . '.zip', 'rb')
            ));
        } catch (Exception $e) {
            file_put_contents($_SERVER['exportLog'], $e , FILE_APPEND | LOCK_EX);
            throw new Exception($e);
        }
    
        //error_log($response->getStatusCode(), 0);
        //error_log(print_r($response, true), 0);
    }

    public function doExportWithoutGUI() {
        // parse options hash
        // TODO: DRY this up by adding as instance var


        // start export, including build initial file list
        $this->start_export(true);

        // do the crawl
        $this->crawl_site(true);

        // create zip
        $this->create_zip();
        

        // do any exports
    }

	
	
    public function post_process_archive_dir() {
        $this->_prependExportLog('POST PROCESSING ARCHIVE DIR: ...');
		//TODO: rm symlink if no folder exists
        $archiveDir = untrailingslashit(file_get_contents($this->getUploadsDirBaseDIR() . '/WP-STATIC-CURRENT-ARCHIVE'));

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
		$updated_uploads_dir =  str_replace(get_home_path(), '', $default_upload_dir['basedir']);
		
		$updated_uploads_dir =  str_replace('wp-content/', '', $updated_uploads_dir);
		$updated_uploads_dir = $new_wp_content . '/' . $updated_uploads_dir;
		$new_uploads_dir = $new_wp_content . '/' . filter_input(INPUT_POST, 'rewriteUPLOADS');


		$updated_theme_root = str_replace(get_home_path(), '/', get_theme_root());
		$updated_theme_root = $new_wp_content . str_replace('wp-content', '/', $updated_theme_root);

		$updated_theme_dir = $new_theme_root . '/' . basename(get_template_directory_uri());
		$updated_theme_dir = str_replace('\/\/', '', $updated_theme_dir);

		// rewrite plugins dir
		$updated_plugins_dir = str_replace(get_home_path(), '/', WP_PLUGIN_DIR);
		$updated_plugins_dir = str_replace('wp-content/', '', $updated_plugins_dir);
		$updated_plugins_dir = $new_wp_content . $updated_plugins_dir;
		$new_plugins_dir = $new_wp_content . '/' . filter_input(INPUT_POST, 'rewritePLUGINDIR');

		// rewrite wp-includes  dir
		$original_wp_includes = $archiveDir . '/' . WPINC;
		$new_wp_includes = $archiveDir . '/' . filter_input(INPUT_POST, 'rewriteWPINC');


		rename($original_wp_content, $new_wp_content);
		rename($updated_uploads_dir, $new_uploads_dir);
		rename($updated_theme_root, $new_theme_root);
		rename($updated_theme_dir, $new_theme_dir);
		rename($updated_plugins_dir, $new_plugins_dir);
		rename($original_wp_includes, $new_wp_includes);

		// rm other left over WP identifying files
		unlink($archiveDir . '/xmlrpc.php');
		unlink($archiveDir . '/wp-login.html');

		// TODO: remove all text files from theme dir 

		echo 'SUCCESS';
	}

    public function post_export_teardown() {
        $this->_prependExportLog('POST EXPORT CLEANUP: starting...');
		//TODO: rm symlink if no folder exists
        $archiveDir = file_get_contents($this->getUploadsDirBaseDIR() . '/WP-STATIC-CURRENT-ARCHIVE');
        
		$retainStaticFiles = filter_input(INPUT_POST, 'retainStaticFiles');
		$retainZipFile = filter_input(INPUT_POST, 'retainZipFile');

        // Remove temporary files unless user requested to keep or needed for FTP transfer
        if ($retainStaticFiles != 1) {
			$this->_prependExportLog('POST EXPORT CLEANUP: removing dir: ' . $archiveDir);
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
			$this->_prependExportLog('POST EXPORT CLEANUP: removing zip: ' . $zipFile);
			unlink($zipFile);
		}

		$this->_prependExportLog('POST EXPORT CLEANUP: complete');

		echo 'SUCCESS';
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

	protected function _getListOfLocalFilesByUrl(array $urls) {
		$files = array();

		foreach ($urls as $url) {
			$directory = str_replace(home_url('/'), ABSPATH, $url);

			if (stripos($url, home_url('/')) === 0 && is_dir($directory)) {
				$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory), RecursiveDirectoryIterator::SKIP_DOTS);
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

    public function _prependExportLog($text) {
        $exportLog = $this->getUploadsDirBaseDIR() . '/WP-STATIC-EXPORT-LOG';
        
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

		//$this->_prependExportLog('urlInfo :' . $urlInfo['path']);
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
			$this->_prependExportLog('PREPARING URL: Invalid URL given, aborting');
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
			$this->_prependExportLog('SETTING EXTENSION TO HTML');
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
		
		$this->_prependExportLog('SAVING URL: ' . $urlInfo['path'] . ' to new path' . $fileName);
		// TODO: what was the 'F' check for?1? Comments exist for a reason
		if ($fileContents != '' && $fileContents != 'F') {
			file_put_contents($fileName, $fileContents);
		} else {
			$this->_prependExportLog('SAVING URL: UNABLE TO SAVE FOR SOME REASON');
			//error_log($fileName);
			//error_log('response body was empty');
		}
	}
}
