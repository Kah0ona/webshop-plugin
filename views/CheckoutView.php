<?php
/**
* Renders the checkout page
*/
class CheckoutView extends GenericView {
	protected $paymentMethodModel=null;
	private function renderIDealForm(){
		$sisow = new Sisow($this->model->getOptions()->getOption('SisowMerchantId'), 
						   $this->model->getOptions()->getOption('SisowMerchantKey')
						   );
		$select = null;
		$testMode = $this->model->getOptions()->getOption('SisowTestModus') === 'true' ?  true : false; 
		
		$sisow->DirectoryRequest($select, true, $testMode);
		return $select;
	}

	private function renderTargetpayIDealForm(){
		$select = '<select name="targetpaybank" class="valid" id="targetpaybank">
					<script src="https://www.targetpay.com/ideal/issuers-nl.js"></script>
				   </select>';
		return $select;
	}

	private function getProductOptionString($product){
		if(isset($product->ProductOption) && count($product->ProductOption) > 0){
			$s = array();

			$ret = "(";
			foreach($product->ProductOption as $o){
				$s[]  = $o['optionName'].' '.$o['optionValueName'];
			}
			$ret .= implode($s, ', ');
			$ret .= ")";

			return $ret;
		}
		return "";
	}
	
	private function calculateProductPrice($product){
		if($product->customPrice != null){
			return 1 * $product->customPrice;
		} else {
			$optionPrice = 0;
			if($product->ProductOption != null) {
				foreach($product->ProductOption as $option){
					if($option['extraPrice'] != null){
						$optionPrice += (float) $option['extraPrice'];					
					}
				}
			}

			$quant = $product->quantity;

			$discountFactor = 1;
			if($product->discount != null){
				$discountFactor = 1-($product->discount/100);
			}

			$productPrice = $product->price;

			if($product->quantumDiscount != null && $product->quantumDiscountPer != null && $quant >= $product->quantumDiscountPer){
					$productPrice -= $product->quantumDiscount;
			}
			
			return $discountFactor * ($productPrice + $optionPrice);
		}
	}
	
	private function renderDeliveryMethod() {
		$m = $this->model->getDeliveryCostModel();
		$data = $m->getData();
		$data = json_decode($data);
		
		$del = $this->model->getDeliveryMethodModel();
		$delDataRaw = $del->getData();
		$deliveryData = json_decode($delDataRaw);
		if(($data == null || count($data) == 0) && ($deliveryData==null || count($deliveryData) == 0)){
			return;
		}
		?>

		<script type="text/javascript">
			var deliveryMethods = <?php echo $delDataRaw; ?>;
		
			jQuery(document).ready(function($){
				jQuery('#deliveryMethods').change(function(elt){
					var sel = $('#deliveryMethods option').filter(':selected');
					var price = sel.attr('methodprice');
					var id = sel.attr("value");
					
				});				
			});
		</script>

		<?php
		echo '<select id="deliveryMethods" name="DeliveryMethod_id"><option value="">-- Maak uw keuze -- </option>';
		if($deliveryData != null && count($deliveryData) > 0){
			//render select item with the options, and the prices and a javascript that makes sure the checkout form sum is added
			foreach($deliveryData as $method){
				$free = '';
				if($method->freeDeliveryIfAbove != null && is_numeric($method->freeDeliveryIfAbove) && $method->freeDeliveryIfAbove > 0){
					$free = ". Gratis bij bestelbedrag va. &euro; ".money_format('%.2n', $method->freeDeliveryIfAbove);
				}
				echo '<option value="'.$method->DeliveryMethod_id.'" methodprice="'.$method->deliveryMethodPrice.'" freedeliverythreshold="'.$method->freeDeliveryIfAbove.'">'.
					$method->deliveryMethodName.' (&euro; '.money_format('%.2n',$method->deliveryMethodPrice).
					$free.
					')</option>';
			}
		}

		if($data != null && count($data) > 0) {
			$txt = 'Door ons bezorgd';
			if($this->model->getOptions()->getOption('delivery_by_us_text') != null) {
				$txt = $this->model->getOptions()->getOption('delivery_by_us_text');
			}
		
			//add an entry called 'Door ons bezorgd', using the price of the delivery cost model
			echo '<option value="0">'.$txt.'</option>';		
		}
		
		echo '</select>';
	}
	
	
	private function renderPaymentMethodForm() {
		$paymentMethods = $this->paymentMethodModel->getData();
		$ret .= '<select name="payment-method" class="payment-methods-form" id="payment-methods-form"><option value="">-- Maak uw keuze -- </option>';	
		if($this->model->getOptions()->getOption('UseSisow') == "true") {
			$ret .= '<option value="ideal">iDeal</option>';	
		}
		if($this->model->getOptions()->getOption('UseTargetpay') == "true") {
			$ret .= '<option value="ideal-targetpay">iDeal</option>';	
		}
		if($this->model->getOptions()->getOption('UseMisterCash') == "true") {
			$ret .= '<option value="mistercash">MisterCash</option>';	
		}

		
		if($this->usesOgoneCreditcard()){
			$ret .= '<option value="ogone">Creditcard</option>';
		}
		
		if($paymentMethods != null){
			foreach($paymentMethods as $method){ 
				$ret .= '<option value="'.$method->PaymentMethod_id.'">'.$method->paymentMethodName.'</option>';
			}
		}
		$ret .= '</select>';
		return $ret;
	}
	
	public function setPaymentMethodModel($m){
		$this->paymentMethodModel = $m;
	}
	
	private function getSelectedOptionIdAttr($product){
		return '';
	}
	
	private function usesOgoneCreditcard(){
		return $this->model->getOptions()->getOption('UseOgoneEcommmerce') == 'true';
	}

	public function render($data=null) { 
	
		$cart = $this->model->getCart();
		setlocale(LC_MONETARY, 'it_IT');
		
		$vatMap = $this->model->initVatMap();
		$numProducts = count($cart);

		?>
		<script type="text/javascript">
		 	var thisPageUrl = '<?php echo $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; ?>';

		 	var hostname ='<?php echo $this->model->getHostname(); ?>';
			var baseUrl = '<?php echo $_SERVER['HTTP_HOST']; ?>';
			var deliveryCostUrl = '<?php echo $deliveryCostUrl; ?>';
			
			function hideIdealFormIfNecessary(){
				var elt = jQuery('#payment-methods-form');
				if(elt.val() == 'ideal' || elt.val() == 'ideal-targetpay'){
						jQuery('.ideal-group').removeClass('hidden');
					}
					else {
						jQuery('.ideal-group').addClass('hidden');
				}
			}
			
			jQuery(document).ready(function($){
				$('.xtooltip').popover();
				
				$('#deliveryElsewhere').change(function(){
					$('.address-line-more').toggleClass('hidden');
				});
				
				
				$('.info-click').click(function(evt){
					evt.preventDefault();
					var id = $(this).attr("id");
					$('.info-click-'+id).toggleClass('hidden');
				});
				
				
				$('#payment-methods-form').change(function(){
					hideIdealFormIfNecessary();
				});
				hideIdealFormIfNecessary();			
				
			});
			
		</script>					
		<div class="row-fluid">
		 <div class="span12 checkout">
			<table  class="table checkout-table">
				<thead>
					<tr>
						<th class="smallcolumn">Aantal</th>	
						<th>Naam</th>	
						<th class="text-right smallcolumn">Prijs</th>							
						<th class="smallcolumn text-center">Verwijder</th>	
					</tr>
				</thead>
				<tbody>
				<?php if($numProducts > 0) { ?>
					<tr>
						<td colspan="4" class="product-category">
							Producten
						</td>
					</tr>
					<?php foreach($cart as $p) { 
								$p = (object) $p; 
					?>
					<tr class="product-row-<?php echo $p->Product_id; ?>">
							<td><input type="text" class="checkout-amount span2" data-productid="<?php echo $p->Product_id; ?>" value="<?php echo $p->quantity; ?>" />x</td>
							<td><?php echo $p->title.' '.$this->getProductOptionString($p); ?>
								<a data-content="<p class=' '><?php echo htmlspecialchars($p->desc); ?></p><img src='<?php echo $p->thumb; ?>' />" 
									 		   rel="popover" 
									 		   data-trigger="hover"
									 		   class="label label-info xtooltip" 
											   href="#" 
											  
								 		   
								 		   data-original-title="<?php echo $p->title; ?>">info</a>
								 <?php if($p->brand != null) { ?>
								<br/>- <strong>Merk/type:</strong> <?php echo $p->brand; ?>	 
								 <?php } ?>
								 <?php if($p->productNumber != null) { ?>
								<br/>- <strong>Productnummer:</strong> <?php echo $p->productNumber; ?>	 
								 <?php } ?>									
							</td>
							<td class="text-right checkout-product-price" data-productid="<?php echo $p->Product_id; ?>">€ <?php echo money_format('%.2n', $this->calculateProductPrice($p)); ?></td>
							<td class="text-center">
								<a class="removefromcart-checkout" href="#" 
								   productid="<?php echo $p->Product_id; ?>" 
									   <?php echo $this->getSelectedOptionIdAttr($p); ?>
								   productdata='<?php echo str_replace('\'', '', json_encode($p)); ?>'>&times;</a>
							</td>					
					</tr>		
					<?php } ?>
				<?php } ?>
					
				
					<tr class="transactioncosts-row thick-border">
						<td><strong>&nbsp;</strong></td>
						<td><strong>Transactiekosten</strong></td>
						<td class="text-right transactioncosts-field"></td>
						<td>&nbsp;</td>
					</tr>							
					<tr class="deliverycosts-row thick-border">
						<td><strong>&nbsp;</strong></td>
						<td><strong>Verzendkosten</strong></td>
						<td class="text-right deliverycosts-field"></td>
						<td>&nbsp;</td>
					</tr>			
					<tr class="subtotal-row thick-border">
						<td><strong>&nbsp;</strong></td>
						<td><strong>Subtotaal (excl. BTW)</strong></td>
						<td class="text-right subtotal-field"></td>
						<td>&nbsp;</td>
					</tr>
				<?php foreach($vatMap as $p): 
					//only generate placeholder HTML ,the JS plugin will populate the divs.
				?>
					<tr class="subtotal-row "> 
						<td><strong>&nbsp;</strong></td>
						<td><strong>BTW (<?php echo ($p*100); ?>%)</strong></td>
						<td class="text-right vat-field-x<?php echo str_replace(".","_",strval($p)); ?>">
							<strong>€ <span class="vat-value-x<?php echo str_replace(".","_",strval($p)); ?>"></span></strong>
						</td>
						<td>&nbsp;</td>
					</tr>
				<?php endforeach; ?>
					<tr class="subtotal-row  hidden " id="discount-row">
						<td><strong>&nbsp;</strong></td>
						<td><strong>Couponkorting</strong></td>
						<td class="text-right discount-field"></td>
						<td>&nbsp;</td>
					</tr>		
					<tr class="subtotal-row thick-border">
						<td><strong>&nbsp;</strong></td>
						<td><strong>Totaal (incl. BTW)</strong></td>
						<td class="text-right total-field"></td>
						<td>&nbsp;</td>
					</tr>	
				</tbody>
			</table>
		 </div>
		</div>
		<div class="row-fluid">
			<div class="span12 deposit-container alert alert-warning hidden">
				<strong>NB:</strong> Voor deze bestelling wordt <strong>€ <span class="deposit-total">34,50</span></strong> borg gerekend voor borden etc. Dit dient contant te worden betaald, en krijgt u ook weer contant terug.
			</div>
		</div>
		<form name="order-form_" id="order-form" class="form-horizontal" action="<?php echo site_url(); ?>/wp-admin/admin-ajax.php" method="post">
			  <fieldset> 
			<div class="row-fluid">
			
			  
				 <div class="span6">
				 	<?php if($this->model->getAllowPickingUp()):  ?>
				 	<h3>Leveren / afhalen</h3>
				 	<div class="control-group">
						<div class="controls">
							<input type="radio" name="deliveryType" value="afhalen"  class="deliveryType input-large" id="afhalen" /> Afhalen &nbsp;&nbsp;
							<input type="radio" name="deliveryType" value="bezorgen" class="deliveryType input-large" id="bezorgen" checked /> Bezorgen
						</div>
					</div>
					<?php else: ?>
							<input type="hidden" name="deliveryType"  class="deliveryType input-large" value="bezorgen" />
					<?php endif;?>
				 
			 		<h3>Persoonlijke gegevens</h3>
			 		<div class="control-group">
						<label class="control-label" for="companyName">Bedrijfsnaam:</label>			
			
						<div class="controls">	
							<input type="text" name="companyName" class="input-large" id="companyName" />
						</div>		
					</div>	
			 		
			 	    <div class="control-group">
						<label class="control-label" for="firstname">Voornaam: *</label>
						
						<div class="controls">
							<input type="text" name="firstname" class="input-large" id="firstname" />
						</div>
					</div>	
			
			 	    <div class="control-group">
						<label class="control-label" for="surname">Achternaam: *</label>
						
						<div class="controls">		
							<input type="text" name="surname" class="input-large" id="surname" />
						</div>
					</div>
			
			 	    <div class="control-group">
						<label class="control-label" for="street">Straat: *</label>			
			
						<div class="controls">	
							<input type="text" name="street" class="input-large address-line" id="street" />
						</div>		
					</div>
				
				    <div class="control-group">
						<label class="control-label" for="number">Huisnummer: *</label>			
			
						<div class="controls">	
							<input type="text" name="number" maxlength="7" class="input-large span3 address-line" id="number" />
						</div>		
					</div>	
				
				    <div class="control-group">
						<label class="control-label" for="postcode">Postcode: *</label>			
			
						<div class="controls">	
							<input type="text" name="postcode" maxlength="7" class="input-large span3 address-line" id="postcode" />
						</div>		
					</div>	
				
				    <div class="control-group">
						<label class="control-label" for="city">Plaats: *</label>			
			
						<div class="controls">	
							<input type="text" name="city" class="input-large address-line" id="city" />
						</div>		
					</div>	
				
				    <div class="control-group">
						<label class="control-label" for="country">Land: </label>			
			
						<div class="controls">	
							<input type="text" name="country" class="input-large address-line" id="country" />
						</div>		
					</div>		
				
				    <div class="control-group">
						<label class="control-label" for="email">E-mail: *</label>			
			
						<div class="controls">	
							<input type="text" name="email" class="input-large" id="email" />
						</div>		
					</div>	
				
				    <div class="control-group">
						<label class="control-label" for="phone">Telefoon: * 
								<a data-content="Vul een telefoonnummer in waarop u op de dag van levering bereikbaar bent." 
										 		   rel="popover" 
										 		   data-trigger="hover"
										 		   class="label label-info xtooltip" 
										 		   href="#" 
									 		   
									 		   data-original-title="Telefoon">&nbsp;?&nbsp;</a>
						
						</label>			
			
						<div class="controls">	
							<input type="text" name="phone" class="input-large" id="phone" />
						</div>		
					</div>	
					
			 		<div class="control-group">
						<label class="control-label" for="VATnumber">BTW-nummer: 
							<a data-content="Vul dit alleen in als u bestelt op naam van een bedrijf." 
										 		   rel="popover" 
										 		   data-trigger="hover"
										 		   class="label label-info xtooltip" 
										 		   href="#" 
									 		   
									 		   data-original-title="BTW-nummer">&nbsp;?&nbsp;</a>
							
							
						</label>			
			
						<div class="controls">	
							<input type="text" name="VATnumber" class="input-large" id="VATnumber" />
						</div>		
					</div>	
					
					<!-- delivery costs, only show if checkbox is set -->
					<div class="control-group">
						<label class="control-label delivery-elsewhere" for="deliveryElsewhere">Ergens anders bezorgen?</label>			
			
						<div class="controls">	
							<input type="checkbox" name="deliveryElsewhere" class="input-large address-line-elsewhere" id="deliveryElsewhere" />
						</div>		
					</div>	
					 <div class="control-group address-line-more hidden">
						<label class="control-label" for="deliveryName">Naam begunstigde: </label>			
			
						<div class="controls">	
							<input type="text" name="deliveryName" class="input-large address-line-elsewhere" id="deliveryName" />
						</div>		
					</div>			
							
					 <div class="control-group address-line-more hidden">
						<label class="control-label" for="deliveryStreet">Straat: </label>			
			
						<div class="controls">	
							<input type="text" name="deliveryStreet" class="input-large address-line-elsewhere" id="deliveryStreet" />
						</div>		
					</div>		
				
				    <div class="control-group address-line-more hidden">
						<label class="control-label" for="deliveryNumber">Huisnummer: </label>			
			
						<div class="controls">	
							<input type="text" name="deliveryNumber" class="input-large address-line-elsewhere" id="deliveryNumber" />
						</div>		
					</div>	
				
				    <div class="control-group address-line-more hidden">
						<label class="control-label" for="deliveryZipcode">Postcode: </label>			
			
						<div class="controls">	
							<input type="text" name="deliveryZipcode" class="input-large address-line-elsewhere" id="deliveryZipcode" />
						</div>		
					</div>	
			
				    <div class="control-group address-line-more hidden">
						<label class="control-label" for="deliveryCity">Plaats:</label>			
			
						<div class="controls">	
							<input type="text" name="deliveryCity" class="input-large address-line-elsewhere" id="deliveryCity" />
						</div>		
					</div>	
					
			 	</div>
				<div class="span6 checkout-right">

					<div class="row-fluid">
						<div class="span12">
							<h3 class="ordercomment-title">Bestellingsgegevens</h3>
							<div class="control-group order-comment-control">
								<p class="infotextordercomment"></p>
								<label class="control-label order-comment" for="orderComment">
									<?php
									$commentsExpl = 'Opmerkingen:';
									if($this->model->getOptions()->getOption('comments_expl') != null){
										$commentsExpl = $this->model->getOptions()->getOption('comments_expl');

									}						
									echo $commentsExpl; ?></label>			
								<div class="controls">	
									<textarea id="orderComment" name="orderComment" class="input-large" rows="4"></textarea>
								</div>		
							</div>				
							<?php 
							if($this->model->allowDeliveryDate()) {
							?>
							<div class="control-group">
						   		<label class="control-label delivery-date-label" for="deliveryDate">Bezorg-/leverdatum (formaat: dd-mm-jjjj): </label>			
								<div class="controls">	
									<div class="deliveryDatePicker"></div>
									
									<input type="text" name="deliveryDate" class="span4 input-large" id="deliveryDate" /> <br/>
									<?php
										if($this->model->allowDeliveryTime()) {
									?>
	
									<select class="span6" name="deliveryTime">
										<?php for($i = 9; $i < 18; $i++){ ?>
										<option value="<?php echo $i; ?>:00-<?php echo ($i+1); ?>:00"><?php echo $i; ?>:00-<?php echo ($i+1); ?>:00</option>
										<?php } ?>
									</select>
									<?php		
										}
									?>
									<?php if($this->model->useScheduler()) { ?>
									<div class='schedulerMessage alert hidden'></div>
									<?php }?>
								</div>		
							</div>					
							<?php 
							}
							?>
						</div><!-- span12 -->	
					</div><!-- /row -->		
					<div class="row-fluid">
						<div class="span12">
							<h3 class="coupon-title">Kortingscode</h3>
							<div class="control-group">
								<p>Heeft u een kortingscode? Vul deze dan hieronder in. Als de code geldig is, wordt de korting toegevoegd aan het prijs-overzicht.</p>						
							    <div id="discount-text" class="alert hidden"></div>
			
								<label class="control-label" for="coupon">Kortingscode:</label>					
								<div class="controls">	
									<input type="text" name="coupon" class="input-large" id="coupon" />
									
								</div>		
							</div>	
						</div>
					</div>
					
					
					
					<div class="row-fluid">
						<div class="span12">
							<h3 class="paymentmethod-title">Betaalmethode</h3>
							<div class="control-group">
								<p class="payment-method-expl">Kies uw betaalmethode</p>						
							    <div id="payment-text" class="alert hidden"></div>
			
<!--								<label class="control-label" for="paymentmethod">Betaalmethode:</label>					-->
								<div class="controls">	
									<?php echo $this->renderPaymentMethodForm(); ?>
									
								</div>		
							</div>	
							

							<div class="control-group ideal-group">
								<p>Kies uw bank om direct via iDeal te betalen</p>						
							    <div id="ideal-text" class="alert hidden"></div>
			
								<label class="control-label" for="coupon">Uw bank:</label>					
								<div class="controls">	
									<?php 
										if($this->model->getOptions()->getOption('UseSisow') == "true") {
											echo $this->renderIDealForm();
										} elseif($this->model->getOptions()->getOption('UseTargetpay') == "true") {
											echo $this->renderTargetpayIDealForm();
										}
									?>
								</div>		
							</div>	
						</div>
					</div>	
					
					<div class="row-fluid">
						<div class="span12">
							<h3 class="deliverymethod-title">Verzending</h3>
							<div class="control-group">
								<?php
									$delMethod = 'Kies uw verzend-/bezorgmethode:';
									if($this->model->getOptions()->getOption('deliverymethod_text') != null){
										$delMethod = $this->model->getOptions()->getOption('deliverymethod_text');
									}						
								?>
								<p class="deliverymethod-text"><?php echo $delMethod; ?></p>						
							    <div id="deliverymethod-text" class="alert hidden"></div>
							    <div id="not-enough-ordered" class="alert hidden"></div>							    
								<div class="controls">	
										<?php echo $this->renderDeliveryMethod();?>									
								</div>		
							</div>	
						</div>
					</div>	
					
					<?php if($this->model->getOptions()->getOption('should_accept_terms')) { ?>
					
					<div class="row-fluid">
						<div class="span12">
							<h3 class="terms-title">Algemene voorwaarden</h3>
							<div class="control-group">
								<p><input  type="checkbox" name="accept_terms" /> Ik accepteer de <a href="/algemene-voorwaarden">algemene voorwaarden</a>.</p>
							</div>	
						</div>
					</div>	
					
					
					<?php } ?>
					
					
							
					<div class="row-fluid">
						<div class="span12">
							<h3 class="payment-options">Verzenden</h3>
							<div class="control-group">
								<p class="checkout-explanation">Als u op de knop hieronder drukt, wordt uw bestelling opgeslagen. Indien u voor iDeal, MisterCash, of creditcard heeft gekozen als betaalmethode, wordt u naar een beveiligde omgeving doorgestuurd.</p>
								<div class="controls">	
									<input type="submit" name="invoice" class="submit-controls btn btn-primary " id="invoice" value="Plaats bestelling" style="width: 130px;" />
								</div>		
							</div>	
						</div>
					</div>
			 	</div>
			</div>
			  </fieldset>
		 </form>			
	
	<?php
	
		
	}
}
