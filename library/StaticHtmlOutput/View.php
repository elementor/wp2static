<?php
class StaticHtmlOutput_View
{
	protected $_variables = array();
	protected $_path = null;
	protected $_directory = 'views';
	protected $_extension = '.phtml';
	protected $_template = null;
	
	public function __construct() {
		// Looking for a basic directory where plugin resides
		list($pluginDir) = explode('/', plugin_basename(__FILE__));
		
		// making up an absolute path to views directory
		$pathArray = array(WP_PLUGIN_DIR, $pluginDir, $this->_directory);
		
		$this->_path = implode('/', $pathArray);
	}
	
	public function setTemplate($template) {
		$this->_template = $template;
		$this->_variables = array();
		return $this;
	}
	
	public function __set($name, $value) {
		$this->_variables[$name] = $value;
		return $this;
	}
	
	public function assign($name, $value) {
		return $this->__set($name, $value);
	}
	
	public function __get($name) {
		$value = array_key_exists($name, $this->_variables) ? $this->_variables[$name] : null;
		return $value;
	}
	
	public function render() {
		$file = $this->_path . '/' . $this->_template . $this->_extension;
		
		if (!is_readable($file)) {
			error_log('Can\'t find view template: ' . $file);
		}
		
		include $file;
		
		return $this;
	}
	
	public function fetch() {
		ob_start();
		
		$this->render();
		$contents = ob_get_contents();
		
		ob_end_clean();
		
		return $contents;
	}
}
