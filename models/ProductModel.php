<?php
class ProductModel extends GenericModel {
	protected $sortedMap=null;
	protected $categoryTitleOrder=null;
	protected $serviceUrl = null;
	
	function __construct($hostname) {
		$this->hostname=$hostname;
		$this->serviceUrl = BASE_URL_WEBSHOP.'/products';
	} 
		
	public function fetchProducts($params, $returnString=false){
		return $this->fetch($this->serviceUrl, $params, $returnString);
	}
	
	/**
	* Fetches the category with the id currently set by $this->setId(), or set by a previous call to $this->isDetailPage(); 
	*/
	public function fetchProduct(){
		return $this->fetchByID('Product_id');
	}
	
	public function isDetailPage(){
		return parent::isDetailPage('products');
	}
	
	public function fetchProductsDefault(){
		$arr = array(
			'hostname'=>$this->hostname,
			'useNesting'=>'true'
		);
		$prods = $this->fetchProducts($arr);	
		return $prods;
	}
		
}
?>