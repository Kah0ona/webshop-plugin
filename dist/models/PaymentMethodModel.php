<?php
class PaymentMethodModel extends GenericModel {
	
	function __construct($hostname) {
		$this->hostname=$hostname;
		$this->serviceUrl = BASE_URL_WEBSHOP.'/paymentmethods';
	} 
		
	public function fetchPaymentMethodsDefault($params, $returnString=false){
		return $this->fetch($this->serviceUrl, $params, $returnString);
	}
	
	/**
	* Fetches the category with the id currently set by $this->setId(), or set by a previous call to $this->isDetailPage(); 
	*/
	public function fetchPaymentMethod(){
		return $this->fetchByID('PaymentMethod_id');
	}
	
	public function isDetailPage(){
		return false;
	}
	
	
	public function fetchPaymentMethods(){
		$arr = array(
			'hostname'=>$this->hostname,
			'useNesting'=>'false'
		);
		$prods = $this->fetchPaymentMethodsDefault($arr);	
		return $prods;
	}
	
	public function storeDataInSession(){
		$_SESSION['paymentMethods'] = $this->getData();
	}
		
}
?>