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
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" 
			   id="<?php echo $this->get_field_id( 'title' ); ?>" 
			   name="<?php echo $this->get_field_name( 'title' ); ?>" 
			   type="text" 
			   value="<?php echo esc_attr( $title ); ?>" /><br/>


		<label for="<?php echo $this->get_field_id( 'definition_id' ); ?>"><?php _e( 'Definition ID:' ); ?></label> 
		<input class="widefat" 
			   id="<?php echo $this->get_field_id( 'definition_id' ); ?>" 
			   name="<?php echo $this->get_field_name( 'definition_id' ); ?>" 
			   type="text" 
               value="<?php echo esc_attr( $defId ); ?>" />
		</p>
		<?php 	
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['definition_id'] = strip_tags( $new_instance['definition_id'] );

		return $instance;
	}

	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		$definition_id = $instance['definition_id'];

		echo $before_widget;
		if ( ! empty( $title ) )
			echo $before_title . $title . $after_title;

		$options = new WebshopOptions();
		$options->loadOptions();
		$hostname = $options->getOption('hostname');

		$render = '
			<div id="filter_module"></div>
			<!-- assumes jquery is loaded above this spot -->
			<script src="'.plugins_url().'/webshop-plugin/js/jquery.filtersystem.js"></script>
			<script type="text/javascript">
				jQuery(document).ready(function($){
					$("#filter_module").filtersystem({
						"base_url" : "'.SYSTEM_URL_WEBSHOP.'",
						"hostname" : "'.$hostname.'",
					    "FilterDefinition_id" : "'.$definition_id.'"
					
					});
				});
			</script>';
		echo $render;

		echo $after_widget;	
	}
}
?>
