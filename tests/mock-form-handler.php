<?php 
	header('Content-Type: application/json; charset=utf-8');
	//header('Content-Type: application/json; charset=UTF8');

	echo json_encode(array('redirectUrl' => 'https://www.sisow.nl/Sisow/iDeal/Simulator.aspx?merchantid=2537507457&txid=TEST080489493323&sha1=c754690982273dc5a0b7ff3f65f733a33a4d142b'));
	exit;
?>