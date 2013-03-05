<?php 
/**
* Backend settings form
* The admin options is set as the model
*/
class CartInitializerView extends GenericView {


	public function render($options=null){
		if($options == null) return;
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($){
			$('#shoppingcart').shoppingCart({
				'session_url' : '<?php echo  get_site_url(); ?>/wp-content/plugins/webshop-plugin/models/CartStore.php'	,		
				'checkout_page' : '<?php echo  get_site_url(); ?>/checkout'								
			});
			$('#shoppingcart').shoppingCart('test');			
		});
		</script> 		
		<?php
	}
}
?>