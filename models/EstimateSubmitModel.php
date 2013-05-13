<?php
class EstimateSubmitModel extends GenericModel {
	function __construct($options) {
		$this->options=$options;
	} 
	
	public function execute($post){
		$hostname = $this->options->getOption('hostname');
		$sender = $post['email']; // person who filled in the form
		$msg = $post['orderComment'];
		$name = $post['name'];
		$productName = $post['productName'];
		$productId = $post['Product_id'];
		$result =  $this->sendToBackend($hostname,$name,$sender,$phone,$productName,$productId,$msg);
		$phone = $post['phone'];		
		
		return json_encode($result);
	}
	
	
	private function sendToBackend($hostname,$name,$sender,$phone, $productName,$productId, $msg){
		$arr = array(
			'hostname'=>$hostname,
			'email'=>$sender,
			'productName'=>$productName,
			'Product_id'=>$productId,
			'name'=>$name,
			'pricequoteMessage'=>nl2br($msg),
			'phone'=>$phone
		);
				
		return $this->curl_post(BASE_URL_WEBSHOP.'/pricequotes', $arr );	
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

		
}
?>