<?php
class CategoryModel extends GenericModel {
	protected $sortedMap=null;
	protected $categoryTitleOrder=null;
	
	function __construct($hostname) {
		$this->hostname=$hostname;
	} 
	
	public function fetchCategories($params, $returnString=false){
		$url = BASE_URL_CATERINGSOFTWARE.'/categories?'.$this->decodeParamsIntoGetString($params);
		$jsonString = $this->curl_fetch($url);
	
		if($returnString)
			return $jsonString;
		else {
			if($jsonString == null) {
				return Array();
			}
			return json_decode($jsonString);
			
		}
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