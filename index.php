<?php

require 'connect.php';
require 'lib/Thumbnail.php';
require 'inc/Page.php';
require 'inc/Dashboard.php';
require 'inc/Message.php';
require 'inc/Dosen.php';
require 'inc/Mahasiswa.php';
require 'inc/Matakuliah.php';
require 'inc/Profile.php';
require 'inc/Settings.php';
require 'inc/NotFound.php';
$auth = &new Auth($db,'login.php','grandturismo');
if(isset($_GET['action']) && $_GET['action'] == 'logout'){
	$auth->logout();
}
if(!isset($_GET['page'])){
	$page = new Dashboard('dashboard','Dashboard Page',$db,$auth);
}
else{
	switch($_GET['page']){
		case 'dashboard': $page = new Dashboard('dashboard','Dashboard Page',$db,$auth);break;
		case 'matakuliah': $page = new Matakuliah('matakuliah','Data matakuliah',$db,$auth);break;
		case 'mahasiswa': $page = new Mahasiswa('mahasiswa','Data Mahasiswa',$db,$auth);break;
		case 'dosen': $page = new Dosen('dosen','Data Dosen',$db,$auth);break;
		case 'profile': $page = new Profile('profile','halaman Profile',$db,$auth);break;
		case 'settings': $page = new Settings('settings','halaman Setting',$db,$auth);break;
		case 'message': $pgae = new Message('message','Pesan',$db,$auth);break;
		case 'notfound':$page = new NotFound('notfound','Not Found',$db,$auth);break;
		default:$page = new NotFound('notfound','Not Found',$db,$auth);break;
	}
}
?>
