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
		$optionPrice = 0;
		if($product->ProductOption != null) {
			foreach($product->ProductOption as $option){
				if($option['extraPrice'] != null){
					$optionPrice += (float) $option['extraPrice'];					
				}
			}
		}
		return $product->price + $optionPrice;
	}
	
	
	private function renderPaymentMethodForm() {
		$paymentMethods = $this->paymentMethodModel->getData();
		$ret .= '<select name="payment-method" class="payment-methods-form" id="payment-methods-form">';	
		if($this->model->getOptions()->getOption('UseSisow') == "true") {
			$ret .= '<option value="ideal">iDeal</option>';	
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

	public function render($data=null) { 
		$cart = $this->model->getCart();
		setlocale(LC_MONETARY, 'it_IT');
		
		$vatMap = $this->model->initVatMap();
		$numProducts = count($cart);

		?>
		<script type="text/javascript">
		 	var thisPageUrl = '<?php echo $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; ?>';
		 	var couponUrl = '<?php echo $couponUrl; ?>';
		 	var hostname ='<?php echo $this->model->getHostname(); ?>';
			var baseUrl = '<?php echo $_SERVER['HTTP_HOST']; ?>';
			var deliveryCostUrl = '<?php echo $deliveryCostUrl; ?>';
			
			function hideIdealFormIfNecessary(){
				var elt = jQuery('#payment-methods-form');
				if(elt.val() == 'ideal'){
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
							<td><?php echo $p->quantity; ?> x</td>
							<td><?php echo $p->title.' '.$this->getProductOptionString($p); ?>
								<a data-content="<p class=' '><?php echo htmlspecialchars($p->desc); ?></p><img src='<?php echo $p->thumb; ?>' />" 
									 		   rel="popover" 
									 		   data-trigger="hover"
									 		   class="label label-info xtooltip" 
									 		   href="#" 
								 		   
								 		   data-original-title="<?php echo $p->title; ?>">info</a>
									
							</td>
							<td class="text-right">€ <?php echo money_format('%.2n', $this->calculateProductPrice($p)); ?></td>
							<td class="text-center">
								<a class="removefromcart-checkout" href="#" 
								   productid="<?php echo $p->Product_id; ?>" 
									   <?php echo $this->getSelectedOptionIdAttr($p); ?>
								   productdata='<?php echo json_encode($p); ?>'>&times;</a>
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
			<div class="row-fluid">
			
			  <fieldset> 
			  
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
						<label class="control-label" for="deliveryElsewhere">Ergens anders bezorgen?</label>			
			
						<div class="controls">	
							<input type="checkbox" name="deliveryElsewhere" class="input-large address-line-elsewhere" id="deliveryElsewhere" />
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
				<div class="span6">
					<div class="row-fluid">
						<div class="span12">
							<h3>Bestellingsgegevens</h3>
							<div class="control-group">
								<label class="control-label" for="orderComment">Opmerkingen:</label>			
								<div class="controls">	
									<textarea id="orderComment" name="orderComment" class="input-large" rows="4"></textarea>
								</div>		
							</div>				
							
						</div><!-- span12 -->	
					</div><!-- /row -->		
					<div class="row-fluid">
						<div class="span12">
							<h3>Kortingscode</h3>
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
							<h3>Betaalmethode</h3>
							<div class="control-group">
								<p>Kies uw betaalmethode</p>						
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
									<?php echo $this->renderIDealForm(); ?>
									
								</div>		
							</div>	
						</div>
					</div>							
							
					<div class="row-fluid">
						<div class="span12">
							<h3 class="payment-options">Verzenden</h3>
							<div class="control-group">
								<p>Als u op de knop hieronder drukt, wordt uw bestelling opgeslagen, en wordt u naar de beveiligde omgeving van uw bank gestuurd, om de betaling te doen via iDeal.</p>
								<div class="controls">	
									<input type="submit" name="invoice" class="submit-controls btn btn-primary " id="invoice" value="Plaats bestelling" style="width: 130px;" />
								</div>		
							</div>	
						</div>
					</div>
			 	</div>
			  </fieldset>
			</div>
		 </form>			
			
			
		<?php	
	}
}
