<?php

class Dosen extends Dashboard{
	function addContent(){
		$current_page = isset($_GET['current_page']) ? $_GET['current_page'] : 0;
		$page_entry = 20;
		$start = $current_page * $page_entry;
		$sql_nolimit = $this->setQueryList();
		$sql  = $sql_nolimit . ' LIMIT '.$start.', '.$page_entry;
		$result = $this->displayListDosen($sql);
		$page_str = $this->setPaging($sql_nolimit, $page_entry);
		$content = <<<EOD
			<div id="result">
				$result
			</div>
			<div id=paging>
				$page_str
			</div>
			<br/><br/>
EOD;
		if($_GET['view'] == 'detail'){
			$content = $this->displayDetailDosen($_GET['uid']);
		}
		if($this->privilege == ADM &&  isset($_GET['view']) ){
			if($_GET['view'] == 'add_dosen'){
				$content = $this->addDosen();
			}
			if($_GET['view'] == 'delete_dsn'){
				$uid = $_GET['uid'];
				$res = $this->db->query("SELECT usr_login FROM tbl_users WHERE usr_id='".$_GET['uid']."'");
				$row = $res->fetch();
				if($this->auth->session->get(POST_LOGIN_VAR) != $row['usr_login']){
					$content = $this->deleteDosen($uid);
				}
			}
		}
		if($this->privilege == ADM || $_GET['uid'] == $this->usr_id){
			if(isset($_GET['view']) && $_GET['view'] == 'edit_dosen'){
				$uid = $_GET['uid']; 
				$content = $this->displayFormEditDosen($uid);
			}
		}
		$this->page .= <<<EOD
			<div class="maincontent">
				<div id="content">
					$content
				</div>
			</div>
EOD;
	}
	
	function setQueryList(){
		if($this->privilege == ADM){
			$sql .= <<<EOD
			SELECT
				tbl_users.usr_login,
			 	tbl_users.usr_url_pic,
			 	tbl_dosen.dsn_nip, 
			 	tbl_dosen.dsn_kode,
			 	tbl_users.usr_nama, 
			 	tbl_users.usr_id 
			 FROM 
			 	tbl_users,
			 	tbl_dosen 
			 WHERE 
			 	tbl_users.usr_id = tbl_dosen.usr_id
			 ORDER BY
			 	tbl_users.usr_nama
EOD;
		}
		else{
			$sql .= <<<EOD
			SELECT 
				tbl_users.usr_login,
			 	tbl_users.usr_url_pic,
			 	tbl_dosen.dsn_nip, 
			 	tbl_dosen.dsn_kode,
			 	tbl_users.usr_nama, 
			 	tbl_users.usr_id 
			 FROM 
			 	tbl_users,
			 	tbl_dosen 
			 WHERE 
			 	tbl_users.usr_id = tbl_dosen.usr_id
			 ORDER BY
				tbl_users.usr_nama
EOD;
		}
		return $sql;
	}
	function displayListDosen($sql){
		$display = '';
		$self = $_SERVER['PHP_SELF'];
		$res = $this->db->query($sql);
		$display .= '<h2>Daftar Dosen</h2>';
		if($this->privilege == ADM){
			$display .='<a href="'.$self.'?page=dosen&view=add_dosen"><button> Tambah Dosen</button></a><br/<br/>';
		}
		$display .= '<table class="t"><thead><th>No</th><th>foto</th><th>NIP</th><th>Kode</th><th>Nama</th>';
		if($this->privilege == ADM){
			$display .= '<th colspan="4">Pilihan</th>';
		}
		else{
			$display .= '<th colspan="2">Pilihan</th>';
		}
		$display .= '</thead>';
		$i = $_GET['current_page'] *20 +1;
		while($row = $res->fetch()){
			$display .= '<tr><td>'.$i.'</td><td><img src="'.$self.'?size=30&img='.$row['usr_url_pic'].'"/></td>';
			$display .= '<td>'.$row['dsn_nip'].'</td>';
			$display .= '<td>'.$row['dsn_kode'].'</td>';
			$display .= '<td>'.$row['usr_nama'].'</td>';
			$display .= '<td><a href="'.$self.'?page=dosen&view=detail&uid='.$row['usr_id'].'">';
			$display .= '<img src="img/detail.png" title="detail"/></a></td>';
			if($this->auth->session->get(POST_LOGIN_VAR) != $row['usr_login']){
				$display .= '<td><a onclick="makeMsg('.$row['usr_id'].');return false" href=""';
				$display .= '<img src="img/msg.gif" title="kirim pesan"/></a></td>';
			}
			else{
				$display .= '<td>&nbsp;</td>';
			}
			if($this->privilege == ADM){
				$display .= '<td><a href="'.$self.'?page=dosen&view=edit_dosen&uid='.$row['usr_id'].'">';
				$display .= '<img src="img/edit.png" title="ubah"/></a></td>';
				if($this->auth->session->get(POST_LOGIN_VAR) != $row['usr_login']){
					$display .= '<td><a href="'.$self.'?page=dosen&view=delete_dsn&uid='.$row['usr_id'].'">';
					$display .= '<img src="img/delete.png" title="hapus"/></a></td>';
				}
				else{
					$display .= '<td></td>';
				}
			}
			$display .= '</tr>';
			$i++;
		}
		$display .= '</table>';
		if($this->privilege == ADM){
			$display .='<a href="'.$self.'?page=dosen&view=add_dosen"><button> Tambah Dosen</button></a><br/<br/>';
		}
		return $display;
	}
	function displayDetailDosen($uid){
		$display = '';
		$display .= '<div id="result">';
		if($this->privilege == ADM || $this->usr_id == $uid){
			$display .= '<a href="'.$_SERVER['PHP_SELF'].'?page=dosen&view=edit_dosen&uid='.$uid.'">';
			$display .= '<button>Ubah Profile</button></a><br><br>';
		}
		$display .= '<table border="0">';
		$display .= $this->displayDetailUser($uid);
		$display .= '</table>';
		if($this->privilege == ADM || $this->usr_id == $uid){
			$display .= '<a href="'.$_SERVER['PHP_SELF'].'?page=dosen&view=edit_dosen&uid='.$uid.'">';
			$display .= '<button>Ubah Profile</button></a><br><br>';
		}
		$display .= '<h3>Matakuliah yang di Pegang</h3>';
		$display .= $this->getBebanDosen($uid);
		$display .= '</div>';
		return $display;
	}
	function displayDetailUser($uid){
		$sql = <<<EOD
SELECT 
	usr_id, usr_privilege, 
	(SELECT mhs_nim FROM tbl_mahasiswa WHERE usr_id= $uid) AS NIM,
	(SELECT dsn_nip FROM tbl_dosen WHERE usr_id= $uid) AS NIP,
	(SELECT dsn_kode FROM tbl_dosen WHERE usr_id= $uid) AS KODE, 
	usr_nama, usr_kelamin, usr_login, usr_email, usr_kontak,usr_url_pic,
	usr_last_login,	usr_desc,
	(SELECT addr_lokasi FROM tbl_alamat WHERE addr_jenis='tinggal' AND usr_id= $uid)
	AS 	addr_lokasi_tinggal,
	(SELECT addr_wilayah FROM tbl_alamat WHERE addr_jenis='tinggal' AND usr_id= $uid) 
	AS 	addr_wilayah_tinggal,
	(SELECT addr_provinsi FROM tbl_alamat WHERE addr_jenis='tinggal' AND usr_id= $uid) 
	AS addr_provinsi_tinggal,
	(SELECT addr_kodepos FROM tbl_alamat WHERE addr_jenis='tinggal' AND usr_id= $uid) 
	AS addr_kodepos_tinggal,
	(SELECT addr_lokasi FROM tbl_alamat WHERE addr_jenis='asal' AND usr_id= $uid) 
	AS addr_lokasi_asal,
	(SELECT addr_wilayah FROM tbl_alamat WHERE addr_jenis='asal' AND usr_id= $uid) 
	AS addr_wilayah_asal,
	(SELECT addr_provinsi FROM tbl_alamat WHERE addr_jenis='asal' AND usr_id= $uid) 
	AS addr_provinsi_asal,
	(SELECT addr_kodepos FROM tbl_alamat WHERE addr_jenis='asal' AND usr_id= $uid) 
	AS addr_kodepos_asal
 FROM tbl_users 
 WHERE usr_id= $uid
EOD;
		$display = '';
		$self = $_SERVER['PHP_SELF'];
		$result = $this->db->query($sql);
		$row = $result->fetch();
		$display .= '<tr><th colspan="4">Data Pribadi</th></tr>';
		$display .= '<tr><td>Nama</td><td>:</td><td>'.$row['usr_nama'].'</td>';
		$display .= '<td rowspan="4"><img src="'.$self.'?size=150&img='.$row['usr_url_pic'].'"/></td></tr>';
		if($row['usr_privilege'] == MHS){
			$display .= '<tr><td>NIM</td><td>:</td><td>'.$row['NIM'].'</td></tr>';
		}
		else{
			$display .= '<tr><td>NIP</td><td>:</td><td>'.$row['NIP'].'</td></tr>';
			$display .= '<tr><td>Kode Dosen</td><td>:</td><td>'.$row['KODE'].'</td></tr>';
		}
		$display .= '<tr><td>jenis Kelamin</td><td>:</td><td>'.$row['usr_kelamin'].'</td></tr>';
		$display .= '<tr><td>Email</td><td>:</td><td>'.$row['usr_email'].'</td></tr>';
		$display .= '<tr><td>No.Kontak</td><td>:</td><td colspan="2">'.$row['usr_kontak'].'</td></tr>';
		$display .= '<tr><td>Deskripsi</td><td>:</td><td colspan="2"><p style="margin:0;padding:0">'.$row['usr_desc'].'</p></td></tr>';
		$display .= '<tr><td>Terakhir login</td><td>:</td><td colspan="2">'.$row['usr_last_login'].'</td></tr>';
		$display .= '<tr><td colspan="4"><b>Alamat Tinggal</b></td></tr>';
		$display .= '<tr><td>Lokasi</td><td>:</td><td colspan="2">'.$row['addr_lokasi_tinggal'].'</td></tr>';
		$display .= '<tr><td>Kota / Kabupaten </td><td>:</td><td colspan="2">'.$row['addr_wilayah_tinggal'].'</td></tr>';
		$display .= '<tr><td>Provinsi</td><td>:</td><td colspan="2">'.$row['addr_provinsi_tinggal'].'</td></tr>';
		$display .= '<tr><td>Kode Pos</td><td>:</td><td colspan="2">'.$row['addr_kodepos_tinggal'].'</td></tr>';
		$display .= '<tr><td colspan="4"><b>Alamat Asal</b></td></tr>';
		$display .= '<tr><td>Lokasi</td><td>:</td><td colspan="2">'.$row['addr_lokasi_asal'].'</td></tr>';
		$display .= '<tr><td>Kota / Kabupaten </td><td>:</td><td colspan="2">'.$row['addr_wilayah_asal'].'</td></tr>';
		$display .= '<tr><td>Provinsi</td><td>:</td><td colspan="2">'.$row['addr_provinsi_asal'].'</td></tr>';
		$display .= '<tr><td>Kode Pos</td><td>:</td><td colspan="2">'.$row['addr_kodepos_asal'].'</td></tr>';
		return $display;
	}
	function isValue($var){
		if(isset($var)){
			$var = trim($var);
			if(!empty($var)){
				return true;
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
	}
	function isEmail($var){
		//$res = $this->db->query("SELECT usr_email FROM tbl_users WHERE usr_email='$var'");
		//$size = $res->size();
		if(ereg('^[^@]+@([a-z\-]+\.)+[a-z]{2,6}$',$var)){
			return true;
		}
		else{
			return false;
		}
	}
	function isNIP($nip){
		$sql = "SELECT dsn_nip FROM tbl_dosen WHERE dsn_nip = '$nip'";
		$res = $this->db->query($sql);
		$size = $res->size();
		if($size == 0 && ctype_digit($nip) && $this->isValue($nip)){
			return true;
		}
		else{
			return false;
		}
	}
	function isKode($kode){
		$sql = "SELECT dsn_kode FROM tbl_dosen WHERE dsn_kode = '$kode'";
		$res = $this->db->query($sql);
		$size = $res->size();
		if($size == 0 && ctype_digit($kode) && $this->isValue($kode)){
			return true;
		}
		else{
			return false;
		}
	}
	function isLogin($var,$opt=0){
		$var = strtolower($var);
		$sql = "SELECT usr_login FROM tbl_users WHERE usr_login ='$var'";
		$res = $this->db->query($sql);
		$size = $res->size();
		if($size == $opt && $this->isValue($var)){
			return true;
		}
		else{
			return false;
		}
	}
	function isPassword($var1,$var2){
		if(strlen($var1) >= 6 && $var1 == $var2){
			return true;
		}
		else{
			return false;
		}
	}
	function addDosen(){
		$display = '';
		if(isset($_POST['tambah'])){
			$display .= $this->checkFormDosen($error, $print_again);
		}
		else{
			$display .= $this->displayFormDosen($error, $print_again);
		}
		return $display;
	}
	function displayFormDosen($error, $print_again){
		$display = '';
		$target = $_SERVER['PHP_SELF'].'page=dosen&view=add_dosen';
		$msg= array(
			'usr_login' => '*',
			'usr_password' => '* (>= 6 karakter)',
			'verify' => '*',
			'dsn_nip' => '* (numerik)',
			'dsn_kode' => '* (numerik)',
			'usr_nama' => '*',
			'usr_email' =>'*',
			'foto' =>'format jpeg',
		);
		$fields = array (
			'usr_login' => 'text',
			'usr_password' => 'password',
			'verify' => 'password',
			'dsn_nip' => 'text',
			'dsn_kode' => 'text',
			'usr_nama' => 'text',
			'usr_kelamin' => 'option',
			'usr_email' => 'text',
			'usr_kontak' => 'text',
			'usr_desc' => 'text',
			'foto' => 'file',
			'addr_lokasi_tinggal' => 'text',
			'addr_wilayah_tinggal' => 'text',
			'addr_provinsi_tinggal' => 'text',
			'addr_kodepos_tinggal' => 'text',
			'addr_lokasi_asal' => 'text',
			'addr_wilayah_asal' => 'text',
			'addr_provinsi_asal' => 'text',
			'addr_kodepos_asal' => 'text'
		);
		$labels = array (
			'usr_login' => 'User Login',
			'usr_password' => 'Password',
			'verify' => 'Verifikasi',
			'dsn_nip' => 'NIP',
			'dsn_kode' => 'Kode Dosen',
			'usr_nama' => 'Nama',
			'usr_kelamin' => 'Jenis Kelamin',
			'usr_email' => 'Email',
			'usr_kontak' => 'Kontak',
			'usr_desc' => 'Deskripsi',
			'foto' => 'Foto',
			'addr_lokasi_tinggal' => 'Alamat Tinggal',
			'addr_wilayah_tinggal' => 'Kota / Kabupaten',
			'addr_provinsi_tinggal' => 'Propinsi',
			'addr_kodepos_tinggal' => 'Kodepost',
			'addr_lokasi_asal' => 'Alamat Asal',
			'addr_wilayah_asal' => 'Kota / Kabupaten',
			'addr_provinsi_asal' => 'Propinsi',
			'addr_kodepos_asal' => 'Kodepost'
		);
		$display .= '<h2 style="text-align:center">Tambah Dosen</h2>';
		if($print_again){
			$display .= '<p style="color:#FF0000">Kesalahan Pengisian!! Periksa kembali!</p>';
		}
		$display .= '<form id="form" action="'. $_SERVER['PHP_SELF'].'?page=dosen&view=add_dosen" method="post" enctype="multipart/form-data">';
		$display .= '<table>';
		foreach ($fields as $name => $type){
			if($name == 'usr_kelamin'){
				$display .= '<tr><td>'.$labels[$name].'</td>';
				$display .= '<td><select name="'.$name.'">';
				$display .= '<option value="Perempuan">Perempuan</option>';
				$display .= '<option value="Laki-laki">Laki-laki</option>';
				$display .= '</select></td><td></td></tr>';
			}
			else if($name == 'usr_desc'){
				$display .= '<tr><td>'.$labels[$name].'</td>';
				$display .= '<td><textarea name="'.$name.'">'.@$_POST[$name].'</textarea><td>';
				if($print_again){
					$display .= $this->errorFlag($error,$name,$fields);
				}
				$display .= '</td></tr>';
			}
			else{
				$display .= '<tr><td>'.$labels[$name].'</td>';
				$display .= '<td><input name="'.$name.'" type="'.$type.'" value="'.@$_POST[$name].'"/></td>';
				$display .= '</select></td><td>';
				if($print_again){
					$display .= $this->errorFlag($error,$name,$fields);
				}
				else{
					if(isset($msg[$name])){
						$display .= '<small style="color:#FF0000"><i>'.$msg[$name].'</i></small>';
					}
				}
				$display .= '</td></tr>';
			}
		}
		$display .= '<tr><td>&nbsp;</td><td><input id="submit" type="submit" name="tambah" value="Tambah Dosen"></td><td></td></tr>';
		$display .= '</table></form>';
		return $display;
	}
	
	function checkFormDosen($error, $print_again){
		$print_again = false;
		if(!$this->isValue($_POST['usr_nama'])){
			$error['usr_nama'] = true;
			$print_again = true;
		}
		if(!$this->isValue($_POST['usr_kelamin'])){
			$error['usr_kelamin'] = true;
			$print_again = true;
		}
		if(!$this->isPassword($_POST['usr_password'],$_POST['verify'])){
			$error['usr_password'] = true;
			$error['verify'] = true;
			$print_again = true;
		}
		if(!$this->isEmail($_POST['usr_email'])){
			$error['usr_email'] = true;
			$print_again = true;
		}
		if(!$this->isLogin($_POST['usr_login'])){
			$error['usr_login'] = true;
			$print_again = true;
		}
		if(!$this->isNIP($_POST['dsn_nip'])){
			$error['dsn_nip'] = true;
			$print_again = true;
		}
		if(!$this->isKode($_POST['dsn_kode'])){
			$error['dsn_kode'] = true;
			$print_again = true;
		}
		if($print_again){
			$display = $this->displayFormDosen($error, $print_again);
		}
		else{
			$display = $this->storeDosen();
		}
		return $display;
	}
	
	function errorFlag($error,$name){
		if($error[$name]){
			$display = '<small style="color:#FF0000"><i>Tidak Valid!</i></small>';
			if($name=='usr_login'||$name=='dsn_nip'|| $name=='dsn_kode' ||$name=='mhs_nim' || $name=='usr_email'){
				$display = '<small style="color:#FF0000"><i>Sudah Terpakai atau Tidak Valid! </i></small>';
			}
		}
		else{
			$display = '';
		}
		return $display;
	}
	function storeDosen(){
		$this-> storeDataUser();
		$this->storeAlamat();
		$this->storeDataDosen();
		$msg = <<<EOD
		<div style="text-align:center;margin:20px">
		Data tersimpan!<br/><br/>Kembali ke:
		<a href="$self?page=dosen&view=add_dosen"> Form pengisian </a> |
		<a href="$self?page=dosen"> Daftar Dosen</a>
		</div>
EOD;
		return $msg;
	}
	function storeDataUser(){
		$input = array_map('htmlentities',$_POST);
		$input = array_map('mysql_real_escape_string',$input);
		$sql ="
		INSERT INTO tbl_users(
			usr_login, 
			usr_password, 
			usr_nama, 
			usr_privilege, 
			usr_kelamin, 
			usr_email, 
			usr_kontak, 
			usr_desc
		)
		VALUE(
			'".strtolower($input['usr_login'])."', 
			MD5('".$input['usr_password']."'), 
			'".$input['usr_nama']."', 
			1 ,
			'".$input['usr_kelamin']."', 
			'".$input['usr_email']."', 
			'".$input['usr_kontak']."', 
			'".$input['usr_desc']."'
		)";
		$this->db->query($sql);
		if(isset($_FILES['foto'])){
			$this->storeFoto($input['usr_login']);
		}
	}
	function storeFoto($login){
		$res = $this->db->query("SELECT usr_id FROM tbl_users WHERE usr_login = '$login'");
		$row = $res->fetch();
		if($_FILES['foto']['type']== 'image/jpeg'){
			if(move_uploaded_file($_FILES['foto']['tmp_name'],'img/users/'.$row['usr_id'].'.jpg')){
				$this->db->query("UPDATE tbl_users SET usr_url_pic='img/users/".$row['usr_id'].".jpg' WHERE usr_login='$login'");
			}
		}
	}
	function storeDataDosen(){
		$input = array_map('htmlentities',$_POST);
		$input = array_map('mysql_real_escape_string',$input);
		$sql = "
		INSERT INTO tbl_dosen(dsn_nip, dsn_kode, usr_id)
		VALUE('".$input['dsn_nip']."', '".$input['dsn_kode']."', 
			(SELECT usr_id FROM tbl_users WHERE usr_login='".$input['usr_login']."')
		)";
		$this->db->query($sql);
	}
	function storeAlamat(){
		$input = array_map('htmlentities',$_POST);
		$input = array_map('mysql_real_escape_string',$input);
		$sql1 = "
		INSERT INTO tbl_alamat(
			addr_jenis,
			usr_id,
			addr_lokasi,
			addr_wilayah,
			addr_provinsi,
			addr_kodepos
		)
		VALUE(
			'tinggal',
			(SELECT usr_id FROM tbl_users WHERE usr_login='".$input['usr_login']."'),
			'".$input['addr_lokasi_tinggal']."',
			'".$input['addr_wilayah_tinggal']."',
			'".$input['addr_provinsi_tinggal']."',
			'".$input['addr_kodepos_tinggal']."'
		)
		";
		$sql2 = "
		INSERT INTO tbl_alamat(
			addr_jenis,
			usr_id,
			addr_lokasi,
			addr_wilayah,
			addr_provinsi,
			addr_kodepos
		)
		VALUE(
			'asal',
			(SELECT usr_id FROM tbl_users WHERE usr_login='".$input['usr_login']."'),
			'".$input['addr_lokasi_asal']."',
			'".$input['addr_wilayah_asal']."',
			'".$input['addr_provinsi_asal']."',
			'".$input['addr_kodepos_asal']."'
		)
		";
		$this->db->query($sql1);
		$this->db->query($sql2);
	}
	function deleteDosen($uid){
		if(!isset($_POST['confirm'])){
			$res = $this->db->query("SELECT usr_nama FROM tbl_users WHERE usr_id = '$uid'");
			$row = $res->fetch();
			$nama = $row['usr_nama'];
			$uri = $_SERVER['REQUEST_URI'];
			$display = <<<EOD
				<div style="text-align:center;margin:20px;">
					Anda Yakin akan menghapus Dosen dengan nama <b>$nama<b></b> <br/><br/>
					<form action="$uri"method="post">
						<input type="submit" name="confirm" value="yes"/> &nbsp;
						<input type="submit" name="confirm" value="no"/> &nbsp;
					</form> 
				</div>
EOD;
			return $display;
		}
		else if($_POST['confirm'] == 'yes'){
			$this->db->query("DELETE FROM tbl_users WHERE usr_id='$uid'");
			$this->db->query("DELETE FROM tbl_alamat WHERE usr_id='$uid'");
			$this->db->query("DELETE FROM tbl_pesan WHERE usr_id_asal='$uid'");
			$this->db->query("DELETE FROM tbl_pesan WHERE usr_id_tujuan='$uid'");
			$this->db->query("DELETE FROM tbl_mk_dsn WHERE dsn_id = (SELECT dsn_id FROM tbl_dosen WHERE usr_id='$uid')");
			$this->db->query("DELETE FROM tbl_dosen WHERE usr_id='$uid'");
			header('Location:'.$_SERVER['PHP_SELF'].'?page=dosen');
		}
		else{
			header('Location:'.$_SERVER['PHP_SELF'].'?page=dosen');
		}
	}
	function storeEditUser(){
		$uid = $_GET['uid'];
		$input = array_map('htmlentities',$_POST);
		$input = array_map('mysql_real_escape_string',$input);
		$this->db->query("UPDATE tbl_users SET 
			usr_nama='".$input['usr_nama']."',
			usr_kelamin='".$input['usr_kelamin']."',
			usr_email='".$input['usr_email']."',
			usr_kontak='".$input['usr_kontak']."',
			usr_desc= '".$input['usr_desc']."'
		WHERE usr_id=$uid
		");
		if(isset($_FILES['foto'])){
			if($this->privilege == ADM){
				$this->storeFoto($input['usr_login']);
			}
			else{
				$this->storeFoto($this->login);
			}
		}
	}
	function storeEditPassword(){
		$input = array_map('htmlentities',$_POST);
		$input = array_map('mysql_real_escape_string',$input);
		if($this->privilege == ADM){
			$this->db->query("UPDATE tbl_users SET usr_password=MD5('".$input['usr_password']."') 
			WHERE usr_login ='".$input['usr_login']."'");
		}
		else{
			$this->db->query("UPDATE tbl_users SET usr_password=MD5('".$input['usr_password']."') 
			WHERE usr_login ='$this->login'");
		}
	}
	function storeEditAlamat(){
		$input = array_map('htmlentities',$_POST);
		$input = array_map('mysql_real_escape_string',$input);
		if($this->privilege == ADM){
			$id = $_GET['uid'];
		}
		else{
			$id = $this->usr_id;
		}
		$this->db->query("
		UPDATE tbl_alamat SET
			addr_lokasi ='".$input['addr_lokasi_tinggal']."',
			addr_wilayah ='".$input['addr_wilayah_tinggal']."',
			addr_provinsi = '".$input['addr_provinsi_tinggal']."',
			addr_kodepos = '".$input['addr_kodepos_tinggal']."'
			WHERE addr_jenis = 'tinggal'
			AND usr_id = $id
		");
		$this->db->query("
		UPDATE tbl_alamat SET
			addr_lokasi ='".$input['addr_lokasi_asal']."',
			addr_wilayah ='".$input['addr_wilayah_asal']."',
			addr_provinsi = '".$input['addr_provinsi_asal']."',
			addr_kodepos = '".$input['addr_kodepos_asal']."'
			WHERE addr_jenis = 'asal'
			AND usr_id = $id
		");
	}
	function displayFormEditDosen($uid){
		$labels = array (
			'usr_login' => 'User Login',
			'usr_password' => 'Password',
			'usr_password_verify' => 'Verifikasi',
			'dsn_nip' => 'NIP',
			'dsn_kode' => 'Kode Dosen',
			'usr_nama' => 'Nama',
			'usr_kelamin' => 'Jenis Kelamin',
			'usr_email' => 'Email',
			'usr_kontak' => 'Kontak',
			'usr_desc' => 'Deskripsi',
			'foto' => 'Foto',
			'addr_lokasi_tinggal' => 'Alamat Tinggal',
			'addr_wilayah_tinggal' => 'Kota / Kabupaten Tinggal',
			'addr_provinsi_tinggal' => 'Propinsi Tinggal',
			'addr_kodepos_tinggal' => 'Kodepost Tinggal',
			'addr_lokasi_asal' => 'Alamat Asal',
			'addr_wilayah_asal' => 'Kota / Kabupaten Asal',
			'addr_provinsi_asal' => 'Propinsi Asal',
			'addr_kodepos_asal' => 'Kodepost Asal'
		);
		$display = '';
		$display .= '<h3 align="center">Ubah Profile Dosen</h3>';
		if(isset($_POST['save'])){
			if($_POST['save'] == 'simpan'){
				unset($_POST['usr_password']);
				unset($_POST['usr_password_verify']);
			}
			$msg_error = $this->checkFormEditDosen($labels);
			if($msg_error != ''){
				$display .= '<ul id="msgerror">'.$msg_error.'</ul>';
			}
			else{
				$this->storeEditDosen();
				$display .= '<div id="saveedit">Data Tersimpan!!</div><br><br>';
			}
			$_POST['foto'] = '';
			unset($_POST['usr_password']);
			unset($_POST['usr_password_verify']);
		}
		else{
			$_POST = $this->getValueEdit($uid);
		}
		$read_only = array('dsn_nip','dsn_kode','usr_login');
		$display .= '<form id="form" action="'. $_SERVER['REQUEST_URI'].'"';
		$display .= 'method="post" enctype="multipart/form-data">';
		$display .= '<table>';
		foreach($_POST AS $key => $value){
			if(in_array($key,$read_only)){
				$use = ' readonly="readonly"';
			}
			else{
				$use = '';
			}
			if(isset($labels[$key])){
				if($key == 'usr_kelamin'){
					$display .= '<tr><td>'.$labels[$key] .'</td><td>:</td>';
					$display .= '<td><select name="'.$key.'">';
					$display .= '<option value="'.$value.'">'.$value.'</option>';
					if($value == 'LAKI-LAKI'){
						$display .= '<option value="PEREMPUAN">PEREMPUAN</option>';
					}
					else{
						$display .= '<option value="LAKI-LAKI">LAKI-LAKI</option>';
					}
					$display .= '</select></td>';
				}
				else if($key == 'usr_desc'){
					$display .= '<tr><td>'.$labels[$key] .'</td><td>:</td>';
					$display .= '<td><textarea name="'.$key.'">'.$value.'</textarea></td></tr>';
				}
				else if($key == 'foto'){
					$display .= '<tr><td>'.$labels[$key] .'</td><td>:</td>';
					$display .= '<td><input'.$use.' type="file" name="'.$key.'" value="'.$value.'"></td></tr>';
				}
				else{
					$display .= '<tr><td>'.$labels[$key] .'</td><td>:</td>';
					$display .= '<td><input'.$use.' type="text" name="'.$key.'" value="'.$value.'"></td></tr>';
				}
			}
		}		
		$display .= '<tr><td colspan="3" style="text-align:center"><input type="submit" name="save" value="simpan"></td></tr>';
		$display .= '<tr><td>Password</td><td>:</td>';
		$display .= '<td><input type="password" name="usr_password"></td></tr>';
		$display .= '<tr><td>Verifikasi</td><td>:</td>';
		$display .= '<td><input type="password" name="usr_password_verify"></td></tr>';
		$display .= '<tr><td colspan="3" style="text-align:center"><input type="submit" name="save" value="simpan & ubah password"></td></tr>';
		$display .= '</table>';	
		$display .= '</form>';
		
		return $display;
	}
	function getValueEdit($uid){
		$sql = <<<EOD
SELECT tbl_dosen.dsn_nip,tbl_dosen.dsn_kode,tbl_users.usr_id, tbl_users.usr_privilege,tbl_users.usr_nama, 
	tbl_users.usr_login, tbl_users.usr_email,tbl_users.usr_kontak,tbl_users.usr_kelamin,
	tbl_users.usr_url_pic AS foto, tbl_users.usr_last_login,tbl_users.usr_desc,
	(SELECT addr_lokasi FROM tbl_alamat WHERE addr_jenis='tinggal' AND usr_id= $uid)
	AS 	addr_lokasi_tinggal,
	(SELECT addr_wilayah FROM tbl_alamat WHERE addr_jenis='tinggal' AND usr_id= $uid) 
	AS 	addr_wilayah_tinggal,
	(SELECT addr_provinsi FROM tbl_alamat WHERE addr_jenis='tinggal' AND usr_id= $uid) 
	AS addr_provinsi_tinggal,
	(SELECT addr_kodepos FROM tbl_alamat WHERE addr_jenis='tinggal' AND usr_id= $uid) 
	AS addr_kodepos_tinggal,
	(SELECT addr_lokasi FROM tbl_alamat WHERE addr_jenis='asal' AND usr_id= $uid) 
	AS addr_lokasi_asal,
	(SELECT addr_wilayah FROM tbl_alamat WHERE addr_jenis='asal' AND usr_id= $uid) 
	AS addr_wilayah_asal,
	(SELECT addr_provinsi FROM tbl_alamat WHERE addr_jenis='asal' AND usr_id= $uid) 
	AS addr_provinsi_asal,
	(SELECT addr_kodepos FROM tbl_alamat WHERE addr_jenis='asal' AND usr_id= $uid) 
	AS addr_kodepos_asal
FROM tbl_users,tbl_dosen
WHERE tbl_users.usr_id = tbl_dosen.usr_id
	AND tbl_users.usr_id = $uid
EOD;
		$res = $this->db->query($sql);
		$row = $res->fetch();
		return $row;
	}
	function checkFormEditDosen($fields){
		$display = '';
		$not_req = array('addr_lokasi_asal'=>'',
		'addr_wilayah_asal'=>'','addr_provinsi_asal'=>'','addr_kodepos_asal'=>'');
		if(!$this->isEmail($_POST['usr_email'])){
			$display .= '<li><b>Email</b> Tidak valid</li>';
		}
		foreach ($_POST AS $key => $value){
			if(!$this->isValue($value) && !isset($not_req[$key])){
				$display .= '<li><b>'.$fields[$key].'</b> Harus diisi!</li>';
			}
		}
		if($_POST['save'] == 'simpan & ubah password' && !$this->isPassword($_POST['usr_password'],$_POST['usr_password_verify'])){
			$display .= '<li><b>Password</b> tidak valid atau tidak sama</li>';
		}
		return $display;
	}
	function storeEditDosen(){
		$this->storeEditUser();
		if($_POST['save'] != 'simpan'){
			$this->storeEditPassword();
		}
		$this->storeEditAlamat();
	}
}

?>
