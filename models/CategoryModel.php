<?php
class CategoryModel extends GenericModel {
	protected $sortedMap=null;
	protected $categoryTitleOrder=null;
	protected $serviceUrl = null;
	public $maxNestingLevels = 1;

	function __construct($hostname) {
		$this->hostname=$hostname;
		$this->serviceUrl = BASE_URL_WEBSHOP.'/categories';
	} 
		
	public function fetchCategories($params, $returnString=false){
		return $this->fetch($this->serviceUrl, $params, $returnString);
	}
	
	/**
	* Fetches the category with the id currently set by $this->setId(), or set by a previous call to $this->isDetailPage(); 
	*/
	public function fetchCategory(){
		return $this->fetchByID('Category_id');
	}
	
	public function isDetailPage(){
		return parent::isDetailPage('categories');
	}
	
	public function setCategoryTitleOrder($map){
		$this->categoryTitleOrder = $map;
	}
	
	public function getSubcategories($parentId){
		if($parentId == null){
			return null;
		}
		return $this->fetch($this->serviceUrl, array('Parent_id'=>$parentId), false);
	}
	
	public function fetchSortedCategories($useNesting = false){
		$arr = array(
			'hostname'=>$this->hostname,
			'useNesting'=>$useNesting,
			
		);
		
		if($this->maxNestingLevels == 1){
			$arr['Parent_id'] = 'NULL';
		}
		
		$cats = $this->fetchCategories($arr);
		//print_r($cats);
		//order by group title, and remap
		$map = Array();
		foreach($cats as $c){
			if($c->groupTitle != null){
				$t = trim($c->groupTitle);
				if($map[$t] == null)
					$map[$t] = Array();
					
				$map[$t][] = $c;
			}
			else {
				if($map['nogroup'] == null)
					$map['nogroup'] = Array();
									
				$map['nogroup'][] = $c;
			}
		}
		
		$this->sortedMap = Array();
		//use guide map to sort
		if($this->categoryTitleOrder != null){
			foreach($this->categoryTitleOrder as $title){
				$this->sortedMap[$title] = $map[$title];
			}
		}
		else {
			$this->sortedMap = $map;
		}
		
		return $this->sortedMap;
	}
	
	public function fetchNestedCategories($useNesting = false){
		$arr = array(
			'hostname'=>$this->hostname,
			'useNesting'=>$useNesting
		);
		
		if($this->maxNestingLevels == 1){
			$arr['Parent_id'] = 'NULL';
		}

		
		
		$cats = $this->fetchCategories($arr);
		
		//order by group title, and remap
		
		foreach($cats as $cat){
			if($cat->Parent_id == null)
				$cat->Parent_id = 0;
		}
		$tree = $this->buildTree($cats);	


		//now, for all root categories, re-map into same group-titles, to achieve backwards compatibility.
		$map = array();
		foreach($tree as $c){ //walk top level only.
			if($c->groupTitle != null){
				$t = trim($c->groupTitle);
				if($map[$t] == null)
					$map[$t] = Array();
					
				$map[$t][] = $c;
			}
			else {
				if($map['nogroup'] == null)
					$map['nogroup'] = Array();
									
				$map['nogroup'][] = $c;
			}
		}
		$this->sortedMap = $map;
		return $this->sortedMap;

	}
	
	private function buildTree(array &$elements, $parentId = 0) {
       $branch = array();

       foreach ($elements as $element) {
           if ($element->Parent_id === $parentId) {
               $children = $this->buildTree($elements, $element->Category_id);

               if (count($children) > 0) {
                   $element->children = $children;
               }
               $branch[$element->Category_id] = $element;
                          }
       }
       return $branch;
    }
    
    
	/**
	* Is null, unless fetchSortedCategories has been called before. This returns the in-memory version
	*/
	public function getSortedMap(){
		return $this->sortedMap;
	}		
}
?>