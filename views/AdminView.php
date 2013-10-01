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
  		  $this->add_cs_field('nested_category_level','Aantal niveau\'s in categorie widget');
  		  
		  $this->add_cs_field_boolean('show_brand', 'Toon merk van product (indien ingevuld)');		  
		  $this->add_cs_field_boolean('show_article_number', 'Toon artikelnummer van product (indien ingevuld)');		  
		  $this->add_cs_field_boolean('productoverview_disabled', 'Deactiveer /products/ overzicht (nodig indien google search gebruikt wordt)');
		  $this->add_cs_field_boolean('use_delivery_date', 'Kunnen klanten een leverdatum aangeven?');		  

		  $this->add_cs_field_boolean('UseSisow', 'Gebruik iDeal via Sisow?:');		  
		  
		  $this->add_cs_field('SisowMerchantId', 'Sisow Merchant ID:');
 		  $this->add_cs_field('SisowMerchantKey', 'Sisow Merchant Key:');		  
		  $this->add_cs_field('SisowDescription', 'Omschrijving iDeal betaling (max 32. tekens):');		  
		  $this->add_cs_field_select('SisowTestModus', 'Sisow Test Modus:', array('true'=>'Aan', 'false'=>'Uit'));
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

}
?>