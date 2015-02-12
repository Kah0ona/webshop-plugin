<?php 


class GenericModel {
	protected $hostname;
	protected $options;	
	protected $data; //any data fetched will be put in this variable
	protected $serviceUrl = null;
	protected $rawData = null;
	
	protected $breadcrumbs = array();
	/*
	* When we are viewing a detail page of one item, this id field will be set.
	*/
	protected $id=null;

	function __construct($hostname, $options = null){
		$this->hostname = $hostname;
		$this->options = $options;
	}
	
	public function getId(){
		return $this->id;
	}
	
	
	public function setId($id){
		$this->id = $id;
	}
	public function setOptions($options){
		$this->options = $options;
	}

	
	protected function decodeParamsIntoGetString($params){
		$ret = "";
		$c = 0;
		foreach($params as $k=>$v){
			$ret .= ($c == 0) ? '' : '&';		
			if(is_array($v)){
				$c2 = 0;
				foreach($v as $v1){
					if($c2 != 0)
						$ret .= '&';
					$ret .= $k.'='.urlencode($v1);
					$c2++;
				}
			
			}
			else {
	
				$ret .= $k.'='.urlencode($v);
	
			}
			$c++;
		}
		return $ret;
	} 
	
	/**
	* Returns true if we are viewing a detail page. This is observed by examining the URL
	* This function has a side-effect: after calling this function, and iff it returned true,
	* a call to $this->getId() will return the id of the element (ie. a Category_id or a Product_id).
	*
	* @param $type can be categories | products 
	*/
	public function isDetailPage($type){
		//explode around /
		
		$pieces = explode('/' , $_SERVER['REDIRECT_URL']);

		$i = 0;
		foreach($pieces as $p){
			$i++;
			if($p == $type) //id is in the following piece, if there.
				break;
		}
		if(isset($pieces[$i]) && is_numeric($pieces[$i])){
			
			
			$this->id = $pieces[$i];
			
			
			return true;
		}
		else {
			return false;
		}
	}
	
	public function isCheckoutPage(){
		return strpos($_SERVER['REDIRECT_URL'], 'checkout') !== false;
	}
	

	public function fetchById($idKeyName, $asString = false, $useNesting = true){
		$arr = Array();
		if($this->id == null)
			throw new Exception('No ID set, make sure you use setId($id) to set an id');
	
		$arr[$idKeyName] = $this->id;
		$arr['useNesting'] = $useNesting ? "true":"false";
		$ret = $this->fetch($this->serviceUrl, $arr, $asString);
		if(!$asString && $ret != null && count($ret)> 0){
			return $ret[0];
		}
		elseif($asString){
			return $ret;
		}
		else return Array();
		
	}

	public function encodeProductToJson($pro,  $getString = true){
		$id = $pro->Product_id;
		$title = addslashes($pro->productName);	
		$desc = $pro->productDesc;
		$thumb = SYSTEM_URL_WEBSHOP.'/uploads/Product/'.$pro->imageDish;
		$quantity = $pro->amount;
		$price = $pro->productPrice;
		$VAT = $pro->productVAT;
		$brand = $pro->brand;
		$productNumber = $pro->productNumber;
		$processingTimeMinutes = $pro->processingTimeMinutes;
		$productDeliveryTime = $pro->productDeliveryTime;
		$quantumDiscount = $pro->quantumDiscount;
		$quantumDiscountPer = $pro->quantumDiscountPer;
		$productColor = $pro->productColor;
		$discount = $pro->productDiscount;
		$options = array();
		if($pro->ProductOption != null)
			$options = $pro->ProductOption;		

		$medialib = array();
		if($pro->MediaLibrary != null){
			$medialib = $pro->MediaLibrary;
		}
		if($pro->ProductProperty != null){
			$pp = $pro->ProductProperty;
		}
		if($quantity === null) {
			$quantity=1;
		}
		
		$skus = array();
		if($pro->SKU != null){
			$skus = $pro->SKU;
		}
			
		$jsonObj = array (
			"Product_id" => $id,
			"title" => $title,
			"desc" => $desc,
			"thumb" => $thumb,
			"quantity" => $quantity,
			"price"=> $price,
			"discount"=> $discount,
			"brand" => $brand,
			"color" => $productColor,
			"productNumber" => $productNumber,
			"VAT" => $VAT,
			"ProductOption"=> $options,
			"MediaLibrary"=>$medialib,
			"ProductProperty"=>$pp,
			"SKU"=> $skus,
			"processingTimeMinutes" => $processingTimeMinutes,
			"productDeliveryTime" => $productDeliveryTime,
			"quantumDiscount" => $quantumDiscount,
			"quantumDiscountPer" => $quantumDiscountPer
		);
		if($getString){
			return json_encode($jsonObj);				
		}
		else {
			return $jsonObj;
		}
	}

	public function getRawData(){
		return $this->rawData;
	}

	protected function fetch($url, $params, $getString = true){
		$this->data = null; //reset
		
	    $params['hostname'] = $this->hostname;
	    
	 	$url = $url.'?'.$this->decodeParamsIntoGetString($params);
		
		$jsonString = $this->curl_fetch($url);
		$this->rawData = $jsonString;		

		if($getString) {
			$this->data = $jsonString;

			return $jsonString;
		}
		else {
			$obj = json_decode($jsonString);
			if($this->isDetailPage() || ($this->id!=null && is_numeric($this->id))){
				if($obj != null && count($obj) > 0)
					$this->data = $obj[0];
			}
			else {
				$this->data = $obj;
			}
			
			return $obj;
		}
		
	}

	public function curl_fetch($url){
		$cached = $this->getCachedData($url);
		$cached=null; //comment this out this if u want caching
		if($cached != null){
			//return cachedData
			return $cached;
		}
		else {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
			$json = curl_exec($ch);
			curl_close($ch);
			$this->setCachedData($url, $json); 
			return $json;
		}
	}

	protected function getCachedData($url){
		if(isset($_SESSION[md5($url)])){
			return $_SESSION[md5($url)];
		}
		else {
			return null;
		}
	}

	protected function setCachedData($url, $data){
		$_SESSION[md5($url)] = $data;
	}
	
	public function setHostname($hostname){
		$this->hostname = hostname;
	}
	
	public function getHostname(){
		return $this->hostname;
	}
	
	public function getData(){
		return $this->data;
	}
	
	public function getOptions(){
		return $this->options;
	}
}
?>
