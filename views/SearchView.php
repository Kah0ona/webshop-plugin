<?php 
/**
* Overview, showing a list of categories
*/
class SearchView extends GenericView {
	public function render($args=null) {  ?>
	
		<script src="<?php echo plugins_url('/webshop-plugin/js/tempo.min.js', 'webshop-plugin');  ?>" type="text/javascript" ></script>
		<script type="text/javascript">
			var search_renderer;

			function searchCallback(jsonObj){
				search_renderer.clear();
				search_renderer.render(jsonObj);
			}		
		
			jQuery(document).ready(function($){
				search_renderer =  Tempo.prepare('search-results');
				$('.search-button').click(function(){
					
					var q = $('#product-search-box').val();
					if(q==null || q.trim() == ""){
						$('#search-results').removeClass('hidden').html('<div class="alert-error">Geef een geldige zoekterm op.</div>');
						return;
					}
					
					$.ajax({
						url: '<?php echo $this->model->getServiceUrl(); ?>',
						data: {"query" : q, 'hostname' : '<?php echo $this->model->getHostname(); ?>'},
						jsonpCallback: 'searchCallback',
						jsonp: 'callback',
						dataType: 'jsonp'
					});
				});
			});

			
		</script>
	
		<p><input type="text" name="search-products" id="product-search-box" /><a data-toggle="modal" data-target="#search-modal" class="btn btn-inverse info-button search-button">Zoeken</a></p>

		<div class="modal hide fade" id="search-modal">
			 <div class="modal-header">
			 	<button class="close" data-dismiss="modal">×</button>
			 	<h3>Zoekresultaten</h3>
			 </div>
			 <div class="modal-body">
			 	<div id="search-results">
				 	<div class="search-result row-fluid" data-template>
				 		<div class="span2">
				 			<a href="<?php echo get_bloginfo('url'); ?>/products/{{Product_id}}/#{{productName}}" target="_blank">
					 			<img src="http://placehold.it/200x200&text=&nbsp;" data-src="{{imageDish | prepend '<?php echo SYSTEM_URL_WEBSHOP; ?>/uploads/Product/'}}" />
				 			</a>
				 		</div>
				 		<div class="span10">
				 			<a href="<?php echo get_bloginfo('url'); ?>/products/{{Product_id}}/#{{productName}}" target="_blank">{{productName | truncate 50 }}</a> €{{productPrice}}<br/>
				 			<span class="search-result-description">
								<strong>Merk:</strong> {{brand | default '-' }} <br/>
								<strong>Art. nr.:</strong> {{productNumber | default  '-'}} <br/>
								{{productDesc | truncate 140}}
								
				 			</span>
				 		</div>
				 	</div>
			 	</div>
			 </div>
			 <div class="modal-footer">
			 
			 </div>
	</div>	
<?php
    }
}
?>