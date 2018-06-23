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
			(is_dir("$dir/$file")) ? StaticHtmlOutput_FilesHelper::delete_dir_with_files("$dir/$file") : unlink("$dir/$file"); 
		} 

		return rmdir($dir); 
	} 

}
