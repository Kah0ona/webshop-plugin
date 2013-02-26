<?php 
/**
* Overview, showing a list of categories
*/
class CategoryView implements IGenericView {
	private $sortedMap=Array();

	public function setData($data) {
		$this->sortedMap = $data;
	}

	public function render($args=null) { ?>	
		<!-- Start rendering CategoryView -->
		<ul class="categories">
		<?php
			if($this->sortedMap == null):
		?>
			<div><p>Er zijn geen categorie&euml;n gevonden.</p></div>
		<?php endif; ?>
		<?php foreach ($this->sortedMap as $group=>$cats) : ?>
			<?php if($group == 'nogroup') : ?>
				<?php foreach($cats as $cat) : ?>
					<li class="category-item category-package">
						<a href="/categories/<?php echo $cat->Category_id; ?>#<?php echo $cat->categoryName; ?>">
							<?php echo $cat->categoryName; ?>
						</a>
					</li>
				<?php endforeach; ?>
			<?php else : ?>
					<li class="category-title"><?php echo $group; ?></li>
					<li class="category dropdown-wrap">
					<ul>
					<?php foreach($cats as $cat) : ?>
						<li class="category-item category-package">
							<a href="/categories/<?php echo $cat->Category_id; ?>#<?php echo $cat->categoryName; ?>">
								<?php echo $cat->categoryName; ?>
							</a>
						</li>
					<?php endforeach; ?>
					</ul>
				</li>
			<?php endif; ?>
		<?php endforeach; ?>
		</ul>
		<!-- End CategoryView -->

	<?php }
	
}
?>