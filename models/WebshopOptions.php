<?php
/**
* Object that fetches the options from the database on construction
*/ 
class WebshopOptions {
	protected $options = null;
	
	function __construct(){
		$this->options = get_option('sytematic_webshop');
	}
	
	public function getOptions(){
		return $this->options;		
	}
	
	public function getOption($optionKey){
		return $this->options[$optionKey];
	}
	
}
?>