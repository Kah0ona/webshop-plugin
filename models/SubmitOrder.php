<?php
//incoming call from ajax
session_start();
define('SYSTEM_URL_WEBSHOP', 'http://webshop.sytematic.nl');
define('BASE_URL_WEBSHOP', SYSTEM_URL_WEBSHOP.'/public');

include_once('GenericModel.php');
include_once('CheckoutModel.php');

$checkout = new CheckoutModel($_POST['hostname']);
$resultStatus = $checkout->sendOrderToBackend();

header('HTTP/1.0 '.$resultStatus);
header('Content-Type: application/json; charset=UTF8');
if($resultStatus != 200)
	echo '{ "error" : "'.$checkout->getStatusMessage().'" }';
else 
	echo '{ "message" : "Bestelling geplaatst" }';