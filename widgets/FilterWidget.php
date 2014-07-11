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

 	public function form( $instance ) {
	 	if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'Titel', 'text_domain' );
		}
	 	if ( isset( $instance[ 'definition_id' ] ) ) {
			$defId = $instance[ 'definition_id' ];
		}
		else {
			$defId = __( 'Definitie ID:', 'text_domain' );
		}

	 	if ( isset( $instance[ 'show_season' ] ) ) {
			$season = $instance[ 'show_season' ];
		}
		else {
			$season = __( 'Toon Seizoen?', 'text_domain' );
		}
	 	if ( isset( $instance[ 'show_color' ] ) ) {
			$color = $instance[ 'show_color' ];
		}
		else {
			$color = __( 'Toon kleur?', 'text_domain' );
		}
		?>
		<p>
		
<?php 
			echo $this->getInput('title','Titel:','text',$title).'<br/>';
			echo $this->getInput('definition_id','Definitie ID:','text',$defId).'<br/>';
			echo $this->getInput('show_season','Toon seizoen?','checkbox',$season).'<br/>';
			echo $this->getInput('show_color','Toon kleur?','checkbox',$color).'<br/>';
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
		$instance['definition_id'] = strip_tags( $new_instance['definition_id'] );
	    $instance['show_season'] = ( ! empty( $new_instance['show_season'] ) ) ? strip_tags( $new_instance['show_season'] ) : '';
	    $instance['show_color'] = ( ! empty( $new_instance['show_color'] ) ) ? strip_tags( $new_instance['show_color'] ) : '';

		return $instance;
	}

	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		$definition_id = $instance['definition_id'];
		$season = $instance['show_season'];
		$color = $instance['show_color'];

		$season = $season ? 'true': 'false';
		$color = $color ? 'true' : 'false';

		echo $before_widget;
		if ( ! empty( $title ) )
			echo $before_title . $title . $after_title;

		$options = new WebshopOptions();
		$options->loadOptions();
		$hostname = $options->getOption('hostname');
		$render = '
			<div id="filter_module_'.$definition_id.'"></div>
			<!-- assumes jquery is loaded above this spot -->
			<script type="text/javascript">
				jQuery(document).ready(function($){
					$("#filter_module_'.$definition_id.'").filtersystem({
						"base_url" : "'.SYSTEM_URL_WEBSHOP.'",
						"hostname" : "'.$hostname.'",
						"FilterDefinition_id" : "'.$definition_id.'",
						"show_color" : '.$color.',
						"show_season" : '.$season.'
					
					});
				});
			</script>';
		echo $render;

		echo $after_widget;	
	}
}
?>
