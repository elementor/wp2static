<?php

class StaticHtmlOutput_Options
{
	protected $_options = array();
	
	protected $_optionKey = null;
	
	public function __construct($optionKey) {
		$options = get_option($optionKey);
		
		if (false === $options)
		{
			$options = array();
		}
		
		$this->_options = $options;
		$this->_optionKey = $optionKey;
	}
	
	public function __set($name, $value) {
		$this->_options[$name] = $value;
		return $this;
	}
	
	public function setOption($name, $value) {
		return $this->__set($name, $value);
	}
	
	public function __get($name) {
		$value = array_key_exists($name, $this->_options) ? $this->_options[$name] : null;
		return $value;
	}
	
	public function getOption($name) {
		return $this->__get($name);
	}
	
	public function save() {
		return update_option($this->_optionKey, $this->_options);
	}
	
	public function delete() {
		return delete_option($this->_optionKey);
	}
}
