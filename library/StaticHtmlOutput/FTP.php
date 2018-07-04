<?php
/**
 * @package WP Static HTML Output
 *
 * Copyright (c) 2011 Leon Stafford
 */

class StaticHtmlOutput_FTP
{
	protected $_host;
	protected $_username;
	protected $_password;
	protected $_remotePath;
	protected $_activeMode;
	protected $_uploadsPath;
	protected $_exportFileList;
	protected $_archiveName;
	
	public function __construct($host, $username, $password, $remotePath, $activeMode, $uploadsPath) {
		$this->_host = $host;
		$this->_username = $username;
		$this->_password = $password;
		$this->_remotePath = $remotePath;
		$this->_activeMode = $activeMode;
		$this->_exportFileList = $uploadsPath . '/WP-STATIC-EXPORT-FTP-FILES-TO-EXPORT';
		$archiveDir = file_get_contents($uploadsPath . '/WP-STATIC-CURRENT-ARCHIVE');
		$this->_archiveName = rtrim($archiveDir, '/');
	}

	public function clear_file_list() {
		$f = @fopen($this->_exportFileList, "r+");
		if ($f !== false) {
			ftruncate($f, 0);
			fclose($f);
		}
	}

	public function test_connection() {
		require_once(__DIR__.'/../FTP/FtpClient.php');
		require_once(__DIR__.'/../FTP/FtpException.php');
		require_once(__DIR__.'/../FTP/FtpWrapper.php');

		$ftp = new \FtpClient\FtpClient();
		
		try {
			$ftp->connect(filter_input(INPUT_POST, 'ftpServer'));
			$ftp->login(filter_input(INPUT_POST, 'ftpUsername'), filter_input(INPUT_POST, 'ftpPassword'));
		} catch (Exception $e) {
			WsLog::l('FTP EXPORT: error encountered');
			WsLog::l($e);
			throw new Exception($e);
		}

		if ($ftp->isdir(filter_input(INPUT_POST, 'ftpRemotePath'))) {
			WsLog::l('FTP EXPORT: Remote dir exists');
		} else {
			WsLog::l('FTP EXPORT: Creating remote dir');
			$ftp->mkdir(filter_input(INPUT_POST, 'ftpRemotePath'), true);
		}

		unset($ftp);
	}


	// TODO: move this into a parent class as identical to bunny and probably others
	public function create_ftp_deployment_list($dir, $archiveName, $remotePath){
		$files = scandir($dir);

		foreach($files as $item){
			if($item != '.' && $item != '..' && $item != '.git'){
				if(is_dir($dir.'/'.$item)) {
					$this->create_ftp_deployment_list($dir.'/'.$item, $archiveName, $remotePath);
				} else if(is_file($dir.'/'.$item)) {
					$subdir = str_replace('/wp-admin/admin-ajax.php', '', $_SERVER['REQUEST_URI']);
					$subdir = ltrim($subdir, '/');
					$clean_dir = str_replace($archiveName . '/', '', $dir.'/');
					$clean_dir = str_replace($subdir, '', $clean_dir);
					$targetPath =  $remotePath . $clean_dir;
					$targetPath = ltrim($targetPath, '/');
					$export_line = $dir .'/' . $item . ',' . $targetPath . "\n";
					file_put_contents($this->_exportFileList, $export_line, FILE_APPEND | LOCK_EX);
				} 
			}
		}
	}

	
	// TODO: move this into a parent class as identical to bunny and probably others
    public function prepare_deployment() {
		if ( wpsho_fr()->is__premium_only() ) {

			$this->test_connection();

			WsLog::l('FTP EXPORT: Preparing list of files to transfer');

			$this->clear_file_list();

			$this->create_ftp_deployment_list($this->_archiveName, $this->_archiveName, $this->_remotePath);

			echo 'SUCCESS';
		}
    }

	public function get_item_to_export() {
		$f = fopen($this->_exportFileList, 'r');
		$line = fgets($f);
		fclose($f);

		// TODO reduce the 2 file reads here, this one is just trimming the first line
		$contents = file($this->_exportFileList, FILE_IGNORE_NEW_LINES);
		array_shift($contents);
		file_put_contents($this->_exportFileList, implode("\r\n", $contents));

		return $line;
	}

	public function get_remaining_items_count() {
		$contents = file($this->_exportFileList, FILE_IGNORE_NEW_LINES);
		
		// return the amount left if another item is taken
		#return count($contents) - 1;
		return count($contents);
	}

	
    public function transfer_files() {
		if ( wpsho_fr()->is__premium_only() ) {
			require_once(__DIR__.'/../FTP/FtpClient.php');
			require_once(__DIR__.'/../FTP/FtpException.php');
			require_once(__DIR__.'/../FTP/FtpWrapper.php');

			if ($this->get_remaining_items_count() < 0) {
				echo 'ERROR';
				die();
			}

			$line = $this->get_item_to_export();

			list($fileToTransfer, $targetPath) = explode(',', $line);

			$targetPath = rtrim($targetPath);


			// vendor specific from here
			$ftp = new \FtpClient\FtpClient();
			
			$ftp->connect($this->_host);
			$ftp->login($this->_username, $this->_password);

			if ( $this->_activeMode ) {
				WsLog::l('FTP EXPORT: setting ACTIVE transfer mode');
				$ftp->pasv(false);
			} else {
				$ftp->pasv(true);
			}

			WsLog::l('FTP EXPORT: transferring ' .  basename($fileToTransfer) . ' TO ' . $targetPath);
		   
			if ($ftp->isdir($targetPath)) {
				WsLog::l('FTP EXPORT: Remote dir exists');
			} else {
				WsLog::l('FTP EXPORT: Creating remote dir');
				$mkdir_result = $ftp->mkdir($targetPath, true); // true = recursive creation
			}

			$ftp->chdir($targetPath);
			$ftp->putFromPath($fileToTransfer);
		  
			unset($ftp);
			// end vendor specific 

			$filesRemaining = $this->get_remaining_items_count();

			if ( $this->get_remaining_items_count() > 0 ) {
				WsLog::l('FTP EXPORT: ' . $filesRemaining . ' files remaining to transfer');
				echo $this->get_remaining_items_count();
			} else {
				echo 'SUCCESS';
			}
		}
    }

}
