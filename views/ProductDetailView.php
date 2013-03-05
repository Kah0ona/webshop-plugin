<?php 
/**
* Detailview, renders a list of products in this Product
*/
class ProductDetailView extends GenericView {

	public function render($data=null) { 
		if($data == null)
			$data = $this->model->getData();
			
		$this->renderBackLink();

		if($data != null){  ?>
			<script type="text/javascript">
				webshopProduct = <?php echo $this->model->encodeProductToJson($data); ?>;
			</script>
		
		
			<div class="single-product">
				<div class="row-fluid">
					<div class="span8 product-image">
						<img src="<?php echo SYSTEM_URL_WEBSHOP.'/'.$data->imageDish; ?>" />
					</div>
					
					<div class="span4">
						<div class="row-fluid">
							<div class="span12">
								<h3><?php echo $data->productName; ?></h3>
								<p class="product-description">
									<?php echo $data->productDesc; ?>
								</p>
								<p class="product-price">
									<?php echo $this->formatMoney($data->productPrice); ?>
								</p>
							</div><!-- /span12 -->
						</div><!-- /row-fluid -->
						<div class="row-fluid">
							<div class="span12 product-data">
								<br/>
								<h4>Aantal:
								    <span class="small">
								    	<input class="input-small" name="product-amount" id="product-amount" value="1" type="text" /> 
								    </span>
							    </h4>							
								<span product-type="product" product-index='0' class="addtocart">
									<a href="#" class="btn" ><i class="icon-shopping-cart icon-white"></i> Toevoegen</a>
						  		</span>							
							</div><!-- /span12 -->
						</div><!-- /row-fluid -->
					</div><!-- /span4 -->
				</div><!-- /row-fluid -->
			</div><!-- /single-product -->
		<?php
		}
		else {
			echo '<div class="product-not-found">Dit product bestaat niet (meer).</div>';
		}
	}
}