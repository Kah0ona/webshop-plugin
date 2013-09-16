<?php
	$_POST['firstname'] = 'Test Case Marten';
	$_POST['surname'] = 'Test Sytema';
	$_POST['street'] = 'Hugo de Grootstraat';
	$_POST['number'] = '62b';
	$_POST['postcode'] = '2518ee';
	$_POST['city'] = 'Den Haag';
	$_POST['email'] = 'marten@sytematic.nl';
	$_POST['companyName'] = 'Test Company';
	$_POST['VATnumber'] = '123456789';
	$_POST['phone'] = '0612345678';
	$_POST['payment-method'] = 'ideal';
	$_POST['DeliveryMethod_id'] = '1';
	
	$_SESSION['shoppingCart'] = json_decode('[{"Product_id":"3","title":"Malene Birger jurk","desc":"Lourena jurkje","thumb":"http://webshop.sytematic.nl/uploads/Product/Product_imageDish_23_1358530224321.jpg","quantity":"1","price":"499.95","VAT":"0.21"}]');


	//print_r($_SERVER);
	if($_SERVER['SERVER_NAME'] != 'localhost')
		define('SYSTEM_URL_WEBSHOP', 'http://webshop.lokaalgevonden.nl');
	else
		define('SYSTEM_URL_WEBSHOP', 'http://webshopdev.sytematic.nl');
	define('BASE_URL_WEBSHOP', SYSTEM_URL_WEBSHOP.'/public');
	define('EURO_FORMAT', '%.2n');
	define('WEBSHOP_PLUGIN_PATH', '/Users/marten/Sites/wordpress/wp-content/plugins/webshop-plugin');
	
	
	include_once('../models/GenericModel.php');
	include_once('../models/WebshopOptions.php');	
	include_once('../lib/ideal/sisow.cls5.php');
	include_once('../models/CheckoutModel.php');
	include_once('../models/DeliveryCostModel.php');	

	$options = new WebshopOptions();
	$options->setOptions(array(
		'hostname' =>'test',
	));
	
	$checkout = new CheckoutModel($options);
	$resultStatus = $checkout->sendOrderToBackend($_POST);
	
	echo 'Status: '.$resultStatus;
	echo '<br/>';
	echo 'Status message: '.$checkout->getStatusMessage();
?>