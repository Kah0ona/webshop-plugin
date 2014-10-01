<?php
session_start();
/**
* This stores the shopping cart in a PHP session variable.
*/
if($_GET['action'] == 'load'){
	if($_SESSION['shoppingCart'] == null )
		$_SESSION['shoppingCart'] = json_decode('[]');
		
	//$_SESSION['shoppingCart'] = json_decode('[]'); //use this to reset the session. for debuggin
		
	header('Content-Type: application/json');
	echo json_encode($_SESSION['shoppingCart']);
	
}

if(isset($_POST['shoppingCart'])){
	if($_POST['shoppingCart'] == "EMPTY"){ 
		$_POST['shoppingCart'] = json_decode('[]');	
	}
	
	$_SESSION['shoppingCart'] = $_POST['shoppingCart'];

	//close session file to allow other users to write to it.
	session_write_close();
	
	header('Content-Type: application/json');
	echo json_encode($_SESSION['shoppingCart']);
}
?>