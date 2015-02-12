<?php 
/**
* Backend settings form
* The admin options is set as the model
*/
class CartInitializerView extends GenericView {
	protected $type=null;
	protected $pageId = null;

	public function setPageType($type){
		$this->type = $type;
	}

	public function setDetailPageId($id){
		$this->pageId = $id;
	}
	
	public function render($options=null, $deliveryMethods = null, $deliveryCostsTable = null){
		if($options == null) return;

		$pricesInclVat = $options->getOption('prices_excl_vat') == 'true' ? "false" : "true";
		?>
		<script type="text/javascript">

		webshopProducts = [];
		jQuery(document).ready(function($){
	
			$('#shoppingcart').shoppingCart({
				'session_url' : '<?php echo  get_site_url(); ?>/wp-content/plugins/webshop-plugin/models/CartStore.php'	,		
				'personaldetails_url' : '<?php echo  get_site_url(); ?>/wp-content/plugins/webshop-plugin/models/PersonalDetailsStore.php'	,		
				'checkout_page' : '<?php echo  get_site_url(); ?>/checkout',
				<?php if(is_page('checkout')){ ?>
				'is_checkout' : true,
				<?php } else { ?>
				'is_checkout' : false,
				<?php } ?>
				'hideSoldOutProducts': '<?php echo $options->getOption('ShowProductsInStock'); ?>',
				<?php if($options->getOption('checkout_link') != null){ ?>
				'checkout_link' : '<?php echo $options->getOption('checkout_link'); ?>',					
				<?php } ?>
				<?php if($options->getOption('popupText') != null){ ?>
				'popupText' : '<?php echo $options->getOption('popupText'); ?>',					
				<?php } ?>
				'address' : '<?php echo $options->getOption('address'); ?>',
				'deliveryCostsTable' : <?php echo $deliveryCostsTable; ?>,
				'pricesInclVat' : <?php echo $pricesInclVat; ?>,
				'couponUrl': '<?php echo SYSTEM_URL_WEBSHOP?>/public/coupons',
				'productsUrl': '<?php echo SYSTEM_URL_WEBSHOP?>/public/products',
				'schedulerUrl': '<?php echo SYSTEM_URL_WEBSHOP?>/public/occupiedtimeslots',
				'baseUrl': '<?php echo SYSTEM_URL_WEBSHOP?>',
				'hostname' : '<?php echo $options->getOption('hostname'); ?>',
				
				'customAddProductValidator' : <?php if($options->getOption('add_product_hook') == 'true') { echo 'customAddProductValidator'; } else { echo 'null';} ?>,
				'beforeInsertingProductToCartHook' : <?php if($options->getOption('modify_product_hook') == 'true') { echo 'beforeInsertingProductToCartHook'; } else { echo 'null';} ?>,
				'searchOnPageLoad' : <?php if($options->getOption('search_on_page_load') == 'true') { echo 'true'; } else { echo 'false'; } ?>,
				'onProductAdded' : <?php if($options->getOption('product_added_hook') == 'true') { echo 'onProductAdded'; } else { echo 'null';} ?>,
				'use_scheduler' : <?php if($options->getOption('use_scheduler') == 'true') { echo 'true'; } else { echo 'false';} ?>,

				<?php if($options->getOption('max_future_delivery_date') != null){ ?>
				'max_future_delivery_date' : <?php echo $options->getOption('max_future_delivery_date'); ?>,
				<?php } ?>
				'deliveryMethods' : <?php echo $deliveryMethods; ?>
			});
		});
		

		<?php
			if($this->pageId == null){
				$this->pageId = -1;
			}
			echo 'WebshopType = "'.$this->type.'"; ';		
			echo 'WebshopItem_id = '.$this->pageId.';';			
		?>

		
		</script> 		
		<?php
	}
}
?>
