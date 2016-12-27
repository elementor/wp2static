<?php
/**
 * @package WP Static HTML Output
 *
 * Copyright (c) 2011 Leon Stafford
 */

/**
 * WP Static HTML Output Plugin
 */
class StaticHtmlOutput
{
	/**
	 * Plugin version
	 */
	const VERSION = '1.1.2';
	
	/**
	 * The lookup key used to locate the options record in the wp_options table
	 */
	const OPTIONS_KEY = 'wp-static-html-output-options';
	
	/**
	 * The hook used in all actions and filters
	 */
	const HOOK = 'wp-static-html-output';
	
	/**
	 * Singleton instance
	 * @var StaticHtmlOutput
	 */
	protected static $_instance = null;
	
	/**
	 * An instance of the options structure containing all options for this plugin
	 * @var StaticHtmlOutput_Options
	 */
	protected $_options = null;
	
	/**
	 * View object
	 * @var StaticHtmlOutput_View
	 */
	protected $_view = null;
	
	/**
	 * Export log (list of processed urls)
	 * @var array
	 */
	protected $_exportLog = array();
	
	/**
	 * Singleton pattern implementation makes "new" unavailable
	 * @return void
	 */
	protected function __construct()
	{}
	
	/**
	 * Singleton pattern implementation makes "clone" unavailable
	 * @return void
	 */
	protected function __clone()
	{}
	
	/**
	 * Returns an instance of WP Static HTML Output Plugin
	 * Singleton pattern implementation
	 * @return StaticHtmlOutput
	 */
	public static function getInstance()
	{
		if (null === self::$_instance)
		{
			self::$_instance = new self();
			self::$_instance->_options = new StaticHtmlOutput_Options(self::OPTIONS_KEY);
			self::$_instance->_view = new StaticHtmlOutput_View();
		}
		
		return self::$_instance;
	}
	
	/**
	 * Initializes singleton instance and assigns hooks callbacks
	 * @param string $bootstrapFile
	 * @return StaticHtmlOutput
	 */
	public static function init($bootstrapFile)
	{
		$instance = self::getInstance();
		
		// Activation
		register_activation_hook($bootstrapFile, array($instance, 'activate'));
		
		// Backend hooks and action callbacks
		if (is_admin())
		{
			add_action('admin_menu', array($instance, 'registerOptionsPage'));
			add_action(self::HOOK . '-saveOptions', array($instance, 'saveOptions'));
		}
		
		return $instance;
	}
	
	/**
	 * Performs activation
	 * @return void
	 */
	public function activate()
	{
		// Not installed?
		if (null === $this->_options->getOption('version'))
		{
			$this->_options
				->setOption('version', self::VERSION)
				->setOption('baseUrl', home_url())
				->setOption('additionalUrls', '')
				->setOption('generateZip', '')
				->setOption('retainStaticFiles', '')
				->setOption('sendViaFTP', '')
				->setOption('ftpServer', '')
				->setOption('ftpUsername', '')
				->setOption('ftpPassword', '')
				->setOption('ftpRemotePath', '')
				->save();
		}
	}
	
	/**
	 * Adds menu navigation items for this plugin
	 * @return void
	 */
	public function registerOptionsPage()
	{
		$page = add_submenu_page('tools.php', __('WP Static HTML Output', 'static-html-output-plugin'), __('WP Static HTML Output', 'static-html-output-plugin'), 'manage_options', self::HOOK . '-options', array($this, 'renderOptionsPage'));
		add_action('admin_print_styles-' . $page, array($this, 'enqueueAdminStyles'));
	}
	
	/**
	 * Enqueues CSS files required for this plugin
	 * @return void
	 */
	public function enqueueAdminStyles()
	{
		$pluginDirUrl = plugin_dir_url(dirname(__FILE__));
		wp_enqueue_style(self::HOOK . '-admin', $pluginDirUrl . '/css/wp-static-html-output.css');
	}
	
	/**
	 * Renders the general options page.
	 * Fires saveOptions action hook.
	 * @return void
	 */
	public function renderOptionsPage()
	{
		// Check system requirements
		$uploadDir = wp_upload_dir();
		$uploadsFolderWritable = $uploadDir && is_writable($uploadDir['path']);
		$supportsZipArchives = extension_loaded('zip');
		$permalinksStructureDefined = strlen(get_option('permalink_structure'));
		
		if (!$uploadsFolderWritable || !$supportsZipArchives ||!$permalinksStructureDefined)
		{
			$this->_view
				->setTemplate('system-requirements')
				->assign('uploadsFolderWritable', $uploadsFolderWritable)
				->assign('supportsZipArchives', $supportsZipArchives)
				->assign('permalinksStructureDefined', $permalinksStructureDefined)
				->render();
		}
		else
		{
			do_action(self::HOOK . '-saveOptions');
			
			$this->_view
				->setTemplate('options-page')
				->assign('exportLog', $this->_exportLog)
				->assign('baseUrl', $this->_options->getOption('baseUrl'))
				->assign('additionalUrls', $this->_options->getOption('additionalUrls'))
				->assign('generateZip', $this->_options->getOption('generateZip'))
				->assign('retainStaticFiles', $this->_options->getOption('retainStaticFiles'))
				->assign('sendViaFTP', $this->_options->getOption('sendViaFTP'))
				->assign('ftpServer', $this->_options->getOption('ftpServer'))
				->assign('ftpUsername', $this->_options->getOption('ftpUsername'))
				->assign('ftpRemotePath', $this->_options->getOption('ftpRemotePath'))
				->assign('onceAction', self::HOOK . '-options')
				->render();
		}
	}
	
	/**
	 * Saves the options
	 * @return void
	 */
	public function saveOptions()
	{
		// Protection
		if (!isset($_POST['action']) || 'generate' != $_POST['action'])
		{
			return;
		}
		
		if (!check_admin_referer(self::HOOK . '-options') || !current_user_can('manage_options'))
		{
			exit('You cannot change WP Static HTML Output Plugin options.');
		}
		
		// Save options
		$this->_options
			->setOption('baseUrl', filter_input(INPUT_POST, 'baseUrl', FILTER_SANITIZE_URL))
			->setOption('additionalUrls', filter_input(INPUT_POST, 'additionalUrls'))
			->setOption('generateZip', filter_input(INPUT_POST, 'generateZip'))
			->setOption('retainStaticFiles', filter_input(INPUT_POST, 'retainStaticFiles'))
			->setOption('sendViaFTP', filter_input(INPUT_POST, 'sendViaFTP'))
			->setOption('ftpServer', filter_input(INPUT_POST, 'ftpServer'))
			->setOption('ftpUsername', filter_input(INPUT_POST, 'ftpUsername'))
			->setOption('ftpRemotePath', filter_input(INPUT_POST, 'ftpRemotePath'))		
			->save();
		
		// Generate archive
		$archiveUrl = $this->_generateArchive();
		
		// Render the message
		if (is_wp_error($archiveUrl))
		{
			$message = 'Error: ' . $archiveUrl->get_error_code;
		}
		else
		{
			$message = sprintf('Archive created successfully: <a href="%s">Download archive</a>', $archiveUrl);
			if ($this->_options->getOption('retainStaticFiles') == 1)
			{
				$message .= sprintf('<br />Static files retained at: %s/', str_replace(home_url(),'',substr($archiveUrl,0,-4)));
			}
		}
		
		$this->_view->setTemplate('message')
			->assign('message', $message)
			->render();
	}
	
	/**
	 * Generates ZIP archive
	 * @return string|WP_Error
	 */
	protected function _generateArchive()
	{
		global $blog_id;
		set_time_limit(0);
		
		// Prepare archive directory
		$uploadDir = wp_upload_dir();
		$exporter = wp_get_current_user();
		$archiveName = $uploadDir['path'] . '/' . self::HOOK . '-' . $blog_id . '-' . time() . '-' . $exporter->user_login;
		$archiveDir = $archiveName . '/';
		if (!file_exists($archiveDir))
		{
			wp_mkdir_p($archiveDir);
		}
		
		// Prepare queue
		$baseUrl = untrailingslashit(home_url());
		$newBaseUrl = untrailingslashit($this->_options->getOption('baseUrl'));
		$urlsQueue = array_unique(array_merge(
			array(trailingslashit($baseUrl)),
			$this->_getListOfLocalFilesByUrl(array(get_template_directory_uri())),
			$this->_getListOfLocalFilesByUrl(explode("\n", $this->_options->getOption('additionalUrls')))
		));
		
		// Process queue
		$this->_exportLog = array();
		while (count($urlsQueue))
		{
			$currentUrl = array_shift($urlsQueue);
			
			//echo "Processing ". $currentUrl."<br />";
			
			$urlResponse = new StaticHtmlOutput_UrlRequest($currentUrl);
			$urlResponse->cleanup();
			
			// Add current url to the list of processed urls
			$this->_exportLog[$currentUrl] = true;
			
			
			// Add new urls to the queue
			
			foreach ($urlResponse->extractAllUrls($baseUrl) as $newUrl)
			{
				if (!isset($this->_exportLog[$newUrl]) && $newUrl != $currentUrl && !in_array($newUrl,$urlsQueue))
				{
					//echo "Adding ".$newUrl." to the list<br />";
					$urlsQueue[] = $newUrl;
				}
			}
			
			// Save url data
			$urlResponse->replaceBaseUlr($baseUrl, $newBaseUrl);
			$this->_saveUrlData($urlResponse, $archiveDir);
			
		}
		
		// Create archive object
		$tempZip = $archiveName . '.tmp';
		$zipArchive = new ZipArchive();
		if ($zipArchive->open($tempZip, ZIPARCHIVE::CREATE) !== true)
		{
			return new WP_Error('Could not create archive');
		}
		
		
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($archiveDir));
		foreach ($iterator as $fileName => $fileObject)
		{
			
			$baseName = basename($fileName);
			if($baseName != '.' && $baseName != '..') 
			{
				if (!$zipArchive->addFile(realpath($fileName), str_replace($archiveDir, '', $fileName)))
				{
					return new WP_Error('Could not add file: ' . $fileName);
				}
			}
		}
			
		$zipArchive->close();
		rename($tempZip, $archiveName . '.zip'); 
		
		if($this->_options->getOption('sendViaFTP') == 1)
		{		
			
			//crude FTP addition		
			require_once '/home/leon/leonwp/wp-content/plugins/static-html-output-plugin/library/FTP/ftp.php';
			$config = array();//keys[passive_mode(true|false)|transfer_mode(FTP_ASCII|FTP_BINARY)|reattempts(int)|log_path|verbose(true|false)|create_mask(default:0777)]
			$ftp = new ftp($config);
			$ftp->conn($this->_options->getOption('ftpServer'), $this->_options->getOption('ftpUsername'), filter_input(INPUT_POST, 'ftpPassword'));
			
			//Crude FTP				
			$ftp->put($this->_options->getOption('ftpRemotePath'), $archiveName . '/');			
		
			unset($ftp);
		}
		
		// Remove temporary files unless user requested to keep or needed for FTP transfer
		if ($this->_options->getOption('retainStaticFiles') != 1)		
		{
			$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($archiveDir), RecursiveIteratorIterator::CHILD_FIRST);
			foreach ($iterator as $fileName => $fileObject)
			{
					
				// Remove file
				if ($fileObject->isDir())
				{
					// Ignore special dirs
					$dirName = basename($fileName);
					if($dirName != '.' && $dirName != '..') {
						rmdir($fileName);
					}
				}
				else
				{
					unlink($fileName);
				}
			}
			rmdir($archiveDir);
		}	
	
	
	
	
		return str_replace(ABSPATH, trailingslashit(home_url()), $archiveName . '.zip');
	}
	
	/**
	 * Returns the list of local files
	 * @param array $urls
	 * @return array
	 */
	protected function _getListOfLocalFilesByUrl(array $urls)
	{
		$files = array();
		
		foreach ($urls as $url)
		{
			$directory = str_replace(home_url('/'), ABSPATH, $url);
			
			if (stripos($url, home_url('/')) === 0 && is_dir($directory))
			{
				$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
				foreach ($iterator as $fileName => $fileObject)
				{
					if (is_file($fileName))
					{
						$pathinfo = pathinfo($fileName);
						if (isset($pathinfo['extension']) && !in_array($pathinfo['extension'], array('php', 'phtml', 'tpl')))
						{
							array_push($files, home_url(str_replace(ABSPATH, '', $fileName)));
						}
					}
				}
			}
		}
		
		return $files;
	}
	
	/**
	 * Saves url data in temporary archive directory
	 * @param StaticHtmlOutput_UrlRequest $url
	 * @param string $archiveDir
	 * @return void
	 */
	protected function _saveUrlData(StaticHtmlOutput_UrlRequest $url, $archiveDir)
	{
		$urlInfo = parse_url($url->getUrl());
		$pathInfo = pathinfo(isset($urlInfo['path']) && $urlInfo['path'] != '/' ? $urlInfo['path'] : 'index.html');
		
		// Prepare file directory and create it if it doesn't exist
		$fileDir = $archiveDir . (isset($pathInfo['dirname']) ? $pathInfo['dirname'] : '');
		if (empty($pathInfo['extension']) && $pathInfo['basename'] == $pathInfo['filename'])
		{
			$fileDir .= '/' . $pathInfo['basename'];
			$pathInfo['filename'] = 'index';
		}
		if (!file_exists($fileDir))
		{
			wp_mkdir_p($fileDir);
		}
		
		// Prepare file name and save file contents
		$fileExtension = ($url->isHtml() || !isset($pathInfo['extension']) ? 'html' : $pathInfo['extension']);
		$fileName = $fileDir . '/' . $pathInfo['filename'] . '.' . $fileExtension;
		file_put_contents($fileName, $url->getResponseBody());
	}
}
