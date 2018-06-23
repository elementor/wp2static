<?php
/**
 * @package WP Static HTML Output
 *
 * Copyright (c) 2011 Leon Stafford
 */

class StaticHtmlOutput_FilesHelper
{
	protected $_directory;
	
	public function __construct() {
		$this->_directory = '';
	}

	public static function delete_dir_with_files($dir) { 
		$files = array_diff(scandir($dir), array('.','..')); 

		foreach ($files as $file) { 
			(is_dir("$dir/$file")) ? self::delete_dir_with_files("$dir/$file") : unlink("$dir/$file"); 
		} 

		return rmdir($dir); 
	} 

	public static function recursively_scan_dir($dir, $siteroot, $file_list_path){
		// rm duplicate slashes in path (TODO: fix cause)
		$dir = str_replace('//', '/', $dir);
		$files = scandir($dir);

		foreach($files as $item){
			if($item != '.' && $item != '..' && $item != '.git'){
				if(is_dir($dir.'/'.$item)) {
					self::recursively_scan_dir($dir.'/'.$item, $siteroot, $file_list_path);
				} else if(is_file($dir.'/'.$item)) {
					$subdir = str_replace('/wp-admin/admin-ajax.php', '', $_SERVER['REQUEST_URI']);
					$subdir = ltrim($subdir, '/');
					$clean_dir = str_replace($siteroot . '/', '', $dir.'/');
					$clean_dir = str_replace($subdir, '', $clean_dir);
					$filename = $dir .'/' . $item . "\n";
					$filename = str_replace('//', '/', $filename);
					//$this->wsLog('FILE TO ADD:');
					//$this->wsLog($filename);
					file_put_contents($file_list_path, $filename, FILE_APPEND | LOCK_EX);
				} 
			}
		}
	}

}
