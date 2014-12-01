<?php
session_start();
/**
* This stores the personal details
*/
if($_GET['action'] == 'load'){
	if($_SESSION['personalDetails'] == null )
		$_SESSION['personalDetails'] = json_decode('[]');
		
	header('Content-Type: application/json');
	echo json_encode($_SESSION['personalDetails']);
}

if(count($_POST) > 0){
	foreach($_POST as $k=>$v){
		$_SESSION['personalDetails'][$k] = $v;
	}	

	//close session file to allow other users to write to it.
	session_write_close();
	
	header('Content-Type: application/json');
	echo json_encode($_SESSION['personalDetails']);
}
?>
