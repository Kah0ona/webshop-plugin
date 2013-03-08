<?php
define('ORDER_SUCCESS', 200);
define('ORDER_FAILED', 400); //add other codes, if necessary
define('ORDER_VALIDATION_ERROR', 400);
class CheckoutModel extends GenericModel {
	protected $status;
	protected $statusMessage;
	protected $cart=null;
	protected $vatMap =null;
	protected $options;
	protected $curlError=0;
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
		$post = $_POST;
		$this->logMessage("-------");
		$this->logMessage("Processing cart: ");
		$this->logMessage(urlencode($_POST['shoppingCart']));
		ob_start();
		print_r($post);	
		$bod = ob_get_contents();
		ob_end_clean();			
		
		$this->logMessage($bod);
		
		
		$post['orderType'] = 'invoice';
		$savedPerson = $this->curl_post(BASE_URL_WEBSHOP.'/persons', $post); //extra fields are automatically removed.
		if(!$savedPerson){
			$this->logMessage("Error sending post to /persons: ".$this->curlError);			
			$this->status = ORDER_FAILED;
			$this->statusMessage = "De klantgegevens konden niet worden opgeslagen.";
		}
		else {
			$savedPerson = utf8_encode($savedPerson);
			$personId = null;
			$obj = json_decode($savedPerson);
			if($obj->error != null){
				$this->logMessage("Error sending post to /persons: ".$obj->error);			
			
				$this->status = ORDER_FAILED;
				$this->statusMessage = "De klantgegevens konden niet worden opgeslagen: ".$obj->error;
			}
			elseif($obj->Person_id != null) { //success continue

				$post['Person_id'] = $obj->Person_id;
				$post['viaSite'] = true;
				$ret = $this->curl_post(BASE_URL_WEBSHOP.'/orders', $post);
				
				if(!$ret){
					$this->logMessage("Error sending post to /orders: ".$this->curlError);			
					$this->status = ORDER_FAILED;
					$this->statusMessage = "De ordergegevens konden niet worden opgeslagen.";
				}
				else {
					$this->status = ORDER_SUCCESS;
					$this->statusMessage = "De bestelling is succesvol verstuurd.";
					$this->logMessage("returned value from /orders: ".$ret);				
				}					
			}
			else { //unknown error
				$this->logMessage("Error sending post to /persons without an error message!");			
				$this->status = ORDER_FAILED;
			}

		}
		return $this->status;
	}
	
	private function logMessage($msg){
		@file_put_contents('../logs/order.log',@date("Y-m-d H:i:s").': '.$msg."\n",FILE_APPEND);
	}

	/**
	 * Send a POST requst using cURL
	 * @param string $url to request
	 * @param array $post values to send
	 * @param array $options for cURL
	 * @return string
	 */
	function curl_post($url, array $post = NULL, array $options = array()) {
		$this->curlError = 0;
	    $defaults = array(
	        CURLOPT_POST => 1,
	        CURLOPT_HEADER => 0,
	        CURLOPT_URL => $url,
	        CURLOPT_FRESH_CONNECT => 1,
	        CURLOPT_RETURNTRANSFER => 1,
	        CURLOPT_FORBID_REUSE => 1,
	        CURLOPT_TIMEOUT => 4,
	        CURLOPT_POSTFIELDS => $this->decodeParamsIntoGetString($post),
			CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT']
	    );
	
	    $ch = curl_init();
	    curl_setopt_array($ch, ($options + $defaults));
	    if( !$result = curl_exec($ch))
	    {    
	    //    trigger_error(curl_error($ch));
	    	$this->curlError = curl_error($ch);
	    	return false;
	    }
	    else {
	    	return $result;
	   
	   	} 	
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
		return $this->statusMessage;
	}

}
?>