<?php
/*
Plugin Name: sytematic-webshop
Plugin URI: http://www.lokaalgevonden.nl
Description: This plugin connects to the backend webshops of Sytematic software
Version: 1.0
Author: Marten Sytema
Author URI: http://www.sytematic.nl
Author Email: marten@sytematic.nl
License:

  Copyright 2013 Sytematic Software (marten@sytematic.nl)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as 
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
  
*/

define('SYSTEM_URL_WEBSHOP', 'http://webshop.sytematic.nl');
define('BASE_URL_WEBSHOP', SYSTEM_URL_WEBSHOP.'/public');
define('EURO_FORMAT', '%.2n');
define('WEBSHOP_PLUGIN_PATH', plugin_dir_path(__FILE__) );

setlocale(LC_MONETARY, 'it_IT');

class SytematicWebshop {
	protected $options = null;
	protected $hostname = 'denimes';//TODO fix me, should be fetched from the $this->options.
	protected $adminView = null;
	/**
	 * Initializes the plugin by setting localization, filters, and administration functions.
	 */
	function __construct() {
		
		// Load plugin text domain
		add_action('init', array( $this, 'plugin_textdomain' ) );
		add_action('init', array($this, 'load_options'));
		// Register admin styles and scripts
		add_action('admin_print_styles', array( $this, 'register_admin_styles' ) );
		add_action('admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );
	
		// Register site styles and scripts
		add_action('wp_enqueue_scripts', array( $this, 'register_plugin_styles' ) );
		add_action('wp_enqueue_scripts', array( $this, 'register_plugin_scripts' ) );
	
		// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		register_uninstall_hook( __FILE__, array( $this, 'uninstall' ) );
		
		
		add_shortcode('webshop_category', array($this, 'render_categories'));
		add_shortcode('webshop_products', array($this, 'render_products'));
		
		add_action( 'widgets_init', array($this, 'register_widgets' ));
		
		
		add_action( 'admin_menu', array($this, 'settings_menu' ));
		add_action( 'admin_init', array($this, 'register_settings') );
		
	} // end constructor
	
	public function register_widgets(){
		include_once('widgets/CategoryWidget.php');

		register_widget('CategoryWidget');
	}
	
	public function load_options(){
		include_once('models/WebshopOptions.php');
		$w = new WebshopOptions();
		$this->options = $w;
	}
	
	
	public function settings_menu(){
		add_options_page( 'Webshop opties', 
						  'Webshop', 
						  'manage_options', 
						  'sytematic-webshop', 
						  array($this, 'render_settings_menu'));
	}
	
	public function render_settings_menu(){
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		
		$this->adminView->render();		
	}
	
	public function register_settings(){
		$this->options->registerSettings();

		include_once('views/AdminView.php');
		$this->adminView = new AdminView($this->options);	
		$this->adminView->registerFieldSettings();
	}
	
	/**
	 * Fired when the plugin is activated.
	 *
	 * @param	boolean	$network_wide	True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog 
	 */
	public function activate( $network_wide ) {
		// TODO:	Define activation functionality here
	} // end activate
	
	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @param	boolean	$network_wide	True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog 
	 */
	public function deactivate( $network_wide ) {
		// TODO:	Define deactivation functionality here		
	} // end deactivate
	
	/**
	 * Fired when the plugin is uninstalled.
	 *
	 * @param	boolean	$network_wide	True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog 
	 */
	public function uninstall( $network_wide ) {
		// TODO:	Define uninstall functionality here		
	} // end uninstall

	/**
	 * Loads the plugin text domain for translation
	 */
	public function plugin_textdomain() {
	
		$domain = 'sytematic-webshop-locale';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
        load_textdomain( $domain, WP_LANG_DIR.'/'.$domain.'/'.$domain.'-'.$locale.'.mo' );
        load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

	} // end plugin_textdomain

	/**
	 * Registers and enqueues admin-specific styles.
	 */
	public function register_admin_styles() {
		wp_enqueue_style( 'sytematic-webshop-admin-styles', plugins_url( '/webshop-plugin/css/admin.css' ) );
	} // end register_admin_styles

	/**
	 * Registers and enqueues admin-specific JavaScript.
	 */	
	public function register_admin_scripts() {
		wp_enqueue_script( 'sytematic-webshop-admin-script', plugins_url( '/webshop-plugin/js/admin.js' ) );
	} // end register_admin_scripts
	
	/**
	 * Registers and enqueues plugin-specific styles.
	 */
	public function register_plugin_styles() {
		wp_enqueue_style( 'sytematic-webshop-plugin-styles', plugins_url( '/webshop-plugin/css/display.css' ) );
		wp_enqueue_style( 'bootstrap', plugins_url( '/webshop-plugin/css/bootstrap.min.css' ) );
		wp_enqueue_style( 'bootstrap-responsive', plugins_url( '/webshop-plugin/css/bootstrap-responsive.min.css' ) );
		
	} // end register_plugin_styles
	
	/**
	 * Registers and enqueues plugin-specific scripts.
	 */
	public function register_plugin_scripts() {
		wp_enqueue_script( 'sytematic-webshop-plugin-script', plugins_url( '/webshop-plugin/js/display.js' ) );
	} // end register_plugin_scripts
	

	/*---------------------------------------------*
	 * Controller Functions
	 *---------------------------------------------*/
	public function render_categories(){
		include_once('views/GenericView.php');	
		
		include_once('models/GenericModel.php');
		include_once('models/CategoryModel.php');

		$model = new CategoryModel($this->hostname, $this->options); 
		
		if($model->isDetailPage()) {
			include_once('views/CategoryDetailView.php');
			$model->fetchCategory();
			$v = new CategoryDetailView($model);
			$v->render();
		}
		else {
			include_once('views/CategoryView.php');
			$model->fetchSortedCategories();
			$v = new CategoryView($model);
			$v->render();
		}		

	}
	
	public function render_products(){
		include_once('views/GenericView.php');	
		
		include_once('models/GenericModel.php');
		include_once('models/ProductModel.php');

		$model = new ProductModel($this->hostname, $this->options); 
		
		if($model->isDetailPage()) {
			include_once('views/ProductDetailView.php');
			$model->fetchProduct();
			$v = new ProductDetailView($model);
			$v->render();
		}
		else {
			include_once('views/ProductView.php');
			$model->fetchProductsDefault();
			$v = new ProductView($model);
			$v->render();
		}		

	}
  
} // end class

$sytematicWebshop = new SytematicWebshop();
