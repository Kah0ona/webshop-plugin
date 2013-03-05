<?php
//incoming call from ajax
session_start();

include_once('CheckoutModel.php');

$checkout = new CheckoutModel($_POST['hostname']);
$resultStatus = $checkout->sendOrderToBackend();

header('Status: '.$resultStatus);
header('Content-type: application/json');
echo '{ "message" : "'.$checkout->getStatusMessage().'" }';