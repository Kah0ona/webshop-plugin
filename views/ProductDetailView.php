<?php 
/**
* Detailview, renders a list of products in this Product
*/
class ProductDetailView extends GenericView {

	public function render($data=null) { 
		if($data == null)
			$data = $this->model->getData();
	
		if($data != null){  ?>
			<div class="single-product">
				<?php echo $data->productName; ?>
			</div>
		<?php
		}
		else {
			echo '<div>Dit product bestaat niet (meer).</div>';
		}
	}
}
?>