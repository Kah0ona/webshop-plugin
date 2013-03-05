<?php
define('ORDER_SUCCESS', 200);
define('ORDER_FAILED', 400); //add other codes, if necessary
define('ORDER_VALIDATION_ERROR', 400);
class CheckoutModel extends GenericModel {
	protected $status;
	protected $cart=null;
	protected $vatMap =null;
	protected $options;
	
	public function __construct($hostname) {
		$this->hostname=$hostname;

	} 
	
	public function setOptions($options){
		$this->options = options;
	}

	private function getCartFromSession(){
		if(!isset($_SESSION['shoppingCart'])){
			$this->cart = json_decode('[]');
		}
		else {
			$this->cart =  $_SESSION['shoppingCart'];
		} 
	}
	
	public function getCart(){
		$this->getCartFromSession();
		return $this->cart;
	}
	
	public function sendOrderToBackend(){
		
	}
	
	public function getAllowPickingUp(){
		return $this->options->getOption['allowPickup'];
	}
	
	public function initVatMap(){
		$this->vatMap = array();
		foreach($this->cart as $i){
			$item = (object) $i;
			if(!in_array($item->VAT, $this->vatMap)){
				$this->vatMap[] = $item->VAT;
			}
		}
		if(!in_array(0.21, $this->vatMap))
			$this->vatMap[] = 0.21;
		
		return $this->vatMap;
	}
	
	public function getStatusMessage(){
		switch($this->status){
			case ORDER_SUCCESS:
				$ret = 'De bestelling is succesvol verwerkt.';
			break;
			case ORDER_FAILED:
				$ret = 'De bestelling is niet verstuurd, door een fout. Probeer het later opnieuw.';			
			break;
			default:
				$ret = 'Er is een onbekende fout opgetreden';
			break;
		}
		
		return $ret;
	}

}
?>