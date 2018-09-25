<?php
/**
 * @package WP Static HTML Output
 *
 * Copyright (c) 2011 Leon Stafford
 */


class StaticHtmlOutput_Dropbox
{
	protected $_accessToken;
	protected $_remotePath;
	protected $_uploadsPath;
	protected $_exportFileList;
	protected $_archiveName;
	
	public function __construct($accessToken, $remotePath, $uploadsPath) {
		$this->_accessToken = $accessToken;
		$this->_remotePath = $remotePath;
		$this->_uploadsPath = $uploadsPath;
		$this->_exportFileList = $uploadsPath . '/WP-STATIC-EXPORT-DROPBOX-FILES-TO-EXPORT';
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

	public function create_dropbox_deployment_list($dir, $archiveName, $remotePath){
		$files = scandir($dir);

		foreach($files as $item){
			if($item != '.' && $item != '..' && $item != '.git'){
				if(is_dir($dir.'/'.$item)) {
					$this->create_dropbox_deployment_list($dir.'/'.$item, $archiveName, $remotePath);
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

			$this->clear_file_list();

			$this->create_dropbox_deployment_list($this->_archiveName, $this->_archiveName, $this->_remotePath);

			echo 'SUCCESS';
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

			if ($this->get_remaining_items_count() < 0) {
				echo 'ERROR';
				die();
			}

			$line = $this->get_item_to_export();

			list($fileToTransfer, $targetPath) = explode(',', $line);

			$targetPath = rtrim($targetPath);


			// vendor specific 
 
			$api_url = 'https://content.dropboxapi.com/2/files/upload'; //dropbox api url

			$headers = array('Authorization: Bearer '. $this->_accessToken,
				'Content-Type: application/octet-stream',
				'Dropbox-API-Arg: '.
				json_encode(
					array(
						"path"=> '/' . $targetPath . basename($fileToTransfer),
						"mode" => "overwrite",
						"autorename" => false
					)
				)

			);

			$ch = curl_init($api_url);

			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POST, true);

			$fp = fopen($fileToTransfer, 'rb');
			$filesize = filesize($fileToTransfer);

			curl_setopt($ch, CURLOPT_POSTFIELDS, fread($fp, $filesize));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$response = curl_exec($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			if ($http_code == 200) {
			} else {
				error_log($response);
				WsLog::l('DROPBOX EXPORT: ERROR');
				WsLog::l($response);
				echo 'FAIL';die();
			}

			curl_close($ch);

			// end vendor specific 

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
