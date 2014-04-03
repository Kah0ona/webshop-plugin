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
	protected $deliveryMethodModel = null;
	protected $deliveryCostModel = null;
	protected $totalPrice = 0;
	
	public function __construct($options) {
		$this->options=$options;
		$this->hostname=$options->getOption('hostname');
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
	
	public function setDeliveryCostModel($m) {
		$this->deliveryCostModel = $m;
	}

	
	public function getDeliveryCostModel(){
		return $this->deliveryCostModel;
	}
	
	public function setDeliveryMethodModel($m) {
		$this->deliveryMethodModel = $m;
	}
	
	public function getDeliveryMethodModel() {
		return $this->deliveryMethodModel;
	}
	
	
	public function allowDeliveryDate(){
		return $this->options->getOption('use_delivery_date');
	}
	public function allowDeliveryTime(){
		return $this->options->getOption('use_delivery_time');
	}
	
	public function sendOrderToBackend($post){
		$post['hostname'] = $this->options->getOption('hostname');
		$post['shoppingCart'] = json_encode($this->cart);
		$post['orderStatus'] = 'nieuw';
		$post['PaymentMethod_id'] = $post['payment-method'];
		if($post['PaymentMethod_id'] == "ideal" || $post['PaymentMethod_id'] == "ogone" || $post['PaymentMethod_id'] == 'mistercash'){
			if($post['PaymentMethod_id'] == 'ideal'){
				$post['thePaymentMethodName'] =  'iDeal betaling';
			}
			if($post['PaymentMethod_id'] == 'mistercash'){
				$post['thePaymentMethodName'] = 'MisterCash betaling';
			}
			if($post['PaymentMethod_id'] == 'ogone'){
				$post['thePaymentMethodName'] = 'CreditCard via Ogone';
			}
			$post['PaymentMethod_id'] = null;
			unset($post['PaymentMethod_id']);

		}		
		
		if($post['DeliveryMethod_id'] == 0){
			$post['DeliveryMethod_id'] = null;
			unset($post['DeliveryMethod_id']);
		}
		$this->logMessage("-------");
		$this->logMessage("Processing cart: ");
		$this->logMessage($post['shoppingCart']);
		
		$post['viaSite'] = 'true';

		if($post['deliveryDate'] != null && "" != trim($post['deliveryDate']) && isset($post['deliveryDate'])){
			$pieces = explode('-', $post['deliveryDate']);
			
			$time = '00:00';
			if($post['deliveryTime'] != null && "" != trim($post['deliveryTime']) && isset($post['deliveryTime'])){
				$piecesTime = explode('-', $post['deliveryTime']);
				$time = $piecesTime[0];
			}
			
			$post['deliveryDate']= $pieces[1].'-'.$pieces[0].'-'.$pieces[2].' '.$time.':00';
		}
		
		if($post['deliveryDate'] == ''){
			unset($post['deliveryDate']);
		}
		
		if($post['deliveryTime'] == ''){
			unset($post['deliveryTime']);
		}



		ob_start();
		print_r($post);	
		$bod = ob_get_contents();
		ob_end_clean();			
		
		$this->logMessage($bod);
			
		$post['orderType'] = 'invoice';
		$savedOrder = $this->curl_post(BASE_URL_WEBSHOP.'/orders', $post); //since we send both person data and order data, the servlet will process both.
		
		$this->resetSessionData();
		
		if(!$savedOrder){
			$this->logMessage("Error sending post to /orders: ".$this->curlError);			
			$this->status = ORDER_FAILED;
			$this->statusMessage = "De bestelling kon niet worden opgeslagen.";
		}
		else {
			$savedOrder = utf8_encode($savedOrder);
			$obj = json_decode($savedOrder);
			ob_start();
			print_r($obj);	
			$bod = ob_get_contents();
			ob_end_clean();			
			$this->logMessage("Returned value from /orders:");
			$this->logMessage($bod);
			if($obj->error != null){
				$this->logMessage("Error sending post to /orders: ".$obj->error);			
				$this->status = ORDER_FAILED;
				$this->statusMessage = "De bestelling kon niet worden opgeslagen: ".$obj->error;
			}
			elseif($obj->Order__id != null) { //success continue
				$this->logMessage("Inserted Order id: ".$obj->Order__id);			
				if($obj->totalPrice == null) {
					$this->status = ORDER_FAILED;
					$this->statusMessage = "Er ging iets mis met de verwerking, order niet goed verwerkt.";
				}
				else {
					$this->totalPrice = $obj->totalPrice;						
					$this->status = ORDER_SUCCESS;
					$this->statusMessage = "De bestelling is succesvol verstuurd.";
					$this->insertedOrderId= $obj->Order__id;
					$this->storeDataInSession();
				}
			}
			else { //unknown error
				$this->logMessage("Error sending post to /orders without an error message!");			
				$this->status = ORDER_FAILED;
				$this->statusMessage = "Er ging iets mis met de verwerking, order niet goed verwerkt.";
			}
		}
		return $this->status;
	}

	/**
	* Resets Order__id and transactionAmount in the session to null;
	*/
	public function resetSessionData(){
		$_SESSION['Order__id'] = null;
		$_SESSION['transactionAmount'] = null;
		
	}
	
	/**
	* Stores the orderid and the amount in the session.
	*/
	public function storeDataInSession() {
		$_SESSION['Order__id'] = $this->insertedOrderId;
		$_SESSION['transactionAmount'] = $this->totalPrice;
	}
	
	public function doSisowTransaction($type='ideal'){
		$this->logMessage("Creating ".$type." transaction");
		
		$sisow = new Sisow($this->options->getOption('SisowMerchantId'), $this->options->getOption('SisowMerchantKey'));
		
		if($type != 'ideal') {
			$sisow->payment = 'mistercash';			
		}
		$sisow->returnUrl = site_url().'/success';
		$sisow->purchaseId = $this->insertedOrderId;
		$sisow->description = $this->options->getOption('SisowDescription');
		$sisow->amount = $this->totalPrice;
		$sisow->issuerId = $_POST["issuerid"];
		$sisow->notifyUrl = SISOW_NOTIFY_URL;

		$this->logMessage("purchaseId: ".$this->insertedOrderId);
		$this->logMessage("description: ".$this->options->getOption('SisowDescription'));
		$this->logMessage("amount: ".$this->totalPrice);
		$this->logMessage("issuerId: ".$_POST["issuerid"]);
		$this->logMessage("returnUrl: ".site_url().'/success');
		$this->logMessage("notifyUrl: ".SISOW_NOTIFY_URL);
		
		if (($ex = $sisow->TransactionRequest()) < 0) {
			$this->logMessage('De '.$type.' betaling is mislukt, foutmelding: '.$sisow->errorCode.", ".$sisow->errorMessage);
			$this->status = ORDER_FAILED;
			$this->statusMessage = 'De '.$type.' betaling is mislukt, foutmelding: '.$sisow->errorCode.", ".$sisow->errorMessage;
			return $sisow->errorCode;			
		}
		$this->redirectUrl = $sisow->issuerUrl;
		$this->logMessage("Setting redirect url: ".$this->redirectUrl);
	}
	
	
	public function doIDeal(){
		$this->doSisowTransaction();
	}
	
	public function doMisterCash(){
		$this->doSisowTransaction('mistercash');
	}
	
	
	public function getOgoneReply(){
		$this->logMessage("Creating Ogone reply, as a json string");
		
		$ret = (object) array(
			'type' => 'ogone',
			'form' => $this->buildOgoneForm($this->calculateSHASignForOgone())
		);
		
		$ret = json_encode($ret);
		$this->logMessage('Returning oGone reply: '.$ret);
		return $ret;
	}
	
	private function buildOgoneForm($sha){
		$env = ($this->options->getOption('OgoneTestMode') == 'true') ? 'test' : 'prod';
	
		$ret = '<form method="post" action="https://secure.ogone.com/ncol/'.$env.'/orderstandard.asp" id="ogone-form" name="ogoneform">
				<input type="hidden" name="PSPID" value="'.$this->hostname.'" />
				<input type="hidden" name="orderID" value="'.$this->insertedOrderId.'"/>
				<input type="hidden" name="amount" value="'.round($this->totalPrice * 100).'"/>
				<input type="hidden" name="currency" value="EUR"/>
				<input type="hidden" name="language" value="nl_NL"/>
				<input type="hidden" name="CN" value="'.$_POST['firstname'].' '.$_POST['surname'].'"/>				
				<input type="hidden" name="EMAIL" value="'.$_POST['email'].'"/>
				<input type="hidden" name="ownerZIP" value="'.$_POST['postcode'].'"/>
				<input type="hidden" name="owneraddress" value="'.$_POST['street'].'"/>
				<input type="hidden" name="ownertown" value="'.$_POST['city'].'"/>
				<input type="hidden" name="ownertelno" value="'.$_POST['phone'].'"/>
				<input type="hidden" name="SHASign" value="'.$sha.'"/>
				<input type="hidden" name="accepturl" value="'.site_url().'/success?status=Success" />
				<input type="hidden" name="declineurl" value="'.site_url().'/success?status=Declined"/>
				<input type="hidden" name="exceptionurl" value="'.site_url().'/success?status=Exception"/>
				<input type="hidden" name="cancelurl" value="'.site_url().'/success?status=Cancelled"/>
			</form>';
			
		//				<input type="hidden" name="ownercty" value="'.$_POST['city'].'"/> <-- this is probably country nog city!!!	
		return $ret;
	}
	
	private function calculateSHASignForOgone(){
		$passPhrase = $this->options->getOption('OgonePassPhrase');

		$ret = array();
		$ret[] = 'AMOUNT='.round($this->totalPrice*100);
		$ret[] = 'CURRENCY=EUR';
		$ret[] = 'CN='.$_POST['firstname'].' '.$_POST['surname'];
		$ret[] = 'LANGUAGE=nl_NL';
		$ret[] = 'ORDERID='.$this->insertedOrderId;
		$ret[] = 'PSPID='.$this->options->getOption('hostname');
		$ret[] = 'EMAIL='.$_POST['email'];
		$ret[] = 'OWNERZIP='.$_POST['postcode'];
		$ret[] = 'OWNERADDRESS='.$_POST['street'];
		//$ret[] = 'OWNERCTY='.$_POST['city']; <-- this probably country, not city
		$ret[] = 'OWNERTOWN='.$_POST['city'];
		$ret[] = 'OWNERTELNO='.$_POST['phone'];
		$ret[] = 'ACCEPTURL='.site_url().'/success?status=Success';
		$ret[] = 'DECLINEURL='.site_url().'/success?status=Declined';
		$ret[] = 'EXCEPTIONURL='.site_url().'/success?status=Exception';				
		$ret[] = 'CANCELURL='.site_url().'/success?status=Cancelled';
				
		sort($ret); //sort alphabetically
		
			//interleave with passphrase
		for($i = 0; $i < count($ret); $i++){
			$ret[$i]=$ret[$i].$passPhrase;
		}
		
		$toHash = implode("",$ret);

		
		$this->logMessage('to Hash: '.$toHash);
		$hash = sha1($toHash);
		$hash = strtoupper($hash);
		$this->logMessage('calculated hash: '.$hash);
		return $hash;
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
		date_default_timezone_set('Europe/Amsterdam');
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
	    
	    $this->logMessage("Posting to: ".$this->decodeParamsIntoGetString($post));
		
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
