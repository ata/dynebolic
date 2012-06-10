<?php

@define('MHS',0);
@define('DSN',1);
@define('ADM',2);

class Dashboard extends Page{
	function Dashboard($id,$title,&$db,&$auth){
		$this->page = '';
		$this->id = $id;
		$this->title = $title;
		$this->db = &$db;
		$this->auth =&$auth;
		$this->privilege = $this->auth->getPrivilege();
		$this->login = $this->auth->session->get(POST_LOGIN_VAR);
		$this->usr_login = $this->login;
		$res = $this->db->query("SELECT usr_id FROM tbl_users WHERE usr_login='$this->login'");
		$row = $res->fetch();
		$this->usr_id = $row['usr_id'];
		$this->addHeader();
		$this->addMenu();
		$this->addSidebar();
		$this->addContent();
		$this->addFooter();
		$this->display();
	}
	function addmenu(){
		$self = $_SERVER['PHP_SELF'];
		$this->page .= <<<EOD
			<div class="menu">
				<ul>
					<li><a href="$self?page=dashboard" class="dashboard">dashboard</a></li>
					<li><a href="$self?page=matakuliah" class="matakuliah">matakuliah</a></li>
					<li><a href="$self?page=mahasiswa" class="mahasiswa">mahasiswa</a></li>
					<li><a href="$self?page=dosen" class="dosen">dosen</a></li>
					<li><a href="$self?page=profile" class="profile">profile</a></li>

EOD;
		if($this->privilege == ADM){
			$this->page .= '<li><a href="?page=settings" class="settings">settings</a></li>';
		}
		$this->page .= '</ul></div>';
	}
	function addSidebar(){
		if($this->privilege == MHS){
			$info = $this->getStatusKontrak();
		}
		else{
			$info = $this->getAjuanKontrak();
		}
		$how_online = $this->getHowOnline();
		$message = $this->getMessage();
		$this->page .=<<<EOD
			<div class="content">
				<div class="sidebar">
					<div class="boxside">
						$info
					</div>
					<div class="boxside">
						$message
					</div>
					<div class="boxside">
						$how_online
					</div>
				</div>
EOD;
	}
	function getHowOnline(){
		$sql = "SELECT usr_login, usr_nama,usr_last_login,usr_privilege,usr_id FROM tbl_users WHERE (NOW() - usr_last_login) < 1200 ORDER BY usr_last_login DESC";
		$res = $this->db->query($sql);
		$self = $_SERVER['PHP_SELF'];
		$ho = '<h4>20 menit terakhir</h4>';
		if($res->size() != 0){
			while($row = $res->fetch()){
				if($row['usr_privilege'] == MHS){
					$ho .= '<a href="'.$self.'?page=mahasiswa&view=detail&uid='.$row['usr_id'].'">'.$row['usr_nama'].' </a>';
				}
				else if($row['usr_privilege'] == DSN){
					$ho .= '<a href="'.$self.'?page=dosen&view=detail&uid='.$row['usr_id'].'"><b>'.$row['usr_nama'].'</b> </a>';;
				}
				else{
					$ho .= '<a style="color:#910000" href="'.$_SERVER['REQUEST_URI'].'"><b>'.$row['usr_nama'].'</b></a>';
				}
				if($row['usr_login'] != $this->auth->session->get(POST_LOGIN_VAR)){
					$ho .= '<a onclick="makeMsg('.$row['usr_id'].');return false" href=""';
					$ho .= '<img src="img/msg.gif" title="kirim pesan"/></a>';
				}
				$ho .= '<br/>';
			}
		}
		else{
			$ho .= 'Tidak ada</br>';
		}
		return $ho;
	}
	function getStatusKontrak(){
		if(isset($_POST['batal'])){
			$kontrak_id = mysql_real_escape_string($_POST['kontrak_id']);
			$this->db->query("DELETE FROM tbl_kontrak WHERE kontrak_id = $kontrak_id");
		}
		if(isset($_POST['ajukan'])){
			$this->db->query("UPDATE tbl_kontrak SET kontrak_status = 1 WHERE kontrak_status=0 
			AND mhs_id = (SELECT mhs_id FROM tbl_mahasiswa WHERE usr_id = $this->usr_id)");
		}
		$display = '';
		$display .= '<h4>Status Matakuliah</h4>';
		if($this->setListStatus() != false){
			$display .= '<p><b>Matakuliah yang akan di ajukan:</b></p>';
			$display .= $this->setListStatus();
			$display .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="post">';
		}
		else if($this->setListStatus(1) != false){
			$display .= '<p><b>Matakuliah yang sudah di ajukan:</b></p>';
			$display .= $this->setListStatus(1);
		}
		else if($this->setListStatus(2) != false){
			$display .= '<p><b> Matakuliah yang di ajukan yaitu :</b></p>';
			$display .= $this->setListStatus(2);
			$display .= '<p><b>Sudah disetujui Oleh wali</b></p>';
		}
		else{
			$display .= '<p>No Activity</p>';
		}
		return $display;
	}
	function setListStatus($status = 0,$lokasi = 'side',$uid = null){
		if($uid != null){
			$usr_id = $uid;
		}
		else{
			$usr_id = $this->usr_id;
		}
		$display = '';
		$sql=<<<EOD
		SELECT
			tbl_kontrak.mk_id, 
			tbl_kontrak.kontrak_id,
			tbl_kontrak.dsn_id,
			tbl_users.usr_nama,
			tbl_matakuliah.mk_kode,
			tbl_matakuliah.mk_sks,
			tbl_matakuliah.mk_nama
		FROM
			tbl_matakuliah,tbl_kontrak,tbl_users,tbl_dosen
		WHERE 
			tbl_kontrak.mk_id = tbl_matakuliah.mk_id
			AND tbl_kontrak.kontrak_status = $status
			AND tbl_dosen.usr_id = tbl_users.usr_id
			AND tbl_kontrak.dsn_id = tbl_dosen.dsn_id 
			AND tbl_kontrak.mhs_id = (SELECT mhs_id FROM tbl_mahasiswa WHERE usr_id = $usr_id)
EOD;
		$res = $this->db->query($sql);
		if($res->size() > 0){
			$display .= '<table>';
			if($lokasi == 'main'){
				$display .= '<thead><th>Kode</th><th>Nama Matakuliah</th><th>sks</th><th>Dosen</th>';
				if($status == 0){
					$display .= '<th>batal</th>';
				}
				$display .='</thead>';
			}
			$jml_sks = 0;
			while($row = $res->fetch()){
				$display .= '<tr><td><b>'.$row['mk_kode'].'</b></td><td>';
				$display .= '<a href="'.$_SERVER['PHP_SELF'].'?page=matakuliah&view=detail&id='.$row['mk_id'].'">';
				$display .= $row['mk_nama'].'</a></td>';
				if($lokasi == 'main'){
					$display .= '<td>'.$row['mk_sks'].'</td><td>'.$row['usr_nama'].'</td>';
				}
				if($status == 0){
					$display .= '</td><td align="right">';
					$display .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="post">';
					$display .= '<input type="hidden" name="kontrak_id" value="'.$row['kontrak_id'].'">';
					$display .= '<input type="submit" name="batal" value="batal">';
					$display .= '</form>';
				}
				$display .= '</td></tr>';
				$jml_sks += $row['mk_sks'];
			}
			if($status == 0){
				$col = ($lokasi == 'main')? 5 : 3;
			}
			else{
				$col = ($lokasi == 'main')? 4 : 2;
			}
			$display .= '<tr><td colspan="'.$col.'"><b>Total SKS : '.$jml_sks.' </b></tr>';
			$display .= '</table>';
			if($status == 0){
				$display .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="post">';
				$display .= '<input type="submit" name="ajukan" value="ajukan kontrak"></p></form>';
			}
		}else{
			$display = false;
		}
		return $display;
	}
	function getAjuanKontrak($lokasi='side'){
		$display = '';
		$sql = <<<EOD
			SELECT
				tbl_users.usr_id, 
				tbl_kontrak.mhs_id,
				tbl_mahasiswa.mhs_nim,
				tbl_users.usr_nama,
				SUM(tbl_matakuliah.mk_sks) AS sks_kontrak,
				tbl_mahasiswa.dsn_id
			FROM tbl_kontrak,tbl_mahasiswa,tbl_users,tbl_matakuliah
			WHERE
				tbl_kontrak.mhs_id = tbl_mahasiswa.mhs_id
				AND tbl_kontrak.kontrak_status = 1
				AND tbl_mahasiswa.usr_id = tbl_users.usr_id
				AND tbl_kontrak.mk_id = tbl_matakuliah.mk_id
				AND tbl_mahasiswa.dsn_id = (SELECT dsn_id FROM tbl_dosen WHERE usr_id = $this->usr_id)
			GROUP BY tbl_kontrak.mhs_id
			ORDER BY tbl_kontrak.kontrak_waktu
EOD;
		$res = $this->db->query($sql);
		$display .= '<h4>Ajuan Kontrak</h4>';
		if($res->size() > 0){
			$display .= '<table>';
			if($lokasi == 'main'){
				$display .= '<thead><th>NIM</th><th>Nama</th><th>SKS Kontrak</th></thead>';
			}
			while($row = $res->fetch()){
				$display .= '<tr><td>'.$row['mhs_nim'].'</td>';
				$display .= '<td><a style="color:#005FFF" href="'.$_SERVER['PHP_SELF'].'?dashboard&view=kontrak_detail&uid='.$row['usr_id'].'">';
				$display .= $row['usr_nama'].'</a></td>';
				if($lokasi == 'main'){
					$display .= '<td>'.$row['sks_kontrak'].'</td>';
				}
				$display .= '</tr>';
			}
			$display .= '</table>';
		}
		else{
			$display .= '<p>Tidak ada ajuan</p>';
		}
		return $display;
	}
	function getMessage(){
		$display = '';
		$sql = <<<EOD
			SELECT
				tbl_users.usr_id,
				tbl_users.usr_privilege,
				tbl_users.usr_nama, 
				COUNT(tbl_pesan.pesan_id) AS jumlah
				FROM tbl_pesan,tbl_users
			WHERE 
				tbl_pesan.usr_id_tujuan = $this->usr_id
				AND tbl_pesan.pesan_status = 0
				AND tbl_pesan.usr_id_asal = tbl_users.usr_id
			GROUP BY tbl_users.usr_id
EOD;
		$res = $this->db->query($sql);
		$display .= '<h4>Status Pesan</h4>';
		if($res->size() > 0){
			while($row = $res->fetch()){
				if($row['usr_privilege'] == MHS){
					$display .= '<a href="'.$self.'?page=mahasiswa&view=detail&uid='.$row['usr_id'].'">'.$row['usr_nama'].' </a>';
				}
				else if($row['usr_privilege'] == DSN){
					$display .= '<a href="'.$self.'?page=dosen&view=detail&uid='.$row['usr_id'].'"><b>'.$row['usr_nama'].'</b> </a>';;
				}
				else{
					$display .= '<a style="color:#910000" href="'.$_SERVER['REQUEST_URI'].'"><b>'.$row['usr_nama'].'</b></a>';
				}
				$display .= '<a onclick="makeMsg('.$row['usr_id'].');return false" href=""';
				$display .= '<img src="img/msg.gif" title="kirim pesan"/> <b>'.$row['jumlah'].' </b></a><br>';
			}
		}
		else{
			$display .= '<p>Tidak ada pesan</p>';
		}
		return $display;
	}
	function addContent(){
		$content = '';
		if($this->privilege == MHS){
			if($this->setListStatus() != false){
				$content .= '<p><b>Matakuliah yang akan di ajukan:</b></p>';
				$content .= $this->setListStatus(0,'main');
				$content .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="post">';
			}
			else if($this->setListStatus(1) != false){
				$content .= '<p><b>Matakuliah yang sudah di ajukan:</b></p>';
				$content .= $this->setListStatus(1,'main');
			}
			else if($this->setListStatus(2) != false){
				$content .= '<p><b> Matakuliah yang di ajukan yaitu :</b></p>';
				$content .= $this->setListStatus(2,'main');
				$content .= '<p><b>Sudah disetujui Oleh wali</b></p>';
			}
			else{
				$content .= '<p>No Activity</p>';
			}
		}
		else{
			if(isset($_GET['view']) && $_GET['view'] == 'kontrak_detail'){
				if(isset($_GET['uid'])){
					$uid = $_GET['uid'];
					$content .= $this->detailKontrak($uid);
				}
			}
			else{
				$content .= $this->getAjuanKontrak('main');
			}
		}
		if($this->privilege != MHS){
			$content .= '<h3>Statistik Jumlah Mahasiswa yang mengontrak</h3>';
			$content .= $this->getBebanDosen($this->usr_id);
		}
		if($this->privilege == ADM){
			$content .= '<h3>Statik beban Dosen Lain</h3>';
			$content .= $this->getStaticDosen();
		}
		if(isset($_GET['view']) && isset($_GET['id']) && $_GET['view'] == 'isi_nilai'){
			$mk_id = $_GET['id'];
			$content = $this->isiNilai($mk_id);
		}
		$this->page .= <<<EOD
			<div class="maincontent">
				<div id="content">
					<div id="dash">
						$content
					</div>
				</div>
			</div>
EOD;
	}
	function getBebanDosen($uid){
		$display = '';
		$sql = <<<EOD
		SELECT 
			tbl_matakuliah.mk_id,
			tbl_matakuliah.mk_kode,
			tbl_matakuliah.mk_nama
		FROM tbl_mk_dsn,tbl_matakuliah
		WHERE tbl_mk_dsn.mk_id = tbl_matakuliah.mk_id
			AND tbl_mk_dsn.dsn_id = (SELECT dsn_id FROM tbl_dosen WHERE usr_id = $uid)
EOD;
		$res = $this->db->query($sql);
		$display .= '<table class="t"><thead><th>kode</th><th>Nama Matakuliah</th>';
		$display .= '<th>Jumlah Mahasiswa</th>';
		if($uid == $this->usr_id){
			$display .= '<th>nilai</th>';
		}
		$display .='</thead>';
		while($row = $res->fetch()){
			$display .= '<tr><td>'.$row['mk_kode'].'</td>';
			$display .= '<td>'.$row['mk_nama'].'</td>';
			$display .= '<td>'.$this->jumlahMhs($uid,$row['mk_id']).'</td>';
			if($uid == $this->usr_id){
				$display .= '<td><a href="'.$_SERVER['isi_nilai'].'?page=dashboard&view=isi_nilai&id='.$row['mk_id'].'"> nilai</a></td>';
			}
			$display .= '</tr>';
		}
		$display .= '</table>';
		return $display;
	}
	function getStaticDosen(){
		$display = '';
		$res = $this->db->query("SELECT tbl_users.usr_id,tbl_users.usr_nama 
		FROM tbl_users,tbl_dosen,tbl_mk_dsn
		WHERE tbl_dosen.usr_id = tbl_users.usr_id
		AND tbl_dosen.dsn_id = tbl_mk_dsn.dsn_id
		GROUP BY  tbl_users.usr_id
		");
		while($row = $res->fetch()){
			$display .= '<table class="t"><th colspan="3">'.$row['usr_nama'];
			$display .= ' <a onclick="makeMsg('.$row['usr_id'].');return false" href=""';
			$display .= '<img src="img/msg.gif" title="kirim pesan"/></a>';
			$display .= '</th></table>';
			$display .=  $this->getBebanDosen($row['usr_id']);
			$display .= '<br><br>';
		}
		return $display;
	}
	function jumlahMhs($uid,$mk_id){
		$res = $this->db->query("SELECT kontrak_id FROM tbl_kontrak
		WHERE dsn_id = (SELECT dsn_id FROM tbl_dosen WHERE usr_id=$uid)
		AND mk_id = $mk_id
		AND kontrak_status > 1 
		");
		$size = $res->size();
		return $size; 
	}
	function detailKontrak($uid){
		$content = '';
		
		if(isset($_POST['ok'])){
			$pesan = mysql_real_escape_string($_POST['pesan']);
			$pesan = strip_tags($pesan,'<b><i><u>');
			$pesan = trim($pesan);
			if(!empty($pesan)){
				$this->db->query("INSERT INTO tbl_pesan(usr_id_asal,usr_id_tujuan,pesan_isi)
				VALUE($this->usr_id,$uid,'$pesan')
				");
			}
			if($_POST['ok'] == 'terima'){
				$this->db->query("UPDATE tbl_kontrak SET kontrak_status = 2 
				WHERE mhs_id = (SELECT mhs_id FROM tbl_mahasiswa WHERE usr_id = $uid)
				AND kontrak_status = 1
				");
				$this->db->query("UPDATE tbl_mahasiswa SET mhs_semester = (mhs_semester +1) 
				WHERE usr_id = $uid");
				header('Location:'.$SERVER['PHP_SELF'].'?page=dashboard');
			}
			else{
				$this->db->query("UPDATE tbl_kontrak SET kontrak_status = 0 
				WHERE mhs_id = (SELECT mhs_id FROM tbl_mahasiswa WHERE usr_id = $uid)
				AND kontrak_status = 1
				");
				header('Location:'.$SERVER['PHP_SELF'].'?page=dashboard');
			}
		}
		$res = $this->db->query("SELECT usr_nama FROM tbl_users WHERE usr_id = $uid");
		$row = $res->fetch();
		$content .= '<h3>Matakuliah yang diajukan oleh '.$row['usr_nama'].'</h3>';
		$content .= $this->setListStatus(1,'main',$uid);
		$uri = $_SERVER['REQUEST_URI'];
		$content .= <<<EOD
			<table><tr>
			<form action="$uri" method="post">
				<td>Tambahkan pesan :<br><textarea name="pesan"></textarea></td>
				<td><input type="submit" name="ok" value="terima">&nbsp;&nbsp;</td>
				<td><input type="submit" name="ok" value="tolak"></td>
			</form>
			</tr></table>
EOD;
		$content .= '<p><a href="'.$_SERVER['HTTP_REFERER'].'">&lt;&lt;kembali</a></p>';
		
		return $content;
	}
	function isiNilai($mk_id){
		if(isset($_POST['ok'])){
			$input = array_map('htmlentities',$_POST);
			$input = array_map('mysql_real_escape_string',$input);
			$this->db->query("UPDATE tbl_kontrak SET
			kontrak_nilai = '".$input['kontrak_nilai']."',
			kontrak_status = 3
			WHERE kontrak_id = ".$input['kontrak_id']."
			");
		}
		$display = '';
		$sql = <<<EOD
			SELECT
				tbl_kontrak.kontrak_id,
				tbl_users.usr_id,
				tbl_mahasiswa.mhs_id,
				tbl_mahasiswa.mhs_nim,
				tbl_users.usr_nama,
				tbl_kontrak.kontrak_nilai
			FROM tbl_users,tbl_mahasiswa,tbl_kontrak
			WHERE 
				tbl_users.usr_id = tbl_mahasiswa.usr_id
				AND tbl_kontrak.mhs_id = tbl_mahasiswa.mhs_id
				AND tbl_kontrak.mk_id = $mk_id
				AND tbl_kontrak.dsn_id = (SELECT dsn_id FROM tbl_dosen WHERE usr_id = $this->usr_id)
			ORDER BY tbl_mahasiswa.mhs_nim
EOD;
		$res = $this->db->query($sql);
		$display .= '<h2>Pengesian Nilai mahasiswa</h2>';
		$display .= '<table class="t"><thead><th>No</th><th>NIM</th><th>Nama</th><th>nilai</th><th>isi nilai</th>';
		$display .= '</thead>';
		$i = 1;
		while($row = $res->fetch()){
			$display .= '<tr>';
			$display .= '<td>'.$i.'</td>';
			$display .= '<td>'.$row['mhs_nim'].'</td>';
			$display .= '<td>'.$row['usr_nama'].'</td>';
			$display .= '<td>'.$row['kontrak_nilai'].'</td>';
			$display .= '<td>';
			$display .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="post">';
			$display .= '<input type="hidden" name="kontrak_id" value="'.$row['kontrak_id'].'">';
			$display .='<select name="kontrak_nilai">';
			$display .= '<option value="A">A</option>';
			$display .= '<option value="B">B</option>';
			$display .= '<option value="C">C</option>';
			$display .= '<option value="D">D</option>';
			$display .= '<option value="E">E</option>';
			$display .= '<option value="BL">BL</option>';
			$display .= '</select>';
			$display .= '<input type="submit" name="ok" value="ok">';
			$display .= '</form>';
			$display .= '</td>';
			$display .= '</tr>';
			$i++;
		}
		$display .= '</table>';
		return $display;
	}
} 
