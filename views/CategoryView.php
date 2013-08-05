<?php 
/**
* Overview, showing a list of categories
*/
class CategoryView extends GenericView {
	protected $numCols = 2;
	protected $maxNestingLevels = 1;
	protected $parentId=null;

	public function render($data=null, $renderMode='list', $recursive=true) { 
		$nest = $this->model->getOptions()->getOption('nested_category_level');
		if($nest !== null && is_numeric($nest)) {
			$this->maxNestingLevels = $nest-1;
		}
		if($data == null)
			$data = $this->model->getSortedMap();
			
		if($renderMode == 'list' && !$recursive){
			$this->renderList($data);
		}	
		elseif($renderMode == 'list' && $recursive){
			$this->renderListRecursive($data);
		}
		else {
			$this->renderGrid($data);
		}
	}
	
	public function setNumCols($num){
		$this->numCols = $num;
	}
	
	protected function calculateSpan(){
		$numColName = '';
		switch($this->numCols){
			case 1:
				$numColName='span12';
			break;
			case 2:
				$numColName='span6';
			break;
			case 3:
				$numColName='span4';
			break;
			case 4:
				$numColName='span3';
			break;
			case 6:
				$numColName='span2';
			break;
			case 12: 
				$numColName='span1';	
			break;
			default:
				$numColName='span4';
			break;
		}
		return $numColName;
	}
	public function setParentId($id){
		$this->parentId = $id;
	}

	protected function shouldRenderRowHtmlStart($i, $total){
		return $i%$this->numCols == 1 || $this->numCols == 1;
	}
	
	protected function shouldRenderRowHtmlEnd($i, $total){
		return $i%$this->numCols == 0 || $i >= $total  || $this->numCols == 1;
	}
	
	protected function getDetailLink($category){
		return site_url().'/categories/'.$category->Category_id.'#'.$category->categoryName;
	}
	
	public function renderGrid($data=null){ 
		if($data == null)
			$data = $this->model->getSortedMap();
			
		$span = $this->calculateSpan();
		//print_r($data);
	?>
				
		<!-- Start rendering CategoryView -->
		<div class="category-overview-grid" id="category-overview-grid-<?php echo $this->parentId; ?>">
			<?php foreach($data as $group=>$categories) : $i = 1; $c = count($categories); ?>
				<?php if($group != 'nogroup'): ?>
				<h3><?php echo $group; ?></h3>
				<?php endif; ?>
				<?php foreach($categories as $category) : ?>
				<?php if($this->shouldRenderRowHtmlStart($i, $c)) :?>
					<div class="row-fluid category-row">
				<?php endif; ?>	
						<div class="<?php echo $span; ?> category category-<?php echo $category->Category_id; ?> <?php echo $this->shouldRenderRowHtmlEnd($i, $c) ? 'last' : ''; ?>">
							<?php $this->renderCategory($category); ?>
						</div>
				<?php if($this->shouldRenderRowHtmlEnd($i, $c)) :?>
					</div><!-- end row-fluid -->
				<?php endif; ?>
				<?php $i++; ?>
				<?php endforeach; ?>				
			<?php endforeach; ?>
		</div>
		<!-- End CategoryView -->

 
	
	<?php	
	}
	
	
	public function renderCategory($category){ ?>
		<div class="category-image category-image-<?php echo $category->Category_id; ?>">
			<?php 
				if($category->categoryImage != null) {
					echo '<a href="'.$this->getDetailLink($category).'">';
					echo   '<img src="'.SYSTEM_URL_WEBSHOP.'/uploads/Category/'.$category->categoryImage.'" alt="'.$category->categoryName.'" title="'.$category->categoryName.'">';
					echo '</a>';
				}
			?>		
		</div>		
		<div class="category-name category-name-<?php echo $category->Category_id; ?>">
			<?php 
			echo '<a href="'.$this->getDetailLink($category).'">';
			echo $category->categoryName; 
			echo '</a>';			
			?>
		</div>	
		
	<?php 
	}
	
	/**
	* $data should contain a nested list (with subcategories), they should have the key 'categories'
	*/
	public function renderListRecursive($data=null, $level = 0){ ?>
		<!-- Start rendering CategoryView -->
		<ul class="categories product-categories">
		<?php
			if($data == null):
		?>
			<div><p>Er zijn geen categorie&euml;n gevonden.</p></div>
		<?php else: ?>
			<?php foreach ($data as $group=>$cats) : ?>
				<?php if($group == 'nogroup') : ?>
					<?php foreach($cats as $cat) : ?>
						
						<li class="category-item category-package">
							<a href="<?php echo site_url(); ?>/categories/<?php echo $cat->Category_id; ?>#<?php echo $cat->categoryName; ?>">
								<?php echo $cat->categoryName; ?>
							</a>
						</li>
						<?php 
							if($cat->children != null && count($cat->children) > 0 && $level < $this->maxNestingLevels){
						?>
						<li class="category-item category-package category-subcategory">
						<?php
							echo $this->renderListRecursiveNoGroupTitles($cat->children, $level+1);
						?>
						</li>						
						<?php
							}
						?>
					<?php endforeach; ?>
				<?php else : ?>
						<li class="category-title"><?php echo $group; ?></li>
						<li class="category dropdown-wrap">
						<ul class="category-level-<?php echo $level; ?>">
						<?php foreach($cats as $cat) : ?>
							<li class="category-item category-package ">
								<a href="<?php echo site_url(); ?>/categories/<?php echo $cat->Category_id; ?>#<?php echo $cat->categoryName; ?>">
									<?php echo $cat->categoryName; ?>
								</a>
								
							</li>
							<?php 
								if($cat->children != null && count($cat->children) > 0 && $level < $this->maxNestingLevels){
							?>
							<li class="category-item category-package category-subcategory">
							<?php
								echo $this->renderListRecursiveNoGroupTitles($cat->children, $level+1);
							?>
							</li>
							<?php
								}
							?>
						<?php endforeach; ?>
						</ul>
					</li>
				<?php endif; ?>
			<?php endforeach; ?>
		<?php endif; ?>

		</ul>
		<!-- End CategoryView -->


	<?php	
	}
	/**
	* $data should contain a nested list (with subcategories), they should have the key 'categories'
	*/
	public function renderListRecursiveNoGroupTitles($data=null, $level){ ?>
	
		<!-- Start rendering CategoryView -->
		<ul class="categories  category-level-<?php echo $level; ?>">
			<?php foreach($data as $cat) : ?>
				<li class="category-item category-package">
					<a href="<?php echo site_url(); ?>/categories/<?php echo $cat->Category_id; ?>#<?php echo $cat->categoryName; ?>">
						<?php echo $cat->categoryName; ?>
					</a>
				</li>
				<?php 
					if($cat->children != null && count($cat->children) > 0 && $level < $this->maxNestingLevels){
				?>
				<li class="category-item category-package category-subcategory">
				<?php
					echo $this->renderListRecursiveNoGroupTitles($cat->children, $level+1);
				?>
				</li>
				<?php
				}
				?>
			<?php endforeach; ?>
		</ul>
		<!-- End CategoryView -->
	<?php	
	}
	
	
	public function renderList($data=null){ ?>
		<!-- Start rendering CategoryView -->
		<ul class="categories">
		<?php
			if($data == null):
		?>
			<div><p>Er zijn geen categorie&euml;n gevonden.</p></div>
		<?php else: ?>
			<?php foreach ($data as $group=>$cats) : ?>
				<?php if($group == 'nogroup') : ?>
					<?php foreach($cats as $cat) : ?>
						<li class="category-item category-package">
							<a href="<?php echo site_url(); ?>/categories/<?php echo $cat->Category_id; ?>#<?php echo $cat->categoryName; ?>">
								<?php echo $cat->categoryName; ?>
							</a>
						</li>
					<?php endforeach; ?>
				<?php else : ?>
						<li class="category-title"><?php echo $group; ?></li>
						<li class="category dropdown-wrap">
						<ul>
						<?php foreach($cats as $cat) : ?>
							<li class="category-item category-package ">
								<a href="<?php echo site_url(); ?>/categories/<?php echo $cat->Category_id; ?>#<?php echo $cat->categoryName; ?>">
									<?php echo $cat->categoryName; ?>
								</a>
							</li>
						<?php endforeach; ?>
						</ul>
					</li>
				<?php endif; ?>
			<?php endforeach; ?>
		<?php endif; ?>

		</ul>
		<!-- End CategoryView -->


	<?php	
	}

}
?>