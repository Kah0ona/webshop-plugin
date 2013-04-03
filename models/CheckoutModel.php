<?php
define('ORDER_SUCCESS', 200);
define('ORDER_FAILED', 400); //add other codes, if necessary
define('ORDER_VALIDATION_ERROR', 400);
define('SISOW_NOTIFY_URL', BASE_URL_WEBSHOP.'/sisow_notifications');
class CheckoutModel extends GenericModel {
	protected $status;
	protected $statusMessage;
	protected $cart=null;
	protected $vatMap =null;
	protected $options;
	protected $curlError=0;
	protected $insertedOrderId=null;
	protected $redirectUrl = '';
	public function __construct($options) {
		$this->options=$options;
		$this->getCartFromSession();
	} 


	private function getCartFromSession(){
		if(!isset($_SESSION['shoppingCart'])){
			$this->cart = json_decode('[]');
		}
		else {
			$this->cart = $_SESSION['shoppingCart'];
		} 
	}
	
	public function getCart(){
		$this->getCartFromSession();
		return $this->cart;
	}
	
	public function sendOrderToBackend(){
		$_POST['hostname'] = $this->options->getOption('hostname');
		$post = $_POST;
		$post['shoppingCart'] = json_encode($this->cart);
		$post['orderStatus'] = 'nieuw';
		$this->logMessage("-------");
		$this->logMessage("Processing cart: ");
		$this->logMessage($post['shoppingCart']);
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
				$this->logMessage("Inserted person id: ".$obj->Person_id);			

				$post['Person_id'] = $obj->Person_id;
				$post['viaSite'] = true;
				
				$savedOrder = $this->curl_post(BASE_URL_WEBSHOP.'/orders', $post);

				if(!$savedOrder){
					$this->logMessage("Error sending post to /orders: ".$this->curlError);			
					$this->status = ORDER_FAILED;
					$this->statusMessage = "De ordergegevens konden niet worden opgeslagen.";
				}
				else {
					$savedOrder = utf8_encode($savedOrder);
					$obj = json_decode($savedOrder);				
					if($obj->error != null){
						$this->logMessage("Error sending post to /orders: ".$obj->error);			
						$this->status = ORDER_FAILED;
						$this->statusMessage = "De bestelgegevens konden niet worden opgeslagen: ".$obj->error;
					}
					else {
						if($obj->Order__id != null) {
							$this->logMessage("Inserted Order id: ".$obj->Order__id);			
						
							$this->status = ORDER_SUCCESS;
							$this->statusMessage = "De bestelling is succesvol verstuurd.";
							$this->insertedOrderId= $obj->Order__id;
						}
						else {
							$this->logMessage("Error sending post to /orders without an error message!");			
							$this->status = ORDER_FAILED;
						}
					}
				}					
			}
			else { //unknown error
				$this->logMessage("Error sending post to /persons without an error message!");			
				$this->status = ORDER_FAILED;
			}
		}
		return $this->status;
	}
	
	
	public function doIDeal(){
		$this->logMessage("Creating iDeal transaction");
		
		$sisow = new Sisow($this->options->getOption('SisowMerchantId'), $this->options->getOption('SisowMerchantKey'));
		$total = $this->calculateTotalPrice();
		$sisow->returnUrl = site_url().'/success';
		$sisow->purchaseId = $this->insertedOrderId;
		$sisow->description = $this->options->getOption('SisowDescription');
		$sisow->amount = $total;
		$sisow->issuerId = $_POST["issuerid"];
		$sisow->notifyUrl = SISOW_NOTIFY_URL;

		$this->logMessage("purchaseId: ".$this->insertedOrderId);
		$this->logMessage("description: ".$this->options->getOption('SisowDescription'));
		$this->logMessage("amount: ".$total);
		$this->logMessage("issuerId: ".$_POST["issuerid"]);
		$this->logMessage("returnUrl: ".site_url().'/success');
		$this->logMessage("notifyUrl: ".SISOW_NOTIFY_URL);


		
		if (($ex = $sisow->TransactionRequest()) < 0) {
			$this->logMessage('De iDeal betaling is mislukt, foutmelding: '.$sisow->errorCode.", ".$sisow->errorMessage);
			$this->status = ORDER_FAILED;
			$this->statusMessage = 'De iDeal betaling is mislukt, foutmelding: '.$sisow->errorCode.", ".$sisow->errorMessage;
			return $sisow->errorCode;			
		}
		$this->redirectUrl = $sisow->issuerUrl;
		$this->logMessage("Setting redirect url: ".$this->redirectUrl);
	}
	
	private function calculateTotalPrice() {
		$total = 0;
		foreach($this->cart as $product){
			$this->logMessage('adding price: '.$product['price'].' '.$product['quantity']);
			$total += ($product['price'] * $product['quantity']);	
		}
		$total += (int) $this->options->getOption('ShippingCosts');
		$discount = 0;
		if(isset($_POST['coupon']) && $_POST['coupon'] != "" &&  $_POST['coupon'] != null){
			$discount = $this->getCouponPercentage($_POST['coupon']);		
		}
		$total = $total * (1-($discount/100));
		
		return $total;
	}
	
	private function getCouponPercentage($coupon){
		$couponResult = $this->curl_post(BASE_URL_WEBSHOP.'/coupons', array('couponCode'=>$coupon, 'hostname'=>$this->options->getOption('hostname')));
		if(!$couponResult){
			return 0;
		}
		else {
			$perc = $couponResult->discount;
			if($perc == null || $perc == undefined){
				return 0;
			} 
			else {
				return $perc;	
			} 
		}
	}
	
	
	private function logMessage($msg){
		file_put_contents(WEBSHOP_PLUGIN_PATH.'/logs/order.log',date("Y-m-d H:i:s").': '.$msg."\n",FILE_APPEND);
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
	public function getStatus(){
		return $this->status;
	}
	
	public function getRedirectUrl(){
		return $this->redirectUrl;
	}
}
