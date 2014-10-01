<?php
/**
* Object that fetches the options from the database on construction
*/ 
class WebshopOptions {
	protected $options = null;
	
	function __construct(){
	}
	
	public function loadOptions(){
		$this->options = get_option('sytematic_webshop');
	}

	public function getOptions(){
		return $this->options;		
	}
	
	public function getOption($optionKey){
		return $this->options[$optionKey];
	}
	public function setOptions($options){
		$this->options = $options;
	}
	
	public function registerSettings(){
		register_setting( 'sytematic_webshop', 'sytematic_webshop' ); //store all webshop settings in one array
	}
	
}
?>