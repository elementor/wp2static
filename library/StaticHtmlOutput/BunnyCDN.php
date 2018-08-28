<?php
/**
 * @package WP Static HTML Output
 *
 * Copyright (c) 2011 Leon Stafford
 */

use GuzzleHttp\Client;

class StaticHtmlOutput_BunnyCDN
{
	protected $_zoneID;
	protected $_APIKey;
	protected $_remotePath;
	protected $_baseURL;
	protected $_uploadsPath;
	protected $_exportFileList;
	protected $_archiveName;
	
	public function __construct($zoneID, $APIKey, $remotePath, $uploadsPath) {
		$this->_zoneID = $zoneID;
		$this->_APIKey = $APIKey;
		$this->_remotePath = $remotePath;
		$this->_baseURL = 'https://storage.bunnycdn.com';
		$this->_uploadsPath = $uploadsPath;
		$this->_exportFileList = $uploadsPath . '/WP-STATIC-EXPORT-BUNNYCDN-FILES-TO-EXPORT';
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

	public function create_bunny_deployment_list($dir, $archiveName, $remotePath){
		$files = scandir($dir);

		foreach($files as $item){
			if($item != '.' && $item != '..' && $item != '.git'){
				if(is_dir($dir.'/'.$item)) {
					$this->create_bunny_deployment_list($dir.'/'.$item, $archiveName, $remotePath);
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

    public function prepare_export() {
		if ( wpsho_fr()->is__premium_only() ) {

			$this->clear_file_list();

			$this->create_bunny_deployment_list($this->_archiveName, $this->_archiveName, $this->_remotePath);

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

    public function transfer_files($viaCLI) {
		if ( wpsho_fr()->is__premium_only() ) {
			require_once dirname(__FILE__) . '/../GuzzleHttp/autoloader.php';

			if ($this->get_remaining_items_count() < 0) {
				echo 'ERROR';
				die();
			}

			$line = $this->get_item_to_export();

			list($fileToTransfer, $targetPath) = explode(',', $line);

			$targetPath = rtrim($targetPath);

			// do the bunny export
			$client = new Client(array(
					'base_uri' => $this->_baseURL
			));	

			try {
				$response = $client->request('PUT', '/' . $this->_zoneID . '/' . $targetPath . basename($fileToTransfer), array(
						'headers'  => array(
							'AccessKey' => ' ' . $this->_APIKey
						),
						'body' => fopen($fileToTransfer, 'rb')
				));
			} catch (Exception $e) {
				WsLog::l('BUNNYCDN EXPORT: error encountered');
				WsLog::l($e);
				error_log($e);
				throw new Exception($e);
			}


			$filesRemaining = $this->get_remaining_items_count();

			if ( $filesRemaining > 0 ) {
        // if this is via CLI, then call this function again here
        if ($viaCLI) {
          $this->transfer_files(true); 
        }

				echo $filesRemaining;
			} else {
				echo 'SUCCESS';
			}
		}
    }

	public function purge_all_cache() {
		require_once dirname(__FILE__) . '/../GuzzleHttp/autoloader.php';
		// purege cache for each file
		$client = new Client();	

		try {
			$response = $client->request('POST', 'https://bunnycdn.com/api/pullzone/' . $this->_zoneID . '/purgeCache', array(
					'headers'  => array(
						'AccessKey' => ' ' . $this->_APIKey
					)
			));

			if ($response->getStatusCode() == 200) {
				echo 'SUCCESS';
			} else {
				echo 'FAIL';
			}
			

		} catch (Exception $e) {
			WsLog::l('BUNNYCDN EXPORT: error encountered');
			WsLog::l($e);
			error_log($e);
			throw new Exception($e);
		}
	}
}
