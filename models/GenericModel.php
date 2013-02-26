<?php 
define('SYSTEM_URL_WEBSHOP', 'http://webshop.sytematic.nl');
define('BASE_URL_WEBSHOP', SYSTEM_URL_WEBSHOP.'/public');
define('EURO_FORMAT', '%.2n');

setlocale(LC_MONETARY, 'it_IT');

class GenericModel {
	protected $hostname;
	protected $options;	
	protected $data; //any data fetched will be put in this variable
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

	public function fetchById($idKeyName, $asString = false){
		$arr = Array()
		if($this->id == null)
			throw new Exception('No ID set, make sure you use setId($id) to set an id');
		
		$arr[$idKeyName] = $this->id;
		
		return $this->fetch($url, $arr, $asString);
		
	}

	protected function encodeProductToJson($pro,  $getString = true){
		$id = $pro->Product_id;
		$title = addslashes($pro->productName);	
		$desc = $pro->productDesc;
		$thumb = SYSTEM_URL_WEBSHOP.'/'.$pro->imageProduct;
		$quantity = $pro->amount;
		$price = $pro->productPrice;
		$VAT = $pro->productVAT;
			
		if($quantity === null) {
			$quantity=1;
		}
			
		$jsonObj = array (
			"Product_id" => $id,
			"title" => $title,
			"desc" => $desc,
			"thumb" => $thumb,
			"quantity" => $quantity,
			"price"=> $price,
			"VAT" => $VAT,
		);
		if($getString){
			return json_encode($jsonObj);				
		}
		else {
			return $jsonObj;
		}
	}

	protected function fetch($url, $params, $getString = true){
	 	$url = $url.'?'.$this->decodeParamsIntoGetString($params);
		$jsonString = $this->curl_fetch();
		
		if($getString)
			return $jsonString;
		else 
			return json_decode($jsonString);
		
	}

	protected function curl_fetch($url){
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
			$json = curl_exec($ch);
			curl_close($ch);
			$this->setCachedData($url, $json); 
			$this->data = $json;
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
}
?>