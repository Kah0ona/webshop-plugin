<?php
function calculateSHASignForOgone(){
		$passPhrase = 'mypass';
		
		define('SYSTEM_URL_WEBSHOP','http://webshop.lokaalgevonden.nl');
		
		$ret = array();
		$ret[] = 'AMOUNT=1500';
		$ret[] = 'CURRENCY=EUR';
		$ret[] = 'LANGUAGE=nl_NL';
		$ret[] = 'ORDERID=1234';
		$ret[] = 'PSPID=denimes';
		$ret[] = 'EMAIL=marten@sytematic.nl';
		$ret[] = 'OWNERZIP=1234AB';
		$ret[] = 'OWNERADDRESS=Hugo de grootstraat 62b';
		$ret[] = 'OWNERCTY=Den Haag';
		$ret[] = 'OWNERTOWN=Den Haag';
		$ret[] = 'OWNERTELNO=06-49343492';
		$ret[] = 'ACCEPTURL='.SYSTEM_URL_WEBSHOP.'/public/ogone/accept';
		$ret[] = 'DECLINEURL='.SYSTEM_URL_WEBSHOP.'/public/ogone/decline';
		$ret[] = 'EXCEPTIONURL='.SYSTEM_URL_WEBSHOP.'/public/ogone/exception';				
		$ret[] = 'CANCELURL='.SYSTEM_URL_WEBSHOP.'/public/ogone/cancel';
				
		sort($ret); //sort alphabetically
		
		//interleave with passphrase
		for($i = 0; $i < count($ret); $i++){
			$ret[$i]=$ret[$i].$passPhrase;
		}
		
		$toHash = implode("",$ret);

		
		echo $toHash;		
		$hash = sha1($toHash);
		$hash = strtoupper($hash);
		return $hash;
}
	
$result = calculateSHASignForOgone();
echo '<br/>';
echo '<br/>';
echo '<br/>';
echo 'expected: ';
$exp = '2085C5CD1E19D3E85EC12F998D2764E1F8A6066C';
echo '<br/>';
echo $exp;

echo '<br/>';
echo 'result: ';
echo '<br/>';
echo $result;
echo '<br/>';

if($result != $exp){
	echo 'FAIL *** HASHING FAILED!';
}
else {
	echo 'HASHING SUCCESS!';
}
?>