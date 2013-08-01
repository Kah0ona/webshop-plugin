<?php 
/**
* Detailview, renders a list of products in this category
*/
class CategoryDetailView extends GenericView {

	public function render($data=null, $renderDetailOnOverview=false) { 
		if($data == null) {
			$data = $this->model->getData();
		}
		
		include_once('ProductView.php');
		//not a problem if we feed it a category model
		$productView = new ProductView($this->model);
		//$this->renderBackLink();
		
		//if it has subcategories, render those. 
		$subCategories = $this->model->getSubcategories($data->Category_id);
		if($subCategories != null && count($subCategories) > 0) {
			$catView = new CategoryView($this->model);
			$catView->setNumCols(2);
	
			echo '<h3>Subcategorieën</h3>';
			$catView->renderGrid(array('nogroup'=>$subCategories));
		}
	
	
		if($data != null && count($data->Product) > 0) 
			echo $productView->render($data->Product, $renderDetailOnOverview, $data->Category_id); 
		elseif ($subCategories == null || count($subCategories) == 0)
			echo '<div>Deze categorie bevat momenteel geen producten.</div>';
		
	}
}
?>