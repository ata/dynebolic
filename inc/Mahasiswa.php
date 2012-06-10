<?php

class Mahasiswa extends Dosen{
	
	function addContent(){
		$current_page = isset($_GET['current_page']) ? $_GET['current_page'] : 0;
		$page_entry = 20;
		$start = $current_page * $page_entry;
		$sql_nolimit = $this->setQueryList();
		$sql  = $sql_nolimit . ' LIMIT '.$start.', '.$page_entry;
		$result = $this->displayListMhs($sql);
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
			$content = $this->displayDetailMhs($_GET['uid']);
		}
		if($this->privilege == ADM &&  isset($_GET['view']) ){
			if($_GET['view'] == 'add_mhs'){
				$content = $this->addMhs();
			}
			else if($_GET['view'] == 'delete_mhs'){
				$uid = $_GET['uid']; 
				$content = $this->deleteMhs($uid);
			}
		}
		if($this->privilege == ADM || $_GET['uid'] == $this->usr_id){
			if(isset($_GET['view']) && $_GET['view'] == 'edit_mhs'){
				$uid = $_GET['uid']; 
				$content = $this->displayFormEditMhs($uid);
			}
		}
		if($this->privilege != MHS){
			if(isset($_GET['view']) && $_GET['view']=='nilai'){
				$uid = $_GET['uid'];
				$content = $this->viewNilai($uid);
			}
			if(isset($_GET['view']) && $_GET['view']=='history_nilai'){
				$uid = $_GET['uid'];
				$mk_id = $_GET['mk_id'];
				$content = $this->historyNilai($uid,$mk_id);
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
	
	function displayListMhs($sql){
		$display = '';
		$self = $_SERVER['PHP_SELF'];
		$res = $this->db->query($sql);
		$display .= '<h2>Daftar Mahasiswa</h2>';
		if($this->privilege == ADM){
			$display .='<a href="'.$self.'?page=mahasiswa&view=add_mhs"><button> Tambah Mahasiswa </button></a><br/<br/>';
		}
		if($this->privilege != MHS) {
			if(!isset($_GET['view']) || $_GET['view'] == 'mhs'){
				$display .='<br>Tampilkan : Mahasiswa Bimbingan | <a href="'.$self.'?page=mahasiswa&view=all">Semua Mahasiswa</a><br/><br/>';
			}
			else if($_GET['view'] == 'all'){
				$display .='Tampilkan : <a href="'.$self.'?page=mahasiswa&view=mhs">Mahasiswa Bimbingan</a> | Semua Mahasiswa <br/><br/>';
			}
			
		} 
		$display .= '<table class="t">';
		$display .= '<thead><th>No</th><th>foto</th><th>NIM</th><th>Nama</th>';
		if($this->privilege == DSN || $this->privilege == ADM){
			$display .= '<th>IPK</th>';
			$display .= '<th>nilai</th>';
		}
		if($this->privilege == ADM){
			$display .= '<th colspan="4">Pilihan</th>';
		}
		else{
			$display .= '<th colspan="2">Pilihan</th>';
		}
		$display .= '</thead>';
		$i = $_GET['current_page']*20 +1;
		while($row = $res->fetch()){
			$IPK = $this->getIPK($row['usr_id']);
			$display .= '<tr><td>'.$i.'</td><td><img src="'.$self.'?size=30&img='.$row['usr_url_pic'].'"/></td>';
			$display .= '<td>'.$row['mhs_nim'].'</td>';
			$display .= '<td>'.$row['usr_nama'].'</td>';
			if($this->privilege != MHS){
				$display .= '<td>'.$IPK.'</td>';
				$display .= '<td><a href="'.$self.'?page=mahasiswa&view=nilai&uid='.$row['usr_id'].'">';
				$display .= 'lihat nilai</a></td>';
			}
			$display .= '<td><a href="'.$self.'?page=mahasiswa&view=detail&uid='.$row['usr_id'].'">';
			$display .= '<img src="img/detail.png" title="detail"/></a></td>';
			if($this->auth->session->get(POST_LOGIN_VAR) != $row['usr_login']){				
				$display .= '<td><a onclick="makeMsg('.$row['usr_id'].');return false" href=""';
				$display .= '<img src="img/msg.gif" title="kirim pesan"/></a></td>';
			}
			else{
				$display .= '<td>&nbsp;</td>';
			}
			if($this->privilege == ADM){
				$display .= '<td><a href="'.$self.'?page=mahasiswa&view=edit_mhs&uid='.$row['usr_id'].'">';
				$display .= '<img src="img/edit.png" title="ubah"/></a></td>';
				$display .= '<td><a href="'.$self.'?page=mahasiswa&view=delete_mhs&uid='.$row['usr_id'].'">';
				$display .= '<img src="img/delete.png" title="hapus"/></a></td>';
			}
			$display .= '</tr>';
			$i++;
		}
		$display .= '</table>';
		if($this->privilege == ADM){
			$display .='<br/><br/><a href="'.$self.'?page=mahasiswa&view=add_mhs"><button> Tambah Mahasiswa </button></a><br/<br/>';
		}
		return $display;
	}
	function getIPK($uid,$format = 0){
		$sql = <<<EOD
			SELECT
				tbl_matakuliah.mk_nama,
				tbl_matakuliah.mk_sks,
				CASE(MIN(tbl_kontrak.kontrak_nilai)) 
				WHEN 'A' THEN 4 
				WHEN 'B' THEN 3 
				WHEN 'C' THEN 2 
				WHEN 'D' THEN 1 
				WHEN 'E' THEN 0 
				END nilai  
				FROM tbl_kontrak,tbl_matakuliah
			WHERE  
				tbl_kontrak.mhs_id = (SELECT mhs_id FROM tbl_mahasiswa WHERE usr_id = $uid)
				AND tbl_matakuliah.mk_id = tbl_kontrak.mk_id
				AND tbl_kontrak.kontrak_status = 3
				AND tbl_kontrak.kontrak_nilai != 'BL'
			GROUP BY tbl_kontrak.mk_id
EOD;
		$res = $this->db->query($sql);
		$total_sks = 0;
		$total_mutu = 0;
		while ($row = $res->fetch()){
			$mutu = $row['mk_sks'] * $row['nilai'];
			$total_mutu = $total_mutu + $mutu;
			$total_sks = $total_sks + $row['mk_sks'];
		}
		if($total_sks == 0){
			$total_sks = 1;
		}
		$ipk =  $total_mutu/$total_sks;
		if($format == 0){
			$ipk =number_format($ipk,2,',','.'); 
		}
		return $ipk;
	}
	function setQueryList(){
		if($this->privilege != MHS){
			if(!isset($_GET['view']) || $_GET['view'] == 'mhs'){
				$usr_login = $this->auth->session->get(POST_LOGIN_VAR);
				$sql = <<<EOD
				SELECT
					tbl_users.usr_login,
					tbl_users.usr_url_pic,
					tbl_mahasiswa.mhs_nim, 
					tbl_users.usr_nama, 
					tbl_users.usr_id 
				FROM 
					tbl_mahasiswa,
					tbl_users
				WHERE (
					tbl_users.usr_id = tbl_mahasiswa.usr_id
					) 
					AND	(
					tbl_mahasiswa.dsn_id = (
						SELECT 
							dsn_id 
						FROM 
							tbl_dosen,
							tbl_users 
						WHERE (
							tbl_users.usr_id = tbl_dosen.usr_id	AND 
							tbl_users.usr_login = '$usr_login'
						)
					)
					)
				ORDER BY tbl_mahasiswa.mhs_nim
EOD;
			}
			else{
				$sql = <<<EOD
				 SELECT
				 	tbl_users.usr_login,
				 	tbl_users.usr_url_pic, 
				 	tbl_mahasiswa.mhs_nim,
				 	tbl_users.usr_nama,
			 		tbl_users.usr_id 
				 FROM 
			 		tbl_mahasiswa,
			 		tbl_users 
				 WHERE (
			 		tbl_mahasiswa.usr_id = tbl_users.usr_id
			 	)
				 ORDER BY
			 		tbl_mahasiswa.mhs_nim
EOD;
			}
		}
		else if($this->privilege == MHS){
			$sql = <<<EOD
			 SELECT
			 	tbl_users.usr_login,
			 	tbl_users.usr_url_pic, 
			 	tbl_mahasiswa.mhs_nim,
			 	tbl_users.usr_nama,
			 	tbl_users.usr_id 
			 FROM 
			 	tbl_mahasiswa,
			 	tbl_users 
			 WHERE (
			 	tbl_mahasiswa.usr_id = tbl_users.usr_id
			 )
			 ORDER BY
			 	tbl_mahasiswa.mhs_nim
EOD;
		}
		return $sql;
	}
	
	function displayDetailMhs($uid){
		$res = $this->db->query("SELECT usr_login FROM tbl_users WHERE usr_id=$uid");
		$row = $res->fetch();
		$login = $row['usr_login'];
		$display = '';
		$display .= '<h3 align="center">Detail Profile Mahasiswa</h3>';
		$display .= '<div id="result">';
		if($this->privilege == ADM || $this->usr_id == $uid){
			$display .= '<a href="'.$_SERVER['PHP_SELF'].'?page=mahasiswa&view=edit_mhs&uid='.$uid.'">';
			$display .= '<button>Ubah Profile</button></a><br><br>';
		}
		$display .= '<table>';
		$display .= $this->displayDetailUser($uid);
		if($this->privilege != MHS || ($this->privilege == MHS && $this->auth->session->get(POST_LOGIN_VAR) == $login)){
			$display .= $this->dataOrtu($uid);
			$display .= $this->dataBeasiswa($uid);
			if(isset($_GET['action']) && $_GET['action'] == 'tambahbeasiswa'){
				$display = '<table>';
				$display .= $this->tambahBeasiswa($uid);
			}
		}
		$display .= '</table><br><br>';
		if($this->privilege == ADM || $this->usr_id == $uid){
			$display .= '<a href="'.$_SERVER['PHP_SELF'].'?page=mahasiswa&view=edit_mhs&uid='.$uid.'">';
			$display .= '<button>Ubah Profile</button></a>';
		}
		$display .= '</div>';
		return $display;
	}
	function tambahBeasiswa($uid){
		if(!isset($_POST['add'])){
			$display = '';
			$display .= '<h3 align="center">Tambahkan beasiswa</h3>';
			$display .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="post">';
			$display .= '<tr><td>Nama Beasiswa</td><td>:</td>';
			$display .= '<td colspan="2"><input type="text" name="bea_nama"/></td></tr>';
			$display .= '<tr><td>Nominal</td><td>:</td>';
			$display .= '<td colspan="2"><input type="text" name="bea_nominal"/></td></tr>';
			$display .= '<tr><td>Tenggang</td><td>:</td>';
			$display .= '<td colspan="2"><input type="text" name="bea_tenggang"/></td></tr>';
			$display .= '<tr><td align="center" colspan="4"><input type="submit" name="add" value="Tambah Beasiswa"></td></tr>';
			$display .='</form>';
		}
		else{
			$input = array_map('htmlentities',$_POST);
			$input = array_map('mysql_real_escape_string',$input);
			$sql = "INSERT INTO tbl_beasiswa(mhs_id, bea_nama, bea_nominal, bea_tenggang)
				VALUE(
					(SELECT mhs_id FROM tbl_mahasiswa WHERE usr_id=$uid),
					'".$input['bea_nama']."',
					'".$input['bea_nominal']."',
					'".$input['bea_tenggang']."'
				)";
			$this->db->query($sql);
			header('Location:'.$_SERVER['PHP_SELF'].'?page=mahasiswa&view=detail&uid='.$uid);
		}
		return $display;
	}
	function dataOrtu($uid){
		$res = $this->db->query("SELECT * FROM tbl_mahasiswa WHERE usr_id = $uid");
		$display = '';
		$display .= '<tr><th colspan=4>Data Orangtua</th></tr>';
		$row = $res->fetch();
		$display .= '<tr><td>Nama Ayah</td><td>:</td><td colspan="2">'.$row['ortu_ayah'].'</td></tr>';
		$display .= '<tr><td>Pekerjaan Ayah</td><td>:</td><td colspan="2">'.$row['ortu_job_ayah'].'</td></tr>';
		$display .= '<tr><td>Nama Ibu</td><td>:</td><td colspan="2">'.$row['ortu_ibu'].'</td></tr>';
		$display .= '<tr><td>Pekerjaan Ibu</td><td>:</td><td colspan="2">'.$row['ortu_job_ibu'].'</td></tr>';
		$display .= '<tr><td>Total Penghasilan</td><td>:</td><td colspan="2">'.$row['ortu_penghasilan'].'</td></tr>';
		$display .= '<tr><td>Email Orangtua</td><td>:</td><td colspan="2">'.$row['ortu_email'].'</td></tr>';
		$display .= '<tr><td>Kontak Orang tua</td><td>:</td><td colspan="2">'.$row['ortu_kontak'].'</td></tr>';
		
		return $display;
	}
	function dataBeasiswa($uid){
		if(isset($_POST['hapus'])){
			$this->db->query("DELETE FROM tbl_beasiswa WHERE bea_id=".$_POST['bea_id']);
		}
		$res = $this->db->query("SELECT * FROM tbl_beasiswa WHERE mhs_id=(SELECT mhs_id FROM tbl_mahasiswa WHERE usr_id=$uid)");
		$display = '';
		$display .= '<tr><th colspan=4>Beasiswa yang diterima</th></tr>';
		if($res->size() == 0){
			$display .= '<tr><td colspan="4"><small>Mahasiswa ini tidak memiliki beasiswa</small></td></tr>';
		}
		else if($res->size() == 1){
			$row = $res->fetch();
			$jlh = number_format($row['bea_nominal'],2,',','.');
			$display .= '<tr><td>Nama Besiswa</td><td>:</td><td colspan="2">'.$row['bea_nama'].'</td></tr>';
			$display .= '<tr><td>Nominal Besiswa</td><td>:</td><td colspan="2">Rp. '.$jlh.'</td></tr>';
			$display .= '<tr><td>Tenggang Pemberian</td><td>:</td><td colspan="2">'.$row['bea_tenggang'].'</td></tr>';
			$display .= '<tr><td></td><td></td><td colspan="2">';
			$display .= '<form method="post" action="'.$_SERVER['REQUEST_URI'].'">';
			$display .= '<input type="hidden" name="bea_id" value="'.$row['bea_id'].'">';
			$display .= '<input type="submit" name="hapus" value="hapus"></form>';
			$display .= '</a></td></tr>';
		}
		else{
			$i = 1;
			while ($row = $res->fetch()) {
				$jlh = number_format($row['bea_nominal'],2,',','.');
				$display .= '<tr><td colspan="4"><b>Besiswa ke-'.$i.'</b></td></tr>';
				$display .= '<tr><td id="beasiswa">Nama Besiswa</td><td>:</td><td colspan="2">'.$row['bea_nama'].'</td></tr>';
				$display .= '<tr><td>Nominal Besiswa</td><td>:</td><td colspan="2">Rp. '.$jlh.'</td></tr>';
				$display .= '<tr><td>Tenggang Pemberian</td><td>:</td><td colspan="2">'.$row['bea_tenggang'].'</td></tr>';
				$display .= '<tr><td></td><td></td><td colspan="2">';
				$display .= '<form method="post" action="'.$_SERVER['REQUEST_URI'].'">';
				$display .= '<input type="hidden" name="bea_id" value="'.$row['bea_id'].'">';
				$display .= '<input type="submit" name="hapus" value="hapus"></form>';
				$display .= '</a></td></tr>';
			}
		}
		$display .= '<tr><td colspan="4" style="text-align:center"><a href="'.$_SERVER['REQUEST_URI'].'&action=tambahbeasiswa"><button>Tambah Beasiswa</button></a></td></tr>';
		return $display;
	}
	function addMhs(){
		$display = '';
		if(isset($_POST['tambah'])){
			$display .= $this->checkFormMhs($error, $print_again);
		}
		else{
			$display .= $this->displayFormMhs($error, $print_again);
		}
		return $display;
	}
	function displayFormMhs($error, $print_again){
		$display = '';
		$target = $_SERVER['PHP_SELF'].'page=dosen&view=add_dosen';
		$msg= array(
			'usr_login' => '*',
			'usr_password' => '* (>= 6 karakter)',
			'verify' => '*',
			'mhs_nim' => '* (numerik)',
			'usr_nama' => '*',
			'usr_kelamin' => '*',
			'usr_email' =>'*',
			'foto' =>'harus jpeg',
		);
		$fields = array (
			'usr_login' => 'text',
			'usr_password' => 'password',
			'verify' => 'password',
			'mhs_nim' => 'text',
			'mhs_angkatan' => 'option',
			'dsn_id' => 'option',
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
			'addr_kodepos_asal' => 'text',
			'ortu_ayah' => 'text',
			'ortu_ibu' => 'text',
			'ortu_job_ayah' => 'text',
			'ortu_job_ibu' => 'text',
			'ortu_penghasilan' => 'option',
			'ortu_kontak' => 'text',
			'ortu_email' => 'text'
		);
		$labels = array (
			'usr_login' => 'User Login',
			'usr_password' => 'Password',
			'verify' => 'Verifikasi',
			'mhs_nim' => 'NIM',
			'mhs_angkatan' => 'Angkatan',
			'dsn_id' => 'Pembimbing Akademik',
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
			'addr_kodepos_asal' => 'Kodepost',
			'ortu_ayah' => 'Nama Ayah',
			'ortu_ibu' => 'Nama Ibu',
			'ortu_job_ayah' => 'Pakerjaan Ayah',
			'ortu_job_ibu' => 'Pekerjaan Ibu',
			'ortu_penghasilan' => 'Total Penghasilan',
			'ortu_kontak' => 'No. Kontak',
			'ortu_email' => 'Email Orangtua'
		);
		$display .= '<h2 style="text-align:center">Tambah Mahasiswa</h2>';
		if($print_again){
			$display .= '<p style="color:#FF0000">Kesalahan Pengisian!! Periksa kembali!</p>';
		}
		$display .= '<form id="form" action="'. $_SERVER['PHP_SELF'].'?page=mahasiswa&view=add_mhs" method="post" enctype="multipart/form-data">';
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
			else if($name == 'dsn_id'){
				$res = $this->db->query("SELECT tbl_dosen.dsn_id,tbl_users.usr_nama FROM tbl_users,tbl_dosen WHERE tbl_users.usr_id = tbl_dosen.usr_id");
				$display .= '<tr><td>'.$labels[$name].'</td>';
				$display .= '<td><select name="'.$name.'">';
				while($row = $res->fetch()){
					$display .= '<option value="'.$row['dsn_id'].'">'.$row['usr_nama'].'</option>';
				}
				$display .= '</select></td><td></td></tr>';
			}
			else if($name == 'mhs_angkatan'){
				$display .= '<tr><td>'.$labels[$name].'</td>';
				$display .= '<td><select name="'.$name.'">';
				for($i=2005;$i<=2010;$i++){
					$display .= '<option value="'.$i.'">'.$i.'</option>';
				}
				$display .= '</select></td><td></td></tr>';
			}
			else if($name == 'ortu_penghasilan'){
				$display .= '<tr><td>'.$labels[$name].'</td>';
				$display .= '<td><select name="'.$name.'">';
				$display .= '<option value="kurang dari 2 juta">kurang dari 2 juta</option>';
				for($i=2;$i<20;$i+=2){
					$j= $i+2;
					$display .= '<option value="'.$i.' juta s/d '.$j.' juta">'.$i.' juta s/d '.$j.' juta</option>';
				}
				$display .= '<option value="lebih dari 20 juta">lebih dari 20 juta</option>';
				$display .= '</select></td><td></td></tr>';
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
		$display .= '<tr><td>&nbsp;</td><td><input id="submit" type="submit" name="tambah" value="Tambah Mahasiswa"></td><td></td></tr>';
		$display .= '</table></form>';
		return $display;
	}
	function checkFormMhs($error, $print_again){
		$print_again = false;
		if(!$this->isValue($_POST['usr_nama'])){
			$error['usr_nama'] = true;
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
		if(!$this->isNIM($_POST['mhs_nim'])){
			$error['mhs_nim'] = true;
			$print_again = true;
		}
		if($print_again){
			$display = $this->displayFormMhs($error, $print_again);
		}
		else{
			$display = $this->storeMhs();
		}
		return $display;
	}
	function deleteMhs($uid){
		if(!isset($_POST['confirm'])){
			$res = $this->db->query("SELECT usr_nama FROM tbl_users WHERE usr_id = '$uid'");
			$row = $res->fetch();
			$nama = $row['usr_nama'];
			$uri = $_SERVER['REQUEST_URI'];
			$display = <<<EOD
				<div style="text-align:center;margin:20px;">
					Anda Yakin akan menghapus Mahasiswa dengan nama <b>$nama<b></b> <br/><br/>
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
			$this->db->query("DELETE FROM tbl_users WHERE usr_id='$uid'");
			$this->db->query("DELETE FROM tbl_alamat WHERE usr_id='$uid'");
			$this->db->query("DELETE FROM tbl_pesan WHERE usr_id_asal='$uid'");
			$this->db->query("DELETE FROM tbl_pesan WHERE usr_id_tujuan='$uid'");
			$this->db->query("DELETE FROM tbl_kontrak WHERE mhs_id=(SELECT mhs_id FROM tbl_mahasiswa WHERE usr_id='$uid')");
			$this->db->query("DELETE FROM tbl_mahasiswa WHERE usr_id='$uid'");
			header('Location:'.$_SERVER['PHP_SELF'].'?page=mahasiswa');
		}
		else{
			header('Location:'.$_SERVER['PHP_SELF'].'?page=mahasiswa');
		}
	}
	function storeMhs(){
		$this-> storeDataUser();
		$this->storeAlamat();
		$this->storeDataMhs();
		$self = $_SERVER['PHP_SELF'];
		$msg = <<<EOD
		<div style="text-align:center;margin:20px">
		Data tersimpan!<br/><br/>Kembali ke:
		<a href="$self?page=mahasiswa&view=add_mhs"> Form pengisian </a> |
		<a href="$self?page=mahasiswa"> Daftar Mahasiswa</a>
		</div>
EOD;
		return $msg;
	}
	function storeDataMhs(){
		$input = array_map('htmlentities',$_POST);
		$input = array_map('mysql_real_escape_string',$input);
		$sql = "
		INSERT INTO tbl_mahasiswa(
			usr_id,
			mhs_nim,
			dsn_id,
			mhs_angkatan,
			ortu_ayah,
			ortu_ibu,
			ortu_job_ayah,
			ortu_job_ibu,
			ortu_penghasilan,
			ortu_kontak,
			ortu_email
		)
		VALUE(
			(SELECT usr_id FROM tbl_users WHERE usr_login='".$input['usr_login']."'),
			'".$input['mhs_nim']."',
			'".$input['dsn_id']."',
			'".$input['mhs_angkatan']."',
			'".$input['ortu_ayah']."',
			'".$input['ortu_ibu']."',
			'".$input['ortu_job_ayah']."',
			'".$input['ortu_job_ibu']."',
			'".$input['ortu_penghasilan']."',
			'".$input['ortu_kontak']."',
			'".$input['ortu_email']."'
		)";
		$this->db->query($sql);
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
			0 ,
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
	function isNIM($nim,$opt=0){
		$sql = "SELECT mhs_nim FROM tbl_mahasiswa WHERE mhs_nim = '$nim'";
		$res = $this->db->query($sql);
		$size = $res->size();
		if($size == $opt && ctype_digit($nim)  && $this->isValue($nim)){
			return true;
		}
		else{
			return false;
		}
	}
	function viewNilai($uid){
		$display = '';
		$res = $this->db->query(" SELECT tbl_users.usr_nama,tbl_mahasiswa.mhs_semester FROM tbl_users,tbl_mahasiswa WHERE tbl_users.usr_id= tbl_mahasiswa.usr_id AND tbl_users.usr_id=$uid");
		$row = $res->fetch();
		$display .= '<h3 style="text-align:center">Transkrip nilai '.$row['usr_nama'].'</h3>';
		$display .= '<h4 style="text-align:center">IPK '.$this->getIPK($uid).'</h4>';
		$display .= '<table border="0">';
		for($i=1;$i<=$row['mhs_semester'];$i++){
			$display .= $this->nilaiSemester($i,$uid);
		}
		$display .= '</table>';
		$display .= '<a href="'.$_SERVER['HTTP_REFERER'].'">&lt;&lt;kembali</a>';
		return $display;
	}
	function nilaiSemester($semester,$uid){
		$semester_string = array(1 => 'I',2 => 'II',3 => 'III',	4 => 'IV',5 =>'V',6=>'VI',
		7 => 'VII',8 => 'VIII', 9 => 'IX',10 => 'X',11=>'XI',12 => 'XII',
		13 => 'XIII',14 => 'XIV',15 => 'XV', 16 => 'XIV'
		); 
		$sql= <<<EOD
		SELECT
			tbl_kontrak.kontrak_semester,
			tbl_kontrak.mk_id,
			tbl_kontrak.kontrak_status,
			tbl_matakuliah.mk_kode,
			tbl_matakuliah.mk_jenis,
			tbl_matakuliah.mk_nama,
			tbl_matakuliah.mk_sks,
			MIN(tbl_kontrak.kontrak_nilai) AS nilai,
			MAX(CASE tbl_kontrak.kontrak_nilai
				WHEN 'A' THEN 4
				WHEN 'B' THEN 3
				WHEN 'C' THEN 2
				WHEN 'D' THEN 1
				WHEN 'E' THEN 0
				WHEN 'BL' THEN 0
			END) 
			AS beban
		FROM 
			tbl_kontrak,
			tbl_matakuliah
		WHERE 
			tbl_kontrak.mhs_id = (
				SELECT mhs_id FROM tbl_mahasiswa WHERE usr_id= $uid
			)
			AND tbl_kontrak.kontrak_status >=2
			AND tbl_kontrak.mk_id = tbl_matakuliah.mk_id
		GROUP BY tbl_kontrak.mk_id
EOD;
		$jml_mk = 0;
		$total_mutu=0;
		$sks_kontrak = 0;
		$sks_bl = 0;
		$self = $_SERVER['PHP_SELF'];
		$result = $this->db->query($sql);
		$display = '';
		$display .= '<tr><td colspan="6"> <b>Semester '.$semester_string[$semester].'</b></td></tr>';
		$display .= '<tr><th>Kode</th><th>Nama Matakuliah</th><th>SKS</th><th>Nilai</th><th>Mutu</th><th>History</th></tr>';
		while($row = $result->fetch()){
			if($row['kontrak_semester']==$semester){
				$mutu = $row['beban'] * $row['mk_sks'];
				$display .= '<tr><td>'.$row['mk_kode'].'</td>';
				$display .= '<td>'.$row['mk_nama'].'</td>';
				$display .= '<td>'.$row['mk_sks'].'</td>';
				$display .= '<td>'.$row['nilai'].'</td>';
				$display .= '<td>'.$mutu.'</td>';
				if($this->privilege == MHS){
					$display .= '<td><a href="'.$self.'?page=profile&view=history_nilai&uid='.$uid.'&mk_id='.$row['mk_id'].'">history</a></td></tr>';
				}
				else{
					$display .= '<td><a href="'.$self.'?page=mahasiswa&view=history_nilai&uid='.$uid.'&mk_id='.$row['mk_id'].'">history</a></td></tr>';
				}
				$total_mutu = $total_mutu + $mutu;
				if($row['kontrak_status'] == 2 || $row['nilai'] == 'BL'){
					$sks_bl = $sks_bl + $row['mk_sks'];
				}
				$sks_kontrak = $sks_kontrak + $row['mk_sks'];
			}
		}
		$sks_selesai = $sks_kontrak - $sks_bl;
		if($sks_selesai == 0){
			$sks_tmp =1;
		}
		else{
			$sks_tmp = $sks_selesai;
		}
		$ip = $total_mutu / $sks_tmp;
		$ip = number_format($ip,2,',','.');
		$display .= '<tr><td colspan="6" style="text-align:right"><b>';
		$display .= ' SKS KONTRAK : '.$sks_kontrak.'&nbsp; &nbsp;&nbsp; &nbsp;';
		$display .= ' SKS SELESAI : '.$sks_selesai.'&nbsp; &nbsp;&nbsp; &nbsp;';
		$display .= ' MUTU : '.$total_mutu.'&nbsp; &nbsp; &nbsp; &nbsp;';
		$display .= ' IP : '.$ip;
		$display .= '</b><br><br></td></tr>';
		return $display;
	}
	function historyNilai($uid,$mk_id){
		$semester_string = array(1 => 'I',2 => 'II',3 => 'III',	4 => 'IV',5 =>'V',6=>'VI',
		7 => 'VII',8 => 'VIII', 9 => 'IX',10 => 'X',11=>'XI',12 => 'XII',
		13 => 'XIII',14 => 'XIV',15 => 'XV', 16 => 'XIV'
		); 
		$res_nama = $this->db->query("SELECT usr_nama FROM tbl_users WHERE usr_id = $uid");
		$res_mk = $this->db->query("SELECT mk_nama FROM tbl_matakuliah WHERE mk_id = $mk_id");
		$row_nama = $res_nama->fetch();
		$row_mk = $res_mk->fetch();
		$sql = <<<EOD
		SELECT 
		 	tbl_kontrak.kontrak_semester,
		 	tbl_matakuliah.mk_kode,
			tbl_matakuliah.mk_jenis,
			tbl_kontrak.kontrak_nilai,
			tbl_users.usr_nama
		FROM
			tbl_kontrak,
			tbl_dosen,
			tbl_matakuliah,
			tbl_mahasiswa,
			tbl_users 
		WHERE
			tbl_kontrak.mhs_id = (SELECT mhs_id FROM tbl_mahasiswa WHERE usr_id = $uid)
			AND tbl_kontrak.mk_id = $mk_id
			AND tbl_kontrak.mk_id = tbl_matakuliah.mk_id
			AND tbl_kontrak.mhs_id = tbl_mahasiswa.mhs_id
			AND tbl_kontrak.dsn_id = tbl_dosen.dsn_id
			AND tbl_dosen.usr_id =tbl_users.usr_id
EOD;
		$res = $this->db->query($sql);
		$display = '';
		$display = '<h3 align="center">History Nilai / Kontrak <br> '.$row_nama['usr_nama'].' <br>pada matakuliah <br>'.$row_mk['mk_nama'].'</h3>';
		$display .= '<table><thead><th>Di Kontrak pada</th>';
		$display .= '<th>Nilai</th><th>Dosen</th></thead>';
		while($row = $res->fetch()){
			$display .= '<tr>';
			$display .= '<td> Semester '.$semester_string[$row['kontrak_semester']].'</td>';
			$display .= '<td>'.$row['kontrak_nilai'].'</td>';
			$display .= '<td>'.$row['usr_nama'].'</td>';
			$display .= '</tr>';
		}
		$display .= '</table>';
		$self = $_GET['PHP_SELF'];
		if($this->privilege != MHS){
			$display .= <<<EOD
			<div style="text-align:center;margin:20px"><br/><br/>Kembali ke:
			<a href="$self?page=mahasiswa&view=nilai&uid=$uid"> Transkrip Nilai </a> |
			<a href="$self?page=mahasiswa"> Daftar Mahasiswa</a>
			</div>
EOD;
		}
		else{
			$display .= '<a href="'.$_SERVER['HTTP_REFERER'].'">&lt;&lt;kembali</a>';
		}
		return $display;
	}
	function displayFormEditMhs($uid){
		$labels = array (
			'usr_login' => 'User Login',
			'usr_password' => 'Password',
			'usr_password_verify' => 'Verifikasi',
			'mhs_nim' => 'NIM',
			'mhs_angkatan' => 'Angkatan',
			'dsn_id' => 'Pembimbing Akademik',
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
			'addr_kodepos_asal' => 'Kodepost Asal',
			'ortu_ayah' => 'Nama Ayah',
			'ortu_ibu' => 'Nama Ibu',
			'ortu_job_ayah' => 'Pakerjaan Ayah',
			'ortu_job_ibu' => 'Pekerjaan Ibu',
			'ortu_penghasilan' => 'Total Penghasilan',
			'ortu_kontak' => 'No. Kontak',
			'ortu_email' => 'Email Orangtua'
		);
		$display = '';
		$display .= '<h3 align="center">Ubah Profile Mahasiswa</h3>';
		if(isset($_POST['save'])){
			if($_POST['save'] == 'simpan'){
				unset($_POST['usr_password']);
				unset($_POST['usr_password_verify']);
			}
			$msg_error = $this->checkFormEditMhs($labels);
			if($msg_error != ''){
				$display .= '<ul id="msgerror">'.$msg_error.'</ul>';
			}
			else{
				$this->storeEditMhs();
				$display .= '<div id="saveedit">Data Tersimpan!!</div><br><br>';
			}
			$_POST['foto'] = '';
			unset($_POST['usr_password']);
			unset($_POST['usr_password_verify']);
		}
		else{
			$_POST = $this->getValueEdit($uid);
		}
		$read_only = array('mhs_nim','mhs_angkatan','usr_login');
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
				if($key == 'dsn_id'){
					$res = $this->db->query("SELECT tbl_dosen.dsn_id,tbl_users.usr_nama
						FROM tbl_users,tbl_dosen
						WHERE tbl_users.usr_id = tbl_dosen.usr_id
					");
					$display .= '<tr><td>'.$labels[$key] .'</td><td>:</td>';
					if($this->privilege != ADM){
						$display .= '<td><select disabled="disabled" name="'.$key.'">';
					}
					else{
						$display .= '<td><select name="'.$key.'">';
					}
					while($row = $res->fetch()){
						if($row['dsn_id'] == $value){
							$display .= '<option selected="selected" value="'.$row['dsn_id'].'">'.$row['usr_nama'].'</option>';
						}
						else{
							$display .= '<option value="'.$row['dsn_id'].'">'.$row['usr_nama'].'</option>';
						}
					}
					$display .= '</select></td>';
				}
				else if($key == 'usr_kelamin'){
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
				else if($key == 'ortu_penghasilan'){
					$display .= '<tr><td>'.$labels[$key] .'</td><td>:</td>';
					$display .= '<td><select name="'.$key.'">';
					$display .= '<option value="kurang dari 2 juta">kurang dari 2 juta</option>';
					for($i=2;$i<20;$i+=2){
						$j= $i+2;
						$display .= '<option value="'.$i.' juta s/d '.$j.' juta">'.$i;
						$display .= ' juta s/d '.$j.' juta</option>';
					}
					$display .= '<option value="lebih dari 20 juta">lebih dari 20 juta</option>';
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
SELECT tbl_users.usr_id, tbl_mahasiswa.mhs_nim,tbl_users.usr_privilege,
	tbl_users.usr_login,tbl_users.usr_nama,
	tbl_users.usr_kelamin, tbl_users.usr_email, 
	tbl_users.usr_kontak, tbl_users.usr_url_pic AS foto, tbl_users.usr_desc,
	tbl_mahasiswa.mhs_id, tbl_mahasiswa.dsn_id,
	tbl_mahasiswa.mhs_angkatan,tbl_mahasiswa.mhs_semester, tbl_mahasiswa.ortu_ayah,
	tbl_mahasiswa.ortu_ibu, tbl_mahasiswa.ortu_job_ayah, tbl_mahasiswa.ortu_job_ibu,
	tbl_mahasiswa.ortu_penghasilan, tbl_mahasiswa.ortu_kontak, tbl_mahasiswa.ortu_email,
	(SELECT addr_lokasi FROM tbl_alamat WHERE addr_jenis='tinggal' AND usr_id= $uid)
	AS addr_lokasi_tinggal,
	(SELECT addr_wilayah FROM tbl_alamat WHERE addr_jenis='tinggal' AND usr_id= $uid) 
	AS addr_wilayah_tinggal,
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
FROM tbl_users,tbl_mahasiswa
WHERE 
	tbl_users.usr_id = tbl_mahasiswa.usr_id
	AND tbl_users.usr_id = $uid

EOD;
		$res = $this->db->query($sql);
		$row = $res->fetch();
		return $row;
	}
	function checkFormEditMhs($fields){
		$display = '';
		$not_req = array('ortu_email'=>'','addr_lokasi_asal'=>'',
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
	function storeEditMhs(){
		$this->storeEditUser();
		if($_POST['save'] != 'simpan'){
			$this->storeEditPassword();
		}
		$this->storeEditAlamat();
		$this->storeEditDataMhs();
	}
	function storeEditDataMhs(){
		$input = array_map('htmlentities',$_POST);
		$input = array_map('mysql_real_escape_string',$input);
		if($this->privilege == ADM){
			$id = $_GET['uid'];
		}
		else{
			$id = $this->usr_id;
		}
		$this->db->query("UPDATE tbl_mahasiswa SET
		ortu_ayah='".$input['ortu_ayah']."',
		ortu_ibu='".$input['ortu_ibu']."',
		ortu_job_ayah='".$input['ortu_job_ayah']."',
		ortu_job_ibu='".$input['ortu_job_ibu']."',
		ortu_penghasilan='".$input['ortu_penghasilan']."',
		ortu_kontak='".$input['ortu_kontak']."',
		ortu_email='".$input['ortu_email']."'
		WHERE usr_id = $id
		");
		if($this->privilege == ADM){
			$this->db->query("UPDATE tbl_mahasiswa SET dsn_id = ".$input['dsn_id']." WHERE usr_id=$id");
		}
	}
}
