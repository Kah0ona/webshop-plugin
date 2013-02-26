<?php
class CategoryModel extends GenericModel {
	protected $sortedMap=null;
	protected $categoryTitleOrder=null;
	
	function __construct($hostname) {
		$this->hostname=$hostname;
	} 
		
	public function fetchCategories($params, $returnString=false){
		$url = BASE_URL_WEBSHOP.'/categories';
		return $this->fetch($url, $params, $returnString);
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
	
	public function fetchSortedMap(){
		$arr = array(
			'hostname'=>$this->hostname
		);
		
		if($_GET['id'] != null){
			$arr['Category_id'] = $_GET['id'];
		}
		else {
			$arr['useNesting']='false';
		}
	
		$cats = $this->fetchCategories($arr);
		
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
	
	
	/**
	* Is null, unless fetchSortedMap has been called before. This returns the in-memory version
	*/
	public function getSortedMap(){
		return $this->sortedMap;
	}		
}
?>