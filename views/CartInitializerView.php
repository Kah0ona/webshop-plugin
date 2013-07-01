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
		jQuery(document).ready(function($){
			$('#shoppingcart').shoppingCart({
				'session_url' : '<?php echo  get_site_url(); ?>/wp-content/plugins/webshop-plugin/models/CartStore.php'	,		
				'checkout_page' : '<?php echo  get_site_url(); ?>/checkout',
				'address' : '<?php echo $options->getOption('address'); ?>',
				'deliveryCostsTable' : <?php echo $deliveryCostsTable; ?>,
				'deliveryMethods' : <?php echo "[{'DeliveryMethod_id':1,'deliveryMethodName':'Hard coded delivery method', 'deliveryMethodDesc':'Hard coded delivery desc','deliveryMethodPrice':7.50}]"; //$deliveryMethods; ?>
			});
		});
		</script> 		
		<?php
	}
}
?>