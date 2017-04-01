<?php
/**
 * @package WP Static HTML Output
 *
 * Copyright (c) 2011 Leon Stafford
 */

class StaticHtmlOutput {
	const VERSION = '1.9';
	const OPTIONS_KEY = 'wp-static-html-output-options';
	const HOOK = 'wp-static-html-output';
	
	protected static $_instance = null;
	
	protected $_options = null;
	
	protected $_view = null;
	
	protected $_exportLog = array();
	
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
		$uploadDir = wp_upload_dir();
		$uploadsFolderWritable = $uploadDir && is_writable($uploadDir['path']);
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
                ->assign('exportLog', $this->_exportLog)
                ->assign('staticExportSettings', $this->_options->getOption('static-export-settings'))
                ->assign('onceAction', self::HOOK . '-options')
                ->render();
		}
	}
	
	public function saveOptions() {
		if (!isset($_POST['action']) || 'generate' != $_POST['action']) {
			return;
		}
		
		if (!check_admin_referer(self::HOOK . '-options') || !current_user_can('manage_options')) {
            error_log('user didnt have permissions to change options');
			exit('You cannot change WP Static HTML Output Plugin options.');
		}
	
		$this->_options
			->setOption('static-export-settings', filter_input(INPUT_POST, 'staticExportSettings', FILTER_SANITIZE_URL))
			->save();

        $message = 'Options have been updated successfully.';

		$this->_view->setTemplate('message')
			->assign('message', $message)
			->render();
	}

	public function genArch() {
		$archiveUrl = $this->_generateArchive();
		
		if (is_wp_error($archiveUrl)) {
			$message = 'Error: ' . $archiveUrl->get_error_code;
		} else {
			$message = sprintf('Archive created successfully: <a href="%s">Download archive</a>', $archiveUrl);
			if ($this->_options->getOption('retainStaticFiles') == 1)
			{
				$message .= sprintf('<br />Static files retained at: %s/', str_replace(home_url(),'',substr($archiveUrl,0,-4)));
			}
		}
		
		$this->_view->setTemplate('message')
			->assign('message', $message)
            ->assign('exportLog', $this->_exportLog)
			->render();
    }
	
	protected function _generateArchive()
	{
		global $blog_id;
		set_time_limit(0);

		$uploadDir = wp_upload_dir();
		$exporter = wp_get_current_user();
		$archiveName = $uploadDir['path'] . '/' . self::HOOK . '-' . $blog_id . '-' . time() . '-' . $exporter->user_login;
		$archiveDir = $archiveName . '/';
		if (!file_exists($archiveDir))
		{
			wp_mkdir_p($archiveDir);
		}

		$baseUrl = untrailingslashit(home_url());
        $newBaseUrl = untrailingslashit(filter_input(INPUT_POST, 'baseUrl', FILTER_SANITIZE_URL));
		$urlsQueue = array_unique(array_merge(
			array(trailingslashit($baseUrl)),
			$this->_getListOfLocalFilesByUrl(array(get_template_directory_uri())),
			explode("\n", filter_input(INPUT_POST, 'additionalUrls'))
		));

		$this->_exportLog = array();

		while (count($urlsQueue))
		{
			$currentUrl = array_shift($urlsQueue);

			$urlResponse = new StaticHtmlOutput_UrlRequest($currentUrl, filter_input(INPUT_POST, 'cleanMeta'));

            if ($urlResponse->checkResponse() == 'FAIL') {
                error_log('Failed to get this file');
                error_log($currentUrl);
            } else {
                // Add current url to the list of processed urls
                $this->_exportLog[$currentUrl] = true;
            }

            // TODO: shifting this block into above conditional prevents index containing error
            //       but doesn't crawl all folders...
            //       alternatively, index.html contains 'F' from 'FAIL or 'Failed to get...'

            // TODO: this shouldnt be part of urlrequest, just general settings
            // add conditional logic here whether to do cleanup, vs in each request?
            $urlResponse->cleanup();

            // get all other urls from within this one and add to queue if not there
			foreach ($urlResponse->extractAllUrls($baseUrl) as $newUrl) {
				if (!isset($this->_exportLog[$newUrl]) && $newUrl != $currentUrl && !in_array($newUrl,$urlsQueue)) {
					//echo "Adding ".$newUrl." to the list<br />";
					$urlsQueue[] = $newUrl;
				}
			}
			
			$urlResponse->replaceBaseUlr($baseUrl, $newBaseUrl);
			$this->_saveUrlData($urlResponse, $archiveDir);
		}
		
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
		rename($tempZip, $archiveName . '.zip'); 

		if(filter_input(INPUT_POST, 'sendViaFTP') == 1) {		
            require_once(__DIR__.'/FTP/FtpClient.php');
            require_once(__DIR__.'/FTP/FtpException.php');
            require_once(__DIR__.'/FTP/FtpWrapper.php');

            $ftp = new \FtpClient\FtpClient();
            $ftp->connect(filter_input(INPUT_POST, 'ftpServer'));
            $ftp->login(filter_input(INPUT_POST, 'ftpUsername'), filter_input(INPUT_POST, 'ftpPassword'));
            $ftp->pasv(true);

            if (!$ftp->isdir(filter_input(INPUT_POST, 'ftpRemotePath'))) {
                $ftp->mkdir(filter_input(INPUT_POST, 'ftpRemotePath'), true);
            }

            $ftp->putAll($archiveName . '/', filter_input(INPUT_POST, 'ftpRemotePath'));
            
            // TODO: error handling when not connected/unable to put, etc
			unset($ftp);
		}

		if(filter_input(INPUT_POST, 'sendViaS3') == 1) {		
            require_once(__DIR__.'/aws/aws-autoloader.php');

            $ACL = Aws\S3\Enum\CannedAcl::PRIVATE_ACCESS;

            function UploadObject($S3, $Bucket, $Key, $Data, $ACL, $ContentType = "text/plain") {
                $Try   = 1;
                $Sleep = 1;
                do {
                    try {
                        $Model = $S3->PutObject(array('Bucket'      => $Bucket,
                            'Key'         => $Key,
                            'Body'        => $Data,
                            'ACL'         => $ACL,
                            'ContentType' => $ContentType));
                        return true;
                    }
                    catch (Exception $e) {
                        error_log($e->getMessage());
                        print("Retry, sleep ${Sleep} - " . $e->getMessage() . "\n");
                        sleep($Sleep);
                        $Sleep *= 2;
                    }
                }
                while (++$Try < 2);
                return false;
            }

            $siteroot = $archiveName . '/';

            function UploadDirectory($S3, $Bucket, $dir, $siteroot) {
                $files = scandir($dir);
                foreach($files as $item){
                    if($item != '.' && $item != '..'){
                        $ContentType = GuessType($item);
                        if(is_dir($dir.'/'.$item)) {
                            UploadDirectory($S3, $Bucket, $dir.'/'.$item, $siteroot);
                        } else if(is_file($dir.'/'.$item)) {
                            $clean_dir = str_replace($siteroot, '', $dir.'/'.$item);

                            $targetPath = $clean_dir;
                            $f = file_get_contents($dir.'/'.$item);

                            if($targetPath == '/index.html') {
                            }
                            
                            UploadObject($S3, $Bucket, $targetPath, $f, Aws\S3\Enum\CannedAcl::PUBLIC_READ, $ContentType);
                        } 
                    }
                }
            }

            /*
             * GuessType -
             *
             *  Make a simple guess as to the file's content type,
             *  and return a MIME type.
             */
            function GuessType($File) {
                $Info = pathinfo($File, PATHINFO_EXTENSION);
                switch (strtolower($Info))
                {
                    case "jpg":
                    case "jpeg":
                        return "image/jpeg";
                    case "png":
                        return "image/png";
                    case "gif":
                        return "image/gif";
                    case "htm":
                    case "html":
                        return "text/html";
                    case "css":
                        return "text/css";
                    case "txt":
                        return "text/plain";
                    case "php":
                        return "text/plain";
                    case "":
                        return "directory";
                    default:
                        return "text/plain";
                }
            }

            $S3 = Aws\S3\S3Client::factory(array(
                'key'    => filter_input(INPUT_POST, 's3Key'),
                'secret' => filter_input(INPUT_POST, 's3Secret'),
                'region' => filter_input(INPUT_POST, 's3Region')
                )
            );

            $Directory = $archiveName . '/';
            $Bucket = filter_input(INPUT_POST, 's3Bucket');

            // Upload the directory to the bucket
            UploadDirectory($S3, $Bucket, $siteroot, $siteroot);

        }

		if(filter_input(INPUT_POST, 'sendViaDropbox') == 1) {
            require_once(__DIR__.'/Dropbox/autoload.php');

            // will exclude the siteroot when copying
            $siteroot = $archiveName . '/';
            $dropboxAccessToken = filter_input(INPUT_POST, 'dropboxAccessToken');
            $dropboxFolder = filter_input(INPUT_POST, 'dropboxFolder');

            $dbxClient = new Dropbox\Client($dropboxAccessToken, "PHP-Example/1.0");

            function FolderToDropbox($dir, $dbxClient, $siteroot, $dropboxFolder){
                $files = scandir($dir);
                foreach($files as $item){
                    if($item != '.' && $item != '..'){
                        if(is_dir($dir.'/'.$item)) {
                            FolderToDropbox($dir.'/'.$item, $dbxClient, $siteroot, $dropboxFolder);
                        } else if(is_file($dir.'/'.$item)) {
                            $clean_dir = str_replace($siteroot, '', $dir.'/'.$item);

                            $targetPath = '/'.$dropboxFolder.'/'.$clean_dir;
                            $f = fopen($dir.'/'.$item, "rb");
                    
                            $result = $dbxClient->uploadFile($targetPath, Dropbox\WriteMode::add(), $f);
                            fclose($f);
                        } 
                    }
                }
            }

            FolderToDropbox($siteroot, $dbxClient, $siteroot, $dropboxFolder);
        }



        // TODO: keep copy of last export folder for incremental addition

		// Remove temporary files unless user requested to keep or needed for FTP transfer
		if ($this->_options->getOption('retainStaticFiles') != 1)		{
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
	
		return str_replace(ABSPATH, trailingslashit(home_url()), $archiveName . '.zip');
	}
	
	protected function _getListOfLocalFilesByUrl(array $urls)
	{
		$files = array();

		foreach ($urls as $url) {
			$directory = str_replace(home_url('/'), ABSPATH, $url);

            // checking if url contains the WP site url at first position and is a directory
			if (stripos($url, home_url('/')) === 0 && is_dir($directory)) {
				$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
				foreach ($iterator as $fileName => $fileObject) {

					if (is_file($fileName)) {
						$pathinfo = pathinfo($fileName);
						if (isset($pathinfo['extension']) && !in_array($pathinfo['extension'], array('php', 'phtml', 'tpl'))) {
							array_push($files, home_url(str_replace(ABSPATH, '', $fileName)));
						}
					}
				}
			} else {
                // if is not empty, add full path to $files list
                if ($url != '') {
                    array_push($files, $url);
                }
            }
		}

		return $files;
	}
	
	protected function _saveUrlData(StaticHtmlOutput_UrlRequest $url, $archiveDir) {
		$urlInfo = parse_url($url->getUrl());
		$pathInfo = pathinfo(isset($urlInfo['path']) && $urlInfo['path'] != '/' ? $urlInfo['path'] : 'index.html');
		
		// Prepare file directory and create it if it doesn't exist
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
        // TODO: fix for unclear issue on PHP5.3
        if ($fileContents != '' && $fileContents != 'F') {
            file_put_contents($fileName, $fileContents);
        } else {
            error_log($filename);
            error_log('response body was empty');
        }
	}
}
