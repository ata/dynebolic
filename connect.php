<?php

$host	= 'localhost';
$dbUser	= 'root';
$dbPass	= '';
$dbName = 'si-wali_akhir';

require_once 'lib/MySQL.php';
require_once 'lib/Auth.php';
require_once 'lib/Session.php';
require_once 'lib/AddSlashes.php';

$db = &new MySQL($host,$dbUser,$dbPass,$dbName, true);
?>
