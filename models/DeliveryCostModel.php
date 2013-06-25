<?php
class DeliveryCostModel extends GenericModel {
	protected $serviceUrl = null;
	
	function __construct($hostname,$options) {
		$this->hostname=$hostname;
		$this->options = $options;
		$this->serviceUrl = BASE_URL_WEBSHOP.'/deliverycosts';
	} 
	
	public function fetchDeliveryCosts($params, $returnString=false){
		return $this->fetch($this->serviceUrl, $params, $returnString);
	}
	
	function fetchDeliveryCostsDefault(){
		$arr = array(
			'hostname'=>$this->hostname,
			'ordering'=>'ASC', 
			'orderBy'=>'minKm'
		);
		return $this->fetchDeliveryCosts($arr, true);
	}	
	
	function isDetailPage() {
		return false;
	}
}
