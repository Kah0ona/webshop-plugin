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
		$show = false;
		if($this->getOptions()->getOption('ShowProductsInStock') == null || 
		   $this->getOptions()->getOption('ShowProductsInStock') == 'show' ) {
			$show = true;
		}
		
		if($show){ //show means we hand it off to javascript, which just will say 'in stock' or 'not in stock', it' not the servers job to hide it up-front
			return true;
		}
		
		if($product->ProductOption == null || count($product->ProductOption) == 0){
			return $this->checkStockForOptionlessProduct($product);			
		}
		else {
			return $this->checkStockForProductWithOneOrMoreOption($product);
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
	
	/**
    * checks if the given product / ProductOptionValue_id combination is in stock
    * If the product option has influencesSKU set to false, it always returns true;
    * If in the backend of wordpress, it's set that we DO show sold out items, it will return true as well
    * @requires $product to have EXACTLY 1 ProductOption, and the SKUs of this product, 
    * should also have one $productOptionValue_id for each SKU set.
    */
    public function productIsInStockSimple($product, $productOptionValue_id){
        if($this->getNumProductOptions($product) != 1){
           throw new Exception('Illegal argument: $product should have exactly 1 productOption');
        }
        
        if($this->options->getOption('ShowProductsInStock') == null || 
		   $this->options->getOption('ShowProductsInStock') == 'show' ) {
		   return true;
		}
        
        if(!$product->ProductOption[0]->influencesSKU ){
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

	
	private function checkStockForProductWithOneOrMoreOption($product){
		if($product->SKU == null || count($product->SKU) == 0){ //no sku row means that we always show it, since the shop owner doesnt use the SKU system for this product.
			return true;
		}
		$ret = true;
		
		//is there any combination of productoptionvalues where there is no sku row ? => show product
		$multiplier = 1;
		foreach($product->ProductOption as $opt){
			if($opt->influencesSku){
				$multiplier = $multiplier * count($opt->ProductOptionValue);
			}
		}
		
		if(count($product->SKU) < $multiplier) { //At least one sku row must be missing. This means we show the product anyway. 
			return true;
		} else { //now we just have to loop through the skus, and see if the are all qty > 0
			foreach($product->SKU as $sku){
				if($sku->skuQuantity > 0){
					return true;
				}
			}
			return false;
		}
	}
	
	public function calculateProductPrice($product){
		$discFactor = 1;
		if($product->productDiscount != null){
			$discFactor = 1 - ($product->productDiscount/100);
		}
		
		return $product->productPrice * $discFactor;
	}
	
	public function calculatePriceWithOption($product, $extraPrice){
		$discFactor = 1;
		if($product->productDiscount != null){
			$discFactor = 1 - ($product->productDiscount/100);
		}
		
		return ($product->productPrice+$extraPrice) * $discFactor;
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