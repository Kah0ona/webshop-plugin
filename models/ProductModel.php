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
	
	public function getNumProductOptions($product){
		if($product->ProductOption == null)
			return 0;
		else
			return count($product->ProductOption);
	}
	
	public function productsOverviewEnabled(){
		return !($this->options->getOption('productoverview_disabled') === 'true');
	}
	
	/**
	* This method returns _false_ iff the following criteria are met:
	* - The product has exactly 0 or 1 ProductOption set
	* - For each product option value, there is a SKU record 
			(ie. no sku record for one or more of the option values means show the product, that is, return true)
	* - For each SKU record, the skuQuantity field is <= 0
	* - In case there are 0 productOption set, iff there is a SKU row without optionvalues set 
	*   AND the skuQuanity <= 0, return false (will get the first matching row, if there are multiple such sku rows)
	* Returns true otherwise
	*/
	public function shouldShowProductBasedOnSku($product){
		if($product->ProductOption == null || count($product->ProductOption) == 0){
			return $this->checkStockForOptionlessProduct($product);			
		}
		elseif(count($product->ProductOption) == 1) {
			return $this->checkStockForProductWithOneOption($product);
		}
		else { // #product options > 1
			return true;
		}
	}
	
	private function checkStockForOptionlessProduct($product){
		if($product->SKU == null || count($product->SKU) == 0){
			return true;
		}

		foreach($product->SKU as $k=>$sku){
			if($sku->ProductOptionValue == null || count($sku->ProductOptionValue) == 0){
				return $sku->skuQuantity == null || $sku->skuQuantity > 0;
			}
		}
		return true;
	}
	
	
	private function checkStockForProductWithOneOption($product){
		if($product->SKU == null || count($product->SKU) == 0){
			return true;
		}
		$ret = true;
		foreach($product->ProductOption[0]->ProductOptionValue as $values){
			$exists = false;
			foreach($product->SKU as $k=>$sku){
				if($sku->ProductOptionValue != null && count($sku->ProductOptionValue) == 1 &&
					$sku->ProductOptionValue[0]->ProductOptionValue_id == $values->ProductOptionValue_id
					&& $sku->skuQuantity <= 0
					){
					$exists = true;
					break;
				}
			}
			if(!$exists) {
				return true;
			}
		}
		return false;		
	}
	/**
	* checks if the given product / ProductOptionValue_id combination is in stock
	* If the product option has influencesSKU set to false, it always returns true;
	* @requires $product to have EXACTLY 1 ProductOption, and the SKUs of this product, 
	* should also have one $productOptionValue_id for each SKU set.
	*/
	public function productIsInStockSimple($product, $productOptionValue_id){
		if($this->getNumProductOptions($product) != 1){
			throw new Exception('Illegal argument: $product should have exactly 1 productOption');
		}
		if(!$product->ProductOption[0]->influencesSKU){
			return true;
		}

		foreach($product->SKU as $k=>$sku){
			if($sku->ProductOptionValue == null || count($sku->ProductOptionValue) != 1){
				//ignoring, we assume that the sku should have exactly one ProductOptionValue, in order to count
				continue;
			}
			if($sku->ProductOptionValue[0]->ProductOptionValue_id == $productOptionValue_id ) {
				return $sku->skuQuantity > 0;
			}	
		}
		
		return true;
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