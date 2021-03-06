<?php 
/**
* Backend settings form
* The admin options is set as the model
*/
class AdminView extends GenericView {


	public function render($data=null) { 
		
	?>	
		<!-- Start rendering AdminView -->


		<div class="wrap">
		 <h2>Webshop instellingen</h2>
		 <p>Hieronder kunt u een paar algemene webshop instellingen doen.</p>
		 <form method="post" action="options.php"> 
		 
		  <?php settings_fields('sytematic_webshop'); ?>
		  <?php do_settings_sections( 'sytematic-webshop' ); ?>
		
		  <?php submit_button(); ?>
		 </form>
		</div>


		<!-- End AdminView -->
	<?php 
	}
	
	function registerFieldSettings(){
		  add_settings_section('sytematic_webshop_main_options', 'Instellingen', array($this,'render_expl'), 'sytematic-webshop');
	
		  $this->add_cs_field('hostname','Hostname:');
		  $this->add_cs_field('address','Adres van uw zaak (formaat: Kalverstraat 12 1234AB Amsterdam):');
		  $this->add_cs_field('cart_class', 'CSS-class van het winkelwagentje:');
  		  $this->add_cs_field('checkout_link', 'Tekst voor winkelmandje afreken knop:');
   		  $this->add_cs_field('delivery_by_us_text', 'Door ons bezorgd label');	
  		  $this->add_cs_field('delivery_by_us_title', 'Door ons bezorgd titel');		  
   		  $this->add_cs_field('delivered_by_us_expl', 'Door ons bezorgd uitleg');		  
   		  $this->add_cs_field('delivered_by_us_message', 'Bezorgtekst. Gebruik [afstand], [vanaf_bedrag], [bezorgkosten]');		  
   		  $this->add_cs_field('deliverymethod_text', 'Uitleg verzendmethode');		
   		  $this->add_cs_field('comments_expl', 'Opmerkingsveld uitleg');		
   		  $this->add_cs_field('popupText', 'Text in popup als product aan winkelmand wordt toegevoegd.');		  

   		  $this->add_cs_field_boolean('prices_excl_vat', 'Zijn de prijzen excl. BTW?');
   		    
   		  $this->add_cs_field_boolean('should_accept_terms', 'Moet de klant algemene voorwaarden accepteren?');
   		  $this->add_cs_field_boolean('no_bootstrap', 'Bootstrap 3 CSS+js laden, ipv bootstrap 2.3.2?');
   		  $this->add_cs_field_boolean('no_plugin_css', 'Geen plugin CSS laden?');
  		  		  		  
  		  $this->add_cs_field('nested_category_level','Aantal niveau\'s in categorie widget');
   		  $this->add_cs_field_boolean('use_pagination', 'Verdeel producten over meerdere pagina\'s');		    		  
   		  $this->add_cs_field('num_items_per_page', 'Aantal producten per pagina');		  

   		  $this->add_cs_field_boolean('add_product_hook', 'Add Product Hook. Maak custom valideer functie voordat een product in het winkelmandje gaat.');		    		  
   		  $this->add_cs_field_boolean('product_added_hook', 'Add Product Hook. Maak custom functie nadat een product in het winkelmandje gaat.');		    		  
   		  $this->add_cs_field_boolean('search_on_page_load', 'Automatisch zoeken na laden pagina indien er een filterwidget in beeld is.');

   		  $this->add_cs_field_boolean('modify_product_hook', 'Maak custom functie die het te inserten product in het winkelmandje kan aanpassen.');
   		  $this->add_cs_field_boolean('cart_loaded_hook', 'Maak custom functie nadat de cart geladen is.');
		  
  		  $this->add_cs_field_upload('NoImage', 'Toon dit plaatje indien er geen plaatje bij een product zit');

  		  
  		  $this->add_cs_field('tracking_pixel','Trackingpixel. Vul hier de gehele img tag in. De tags {transactionID} en {transactionAmount} zullen worden vervangen door de juiste gegevens. Dit kan dus in de url gebruikt worden.');

  		  $this->add_cs_field_select('ShowProductsInStock', 'Producten die niet op voorraad zijn:', array('show'=>'Tonen met melding', 'hide'=>'Verbergen'));
		  $this->add_cs_field_boolean('show_brand', 'Toon merk van product (indien ingevuld)');		 
		  $this->add_cs_field_boolean('show_color', 'Toon kleur van product (indien ingevuld)');		  
		  $this->add_cs_field_boolean('custom_validation','Custom validatie? (Maak een globale functie modifyValidationRules(valOpt))');
		   
		  $this->add_cs_field_boolean('show_article_number', 'Toon artikelnummer van product (indien ingevuld)');		  
		  $this->add_cs_field_boolean('productoverview_disabled', 'Deactiveer /products/ overzicht (nodig indien google search gebruikt wordt)');
		  $this->add_cs_field_boolean('use_delivery_date', 'Kunnen klanten een leverdatum aangeven?');		
		  $this->add_cs_field_boolean('use_delivery_time', 'Kunnen klanten een levertijd aangeven?');		    		  
		  $this->add_cs_field('max_future_delivery_date', 'Maximaal aantal dagen in toekomst te bestellen?');		    		  
		  $this->add_cs_field_boolean('custom_search_results_renderer', 'Gebruik custom javascript om zoekresultaten te tonen?');		    		  
		  $this->add_cs_field_boolean('use_scheduler', 'Kunnen klanten een afspraak plannen?');		    		  
		    
		  $this->add_cs_field_boolean('UseSisow', 'Gebruik iDeal via Sisow?:');		  
		  $this->add_cs_field_boolean('UseMisterCash', 'Gebruik MisterCash via Sisow?:');		  		  
		  $this->add_cs_field('SisowMerchantId', 'Sisow Merchant ID:');
 		  $this->add_cs_field('SisowMerchantKey', 'Sisow Merchant Key:');		  
		  $this->add_cs_field('SisowDescription', 'Omschrijving iDeal betaling (max 32. tekens):');		  
		  $this->add_cs_field_select('SisowTestModus', 'Sisow Testmodus:', array('true'=>'Aan', 'false'=>'Uit'));

		  $this->add_cs_field_boolean('UseTargetpay', 'Gebruik iDeal via Targetpay?:');		  
		  $this->add_cs_field('TargetpayLayoutCode', 'Targetpay Layoutcode:');
		  $this->add_cs_field('TargetpayDescription', 'Targetpay omschrijving:');

		  $this->add_cs_field_boolean('UseOgoneEcommmerce', 'Gebruik Ogone e-Commerce creditcard gateway?:');		  
		  $this->add_cs_field_select('OgoneTestMode', 'Ogone Testmodus:', array('true'=>'Aan', 'false'=>'Uit'));
		  $this->add_cs_field('OgonePassPhrase', 'Ogone Passphrase voor authenticatie:');
		  
		  
		  $this->add_cs_field_select('region','Land:', array('nl'=>'Nederland','be'=>'Belgi&euml;'));
	}
	

	public function render_expl(){
		echo '';
	}
	
	public function add_cs_field($name, $title, $type='text', $size='40'){
		add_settings_field('sytematic_webshop_'.$name, $title, array($this, 'cateringsoftware_add_setting_field'), 'sytematic-webshop', 'sytematic_webshop_main_options', 
			array('name'=>$name, 'type'=>$type, 'size'=>$size)
		);
	}
	
	
	public function add_cs_field_upload($name, $title){
		add_settings_field('sytematic_webshop_'.$name, $title, array($this, 'cateringsoftware_add_setting_field_upload'), 'sytematic-webshop', 'sytematic_webshop_main_options', array('name'=>$name));	
	}
	
	
	public function add_cs_field_boolean($name,$title){
			add_settings_field('sytematic_webshop_'.$name, $title, array($this, 'cateringsoftware_add_setting_field_boolean'), 'sytematic-webshop', 'sytematic_webshop_main_options', 
			array('name'=>$name)
		);
	}
	
	public function add_cs_field_select($name,$title,$values){
		add_settings_field('sytematic_webshop_'.$name, $title, array($this, 'cateringsoftware_add_setting_field_select'), 'sytematic-webshop', 'sytematic_webshop_main_options', 
			array('values'=>$values, 'name'=>$name)
		);
	}
	
	public function cateringsoftware_add_setting_field($args) {
		$name = $args['name'];
		$size = $args['size'];
		$type = $args['type'];
		$options = $this->model->getOptions();
		echo "<input id='sytematic_webshop_".$name."' name='sytematic_webshop[".$name."]' size='".$size."' type='".$type."' value='{$options[$name]}' />";
	}
	
	public function cateringsoftware_add_setting_field_boolean($args){
		$name = $args['name'];
		$options = $this->model->getOptions();
		$checked = $options[$name] == 'true' ? 'checked="checked"' : '';
		
		echo '<input type="checkbox" name="sytematic_webshop['.$name.']" value="true" '.$checked.' />';
	}
	
	public function cateringsoftware_add_setting_field_select($args){
		$values = $args['values'];
		$name = $args['name'];
	
		$options = $this->model->getOptions();
		$ret = '<select name="sytematic_webshop['.$name.']">';
		foreach($values as $k=>$v){
			$selected="";
			if($options[$name] == $k)
				$selected = 'selected="selected"';
				
			$ret .= '<option value="'.$k.'" '.$selected.'>';
			$ret .= ($v == null) ? $k : $v;
			$ret .= '</option>';
		}	
		$ret .= '</select>';
		
		echo $ret;
	}


	public function cateringsoftware_add_setting_field_upload($args){
		$name = $args['name'];
		$options = $this->model->getOptions();	  
		
		echo '<div class="uploader">
				  <input type="text" name="sytematic_webshop['.$name.']" id="sytematic_webshop_'.$name.'" value="'.$options[$name].'" />
				  <input class="button webshop-upload-button" name="sytematic_webshop_button_'.$name.'" id="sytematic_webshop_button_'.$name.'" value="Upload" />
				</div>';	  
	}
}
?>
