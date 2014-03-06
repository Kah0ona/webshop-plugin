<?php

include_once('sisow.cls5.php');

$sisow = new Sisow("2537637899", "d11fc104368111446e7f8337a803ca59dad376a7");

$res = $sisow->DirectoryRequest($out);
print_r($out);

?>