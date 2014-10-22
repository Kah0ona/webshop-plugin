<?php 
echo 'xxx';
/*
error_reporting(E_ALL);
function makeParamString($aParams){
	$strString = '';
	foreach ( $aParams as $strKey => $strValue ) 
	  $strString .= '&' . urlencode($strKey) . '=' . urlencode($strValue);

	# remove first &  
	return substr( $strString ,1 )  ;   
}

$aParameters = array();
$aParameters['rtlo'] = 120639;
$aParameters['bank'] = '0721';
$aParameters['description'] = 'Test Desc';
$aParameters['amount'] =  100;
$aParameters['returnurl'] = '/';
$aParameters['reporturl'] = 'http://webshop.lokaalgevonden.nl/public/targetpay_notifications/1355';
  
$params = makeParamString($aParameters);
$ch = curl_init();
$url = 'https://www.targetpay.com/ideal/start?';
$url = $url.''.$params;
echo $url;
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($link, CURLOPT_SSL_VERIFYPEER, FALSE); 
$strResponse = curl_exec($ch);
curl_close($ch);

echo 'Response: ' .$strResponse;


*/


include_once('../models/GenericModel.php');
include_once('../views/GenericView.php');
include_once('../models/TransactionResultModel.php');
include_once('../views/TransactionResultView.php');


$_SESSION['Order__id'] = 1358;
$m = new TransactionResultModel(null);
define('TARGETPAY_NOTIFY_URL','http://webshop.lokaalgevonden.nl/public/targetpay_notifications');
$m->doTargetpayCallbackToBackend('000000 OK', '123123123', 120639);
echo 'err '.$m->curlError;
?>
