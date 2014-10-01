<?php
class SaleModel  extends GenericModel  {

	function __construct($hostname,$options) {
		$this->hostname=$hostname;
		$this->options = $options;
		$this->serviceUrl = BASE_URL_WEBSHOP.'/search';
	} 
	
	public function getSaleMode(){
		if(!isset($_SESSION['sale_mode'])){
			$_SESSION['sale_mode'] = 'both';
		}
		
		return $_SESSION['sale_mode'];
	}
	
	public function getSaleModeText(){
		$m = $this->getSaleMode();
		
		if($m == 'off')
			return 'NEW COLLECTION';
		elseif($m == 'both')
			return 'NEW+SALE';
		else 
			return 'SALE!';
	}
	
	
	/**
	* AJAX function
	*/
	public function saveSaleModeInSession($mode){
		$_SESSION['sale_mode'] = $mode; 
		
	}

	public function isDetailPage() {
		return false;
	}
	
	public function getServiceUrl(){
		return $this->serviceUrl;
	}

}

?>