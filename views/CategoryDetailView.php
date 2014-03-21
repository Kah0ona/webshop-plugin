<?php 
/**
* Detailview, renders a list of products in this category
*/
class CategoryDetailView extends GenericView {
	protected $numCols= 2;
	protected $productModel = null;
	public function setNumCols($num){
		$this->numCols = $num;
	}
	
	public function setProductModel($mod){
		$this->productModel = $mod;
	}

	public function render($data=null, $renderDetailOnOverview=false) { 
		if($data == null) {
			$data = $this->model->getData();
		}
		
		include_once('ProductView.php');
		//not a problem if we feed it a category model

//		$productModel = new ProductModel($this->model->getOptions()->getOption('hostname'));
//		$productModel->setOptions($this->model->getOptions());

		$productView = new ProductView($this->productModel);
		//$this->renderBackLink();
		//
		if($data->numColsOnSite != null){
			$this->numCols = $data->numColsOnSite;
		}	

		$productView->setNumCols($this->numCols);


		//if it has subcategories, render those. 
		$subCategories = $this->model->getSubcategories($data->Category_id, !$data->noNestingOnSite);

		if($data->categoryDesc != null && $data->categoryDesc != ''){
			echo '<p class="category-description">'.nl2br($data->categoryDesc).'</p>';
		}				

	
		if($data != null && count($this->productModel->getData()) > 0){
			if($subCategories != null && count($subCategories) > 0){
				echo '<h3>Producten</h3>';
			}
			echo $productView->render($data->Product, $renderDetailOnOverview, $data->Category_id); 
		}
		elseif ($subCategories == null || count($subCategories) == 0)
			echo '<div>Deze categorie bevat momenteel geen producten.</div>';
		
		if($subCategories != null && count($subCategories) > 0) {
			$catView = new CategoryView($this->model);
			$catView->setNumCols($this->numCols);
			$catView->setParentId($data->Category_id);
			echo '<h3>Subcategorieën</h3>';
		
			$catView->renderGrid(array('nogroup'=>$subCategories));
		}

		
	}
	
	
}
?>
