<?php 
abstract class GenericView {
	protected $model;

	public function __construct($model){
		$this->model = $model;
	}
	
	public function getModel(){
		return $this->model;
	}
	
	public function formatMoney($amount, $format = 'nl_NL'){
		 setlocale(LC_MONETARY, $format);
		 return money_format('%.2n', $amount);
	}
	

	
	public function renderBackLink(){
	
		?>
		<div class="row-fluid">
			<div class="span12"><p><a class="backtooverview" href="javascript:history.back()">&larr; terug naar overzicht</a></p></div>
		</div>
		<?php
	}
	
	public function startsWith($haystack, $needle) {
		return $needle === "" || strpos($haystack, $needle) === 0;
	}

	public function getImageUrl($data){
		if($this->startsWith($data, 'https://') || $this->startsWith($data, 'http://')){
			return $data;
		} else {
			return	SYSTEM_URL_WEBSHOP.'/uploads/Product/'.$data;
		}
	}
	/**
	* $args contains an object or array with values, to be used for rendering
	*/
	public abstract function render($args=null);	
}
?>
