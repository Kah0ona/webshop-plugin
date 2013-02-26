<?php 
interface IGenericView {
	/**
	* $args contains an object or array with values, to be used for rendering
	*/
	public function render($args=null);	
}
?>