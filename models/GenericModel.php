<?php 
define('SYSTEM_URL_WEBSHOP', 'http://webshop.sytematic.nl');
define('BASE_URL_WEBSHOP', SYSTEM_URL_WEBSHOP.'/public');
define('EURO_FORMAT', '%.2n');

setlocale(LC_MONETARY, 'it_IT');

class GenericModel {
	protected $hostname;
	protected $options;	

	function __construct($hostname, $options = null){
		$this->hostname = $hostname;
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
}
?>