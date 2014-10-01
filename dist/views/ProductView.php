<?php 
/**
* Overview, showing an overview of products
*/
class ProductView extends GenericView {
	protected $numCols = 3;
	protected $data = null;
	protected $renderDetailOnOverview = false;
	public function setNumCols($num){
		$this->numCols = $num;
	}
	
	public function containsProductWithExtraPrice($options){
		if($options == null) return false;
		$ret = false;
		foreach($options as $option){
			foreach($option->ProductOptionValue as $v){
				if($v->extraPrice != null && $v->extraPrice > 0)
					return true;
			}
		}
		return $ret;
	}

	
	protected function calculateSpan(){
		$numColName = '';
		switch($this->numCols){
			case 1:
				$numColName='span12';
			break;
			case 2:
				$numColName='span6';
			break;
			case 3:
				$numColName='span4';
			break;
			case 4:
				$numColName='span3';
			break;
			case 6:
				$numColName='span2';
			break;
			case 12: 
				$numColName='span1';	
			break;
			default:
				$numColName='span4';
			break;
		}
		return $numColName;
	}
	
	public function render($data=null, $renderDetailOnOverview=false, $categoryId = null) { 
		$this->renderDetailOnOverview = $renderDetailOnOverview;
		
		if($data == null)
			$this->data = $this->model->getData();
		else 
			$this->data = $data;
	
		$this->renderScript();
		$this->renderMain($categoryId);
	}


	protected function renderScript(){
		?>
		<script type="text/javascript">
			var tmp = 
			
			<?php 
				$ret = array();
				foreach($this->data as $k=>$product) {
					$ret[] =  $this->model->encodeProductToJson($product,true); 
				}
				
				echo '['.implode($ret, ',').']';
			?>;
			
			for(var i = 0; i < tmp.length; i++){
				webshopProducts.push(tmp[i]);
			}
			
		</script>
		<?php
	}


	protected function shouldRenderRowHtmlStart($i, $product){
		return $i%$this->numCols == 1 || $product->productFeatured || $this->numCols == 1;
	}
	
	protected function shouldRenderRowHtmlEnd($i, $product){		
		return $i%$this->numCols == 0 || $product->productFeatured  || $this->numCols == 1;
	}
	
	public function shouldDisplayBrand($brand = null) {
		return $this->model->getOptions()->getOption('show_brand') == true && $brand !== null;
	}
	
	
	protected function getDetailLink($product){
		$brand = '';
		if($product->brand != null)
			$brand = $product->brand.'-';
		
		$prod = $brand.$product->productName;
		return site_url().'/products/'.$product->Product_id.'/#'.$prod;
	}
	
	protected function renderMain($categoryId = null){ 

		$span = $this->calculateSpan();

		if($this->data == null){
			$this->data = array();
		}
			
		$i = 1;
	?>	
		<!-- Start rendering ProductView -->
		<div class="product-overview <?php echo $categoryId != null ? 'product-category-'.$categoryId : ""; ?>">
			<?php foreach($this->data as $k=>$v) : ?>
				<?php 
				if($v->productFeatured) { 
					$span = 'span12'; 
				} 
				?>
				<?php if($this->model->shouldShowProductBasedOnSku($v)) : ?>
					<?php if($this->shouldRenderRowHtmlStart($i, $v)) :?>
						<div class="row-fluid product-row">
					<?php endif; ?>	

							<div data-productid="<?php echo $v->Product_id; ?>"  class="<?php echo $span; ?> product product-<?php echo $v->Product_id; ?> <?php echo $this->shouldRenderRowHtmlEnd($i, $v) ? 'last' : ''; ?>"
								itemscope 
								itemtype="http://schema.org/Product">
								<?php $this->renderProduct($v, $this->model); ?>
							</div>
					<?php if($this->shouldRenderRowHtmlEnd($i, $v)) :?>
						</div><!-- end row-fluid -->
					<?php endif; ?>
					<?php $i++; ?>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		
		<?php 
		
		if($this->model->getOptions()->getOption('use_pagination') == 'true'){
			$this->renderPagination($categoryId);
		}
		?>		
		
		<!-- End ProductView -->
	 <?php
	 }
	 
	 public function renderPagination($categoryId=null){
	 ?>
	 	<script type="text/javascript">
	 		<?php 
	 		$curPage = $_GET['webshop_page'];
	 		if($curPage == null) {
				$curPage = 0;
			}
	 		?>
	 	
	 		//fetch the next page using ajax, after page has been loaded, and if it's empty hide the 'next' button
	 		jQuery(document).ready(function($){
	 		<?php
				echo 'var current_page='.$curPage.'; ';
				echo 'var num_per_page='.$this->model->getOptions()->getOption('num_items_per_page').'; ';
				echo 'var hostname="'.$this->model->getOptions()->getOption('hostname').'"; ';			
				echo 'var cat_id='.$categoryId.'; ';						
				echo 'var backend_url="'.BASE_URL_WEBSHOP.'/products"; ';
				echo 'var sale="'.$this->model->getSaleParam().'"; ';
	 		?>
		 		$.ajax({
					url: backend_url,
					data : {
						"Category_id" : cat_id,
						"hostname" : hostname,
						"sale" : sale,
						"start" : ((current_page+1) * num_per_page),						
						"limit" : 1									
					},
					type: 'GET',
					success: function (jsonObj, textStatus, jqXHR){
						if(jsonObj.length == 0){
							$('.webshop_pagination .next').hide();
						}
					},
					dataType: 'jsonp'
				});
	 		});
	 	
	 	</script>
	 <?php
	 
	 	 echo '<div class="webshop_pagination">';
		 if($_GET['webshop_page'] != null && $_GET['webshop_page'] > 0){
			 $this->renderPrev();
		 }
		 
		 if(count($this->data) >= $this->model->getOptions()->getOption('num_items_per_page')){
			$this->renderNext();		 
		 }
		 echo '</div><div style="clear:both"></div>';
	 }
	 
	 
	 public function renderPrev(){ 
		 $curPage = $_GET['webshop_page'];
		 if($curPage == null) {
			 $curPage = 0;
		 }
	 ?>
		 <div class="prev"><a href="<?php echo $_SERVER['REDIRECT_URL']; ?>?webshop_page=<?php echo ($curPage-1); ?>">‹ Vorige</a></div>
		 <?php
	 }
	 
	 public function renderNext(){ 
 		 $curPage = $_GET['webshop_page'];
		 if($curPage == null) {
			 $curPage = 0;
		 }
	 ?>
	 
		 <div class="next"><a href="<?php echo $_SERVER['REDIRECT_URL']; ?>?webshop_page=<?php echo ($curPage+1); ?>">Volgende ›</a></div>		 
		 <?php
	 }

	 public function renderProduct($product, $productModel=null){  ?>
		 <!-- Rendering single product -->
		 <div class="product-image">
		 	<?php if($product->productDiscount != null){ ?>
			 	<div class="product-discount-label">-<?php echo $product->productDiscount; ?>%</div>
		 	<?php } ?>
 			<?php if($product->imageDish != null && $product->imageDish != "/uploads/Product" && $product->imageDish != '') { ?>
		 	<a href="<?php echo $this->getDetailLink($product); ?>">
			 	<img src="<?php echo SYSTEM_URL_WEBSHOP.'/uploads/Product/'.$product->imageDish; ?>" />
		 	</a>
		 	<?php } elseif($this->model->getOptions()->getOption('NoImage') != null ) { ?>
						<img itemprop="image" 
							 alt="<?php echo $data->productName; if($data->productNumber != null){ echo ' '.$data->productNumber; } if($data->brand != null){ echo ' '.$data->brand; } ?>" 
							 src="<?php echo $this->model->getOptions()->getOption('NoImage'); ?>"  />	
							
							
			<?php }?>
		 </div>
		 <div class="product-data-container">
			 <div class="product-title product-title-<?php echo $product->Product_id; ?>" itemprop="name">
			 	<?php if($this->shouldDisplayBrand($product->brand)) { echo $product->brand.' - '; }?><?php echo $product->productName; ?> 
			 </div>
		 
			 <div itemprop="offers" itemscope itemtype="http://schema.org/Offer" >
				 <?php if($product->priceOnDemand) {  ?>
					 <div class="product-price product-price-<?php echo $product->Product_id; ?> price-on-demand">Prijs op aanvraag, <br/>zie details.</div>
				 <?php } else { ?>
		
				 <?php if($product->productDiscount != null){ ?>
				 <div class="product-price-from product-price-from-<?php echo $product->Product_id; ?>">
				 	<del>€ <?php echo $this->formatMoney($product->productPrice); ?></del>
				 </div>
				  <?php } ?>
		
				 <div class="product-price product-price-<?php echo $product->Product_id; ?>" itemprop="price">
				 	<?php if($this->containsProductWithExtraPrice($product->ProductOption)){ echo 'vanaf '; } ?>
				 	
				 	€ <?php echo $this->formatMoney($this->model->calculateProductPrice($product)); ?>
				 </div>
				 <meta itemprop="priceCurrency" content="EUR" />
				 <link itemprop="availability" href="http://schema.org/InStock" />
				 <?php } ?>
				 <?php if($this->renderDetailOnOverview) { 
						 	$v = new ProductDetailView($productModel);
							echo $v->renderOptionForm($product); 
					 	}
				 ?>
				 <div class="product-detail-button product-detail-button-<?php echo $product->Product_id; ?>">
				 	<a href="<?php echo $this->getDetailLink($product); ?>" class="product-detail-link">details</a>
				 </div>
				 <div style="clear:both"></div>
	 		 </div>
		 </div>

		 <!-- End Rendering single product -->
	 <?php 
	 }
}
?>