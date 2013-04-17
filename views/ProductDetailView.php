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
						<img src="<?php echo SYSTEM_URL_WEBSHOP.'/uploads/Product/'.$data->imageDish; ?>" />
					</div>
					
					<div class="span4">
						<div class="row-fluid">
							<div class="span12">
								<h3><?php echo $data->productName; ?></h3>
								<p class="product-description">
									<?php echo $data->productDesc; ?>
								</p>
								<p class="product-price">
									€ <?php echo $this->formatMoney($data->productPrice); ?>
								</p>
							</div><!-- /span12 -->
						</div><!-- /row-fluid -->
						<div class="row-fluid">
							<div class="span12 product-data">
								<?php echo $this->renderOptionForm($data); ?>
								 
													
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
	
	/**
	* @param $data contains data of one product
	*/
	public function renderOptionForm($data){ ?>
		<div style="clear:both"></div>
		<form class="form">
		    <div class="control-group" style="margin-top: 15px;">
				<label class="control-label control-label-<?php echo $data->Product_id; ?>" for="surname">Aantal: *</label>
				
				<div class="controls">		
					<input class="input-large product-amount-<?php echo $data->Product_id; ?>" name="product-amount" id="product-amount-<?php echo $data->Product_id; ?>" value="1" type="text" /> 
				</div>
			</div>
			<!-- Product options, if any -->
		    <?php foreach($data->ProductOption as $p){ ?>
			<div class="control-group" style="margin-top: 15px;">
				<label class="control-label control-label-<?php echo $data->Product_id; ?>" id="ProductOptionName_<?php echo $p->ProductOption_id; ?>" for="<?php echo $p->optionName; ?>"><?php echo $p->optionName; ?>:</label>
				
				<div class="controls controls-<?php echo $data->Product_id; ?>">		
					<select name="size" class="input-large " id="ProductOption_<?php echo $p->ProductOption_id; ?>">
						<?php foreach($p->ProductOptionValue as $v ) {?>
							<?php if((count($data->ProductOption) == 1 &&
							         $this->model->productIsInStockSimple($data, $v->ProductOptionValue_id)
							         )
							         ||
							         (count($data->ProductOption) > 1)
							         ) { ?>
								<option extraPrice="<?php echo $v->extraPrice; ?>" 
										value="<?php echo $v->ProductOptionValue_id; ?>" 
										valueName="<?php echo $v->optionValue;?>" 
										id="ProductOptionValueName_<?php echo $v->ProductOptionValue_id; ?>">
											<?php echo $v->optionValue; ?>
									<?php if($v->extraPrice != null && $v->extraPrice > 0) { ?>
										(€ <?php echo $this->formatMoney($data->productPrice+ $v->extraPrice); ?>)
									<?php } ?>
								</option>
							<?php } ?>
						<? } ?>										
					</select>
				</div>
			</div>	
			<? } ?>														    
		  </form>	
		  <span product-type="product" product-index='0' product-id='<?php echo $data->Product_id; ?>' class="addtocart">
			<a href="#" class="btn" ><i class="icon-shopping-cart icon-white"></i> Toevoegen</a>
		  </span>			
	<?php }
}