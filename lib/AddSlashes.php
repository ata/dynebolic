<?php

if(get_magic_quotes_gpc()){
	$_GET		= array_map('stripslashes',$_GET);
	$_POST		= array_map('stripslashes',$_POST);
	$_COOKIE	= array_map('stripslashes',$_COOKIE);
}

?>
