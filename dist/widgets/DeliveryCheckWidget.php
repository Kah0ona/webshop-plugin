<?php
class DeliveryCheckWidget extends WP_Widget {

	public function __construct() {
		parent::__construct(
	 		'sytematic_webshop_deliverycheckwidget', // Base ID
			'Webshop bezorgcheck', // Name
			array( 'description' => __( 'Een widget die kaartje toont, en de gebruiker in staat stelt te kijken of er bezorgd wordt op zijn/haar adres.', 'text_domain' ), ) // Args
		);
		
		include_once(WEBSHOP_PLUGIN_PATH.'views/GenericView.php');
		include_once(WEBSHOP_PLUGIN_PATH.'models/GenericModel.php');
		include_once(WEBSHOP_PLUGIN_PATH.'models/DeliveryCostModel.php');
		include_once(WEBSHOP_PLUGIN_PATH.'views/DeliveryCostView.php');
		
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
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php 	
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
		$m = new DeliveryCostModel($hostname,$options);
		
		$v = new DeliveryCostView($m);
		
		$v->render();
		
		echo $after_widget;	
	}
}
?>