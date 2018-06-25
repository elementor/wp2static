<?php
/**
 * @package WP Static HTML Output
 *
 * Copyright (c) 2011 Leon Stafford
 */


class StaticHtmlOutput_S3
{
	protected $_key;
	protected $_secret;
	protected $_region;
	protected $_bucket;
	protected $_remotePath;
	protected $_uploadsPath;
	protected $_exportFileList;
	protected $_archiveName;

	public function __construct($key, $secret, $region, $bucket, $remotePath, $uploadsPath) {

		$this->_key = $key;
		$this->_secret = $secret;
		$this->_region = $region;
		$this->_bucket = $bucket;
		$this->_remotePath = $remotePath;
		$this->_exportFileList = $uploadsPath . '/WP-STATIC-EXPORT-S3-FILES-TO-EXPORT';
		$archiveDir = file_get_contents($uploadsPath . '/WP-STATIC-CURRENT-ARCHIVE');
		$this->_archiveName = rtrim($archiveDir, '/');
	}

	public function clear_file_list() {
		$f = @fopen($this->_exportFileList, "r+");
		if ($f !== false) {
			ftruncate($f, 0);
			fclose($f);
		}

		$f = @fopen($this->_globHashAndPathList, "r+");
		if ($f !== false) {
			ftruncate($f, 0);
			fclose($f);
		}
	}

	// TODO: move this into a parent class as identical to bunny and probably others
	public function create_s3_deployment_list($dir, $archiveName, $remotePath){
		$files = scandir($dir);

		foreach($files as $item){
			if($item != '.' && $item != '..' && $item != '.git'){
				if(is_dir($dir.'/'.$item)) {
					$this->create_s3_deployment_list($dir.'/'.$item, $archiveName, $remotePath);
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

			//$this->wsLog('S3 EXPORT: Preparing list of files to transfer');

			$this->clear_file_list();

			$this->create_s3_deployment_list($this->_archiveName, $this->_archiveName, $this->_remotePath);

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

	public function s3_put_object($targetPath, $fileContents, $contentType = "text/plain", $pluginInstance) {
		if ( wpsho_fr()->is__premium_only() ) {
		
			require_once(__DIR__.'/../S3/S3.php');

			$client = new S3(
				$this->_key,
				$this->_secret,
				's3.' . $this->_region .  '.amazonaws.com'
			);

			// [OPTIONAL] Specify different curl options
			$client->useCurlOpts(array(
				CURLOPT_MAX_RECV_SPEED_LARGE => 1048576,
				CURLOPT_CONNECTTIMEOUT => 10
			));

			$response = $client->putObject(
				$this->_bucket, // bucket name without s3.amazonaws.com
				$targetPath, // path to create in bucket
				$fileContents, // file contents - path to stream or fopen result
				array(
					'Content-Type' => $contentType,
					'x-amz-acl' => 'public-read', // public read for static site
				)
			);

			if ($response->code == 200) {
				echo 'SUCCESS';
			} else {
				//$pluginInstance->wsLog('S3 EXPORT: following error returned from S3:');
				//$pluginInstance->wsLog(print_r($response, true));
				error_log(print_r($response, true));
				echo 'FAIL';
			}
		}
	}

	
    public function transfer_files() {
		if ( wpsho_fr()->is__premium_only() ) {

			if ($this->get_remaining_items_count() < 0) {
				echo 'ERROR';
				die();
			}

			$line = $this->get_item_to_export();

			list($fileToTransfer, $targetPath) = explode(',', $line);

			$targetPath = rtrim($targetPath);

			// vendor specific from here

			require_once(__DIR__.'/MimeTypes.php'); 

			$this->s3_put_object(
				$targetPath . basename($fileToTransfer),
				file_get_contents($fileToTransfer),
				GuessMimeType($fileToTransfer),
				$this
			); 
		   
			// end vendor specific 

			//$this->wsLog('S3 EXPORT: ' . $filesRemaining . ' files remaining to transfer');

			if ( $this->get_remaining_items_count() > 0 ) {
				//$this->wsLog('S3 EXPORT: ' . $filesRemaining . ' files remaining to transfer');
				echo $this->get_remaining_items_count();
			} else {
				echo 'SUCCESS';
			}
		}
    }

    public function commit_new_tree() {
		if ( wpsho_fr()->is__premium_only() ) {
			require_once(__DIR__.'/../Github/autoload.php');

			if ($this->get_remaining_items_count() < 0) {
				echo 'ERROR';
				die();
			}

			$line = $this->get_item_to_export();

			list($fileToTransfer, $targetPath) = explode(',', $line);

			$targetPath = rtrim($targetPath);

			// vendor specific from here

			$client = new \Github\Client();

			$client->authenticate($this->_accessToken, Github\Client::AUTH_HTTP_TOKEN);
			$reference = $client->api('gitData')->references()->show($this->_user, $this->_repository, 'heads/' . $this->_branch);
			$commit = $client->api('gitData')->commits()->show($this->_user, $this->_repository, $reference['object']['sha']);
			$commitSHA = $commit['sha'];
			$treeSHA = $commit['tree']['sha'];
			$treeURL = $commit['tree']['url'];
			$treeContents = array();
			$contents = file($this->_globHashAndPathList);

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

			$newTree = $client->api('gitData')->trees()->create($this->_user, $this->_repository, $treeData);
			
			$commitData = array('message' => 'WP Static HTML Output plugin: ' . date("Y-m-d h:i:s"), 'tree' => $newTree['sha'], 'parents' => array($commitSHA));
			$commit = $client->api('gitData')->commits()->create($this->_user, $this->_repository, $commitData);
			$referenceData = array('sha' => $commit['sha'], 'force' => true); //Force is default false

			try {
				$reference = $client->api('gitData')->references()->update(
						$this->_user,
						$this->_repository,
						'heads/' . $this->_branch,
						$referenceData);
			} catch (Exception $e) {
				$this->wsLog($e);
				throw new Exception($e);
			}
		   
			// end vendor specific 

			//$this->wsLog('S3 EXPORT: ' . $filesRemaining . ' files remaining to transfer');

			if ( $this->get_remaining_items_count() > 0 ) {
				//$this->wsLog('S3 EXPORT: ' . $filesRemaining . ' files remaining to transfer');
				echo $this->get_remaining_items_count();
			} else {
				echo 'SUCCESS';
			}
		}
    }

}
