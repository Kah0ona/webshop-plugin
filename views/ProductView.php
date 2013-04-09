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
	
	protected function renderScript(){
		?>
		<script type="text/javascript">
			webshopProducts = 
			
			<?php 
				$ret = array();
				foreach($this->data as $k=>$product) {
					$ret[] =  $this->model->encodeProductToJson($product,true); 
				}
				
				echo '['.implode($ret, ',').']';
			?>
		</script>
		<?php
	}

	public function render($data=null, $renderDetailOnOverview=false) { 
		$this->renderDetailOnOverview = $renderDetailOnOverview;
		
		if($data == null)
			$this->data = $this->model->getData();
		else 
			$this->data = $data;
	
		$this->renderScript();
		$this->renderFeaturedProducts();		
		$this->renderMain();
	}

	protected function renderFeaturedProducts(){
		
	}
	
	protected function shouldRenderRowHtmlStart($i){
		return $i%$this->numCols == 1;
	}
	
	protected function shouldRenderRowHtmlEnd($i){
		return $i%$this->numCols == 0;
	}
	
	protected function getDetailLink($product){
		return site_url().'/products/'.$product->Product_id.'#'.$product->productName;
	}

	
	protected function renderMain(){ 
		$span = $this->calculateSpan();
		$i = 1;
	?>	
		<?php echo $this->renderScript(); ?>
		<!-- Start rendering ProductView -->
		<div class="product-overview">
			<?php foreach($this->data as $k=>$v) : ?>
				<?php if($this->shouldRenderRowHtmlStart($i)) :?>
					<div class="row-fluid product-row">
				<?php endif; ?>	
						<div class="<?php echo $span; ?> product product-<?php echo $v->Product_id; ?>">
							<?php $this->renderProduct($v); ?>
						</div>
				<?php if($this->shouldRenderRowHtmlEnd($i)) :?>
					</div><!-- end row-fluid -->
				<?php endif; ?>
				<?php $i++; ?>				
			<?php endforeach; ?>
		</div>
		<!-- End ProductView -->
	 <?php
	 }

	 public function renderProduct($product){  ?>
		 <!-- Rendering single product -->
		 <div class="product-image">
		 	<a href="<?php echo $this->getDetailLink($product); ?>">
			 	<img src="<?php echo SYSTEM_URL_WEBSHOP.'/uploads/Product/'.$product->imageDish; ?>" />
		 	</a>
		 </div>
		 <div class="product-title product-title-<?php echo $product->Product_id; ?>">
		 	<?php echo $product->productName; ?>
		 </div>
		 <div class="product-price product-price-<?php echo $product->Product_id; ?>">
		 	€ <?php echo $this->formatMoney($product->productPrice); ?>
		 </div>
		 <?php if($this->renderDetailOnOverview) { 
				 	$v = new ProductDetailView(null);
				 	echo $v->renderOptionForm($product);
			 	}
		 ?>
		 <div class="product-detail-button product-detail-button-<?php echo $product->Product_id; ?>">
		 	<a href="<?php echo $this->getDetailLink($product); ?>" class="product-detail-link">details</a>
		 </div>
		 <!-- End Rendering single product -->
	 <?php 
	 }
}
?>