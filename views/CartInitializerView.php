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
		?>
		<script type="text/javascript">
		webshopProducts = [];
		jQuery(document).ready(function($){
	
			$('#shoppingcart').shoppingCart({
				'session_url' : '<?php echo  get_site_url(); ?>/wp-content/plugins/webshop-plugin/models/CartStore.php'	,		
				'checkout_page' : '<?php echo  get_site_url(); ?>/checkout',
				<?php if($options->getOption('checkout_link') != null){ ?>
				'checkout_link' : '<?php echo $options->getOption('checkout_link'); ?>',					
				<?php } ?>
				'address' : '<?php echo $options->getOption('address'); ?>',
				'deliveryCostsTable' : <?php echo $deliveryCostsTable; ?>,
				'couponUrl': '<?php echo SYSTEM_URL_WEBSHOP?>/public/coupons',
				'hostname' : '<?php echo $options->getOption('hostname'); ?>',
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