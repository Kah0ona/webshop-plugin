<?php 
/**
* Detailview, renders a list of products in this Product
*/
class ProductDetailView extends GenericView {

	public function render($data=null) { 
		if($data == null)
			$data = $this->model->getData();
			
		$this->renderBackLink();
	
		if($data != null){  ?>
			<div class="single-product">
				<div class="row-fluid">
					<div class="span8 product-image">
						<img src="<?php echo SYSTEM_URL_WEBSHOP.'/'.$data->imageDish; ?>" />
					</div>
					<div class="span4 product-info">
						<h3><?php echo $data->productName; ?></h3>
						<p class="product-description">
							<?php echo $data->productDesc; ?>
						</p>
						<p class="product-price">
							<?php echo $this->formatMoney($data->productPrice); ?>
						</p>
					</div>
				</div>
			</div>
		<?php
		}
		else {
			echo '<div class="product-not-found">Dit product bestaat niet (meer).</div>';
		}
	}
}
?>