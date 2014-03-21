<?php

class SaleView extends GenericView {


	public function render($data=null){
		
		$saleMode = $this->model->getSaleMode();
		
		$saleStatus	= $this->model->getSaleModeText();
		?>

		<script type="text/javascript">
			jQuery(document).ready(function($){
				var posturl = '<?php echo site_url(); ?>/wp-admin/admin-ajax.php';
			
				$('#sale-light span').click(function(){
					var e = $(this);
					$('#sale-light span').removeClass('active');
					e.addClass('active');
					$('#sale-status').html(e.attr('data-status'));
					//make update to the session, and reload the page
					$.post(posturl,
			 			{ 
			 				'action' : 'sale_button',
	 				   		'sale_mode' : e.attr('data-mode') 
			 			},
						function(result){ 
							location.reload();
						}
					);				
				});
				
				$('#sale-light span').hover(function(){
					var e = $(this);
					
					$('#sale-hover').html(e.attr('data-hover')).show();
				}, function(){
					$('#sale-hover').hide();
				});
			});
		
		</script>
		<div id="sale-showing">Showing:</div>
		<div id="sale-light">
			<span class="<?php echo $saleMode == 'off' ? 'active' : ''?>" id="light-off" data-mode="off" data-status="NEW COLLECTION!" data-hover="Toon de nieuwe collectie"></span>
			<span class="<?php echo $saleMode == 'both' ? 'active' : ''?>" id="light-both"  data-mode="both" data-status="NEW+SALE" data-hover="Toon alles"></span>
			<span class="<?php echo $saleMode == 'on' ? 'active' : ''?>" id="light-on"  data-mode="on" data-status="SALE!" data-hover="Toon de producten in SALE!"></span>
		</div>
		<div id='sale-status'><?php echo $saleStatus; ?></div>
		<?php
	}
}

?>
