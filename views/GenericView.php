<?php 
abstract class GenericView {
	protected $model;

	public function __construct($model){
		$this->model = $model;
	}
	
	public function getModel(){
		return $this->model;
	}
	
	public function formatMoney($amount, $format = 'it_IT'){
		 setlocale(LC_MONETARY, $format);
		 return money_format('%.2n', $amount);
	}
	
	/**
	* $args contains an object or array with values, to be used for rendering
	*/
	public abstract function render($args=null);	
}
?>