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
									€ <?php echo $this->formatMoney($data->productPrice); ?>
								</p>
							</div><!-- /span12 -->
						</div><!-- /row-fluid -->
						<div class="row-fluid">
							<div class="span12 product-data">
								 <form class="form-horizontal">
								    <div class="control-group" style="margin-top: 15px;">
										<label class="control-label" for="surname">Aantal: *</label>
										
										<div class="controls">		
											<input class="input-small" name="product-amount" id="product-amount" value="1" type="text" /> 
										</div>
									</div>
									<!-- Product options, if any -->
								    <?php foreach($data->ProductOption as $p){ ?>
									<div class="control-group" style="margin-top: 15px;">
										<label class="control-label" id="ProductOptionName_<?php echo $p->ProductOption_id; ?>" for="<?php echo $p->optionName; ?>"><?php echo $p->optionName; ?>:</label>
										
										<div class="controls">		
											<select name="size" class="input-small" id="ProductOption_<?php echo $p->ProductOption_id; ?>">
												<?php foreach($p->ProductOptionValue as $v ) {?>
												<option value="<?php echo $v->ProductOptionValue_id; ?>" id="ProductOptionValueName_<?php echo $v->ProductOptionValue_id; ?>"><?php echo $v->optionValue; ?>
													<?php if($v->extraPrice != null) { ?>
														(€ <?php echo $this->formatMoney($v->extraPrice); ?>)
													<?php } ?>
												</option>
												<? } ?>										
											</select>
										</div>
									</div>	
									<? } ?>														    
								  </form>
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