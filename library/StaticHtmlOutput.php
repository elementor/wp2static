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
	const VERSION = '2.2';
	const OPTIONS_KEY = 'wp-static-html-output-options';
	const HOOK = 'wp-static-html-output';

	protected static $_instance = null;
	protected $_options = null;
	protected $_view = null;
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

			$this->_view
				->setTemplate('options-page')
				->assign('staticExportSettings', $this->_options->getOption('static-export-settings'))
				->assign('wpUploadsDir', wp_upload_dir()['baseurl'])
				->assign('wpPluginDir', plugins_url('/', __FILE__))
				->assign('onceAction', self::HOOK . '-options')
				->render();
		}
	}

    public function saveExportSettings () {
		if (!check_admin_referer(self::HOOK . '-options') || !current_user_can('manage_options')) {
			error_log('user didnt have permissions to change options');
			exit('You cannot change WP Static HTML Output Plugin options.');
		}

		$this->_options
			->setOption('static-export-settings', filter_input(INPUT_POST, 'staticExportSettings', FILTER_SANITIZE_URL))
			->save();
    }

	public function get_write_directory(){
		$outputDir = filter_input(INPUT_POST, 'outputDirectory');

		if ( $outputDir && is_dir($outputDir)) {
			if( is_writable( $outputDir ) ){
				return $outputDir;
			}
		}

		$wp_upload_dir = wp_upload_dir();
		return $wp_upload_dir['path'];
	}

    public function progressThroughExportTargets() {
		$wpUploadsDir = wp_upload_dir()['basedir'];
        $exportTargetsFile = $wpUploadsDir . '/WP-STATIC-EXPORT-TARGETS';

        // remove first line from file (disabled while testing)
        $exportTargets = file($exportTargetsFile, FILE_IGNORE_NEW_LINES);
        $filesRemaining = count($exportTargets) - 1;
        $first_line = array_shift($exportTargets);
        file_put_contents($exportTargetsFile, implode("\r\n", $exportTargets));

        
        $this->_prependExportLog('PROGRESS: Starting export type:' . $target . PHP_EOL);
    }

    public function githubFinaliseExport() {
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
        $treeContents = [];
		$wpUploadsDir = wp_upload_dir()['basedir'];
        $githubGlobHashesAndPaths = $wpUploadsDir . '/WP-STATIC-EXPORT-GITHUB-GLOBS-PATHS';
        $contents = file($githubGlobHashesAndPaths);

        foreach($contents as $line) {
            list($blobHash, $targetPath) = explode(',', $line);

            $treeContents[] = [
                'path' => $targetPath,
                'mode' => '100644',
                'type' => 'blob',
                'sha' => $blobHash
            ];
        }

        $treeData = [
            'base_tree' => $treeSHA,
            'tree' => $treeContents
        ];

        $this->_prependExportLog('GITHUB: Creating tree ...' . PHP_EOL);
        $this->_prependExportLog('GITHUB: tree data: '. PHP_EOL);
        $this->_prependExportLog(print_r($treeData, true) . PHP_EOL);
        $newTree = $client->api('gitData')->trees()->create($githubUser, $githubRepo, $treeData);
        $this->_prependExportLog('GITHUB: Tree created');
        
        $commitData = ['message' => 'WP Static HTML Export Plugin on ' . date("Y-m-d h:i:s"), 'tree' => $newTree['sha'], 'parents' => [$commitSHA]];
        $this->_prependExportLog('GITHUB: Creating commit ...');
        $commit = $client->api('gitData')->commits()->create($githubUser, $githubRepo, $commitData);
        $this->_prependExportLog('GITHUB: Updating head to reference commit ...');
        $referenceData = ['sha' => $commit['sha'], 'force' => true ]; //Force is default false
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

    }

	public function githubUploadBlobs() {
        $client = new \Github\Client();
        $githubRepo = filter_input(INPUT_POST, 'githubRepo');
        $githubPersonalAccessToken = filter_input(INPUT_POST, 'githubPersonalAccessToken');
        list($githubUser, $githubRepo) = explode('/', $githubRepo);

        $client->authenticate($githubPersonalAccessToken, Github\Client::AUTH_HTTP_TOKEN);

		$wpUploadsDir = wp_upload_dir()['basedir'];
        $_SERVER['githubFilesToExport'] = $wpUploadsDir . '/WP-STATIC-EXPORT-GITHUB-FILES-TO-EXPORT';

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
                ['content' => $encodedFile, 'encoding' => 'base64']
                ); # utf-8 or base64

		$wpUploadsDir = wp_upload_dir()['basedir'];
        $githubGlobHashesAndPaths = $wpUploadsDir . '/WP-STATIC-EXPORT-GITHUB-GLOBS-PATHS';

        $globHashPathLine = $globHash['sha'] . ',' . $targetPath;
        file_put_contents($githubGlobHashesAndPaths, $globHashPathLine, FILE_APPEND | LOCK_EX);


        $this->_prependExportLog('GITHUB: ' . $filesRemaining . ' blobs remaining to create');
        
        echo $filesRemaining;
    }

	public function startExport($viaCLI = false) {
        // prepare export targets
		$wpUploadsDir = wp_upload_dir()['basedir'];
        $exportTargetsFile = $wpUploadsDir . '/WP-STATIC-EXPORT-TARGETS';
        unlink($wpUploadsDir . '/WP-STATIC-EXPORT-TARGETS');

        // set options from GUI or override via CLI
        $sendViaGithub = filter_input(INPUT_POST, 'sendViaGithub');
        $sendViaFTP = filter_input(INPUT_POST, 'sendViaFTP');
        $sendViaS3 = filter_input(INPUT_POST, 'sendViaS3');
        $sendViaNetlify = filter_input(INPUT_POST, 'sendViaNetlify');
        $sendViaDropbox = filter_input(INPUT_POST, 'sendViaDropbox');

        if ($viaCLI) {
            error_log('DOING EXPORT VIA CLI');
            parse_str($this->_options->getOption('static-export-settings'), $pluginOptions);

            $sendViaGithub = $pluginOptions['sendViaGithub'];
            $sendViaFTP = $pluginOptions['sendViaFTP'];
            $sendViaS3 = $pluginOptions['sendViaS3'];
            $sendViaNetlify = $pluginOptions['sendViaNetlify'];
            error_log($sendViaNetlify);
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
            $this->_prependExportLog('crawl list function complete. client should trigger crawl now');


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

        error_log('baseurl from options');
        error_log($newBaseURL);

		$uploadDir = $this->get_write_directory();
		$exporter = wp_get_current_user();
		$wpUploadsDir = wp_upload_dir()['basedir'];
		$_SERVER['urlsQueue'] = $wpUploadsDir . '/WP-STATIC-INITIAL-CRAWL-LIST';
		$_SERVER['currentArchive'] = $wpUploadsDir . '/WP-STATIC-CURRENT-ARCHIVE';
		$_SERVER['exportLog'] = $wpUploadsDir . '/WP-STATIC-EXPORT-LOG';
		$_SERVER['githubFilesToExport'] = $wpUploadsDir . '/WP-STATIC-EXPORT-GITHUB-FILES-TO-EXPORT';
		$archiveName = $uploadDir . '/' . self::HOOK . '-' . $blog_id . '-' . time() . '-' . $exporter->user_login;
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

        $this->_prependExportLog('INITIAL CRAWL LIST CONTAINS ' . count($urlsQueue) . ' FILES');

        $str = implode("\n", $urlsQueue);
        file_put_contents($_SERVER['urlsQueue'], $str);
        file_put_contents($wpUploadsDir . '/WP-STATIC-CRAWLED-LINKS', '');

        return 'initial crawl list ready';
    }

	public function crawlABitMore($viaCLI = false) {
        error_log('DOING A BIT OF CRAWLING');
		$wpUploadsDir = wp_upload_dir()['basedir'];
		$initial_crawl_list_file = $wpUploadsDir . '/WP-STATIC-INITIAL-CRAWL-LIST';
        $crawled_links_file = $wpUploadsDir . '/WP-STATIC-CRAWLED-LINKS';
        $initial_crawl_list = file($initial_crawl_list_file, FILE_IGNORE_NEW_LINES);
        $crawled_links = file($crawled_links_file, FILE_IGNORE_NEW_LINES);

        $first_line = array_shift($initial_crawl_list);
        file_put_contents($initial_crawl_list_file, implode("\r\n", $initial_crawl_list));
        $currentUrl = $first_line;
        $this->_prependExportLog('CRAWLING URL: ' . $currentUrl);

        if (empty($currentUrl)){
            $this->_prependExportLog('EMPTY FILE ENCOUNTERED');
        }

        $urlResponse = new StaticHtmlOutput_UrlRequest($currentUrl, filter_input(INPUT_POST, 'cleanMeta'));

        if ($urlResponse->checkResponse() == 'FAIL') {
            $this->_prependExportLog('FAILED TO CRAWL FILE: ' . $currentUrl);
        } else {
            file_put_contents($crawled_links_file, $currentUrl . PHP_EOL, FILE_APPEND | LOCK_EX);
            $this->_prependExportLog('CRAWLED FILE: ' . $currentUrl);
        }

        $baseUrl = untrailingslashit(home_url());
        $newBaseUrl = untrailingslashit(filter_input(INPUT_POST, 'baseUrl', FILTER_SANITIZE_URL));
        $urlResponse->cleanup();
        $urlResponse->replaceBaseUrl($baseUrl, $newBaseUrl);
        $wpUploadsDir = wp_upload_dir()['basedir'];
        $archiveDir = file_get_contents($wpUploadsDir . '/WP-STATIC-CURRENT-ARCHIVE');
        $this->_saveUrlData($urlResponse, $archiveDir);

        foreach ($urlResponse->extractAllUrls($baseUrl) as $newUrl) {
            if ($newUrl != $currentUrl && !in_array($newUrl, $crawled_links) && !in_array($newUrl, $initial_crawl_list)) {
                $this->_prependExportLog('DISCOVERED NEW FILE: ' . $newUrl);
                
                $urlResponse = new StaticHtmlOutput_UrlRequest($newUrl, filter_input(INPUT_POST, 'cleanMeta'));

                if ($urlResponse->checkResponse() == 'FAIL') {
                    $this->_prependExportLog('FAILED TO CRAWL FILE: ' . $newUrl);
                } else {
                    file_put_contents($crawled_links_file, $newUrl . PHP_EOL, FILE_APPEND | LOCK_EX);
                    $crawled_links[] = $newUrl;
                    $this->_prependExportLog('CRAWLED FILE: ' . $newUrl);
                }

                $urlResponse->cleanup();
                $urlResponse->replaceBaseUrl($baseUrl, $newBaseUrl);
                $wpUploadsDir = wp_upload_dir()['basedir'];
                $archiveDir = file_get_contents($wpUploadsDir . '/WP-STATIC-CURRENT-ARCHIVE');
                $this->_saveUrlData($urlResponse, $archiveDir);
            } 
        }
        
        // loop for CLI
        if ($viaCLI) {
            $this->crawlTheWordPressSite(true);
        }
    }

	public function crawlTheWordPressSite($viaCLI = false) {
		$wpUploadsDir = wp_upload_dir()['basedir'];
		$initial_crawl_list_file = $wpUploadsDir . '/WP-STATIC-INITIAL-CRAWL-LIST';
        $initial_crawl_list = file($initial_crawl_list_file, FILE_IGNORE_NEW_LINES);

        // NOTE: via GUI hits this function repeatedly,
        // viaCLI, we want to do it in one hit
        
        if (!empty($initial_crawl_list)) {
            $this->crawlABitMore($viaCLI);
        } else {
            $this->_prependExportLog('CRAWLING COMPLETED');
            error_log('CRAWLING COMPLETED');
            echo 'CRAWLING COMPLETED';
        }
    }

    public function createTheArchive() {
        $this->_prependExportLog('CREATING ZIP FILE...');
        $wpUploadsDir = wp_upload_dir()['basedir'];
        $archiveDir = file_get_contents($wpUploadsDir . '/WP-STATIC-CURRENT-ARCHIVE');
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

        echo $publicDownloadableZip;
    }

    public function ftpPrepareExport() {
        $this->_prependExportLog('FTP EXPORT: Checking credentials..:');

        require_once(__DIR__.'/FTP/FtpClient.php');
        require_once(__DIR__.'/FTP/FtpException.php');
        require_once(__DIR__.'/FTP/FtpWrapper.php');

        $ftp = new \FtpClient\FtpClient();
        
        $ftp->connect(filter_input(INPUT_POST, 'ftpServer'));
        $ftp->login(filter_input(INPUT_POST, 'ftpUsername'), filter_input(INPUT_POST, 'ftpPassword'));

        if ($ftp->isdir(filter_input(INPUT_POST, 'ftpRemotePath'))) {
            $this->_prependExportLog('FTP EXPORT: Remote dir exists');
        } else {
            $this->_prependExportLog('FTP EXPORT: Creating remote dir');
            $ftp->mkdir(filter_input(INPUT_POST, 'ftpRemotePath'), true);
        }

        unset($ftp);

        $this->_prependExportLog('FTP EXPORT: Preparing list of files to transfer');

        // prepare file list
        $wpUploadsDir = wp_upload_dir()['basedir'];
        $_SERVER['ftpFilesToExport'] = $wpUploadsDir . '/WP-STATIC-EXPORT-FTP-FILES-TO-EXPORT';

        $f = @fopen($_SERVER['ftpFilesToExport'], "r+");
        if ($f !== false) {
            ftruncate($f, 0);
            fclose($f);
        }

        $ftpTargetPath = filter_input(INPUT_POST, 'ftpRemotePath');
        $archiveDir = file_get_contents($wpUploadsDir . '/WP-STATIC-CURRENT-ARCHIVE');
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

    public function ftpTransferFiles($batch_size = 5) {
        $wpUploadsDir = wp_upload_dir()['basedir'];
        $archiveDir = file_get_contents($wpUploadsDir . '/WP-STATIC-CURRENT-ARCHIVE');
        $archiveName = rtrim($archiveDir, '/');

        require_once(__DIR__.'/FTP/FtpClient.php');
        require_once(__DIR__.'/FTP/FtpException.php');
        require_once(__DIR__.'/FTP/FtpWrapper.php');

        $ftp = new \FtpClient\FtpClient();
        
        $ftp->connect(filter_input(INPUT_POST, 'ftpServer'));
        $ftp->login(filter_input(INPUT_POST, 'ftpUsername'), filter_input(INPUT_POST, 'ftpPassword'));

        $ftp->pasv(true);
        
        $_SERVER['ftpFilesToExport'] = $wpUploadsDir . '/WP-STATIC-EXPORT-FTP-FILES-TO-EXPORT';

        // grab first line from filelist
        $ftpFilesToExport = $_SERVER['ftpFilesToExport'];
        $f = fopen($ftpFilesToExport, 'r');
        $line = fgets($f);
        fclose($f);

        // TODO: look at these funcs above and below, seems redundant...

        // TODO: refactor like the crawling function, first_line unused
        $contents = file($ftpFilesToExport, FILE_IGNORE_NEW_LINES);
        $filesRemaining = count($contents) - 1;

        error_log($filesRemaining);

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
            error_log(print_r($mkdir_result, true));
        }

        $ftp->chdir($targetPath);
        $ftp->putFromPath($fileToTransfer);

        $this->_prependExportLog('FTP EXPORT: ' . $filesRemaining . ' files remaining to transfer');

        // TODO: error handling when not connected/unable to put, etc
        unset($ftp);

        echo $filesRemaining;
    }

    public function s3Export() {
        require_once(__DIR__.'/aws/aws-autoloader.php');
        require_once(__DIR__.'/StaticHtmlOutput/MimeTypes.php');

        function UploadObject($S3, $Bucket, $Key, $Data, $ACL, $ContentType = "text/plain") {
            try {
                $Model = $S3->PutObject(array('Bucket'      => $Bucket,
                            'Key'         => $Key,
                            'Body'        => $Data,
                            'ACL'         => $ACL,
                            'ContentType' => $ContentType));
                return true;
            }
            catch (Exception $e) {
                file_put_contents($_SERVER['exportLog'], $e , FILE_APPEND | LOCK_EX);
                throw new Exception($e);
            }
        }

        function UploadDirectory($S3, $Bucket, $dir, $siteroot) {
            $files = scandir($dir);
            foreach($files as $item){
                if($item != '.' && $item != '..'){
                    if(is_dir($dir.'/'.$item)) {
                        UploadDirectory($S3, $Bucket, $dir.'/'.$item, $siteroot);
                    } else if(is_file($dir.'/'.$item)) {
                        $clean_dir = str_replace($siteroot, '', $dir.'/'.$item);

                        $targetPath = $clean_dir;
                        $f = file_get_contents($dir.'/'.$item);

                        if($targetPath == '/index.html') {
                        }

                        UploadObject($S3, $Bucket, $targetPath, $f, 'public-read', GuessMimeType($item));
                    } 
                }
            }
        }

        $S3 = Aws\S3\S3Client::factory(array(
            'version'=> '2006-03-01',
            'key'    => filter_input(INPUT_POST, 's3Key'),
            'secret' => filter_input(INPUT_POST, 's3Secret'),
            'region' => filter_input(INPUT_POST, 's3Region')
            )
        );

        $Bucket = filter_input(INPUT_POST, 's3Bucket');

        UploadDirectory($S3, $Bucket, $archiveName, $archiveName.'/');

        if(strlen(filter_input(INPUT_POST, 'cfDistributionId'))>12) {
            $CF = Aws\CloudFront\CloudFrontClient::factory(array(
                'version'		=> '2016-01-28',
                'key'           => filter_input(INPUT_POST, 's3Key'),
                'secret'        => filter_input(INPUT_POST, 's3Secret'),
                )
            );
            $result = $CF->createInvalidation(array(
                'DistributionId' => filter_input(INPUT_POST, 'cfDistributionId'),
                'Paths' => array (
                    'Quantity' => 1, 'Items' => array('/*')),
                    'CallerReference' => time()
            ));
        }
    }

    public function dropboxExport() {
        $siteroot = $archiveName . '/';
        $dropboxAppKey = filter_input(INPUT_POST, 'dropboxAppKey');
        $dropboxAppSecret = filter_input(INPUT_POST, 'dropboxAppSecret');
        $dropboxAccessToken = filter_input(INPUT_POST, 'dropboxAccessToken');
        $dropboxFolder = filter_input(INPUT_POST, 'dropboxFolder');
        $app = new DropboxApp($dropboxAppKey, $dropboxAppSecret, $dropboxAccessToken);
        $dbxClient = new Dropbox($app);

        function FolderToDropbox($dir, $dbxClient, $siteroot, $dropboxFolder){
            $files = scandir($dir);
            foreach($files as $item){
                if($item != '.' && $item != '..'){
                    if(is_dir($dir.'/'.$item)) {
                        FolderToDropbox($dir.'/'.$item, $dbxClient, $siteroot, $dropboxFolder);
                    } else if(is_file($dir.'/'.$item)) {
                        $clean_dir = str_replace($siteroot, '', $dir.'/'.$item);
                        $targetPath =  $dropboxFolder . $clean_dir;

                        try {
                            $dropboxFile = new DropboxFile($dir.'/'.$item);
                            $uploadedFile = $dbxClient->upload($dropboxFile, $targetPath, ['autorename' => true]);
                        } catch (Exception $e) {
                            file_put_contents($_SERVER['exportLog'], $e , FILE_APPEND | LOCK_EX);
                            throw new Exception($e);

                        }
                    } 
                }
            }
        }

        FolderToDropbox($siteroot, $dbxClient, $siteroot, $dropboxFolder);
    }

    public function githubPrepareExport () {
        // empty the list of GH export files in preparation
        $wpUploadsDir = wp_upload_dir()['basedir'];
		$_SERVER['githubFilesToExport'] = $wpUploadsDir . '/WP-STATIC-EXPORT-GITHUB-FILES-TO-EXPORT';
        $f = @fopen($_SERVER['githubFilesToExport'], "r+");
        if ($f !== false) {
            ftruncate($f, 0);
            fclose($f);
        }

        $githubGlobHashesAndPaths = $wpUploadsDir . '/WP-STATIC-EXPORT-GITHUB-GLOBS-PATHS';
        $f = @fopen($githubGlobHashesAndPaths, "r+");
        if ($f !== false) {
            ftruncate($f, 0);
            fclose($f);
        }
            
        // optional path within GH repo
        $githubPath = filter_input(INPUT_POST, 'githubPath');

        $archiveDir = file_get_contents($wpUploadsDir . '/WP-STATIC-CURRENT-ARCHIVE');
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
    }

    public function netlifyExport () {
        // will exclude the siteroot when copying
        $siteroot = $archiveName . '/';
        $netlifySiteID = filter_input(INPUT_POST, 'netlifySiteID');
        $netlifyPersonalAccessToken = filter_input(INPUT_POST, 'netlifyPersonalAccessToken');


# get ZIP archive's path

# make Guzzle request to Netlify aka 
#	curl -H "Content-Type: application/zip" \
#	 -H "Authorization: Bearer my-api-access-token" \
#	 --data-binary "@website.zip" \
#	 https://api.netlify.com/api/v1/sites/mysite.netlify.com/deploys

        $client = new Client([
                // Base URI is used with relative requests
                'base_uri' => 'https://api.netlify.com'
        ]);	

        try {
            $response = $client->request('POST', '/api/v1/sites/' . $netlifySiteID . '.netlify.com/deploys', [
                    'headers'  => [
                        'Content-Type' => 'application/zip',
                        'Authorization' => 'Bearer ' . $netlifyPersonalAccessToken
                    ],
                    'body' => fopen($archiveName . '.zip', 'rb')
            ]);
        } catch (Exception $e) {
            file_put_contents($_SERVER['exportLog'], $e , FILE_APPEND | LOCK_EX);
            throw new Exception($e);
        }
    
        error_log($response->getStatusCode(), 0);
        error_log(print_r($response, true), 0);
    }

    public function doExportWithoutGUI() {
        // parse options hash
        // TODO: DRY this up by adding as instance var


        // start export, including build initial file list
        $this->startExport(true);

        $this-> crawlTheWordPressSite(true);

        // create zip

        // do any exports
    }

    public function cleanupAfterExports() {
        // TODO: need folder to do GH export, force keep for now

        // TODO: keep copy of last export folder for incremental addition

        $retainStaticFiles = filter_input(INPUT_POST, 'retainStaticFiles');

        // Remove temporary files unless user requested to keep or needed for FTP transfer
        //if ($retainStaticFiles != 1)		{
        if ($retainStaticFiles == 'banana')		{
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

        $this->_prependExportLog('EXPORT CLEANUP COMPLETE' . PHP_EOL);
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

    protected function _prependExportLog($text) {
		$wpUploadsDir = wp_upload_dir()['basedir'];
		$exportLog = $wpUploadsDir . '/WP-STATIC-EXPORT-LOG';
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
		$pathInfo = pathinfo(isset($urlInfo['path']) && $urlInfo['path'] != '/' ? $urlInfo['path'] : 'index.html');

		$fileDir = $archiveDir . (isset($pathInfo['dirname']) ? $pathInfo['dirname'] : '');

		if (empty($pathInfo['extension']) && $pathInfo['basename'] == $pathInfo['filename']) {
			$fileDir .= '/' . $pathInfo['basename'];
			$pathInfo['filename'] = 'index';
		}

		if (!file_exists($fileDir)) {
			wp_mkdir_p($fileDir);
		}

		$fileExtension = ($url->isHtml() || !isset($pathInfo['extension']) ? 'html' : $pathInfo['extension']);
		$fileName = $fileDir . '/' . $pathInfo['filename'] . '.' . $fileExtension;
		$fileContents = $url->getResponseBody();
		if ($fileContents != '' && $fileContents != 'F') {
			file_put_contents($fileName, $fileContents);
		} else {
			error_log($fileName);
			error_log('response body was empty');
		}
	}
}
