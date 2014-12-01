<?php
class FilterWidget extends WP_Widget {

	public function __construct() {
		parent::__construct(
	 		'sytematic_webshop_filterwidget', // Base ID
			'Webshop filter', // Name
			array( 'description' => __( 'Een widget die een filter selectie toont, om producten te zoeken.', 'text_domain' ), ) // Args
		);
		
		include_once(WEBSHOP_PLUGIN_PATH.'views/GenericView.php');
		include_once(WEBSHOP_PLUGIN_PATH.'models/GenericModel.php');
		include_once(WEBSHOP_PLUGIN_PATH.'models/WebshopOptions.php');		
	}

	private function parseFromInstance($instance, $key, $title) {
	 	if ( isset( $instance[ $key ] ) ) {
			$ret = $instance[ $key ];
		}
		else {
			$ret = __( $title, 'text_domain' );
		}
		return $ret;
		
	}

 	public function form( $instance ) {
		$title  = $this->parseFromInstance($instance, 'title', 'Titel');
		$defId  = $this->parseFromInstance($instance, 'definition_id', 'Definitie iD:');
		$season = $this->parseFromInstance($instance, 'show_season', 'Toon seizoen?');
		$brand  = $this->parseFromInstance($instance, 'show_brandfilter', 'Toon merk?');
		$color  = $this->parseFromInstance($instance, 'show_color', 'Toon kleur?');
		$extraParam = $this->parseFromInstance($instance, 'extra_param', 'Extra parameter string');
		$useCat = $this->parseFromInstance($instance, 'use_category', 'Gebruik category ID');
		?>
		<p>
		<?php 
			echo $this->getInput('title','Titel:','text',$title).'<br/>';
			echo $this->getInput('definition_id','Definitie ID:','text',$defId).'<br/>';
			echo $this->getInput('show_brandfilter','Toon merk?','checkbox',$brand).'<br/>';
			echo $this->getInput('show_season','Toon seizoen?','checkbox',$season).'<br/>';
			echo $this->getInput('show_color','Toon kleur?','checkbox',$color).'<br/>';
			echo $this->getInput('extra_param','Extra GET parameter string ','text',$extraParam).'<br/>';
			echo $this->getInput('use_category','Gebruik category ID','checkbox',$useCat).'<br/>';
		?>
		</p>
		<?php 	
	}

	private function getInput($name, $title, $type='text', $val=''){
		if($type == 'checkbox' && $val == 'true') {
			$c = ' checked="checked" ';
			$val = 'true';
		} else {
			$c = '';
		}

		if($type == 'checkbox'){
			$val = 'true';
		}
		return '<label for="'.$this->get_field_id($name).'">'._e($title).'</label> 
		<input class="widefat" 
			   id="'.$this->get_field_id($name).'" 
			   name="'.$this->get_field_name($name).'" 
			   type="'.$type.'" 
			   value="'.esc_attr($val).'"  
			   '.$c.' />';

	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['extra_param'] = strip_tags( $new_instance['extra_param'] );
		$instance['definition_id'] = strip_tags( $new_instance['definition_id'] );
	    $instance['show_brandfilter'] = ( ! empty( $new_instance['show_brandfilter'] ) ) ? strip_tags( $new_instance['show_brandfilter'] ) : '';
	    $instance['show_season'] = ( ! empty( $new_instance['show_season'] ) ) ? strip_tags( $new_instance['show_season'] ) : '';
	    $instance['show_color'] = ( ! empty( $new_instance['show_color'] ) ) ? strip_tags( $new_instance['show_color'] ) : '';
	    $instance['use_category'] = ( ! empty( $new_instance['use_category'] ) ) ? strip_tags( $new_instance['use_category'] ) : '';

		return $instance;
	}

	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		$definition_id = $instance['definition_id'];
		$season = $instance['show_season'];
		$brand = $instance['show_brandfilter'];
		$color = $instance['show_color'];
		$extraParam = $instance['extra_param'];
		$useCat = $instance['use_category'];

		$season = $season ? 'true': 'false';
		$brand = $brand ? 'true'  : 'false';
		$color = $color ? 'true'  : 'false';
		$useCat = $useCat ? 'true' : 'false';
		echo $before_widget;
		if ( ! empty( $title ) )
			echo $before_title . $title . $after_title;

		$options = new WebshopOptions();
		$options->loadOptions();
		$hostname = $options->getOption('hostname');

		if(!$useCat || $useCat == 'false'){ 
			$defId = $definition_id;
		} else {
			$defId = 0; 
		}

		if($options->getOption('custom_search_results_renderer') == 'true'){
			$customRenderer = '"customResultsRenderer" : myCustomRenderer_'.$defId.',';
		}

		

		$render = '
			<div id="filter_module_'.$defId.'"></div>
			<!-- assumes jquery is loaded above this spot -->
			<script type="text/javascript">
				var catId = null;

				if(WebshopItem_id != null && WebshopType != null && WebshopType == "categories"){
					catId = WebshopItem_id;
				}

				jQuery(document).ready(function($){
					$("#filter_module_'.$defId.'").filtersystem({
						"base_url" : "'.SYSTEM_URL_WEBSHOP.'",
						"hostname" : "'.$hostname.'",
						"FilterDefinition_id" : "'.$definition_id.'",
						"Category_id" : catId,
						"show_color" : '.$color.',
						"show_season" : '.$season.',
						"show_brand" : '.$brand.',
						"use_category" : '.$useCat.',
						'.$customRenderer.'
						"extra_param_string" : "'.$extraParam.'",
					
					});
				});

 
			</script>';
		echo $render;

		echo $after_widget;	
	}
}
?>
