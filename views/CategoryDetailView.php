<?php 
/**
* Overview, showing a list of categories
*/
class CategoryDetailView extends GenericView {
	private $sortedMap=Array();

	public function setData($data) {
		$this->sortedMap = $data;
	}

	public function render($args=null) { 
		if($args == null)
			$args = $this->model->getData();
		
	?>	
		<!-- Start rendering CategoryDetailView -->
		<div>DETAIL OF ID <?php echo $args['title']; ?> </div>
		<!-- End CategoryDetailView -->
	<?php }
}
?>