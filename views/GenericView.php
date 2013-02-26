<?php 
abstract class GenericView {
	protected $model;
	public function __construct($model){
		$this->model = $model;
	}
	
	public function getModel(){
		return $this->model;
	}
	
	/**
	* $args contains an object or array with values, to be used for rendering
	*/
	public abstract function render($args=null);	
}
?>