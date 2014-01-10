<?php 
/**
* Backend settings form
* The admin options is set as the model
*/
class CartInitializerView extends GenericView {


	public function render($options=null, $deliveryMethods = null, $deliveryCostsTable = null){
		if($options == null) return;
		?>
		<script type="text/javascript">
		webshopProducts = [];
		jQuery(document).ready(function($){
			$('#shoppingcart').shoppingCart({
				'session_url' : '<?php echo  get_site_url(); ?>/wp-content/plugins/webshop-plugin/models/CartStore.php'	,		
				'checkout_page' : '<?php echo  get_site_url(); ?>/checkout',
				'address' : '<?php echo $options->getOption('address'); ?>',
				'deliveryCostsTable' : <?php echo $deliveryCostsTable; ?>,
				'couponUrl': '<?php echo SYSTEM_URL_WEBSHOP?>/public/coupons',
				'hostname' : '<?php echo $options->getOption('hostname'); ?>',
				'deliveryMethods' : <?php echo $deliveryMethods; ?>
			});
		});
		</script> 		
		<?php
	}
}
?>