<?php

class SaleView extends GenericView {


	public function render($data=null){
		
		$saleMode = $this->model->getSaleMode();
		
		$saleStatus	= $this->model->getSaleModeText();
		?>

		<script type="text/javascript">
			WebshopSaleModus = '<?php echo $saleMode; ?>';
			jQuery(document).ready(function($){
				var posturl = '<?php echo site_url(); ?>/wp-admin/admin-ajax.php';
			
				$('#webshop-sale').click(function(){
					var s = 'on';
					if($(this).children('a').first().html() == 'NEW'){
						s = 'off';
					} else {
						s = 'on';
					}
					$.post(posturl,
			 			{ 
			 				'action' : 'sale_button',
	 				   		'sale_mode' : s 
			 			},
						function(result){ 
							location.reload();
						}
					);				
				});
				
				$('#navigationbar a').click(function(evt){
					if($(this).html().toUpperCase() == 'HOME'){
						evt.preventDefault();
						$.post(posturl,
				 			{ 
				 				'action' : 'sale_button',
		 				   		'sale_mode' : 'off' 
				 			},
							function(result){ 
								window.location.href= '/';
							}
						);				
					}
				});
			});
		</script>
		<div id="webshop-sale">
		<?php if($saleMode == 'on') { ?>
		<a href="#">NEW</a>
		<?php } else { ?>
		<a href="#">SALE</a>		
		<?php } ?>
		</div>
		<?php
	}
}

?>
