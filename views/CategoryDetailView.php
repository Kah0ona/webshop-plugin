<?php 
/**
* Detailview, renders a list of products in this category
*/
class CategoryDetailView extends GenericView {

	public function render($data=null) { 
		if($data == null)
			$data = $this->model->getData();
		
		include_once('ProductView.php');
		$productView = new ProductView(null);
	
		if($data != null && count($data) > 0) 
			echo $productView->render($data->Product); 
		else
			echo '<div>Deze categorie bestaat niet (meer).</div>';
	}
}
?>