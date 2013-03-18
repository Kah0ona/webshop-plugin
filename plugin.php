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
	protected $hostname = null;//TODO fix me, should be fetched from the $this->options.
	protected $adminView = null;
	protected $productModel = null; //only set if there is a post with a product shortcode
	protected $categoryModel = null; //only set if there is a post with a category shortcode
	protected $checkoutModel = null;
	/**
	 * Initializes the plugin by setting localization, filters, and administration functions.
	 */
	function __construct() {
		include_once('views/GenericView.php');	
		include_once('models/GenericModel.php');		


		// Load plugin text domain
		add_action('init', array( $this, 'plugin_textdomain' ) );
		add_action('init', array($this, 'load_options'));
		add_filter('the_posts', array($this, 'init_models'));
		add_filter('plugins_loaded', array($this,'start_session')); //first code to be executed.

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
		
		
		add_shortcode('webshop_categories', array($this, 'render_categories'));
		add_shortcode('webshop_products', array($this, 'render_products'));
		add_shortcode('webshop_checkout', array($this, 'render_checkout'));

		
		add_action( 'widgets_init', array($this, 'register_widgets' ));
				
		add_action( 'admin_menu', array($this, 'settings_menu' ));
		add_action( 'admin_init', array($this, 'register_settings'));
	
		add_filter( 'the_title', array($this, 'modify_title'));	
		add_action('wp_head', array($this, 'init_cart'));
	
		//this must be inside is_admin, see: http://codex.wordpress.org/AJAX_in_Plugins#Ajax_on_the_Viewer-Facing_Side	
		if(is_admin()){
			add_action('wp_ajax_nopriv_place_order',array($this,'process_order'));			
			add_action('wp_ajax_place_order',array($this,'process_order'));			
		}
	} // end constructor
	
	
	/**
	* Responds to the ajax call that submits the checkout form
	*/
	public function process_order(){
		session_start();
		include_once('models/GenericModel.php');
		include_once('lib/ideal/sisow.cls5.php');
		include_once('models/CheckoutModel.php');
		
		$this->load_options();
		
		$checkout = new CheckoutModel($this->options);
		$resultStatus = $checkout->sendOrderToBackend();
		$error = null;
		if($resultStatus == 200){
			$checkout->doIDeal(); //redirects away if everything goes well, returns an error if not. 
		}		

		header('Content-Type: application/json; charset=UTF8');

		if($checkout->getStatus() != 200)
			echo json_encode(array('error' => $checkout->getStatusMessage()));
		else 
			echo json_encode(array('message' => 'Bestelling geplaatst'));
		
		exit;
	}
	
	public function start_session(){
		session_start();
	}
	
	public function init_models($posts){
		if($this->containsShortCode($posts, 'products')){
			include_once('models/ProductModel.php');
			$this->productModel = new ProductModel($this->hostname);
			$this->productModel->isDetailPage('products') ? $this->productModel->fetchProduct() : 
															$this->productModel->fetchProductsDefault();
		}
		
		if($this->containsShortCode($posts, 'categories')){
			include_once('models/CategoryModel.php');		
			$this->categoryModel = new CategoryModel($this->hostname);
			$this->categoryModel->isDetailPage('categories') ? 	$this->categoryModel->fetchCategory() : $this->categoryModel->fetchSortedCategories();
		}
			
		return $posts;
	}
	
	
	
	private function containsShortcode($posts, $type='products'){
		foreach($posts as $post){
			if(stripos($post->post_content, '[webshop_'.$type) !== false ) {
	        	return true;
	        }
		}
		return false;
	}
	
	
	public function init_cart(){
		include_once('views/CartInitializerView.php');
		$init = new CartInitializerView($this->options);
		$init->render($this->options);
	}
	
	public function modify_title($title){
		if($this->isWebshopPage() && in_the_loop())	{ //modify the title, iff it is a page from the webshop
			return $this->getWebshopPageTitle();
		}
		return $title;
	}



	public function isWebshopPage(){
		//examine $_SERVER, to see if 'categories' or 'products' is in the URL.
		$url = $_SERVER['REDIRECT_URL'];
		return stristr($url, 'categories') || stristr($url, 'products');
	}
	
	
	public function getWebshopPageTitle(){
		
	}
	
	
	public function register_widgets(){
		include_once('widgets/CategoryWidget.php');

		register_widget('CategoryWidget');
	}
	
	public function load_options(){
		include_once('models/WebshopOptions.php');
		$w = new WebshopOptions();
		$this->options = $w;
		$this->hostname = $this->options->getOption('hostname');
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
		//wp_enqueue_style('jquery-ui-css', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1/themes/base/jquery-ui.css');
		wp_enqueue_style( 'sytematic-webshop-plugin-styles', plugins_url( '/webshop-plugin/css/display.css' ) );
		wp_enqueue_style( 'bootstrap', plugins_url( '/webshop-plugin/css/bootstrap.min.css' ) );
		wp_enqueue_style( 'bootstrap-responsive', plugins_url( '/webshop-plugin/css/bootstrap-responsive.min.css' ) );
	} // end register_plugin_styles
	
	/**
	 * Registers and enqueues plugin-specific scripts.
	 */
	public function register_plugin_scripts() {
		wp_enqueue_script('bootstrap-js', plugins_url('/webshop-plugin/js/bootstrap.min.js'), array('jquery') );		
		wp_enqueue_script('jquery.json', plugins_url('/webshop-plugin/js/jquery.json.min.js'), array('jquery') );		
		
		
		if($this->isCheckoutPage()){
			wp_enqueue_script('form.js', plugins_url('/webshop-plugin/js/jquery.form.js'), array('jquery'));
			wp_enqueue_script('validation.js', plugins_url('/webshop-plugin/js/jquery.validate.js'), array('jquery', 'form.js'));
			wp_enqueue_script('sytematic-webshop-shopping-cart-order', plugins_url('/webshop-plugin/js/order-form.js' ), array('jquery','jquery.json') );
			
			wp_localize_script( 'sytematic-webshop-shopping-cart-order', 'SubmitFormUrl', array( 
						'ajaxurl' => plugins_url('/webshop-plugin/models/SubmitOrder.php'),
					//	'cart_nonce'=>wp_create_nonce('cart_nonce')
						
							
					) 
			);

			
			include_once('lib/ideal/sisow.cls5.php');
		
		}
		
		wp_enqueue_script('sytematic-webshop-shopping-cart', plugins_url( '/webshop-plugin/js/jquery.shoppingcart.js' ), array('jquery','jquery.json') );		

	} // end register_plugin_scripts
	

	public function isCheckoutPage(){
		return strpos($_SERVER['REDIRECT_URL'], 'checkout') !== false;
	}
	

	/*---------------------------------------------*
	 * Controller Functions
	 *---------------------------------------------*/
	public function render_categories(){
		if($this->categoryModel->isDetailPage()) {
			include_once('views/CategoryDetailView.php');
			$v = new CategoryDetailView($this->categoryModel);
			$v->render();
		}
		else {
			include_once('views/CategoryView.php');
			$v = new CategoryView($this->categoryModel);
			$v->render();
		}		
	}
	
	public function render_products(){
		if($this->productModel->isDetailPage()) {
			include_once('views/ProductDetailView.php');
			$v = new ProductDetailView($this->productModel);
			$v->render();
		}
		else {
			include_once('views/ProductView.php');
			$v = new ProductView($this->productModel);
			$v->render();
		}		
	}
  
	public function render_checkout(){
		include_once('models/CheckoutModel.php');
		include_once('views/CheckoutView.php');
		$this->checkoutModel = new CheckoutModel($this->options);
		$v = new CheckoutView($this->checkoutModel);
		$v->render();
	}
	  
} // end class

$sytematicWebshop = new SytematicWebshop();
