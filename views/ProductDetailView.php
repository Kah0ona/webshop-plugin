<?php 
/**
* Detailview, renders a list of products in this Product
*/
class ProductDetailView extends GenericView {

	protected $shouldRenderBackLink = true;
	
	public function setShouldRenderBackLink($b){
		$this->shouldRenderBackLink = $b;
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
	
	public function shouldDisplayBrand($brand = null) {
		return $this->model->getOptions()->getOption('show_brand') == true && $brand !== null;
	}

	public function shouldDisplayColor($color = null) {
		return $this->model->getOptions()->getOption('show_color') == true && $color !== null;
	}



	public function shouldDisplayProductNumber($nr = null) {
		return $this->model->getOptions()->getOption('show_article_number') == true && $nr !== null;
	}	
	
	
	public function doMarkdown($text) {

 //       $parser->no_markup = true;
   //     $parser->no_entities = true;
	    return nl2br(Markdown($text));
	}

	public function render($data=null) { 
		if($data == null)
			$data = $this->model->getData();
			
		if($this->shouldRenderBackLink)
			$this->renderBackLink();


		if($data != null){ ?>
				

		
			<script type="text/javascript">
				if(webshopProducts == null || webshopProducts == undefined){
					webshopProducts = [];
			    }
						
				webshopProducts.push(<?php echo $this->model->encodeProductToJson($data); ?>);
				
			</script>
			<script type="text/javascript">
				jQuery(document).ready(function($){
					function nl2br (str, is_xhtml) {
					  var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>'; // Adjust comment to avoid issue on phpjs.org display
					  return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
					}
				
					function findProductTextByOptionValue(optionValue){
						if(webshopProducts.ProductOption == null){ return null; }
						
						for(var i = 0 ; i < webshopProducts.ProductOption.length ; i++){
							var cur = webshopProducts.ProductOption[i];
							for(var j = 0 ; j < cur.ProductOptionValue.length ; j++){
								var cur2 = cur.ProductOptionValue[j];
								if(parseInt(cur2.ProductOptionValue_id) == parseInt(optionValue)){
									if(cur2.optionValueDescription == null || cur2.optionValueDescription == undefined){
										return "";
									}
									else {
										return nl2br(cur2.optionValueDescription);
									}
								}
							}
						}
						return "";
					}
					
					function replaceSomeWords(str){
						if(str != null) {
							str = str.replace(/Geaccepteerd wordt:/g, '<span class="greenaccept">Geaccepteerd wordt:</span>');
							str = str.replace(/Niet geaccepteerd wordt:/g, '<span class="redaccept">Niet geaccepteerd wordt:</span>');
						}
						return str;
					}
					
					$('.ProductOptionSelector').change(function(evt){
						var selector = '#productoptionvalueinfo-'+$(evt.target).attr('option_id');
					
						$(selector).html(replaceSomeWords(findProductTextByOptionValue($(evt.target).val())));
					});
					
					
					//init
					$('.ProductOptionSelector').each(function(){
						var evt = $(this);
						var selector = '#productoptionvalueinfo-'+evt.attr('option_id');
						$(selector).html(replaceSomeWords(findProductTextByOptionValue(evt.val())));
					});

				});
				

			</script>
		
			<div class="single-product product-<?php echo $data->Product_id; ?>" data-productid="<?php echo $data->Product_id; ?>" itemscope itemtype="http://schema.org/Product">
				<div class="row-fluid">
					<div class="span8 product-image">
						<?php if($data->imageDish != null && $data->imageDish != "/uploads/Product" && $data->imageDish != '') { ?>
						<img itemprop="image" alt="<?php echo $data->productName; if($data->productNumber != null){ echo ' '.$data->productNumber; } if($data->brand != null){ echo ' '.$data->brand; } ?>" src="<?php echo SYSTEM_URL_WEBSHOP.'/uploads/Product/'.$data->imageDish; ?>"  />
						<?php } elseif($this->model->getOptions()->getOption('NoImage') != null) { ?>
						<img itemprop="image" 
							 alt="<?php echo $data->productName; if($data->productNumber != null){ echo ' '.$data->productNumber; } if($data->brand != null){ echo ' '.$data->brand; } ?>" 
							 src="<?php echo $this->model->getOptions()->getOption('NoImage'); ?>"  />	
							
							
						<?php }?>
					</div>
					
					<div class="span4">
						<div class="row-fluid">
							<div class="span12">
								<h3 itemprop="name"><?php echo $data->productName; ?></h3>
								<?php if($this->shouldDisplayBrand($data->brand)) : ?>
								<p itemprop="brand" itemscope itemtype="http://schema.org/Brand" class="product-brand"><strong>Merk:</strong> <span itemprop="name"><?php echo $data->brand; ?></span></p>
								<?php endif; ?>
								<?php if($this->shouldDisplayColor($data->productColor)) : ?>
								<p itemprop="color" itemscope itemtype="http://schema.org/Color" class="product-color"><strong>Kleur:</strong> <span itemprop="name"><?php echo $data->productColor; ?></span></p>												<?php endif; ?>
								<?php if($this->shouldDisplayProductNumber($data->productNumber)) : ?>
								<p class="product-productnr"><strong>Artikelnummer:</strong> <?php echo $data->productNumber; ?></p>
								<?php endif; ?>
								<p class="product-description" itemprop="description">
									<?php echo $this->doMarkdown($data->productDesc); ?>
								</p>
								<div itemprop="offers" itemscope itemtype="http://schema.org/Offer">
									<p class="product-price" itemprop="price">
									<?php if($this->containsProductWithExtraPrice($data->ProductOption)) { echo 'vanaf '; } ?>
										€ <?php echo $this->formatMoney($this->model->calculateProductPrice($data)); ?>
									</p>
									<meta itemprop="priceCurrency" content="EUR" />
									<link itemprop="availability" href="http://schema.org/InStock" />
								</div>

							</div><!-- /span12 -->
						</div><!-- /row-fluid -->
						<div class="row-fluid">
							<div class="span12 product-data">
								<?php 
									if($data->priceOnDemand){ 								
										echo $this->renderPriceOnDemandForm($data);
									}
									else {
										echo $this->renderOptionForm($data); 
									}
									
								 ?>
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
		<div class="skuInfo alert alert-error skuInfo-<?php echo $data->Product_id; ?>"></div>
		<form class="form <?php echo $data->priceOnDemand ? 'hidden' : ''; ?>">
		    <div class="control-group" style="margin-top: 15px;">
				<label class="control-label control-label-<?php echo $data->Product_id; ?>" for="surname">Aantal: *</label>
				
				<div class="controls">		
					<input class="input-large product-amount-<?php echo $data->Product_id; ?>  " name="product-amount" id="product-amount-<?php echo $data->Product_id; ?>" value="1" type="text" /> 
				</div>
			</div>
			<!-- Product options, if any -->
		    <?php foreach($data->ProductOption as $p){ ?>
			<div class="control-group" style="margin-top: 15px;">
				<label class="control-label control-label-<?php echo $data->Product_id; ?>" id="ProductOptionName_<?php echo $p->ProductOption_id; ?>" for="<?php echo $p->optionName; ?>"><?php echo $p->optionName; ?>:</label>
				
				<div class="controls controls-<?php echo $data->Product_id; ?>">		
					<select name="size" class="input-large ProductOptionSelector" id="ProductOption_<?php echo $p->ProductOption_id; ?>" option_id="<?php echo $p->ProductOption_id; ?>">
						<?php
							$lowestValueId = 0;
							$lowestValue = 999999;
							foreach($p->ProductOptionValue as $v){
								$o = $v->extraPrice;
								if($v->extraPrice == null)
									$o = 0;
								
								if($o < $lowestValue) {
									$lowestValueId = $v->ProductOptionValue_id;
									$lowestValue = $o;
								}	
							}
						?>
					
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
										<?php if($v->ProductOptionValue_id == $lowestValueId) { echo ' selected '; } ?>
										id="ProductOptionValueName_<?php echo $v->ProductOptionValue_id; ?>">
											<?php echo $v->optionValue; ?>
									<?php if($v->extraPrice !== null) { ?>
										(€ <?php echo $this->formatMoney($this->model->calculatePriceWithOption($data, $v->extraPrice)); ?>)
									<?php } ?>
								</option>
							<?php } ?>
						<? } ?>										
					</select>
					<p class="productoptionvalueinfo" id="productoptionvalueinfo-<?php echo $p->ProductOption_id; ?>"></p>
				</div>
			</div>	
			<? } ?>														    
		  </form>	
		  <span product-type="product" product-index='0' product-id='<?php echo $data->Product_id; ?>' class="addtocart <?php echo $data->priceOnDemand ? 'hidden' : ''; ?>">
			<a href="#" class="btn" ><i class="icon-shopping-cart icon-white"></i> Toevoegen</a>
		  </span>
		  
		  <div class="share-product" style="margin-top: 10px;">
			<!-- AddThis Button BEGIN -->
			<div class="addthis_toolbox addthis_default_style addthis_32x32_style">
			<a class="addthis_button_preferred_1"></a>
			<a class="addthis_button_preferred_2"></a>
			<a class="addthis_button_preferred_3"></a>
			<a class="addthis_button_preferred_4"></a>
			<a class="addthis_button_compact"></a>
			<a class="addthis_counter addthis_bubble_style"></a>
			</div>
			<script type="text/javascript">
				var addthis_config = {"data_track_addressbar":true};
				var addthis_share = {"url": '<?php echo site_url().'/products/'.$data->Product_id.'/#'.$product->productName; ?>'};
			</script>
			
			<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-52076b1967f6cd45"></script>
			<!-- AddThis Button END -->
		  </div>			
	<?php 
	}
	
	
	public function renderPriceOnDemandForm($data){ 
		wp_enqueue_script('form.js', plugins_url('/webshop-plugin/js/jquery.form.js'), array('jquery'));
		wp_enqueue_script('validation.js', plugins_url('/webshop-plugin/js/jquery.validate.js'), array('jquery', 'form.js'));
	?>
	
		<script type="text/javascript" >
		
		var reqMsg = "Dit veld is verplicht.";
		var emailMsg = "Vul een geldig e-mailadres in. ";

		jQuery(document).ready(function($) {
			var submitOptions = {
				beforeSubmit : function(arr, form, options){
					return true;	
				},
				data : { 
					"action": 'price_quote',
				},
				success : function(data, textStatus, jqXHR) {
					//console.log(data);
					$('#order-form').addClass('hidden');
					$('.successmsg').html('Bedankt voor uw aanvraag. We nemen z.s.m. contact met u op.').addClass('alert').addClass('alert-success');
				}
			};
			
			var validationOptions = {
					rules : {
						name : {
							required: true
						},
						email : {
							required: true
						},
						phone : {
							required: true
						},
						orderComment : {
							required: true
						},
						street: {
						   required: true

					    },
					    city: {
						   required: true

					    },
					},
				
					messages : {
						name: {
						   required: reqMsg
					    },
						email: {
						   required: reqMsg
					    },
						phone: {
						   required: reqMsg
					    },
						orderComment: {
						   required: reqMsg
					    },
					    street: {
						   required: reqMsg
					    },
					    city: {
						   required: reqMsg
					    },
					},
				    errorPlacement: function(error, element) {
					   error.insertAfter(element);
					}
					,
					submitHandler : function(){
						$('#order-form').ajaxSubmit(submitOptions);	//does some extra validation.		
					},
					invalidHandler: function(form, validator) {
						//alert('invalid');
					}
			}; 
					
		    $('#order-form').validate(validationOptions);

		});
		</script>	
	
		<div style="clear:both"></div>
		<div class="successmsg"></div>
		<form id="order-form" name="order-form_" class="form" action="<?php echo site_url(); ?>/wp-admin/admin-ajax.php" method="post">
			<div class="control-group" style="margin-top: 15px;">
				<label class="control-label control-label-ondemand" for="name">Naam: *</label>
				<div class="controls">		
					<input class="input-large " name="name" id="name" type="text" /> 
				</div>
			</div>
			<div class="control-group" style="margin-top: 15px;">
				<label class="control-label control-label-ondemand" for="email">Email: *</label>
				<div class="controls">		
					<input class="input-large " name="email" id="email" type="text" /> 
				</div>
			</div>
			<div class="control-group" style="margin-top: 15px;">
				<label class="control-label control-label-ondemand" for="street">Adres: *</label>
				<div class="controls">		
					<input class="input-large " name="street" id="street" type="text" /> 
				</div>
			</div>
			<div class="control-group" style="margin-top: 15px;">
				<label class="control-label control-label-ondemand" for="city">Plaats: *</label>
				<div class="controls">		
					<input class="input-large " name="city" id="city" type="text" /> 
				</div>
			</div>
			<div class="control-group">
				<label class="control-label" for="orderComment">Bericht:</label>			
				<div class="controls">	
					<textarea id="orderComment" name="orderComment" class="input-large" rows="10">Graag vraag ik een offerte aan voor: '<?php echo $data->productName; ?>'. 
[Type hier uw verdere wensen]
					</textarea>
				</div>		
			</div>
			<div class="controls">	
				<input type="hidden" name="productName" value="<?php echo $data->productName; ?>" />
				<input type="hidden" name="Product_id" value="<?php echo $data->Product_id; ?>" />
				<input type="submit" name="submit" class="submit-controls btn btn-primary " id="invoice" value="Vraag offerte op" style="width: 130px;" />
			</div>
			
		</form>
		
	
	
		
	<?php 
	}
}