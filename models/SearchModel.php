<?php
class SearchModel extends GenericModel {
	protected $serviceUrl = null;
	
	function __construct($hostname,$options) {
		$this->hostname=$hostname;
		$this->options = $options;
		$this->serviceUrl = BASE_URL_WEBSHOP.'/search';
	} 
	
	/**
	* $params should contain a 'query' key with the search query, 
	*/
	public function fetchSearchResults($params, $returnString=false){
		return $this->fetch($this->serviceUrl, $params, $returnString);
	}
		
	public function isDetailPage() {
		return false;
	}
	
	public function getServiceUrl(){
		return $this->serviceUrl;
	}
}



//aaanpaslink: