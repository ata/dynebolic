<?php

class Page{
	var $page;
	var $id;
	var $title;
	var $db;
	var $session;
	var $auth;
	var $privilege;
	var $login;
	var $usr_login;
	var $usr_id;
	function Page($id,$title,&$db){
		$this->page = '';
		$this->db = &$db;
		$this->id = $id;
		$this->title = $title;
		$this->addHeader();
		$this->addSidebar();
		$this->addContent();
		$this->addFooter();
		$this->display();
	}
	
	function addHeader(){
		if(isset($_GET['img']) && isset($_GET['size'])){
			$img = $_GET['img'];
			$size = $_GET['size'];
			$thumb = new Thumbnail($img,$size);
			$thumb->getImage();
		}
		$q_prodi = "SELECT set_value FROM tbl_settings WHERE set_option='prodi'";
		$q_univ = "SELECT set_value FROM tbl_settings WHERE set_option='universitas'";
		$q_url_logo = "SELECT set_value FROM tbl_settings WHERE set_option='url_logo'";
		
		$rs_prodi = $this->db->query($q_prodi);
		$rs_univ = $this->db->query($q_univ);
		$rs_url_logo = $this->db->query($q_url_logo);
		
		$r_prodi = $rs_prodi->fetch();
		$r_univ = $rs_univ->fetch();
		$r_url_logo = $rs_url_logo->fetch();
		// set variabel
		$prodi = $r_prodi['set_value'];
		$univ = $r_univ['set_value'];
		$url_logo = $r_url_logo['set_value'];
		session_start();
		if(isset($_SESSION['login_hash'])){
			$usr_login = $_SESSION[POST_LOGIN_VAR];
			$sql = "SELECT usr_nama FROM tbl_users WHERE usr_login ='$usr_login'";
			$res = $this->db->query($sql);
			$row = $res->fetch();
			$status = 'Anda login sebagai <b>'.$row['usr_nama'].'</b> | <b><a href="?action=logout">Log Out</a></b>';
			$sql = "UPDATE ". TABLE_USERS . " SET " . USER_LAST_LOGIN . "=NOW() 
					WHERE ".USER_LOGIN." = '$usr_login'";
			$this->db->query($sql);
		}
		else{
			$status = '';
		}
		$self = $_SERVER['PHP_SELF'];
		$icon = $self.'?size=20&img='.$url_logo;
		$logo = $self.'?size=130&img='.$url_logo;
		$this->page .= <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Sistem Informasi Wali - $this->title </title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="Author" content="Ata" />
	<script language="JavaScript" type="text/javascript" src="inc/func.js"></script>
	<link rel="shortcut icon" href="$icon"/>
	<link rel="stylesheet" href="style.css" type="text/css" />
	<style>
	#$this->id a.$this->id:link, #$this->id a.$this->id:visited{
 		background-color:#FFFFFF;
 		padding:0.6em 1em 1px 1em;
 		border-bottom:none;
	}
	</style>
</head>
<body id="$this->id">
	<div class="wraper">
		<div class="main">
			<div class="header">
				<div id="logo">
					<h2>[ALPHA VERSION]</h2>
					<img src="$logo"/>
					<h3>Sistem Informasi perwalian</h3>
				</div>
				<div id="status">
					<p> $status </p>
				</div>
				<div id="name">
					<h2>$prodi</h2>
					<h3>$univ</h3>
				</div>
			</div>
			
EOD;
	}

	function addSidebar(){
		if(!isset($_GET['from'])){
			$target = 'index.php';
		}
		else{
			$target = $_GET['from'];
		}
		$this->page .=<<<EOD
			<div class="content">
				<div class="sidebar">
					<form action="$target" method="post" id="formlogin">
						username: <br/>
						<input type="text" name="username"/><br/>
						password: <br/>
						<input type="password" name="password"/><br/>
						<input type="submit" value="login"/><br/>
					</form>
				</div>
EOD;

	}
	
	function addContent(){
		$q_content = "SELECT set_value FROM tbl_settings WHERE set_option='sambutan'";
		$rs_content = $this->db->query($q_content);
		$r_content = $rs_content->fetch();
		$content = $r_content['set_value'];
		$this->page .= <<<EOD
<div class="maincontent">
					<div id="content">
						<div id="welcome">
							$content
						</div>
					</div>
				</div>
				
EOD;
	}
	function addFooter(){
		$this->page .= <<<EOD
<div class="footer">
					<p>copyright &copy; kelompok Dyne:Bolic</p>
				</div>
			</div>
		</div>
	</div>
</body>
</html>
EOD;
	}
	function setPaging($sql_nolimit, $page_entry){
		$current_page = isset($_GET['current_page']) ? $_GET['current_page'] : 0;
		$self = $_SERVER['PHP_SELF'];
		$req_uri = $_SERVER['REQUEST_URI'];
		$res = $this->db->query($sql_nolimit);
		$size_res = $res->size();
		$total_page = ceil($size_res / $page_entry);
		
		$page_str = '';
		// jika berada pada halaman ketiga atau lebih
		if ($current_page > 1) {
			$page_str .= '<a href="'.$req_uri.'&current_page=0"> &#60;&#60;pertama </a> ';
		} 
		// jika berada pada halaman kedua atau lebih
		if ($current_page > 0) {
			$previous = $current_page - 1;
			$page_str .= '<a href="'.$req_uri.'&current_page='.$previous.'"> &#60;sebelumnya </a> ';
		}
		// ambil semua no halamn dan jadikan link (kecuali berada pada halaman tsb )
		for ($i = 0; $i < $total_page ; $i++) {
			$current = $i + 1;
			if($i == $current_page){
				$page_str .= '<a>'. $current.'</a>';
			}
			else{
				$page_str .= '<a href="'.$req_uri.'&current_page='.$i.'"> '.$current .' </a> ';
			}
		}
		if ($current_page < ($total_page - 1)) {
			$next = $current_page + 1;
			$page_str .= '<a href="'.$req_uri.'&current_page='.$next.'"> selanjutnya&#62; </a> ';
		}
		if ($current_page < ($total_page - 2)) {
			$last = $total_page - 1;
			$page_str .= '<a href="'.$req_uri.'&current_page='.$last.'"> terakhir&#62;&#62; </a> ';
		}
		return $page_str;
	}
	function display(){
		echo $this->page;
	}
}

?>
