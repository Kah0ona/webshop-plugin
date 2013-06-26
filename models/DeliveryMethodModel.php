<?php
class DeliveryMethodModel extends GenericModel {
	protected $serviceUrl = null;
	
	function __construct($hostname,$options) {
		$this->hostname=$hostname;
		$this->options = $options;
		$this->serviceUrl = BASE_URL_WEBSHOP.'/deliverymethods';
	} 
	
	public function fetchDeliveryMethods($params, $returnString=false){
		return $this->fetch($this->serviceUrl, $params, $returnString);
	}
	
	function fetchDeliveryMethodsDefault(){
		$arr = array(
			'hostname'=>$this->hostname
		);
		return $this->fetchDeliveryMethods($arr, true);
	}	
	
	function isDetailPage() {
		return false;
	}
	
	//TODO REMOVE ME!
	public function getData(){
		return json_decode('[{"DeliveryMethod_id":1,"deliveryMethodName":"Hard coded delivery method", "deliveryMethodDesc":"Hard coded delivery desc","deliveryMethodPrice":7.50}]');
	}
}
