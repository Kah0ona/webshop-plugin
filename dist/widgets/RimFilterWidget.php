<?php
class RimFilterWidget extends WP_Widget {

	public function __construct() {
		parent::__construct(
	 		'sytematic_webshop_rimfilterwidget', // Base ID
			'Webshop Velgen filter', // Name
			array( 'description' => __( 'Een widget die een speciale velgen filter toont, om velgen te zoeken op basis van auto.', 'text_domain' ), ) // Args
		);
		
		include_once(WEBSHOP_PLUGIN_PATH.'views/GenericView.php');
		include_once(WEBSHOP_PLUGIN_PATH.'models/GenericModel.php');
		include_once(WEBSHOP_PLUGIN_PATH.'models/WebshopOptions.php');		
	}

 	public function form( $instance ) {
	 	if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'Titel', 'text_domain' );
		}
		?>
		<p>
		
		<?php 
			echo $this->getInput('title','Titel:','text',$title).'<br/>';
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
		return $instance;
	}

	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		echo $before_widget;
		if ( ! empty( $title ) )
			echo $before_title . $title . $after_title;

		$options = new WebshopOptions();
		$options->loadOptions();
		$hostname = $options->getOption('hostname');

		$carsdb = $this->loadCarsFile();
		$render = '
			<div id="rim_filter_widget"></div>
			<!-- assumes jquery is loaded above this spot -->
			<script type="text/javascript">
				jQuery(document).ready(function($){
					$("#rim_filter_widget").rimfiltersystem({
						"base_url" : "'.SYSTEM_URL_WEBSHOP.'",
						"hostname" : "'.$hostname.'",
						"db"	   : '.$carsdb.'

					
					});
				});
			</script>';
		echo $render;

		echo $after_widget;	
	}

	private function loadCarsFile(){
		return file_get_contents(WEBSHOP_PLUGIN_PATH.'js/carsdatabase.js');
	}
}
?>
