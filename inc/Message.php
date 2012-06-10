<?php

class Message extends Dashboard{
	function addHeader(){
		if(isset($_GET['img']) && isset($_GET['size'])){
			$img = $_GET['img'];
			$size = $_GET['size'];
			$thumb = new Thumbnail($img,$size);
			$thumb->getImage();
		}
		$this->page .= <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Sistem Informasi Wali - $this->title </title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="Author" content="Ata" />
	<script language="JavaScript" type="text/javascript" src="inc/func.js"></script>
	<link rel="stylesheet" href="msg.css" type="text/css" />
</head>
EOD;
		$page->page .= 'Halaman Pesan';
	}
	function Message($id,$title,&$db,&$auth){
		$this->page = '';
		$this->id = $id;
		$this->title = $title;
		$this->db = &$db;
		$this->auth =&$auth;
		$this->privilege = $this->auth->getPrivilege();
		$this->login = $this->auth->session->get(POST_LOGIN_VAR);
		$res = $this->db->query("SELECT usr_id FROM tbl_users WHERE usr_login='$this->login'");
		$row = $res->fetch();
		$this->usr_id = $row['usr_id'];
		$this->addHeader();
		$this->addContent();
		$this->addFooter();
		$this->display();
	}
	function addContent(){
		$smile = array(
			':D' => '<img src="img/smilies/big_smile.png" border=0/>',
			'8,' => '<img src="img/smilies/cool.png" border=0/>',
			':/' => '<img src="img/smilies/hmm.png" border=0/>',
			':))' => '<img src="img/smilies/lol.png" border=0/>',
			':C' => '<img src="img/smilies/mad.png" border=0/>',
			':|' => '<img src="img/smilies/neutral.png" border=0/>',
			'8/' => '<img src="img/smilies/roll.png" border=0/>',
			':(' => '<img src="img/smilies/sad.png" border=0/>',
			':)' => '<img src="img/smilies/smile.png" border=0/>',
			':b' => '<img src="img/smilies/tonggue.png" border=0/>',
			';)' => '<img src="img/smilies/wink.png" border=0/>',
			':()' => '<img src="img/smilies/tikes.png" border=0/>',
		);
		$uri = $_SERVER['REQUEST_URI'];
		$self = $_SERVER['PHP_SELF'];
		if(isset($_POST['send'])){
			$uid = $_GET['uid'];
			$pesan = mysql_real_escape_string($_POST['pesan']);
			$pesan = strip_tags($pesan,'<b><i><u>');
			$pesan = str_replace(array_keys($smile),array_values($smile),$pesan);
			$pesan = trim($pesan);
			$this->db->query("INSERT INTO tbl_pesan(usr_id_asal,usr_id_tujuan,pesan_isi)
				VALUE($this->usr_id,$uid,'$pesan')");
			
		}
		if(!isset($_GET['view'])){
			$uid = $_GET['uid'];
			$res = $this->db->query("SELECT usr_nama, usr_url_pic FROM tbl_users WHERE usr_id=$uid");
			$row = $res->fetch();
			$nama = $row['usr_nama'];
			$pic = $row['usr_url_pic'];
			$this->page .= <<<EOD
				<p>
					<table>
						<tr>
							<td rowspan="2"><img id="pic" src="$self?size=100&img=$pic"/></td>
							<td><b>$nama</b></td>
						</tr>
						<tr>
							<td>
							<a href="$uri&view=read#bawah" target="msgbox">Pesan</a> |
							<a href="$uri&view=history" target="msgbox">History</a>
							</td>
						</tr>
					</table>
				</p>
				<iframe 
					name="msgbox" 
					width="100%" 
					height="200px" 
					frameborder="1" 
					src="$uri&view=read#bawah">
				</iframe><br><br>
				<script language="javascript">
				<!--
				function setIcon(icon){
					document.post.pesan.value = document.post.pesan.value + icon;
				}
				-->
				</script>
EOD;
			foreach($smile as $key => $img){
				$this->page .= '<a href="javascript:setIcon(\''.$key.'\')">'.$img.' </a>';
			}
			$this->page .= <<<EOD
				<br>
				<form action="$uri" method="post" name="post">
					<textarea name="pesan"></textarea><br>
					<input type="submit" value="send" name="send">
				</form>
				<p style="text-align:right;font-size:0.8em">copyright &copy; kelompok Dyne:Bolic</p>
EOD;
		}
		else if($_GET['view'] == 'read'){
			$this->chatBoard();
		}
		else if($_GET['view'] == 'history'){
			$this->chatBoard(10000);
		}
	}
	
	function chatBoard($limit = 20){
		$uid = $_GET['uid'];
		$sql = <<<EOD
			SELECT tbl_pesan.pesan_status, tbl_users.usr_nama, tbl_pesan.pesan_isi
			FROM tbl_pesan,tbl_users
			WHERE
				((tbl_pesan.usr_id_asal = $this->usr_id AND tbl_pesan.usr_id_tujuan = $uid )
					OR (tbl_pesan.usr_id_asal = $uid AND tbl_pesan.usr_id_tujuan = $this->usr_id ))
				AND tbl_users.usr_id = tbl_pesan.usr_id_asal
			ORDER by pesan_id DESC LIMIT 0,$limit
EOD;
		$res = $this->db->query($sql);
		$message = array();
		while($row = $res->fetch()){
			$message[] = '<p><b>'.$row['usr_nama'].' : </b><br> '.$row['pesan_isi'].'</p>';
		}
		for($i = count($message) - 1; $i >= 0; $i-- ){
			$this->page .= $message[$i];
		}
		$this->db->query("UPDATE tbl_pesan SET pesan_status = 1 WHERE usr_id_tujuan = $this->usr_id AND usr_id_asal = $uid");
		$this->page .= '<a name="bawah"></a>';
	}
	
	function addFooter(){
		$this->page .= <<<EOD
</body>
</html>
EOD;
	}
}

?>
