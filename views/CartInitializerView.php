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
			$('#shoppingcart').shoppingCart({});
			$('#shoppingcart').shoppingCart('test');			
		});
		</script> 		
		<?php
		
		
	}
}
?>