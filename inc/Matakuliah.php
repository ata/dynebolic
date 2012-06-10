<?php

class Matakuliah extends Dosen{
	
	function addContent(){
		$content = $this->listMatakuliah();
		if((isset($_GET['view']) && $_GET['view']=='detail')){
			$id = $_GET['id'];
			$content = $this->detailMatakuliah($id);
		}
		if((isset($_GET['view']) && $_GET['view']=='kontrak')){
			$id = $_GET['id'];
			if(isset($id) && $this->privilege == MHS){
				$content = $this->kontrak($id);
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
	function listMatakuliah(){
		$display = '';
		$self = $_SERVER['PHP_SELF'];
		$display .= '<h2 align="center">Daftar Matakuliah</h2>';
		if(!isset($_GET['view']) || $_GET['view'] == 'available'){
			$display .= '<p align="center">Tampilkan Matakuliah : ';
			$display .= 'Tersedia | ';
			$display .= '<a href="'.$self.'?page=matakuliah&view=all">Semua</a></p><br>';
			$display .= $this->listMatakuliahAvailable();
		}
		else if(isset($_GET['view']) && $_GET['view'] == 'all'){
			$display .= '<p align="center">Tampilkan Matakuliah : ';
			$display .= '<a href="'.$self.'?page=matakuliah&view=available">Tersedia</a> | ';
			$display .= 'Semua </p>';
			$display .= $this->listMatakuliahAll();
		}
		return $display;
	}
	function listMatakuliahAvailable(){
		if(isset($_POST['delete'])){
			$mk_id = mysql_real_escape_string($_POST['mk_id']);
			$this->db->query("DELETE FROM tbl_mk_dsn WHERE mk_id=$mk_id");
		}
		$display = '';
		$sql = <<< EOD
			SELECT
				tbl_matakuliah.mk_id,
				tbl_matakuliah.mk_semester,
				tbl_matakuliah.mk_kode,
				tbl_matakuliah.mk_nama,
				tbl_matakuliah.mk_sks
			FROM
				tbl_matakuliah,
				tbl_mk_dsn
			WHERE
				tbl_matakuliah.mk_id = tbl_mk_dsn.mk_id
			GROUP BY
				tbl_mk_dsn.mk_id
			ORDER BY
				tbl_matakuliah.mk_semester
EOD;
		$res = $this->db->query($sql);
		$display .= '<table class="t">';
		$display .= '<thead><th>Kode</th><th>Nama Matakuliah</th><th>Semester</th><th>SKS</th>';
		$display .= '<th>Detail</th>';
		if($this->privilege == MHS){
			$display .= '<th>Kontrak</th>';
		}
		if($this->privilege == ADM){
			$display .= '<th>Hapus</th>';
		}
		$display .= '</thead>';
		$self = $_SERVER['PHP_SELF'];
		while ($row = $res->fetch()) {
			$display .= '<tr><td>'.$row['mk_kode'].'</td>';
			$display .= '<td>'.$row['mk_nama'].'</td>';
			$display .= '<td align="center">'.$row['mk_semester'].'</td>';
			$display .= '<td>'.$row['mk_sks'].'</td>';
			$display .= '<td><a href="'.$self.'?page=matakuliah&view=detail&id='.$row['mk_id'].'">';
			$display .= '<img src="img/detail.png" title="detail"/></a></td>';
			if($this->privilege == MHS){
				$display .= '<td><a href="'.$_SERVER['PHP_SELF'].'?page=matakuliah&view=kontrak&id='.$row['mk_id'].'"><button>Kontrak</button></a></a></td>';
			}
			if($this->privilege == ADM){
				$display .= '<td>';
				$display .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="post">';
				$display .= '<input type="hidden" name="mk_id" value="'.$row['mk_id'].'">';
				$display .= '<input type="submit" name="delete" value="hapus ketersediaan"></form>';
				$display .= '</td>';
			}
			$display .= '</tr>';
		}
		$display .= '</table>';
		return $display;
	}
	function listMatakuliahAll(){
		$display = '';
		$sql = <<<EOD
			SELECT mk_id,mk_kode,
				CASE mk_jenis
				WHEN 'MKU' THEN 'Mata Kuliah Umum'
				WHEN 'MKK' THEN 'Mata Kuliah Keahlian Fakultas'
				WHEN 'MKK-PRODI' THEN 'Mata Kuliah Keahlian Program Studi'
				WHEN 'MKP' THEN 'Mata Kuliah Pilihan'
				WHEN 'MKDA' THEN 'Mata Kuliah Dasar Akademik'
				WHEN 'MKKA' THEN 'Mata Kuliah Keahlian Akademik'
				WHEN 'MKLA' THEN 'Mata Kuliah Latihan Akademik'
				END AS jenis,
				mk_semester,mk_nama,mk_sks,
				CASE mk_jenis
				WHEN 'MKU' THEN 1
				WHEN 'MKK' THEN 2
				WHEN 'MKK-PRODI' THEN 3
				WHEN 'MKP' THEN 4
				WHEN 'MKDA' THEN 5
				WHEN 'MKKA' THEN 6
				WHEN 'MKLA' THEN 7
				END AS no_jenis
			FROM tbl_matakuliah 
			ORDER BY no_jenis,mk_kode
EOD;
		$res = $this->db->query($sql);
		$display .= '<table class="t"><thead><th>Kode</th><th>Nama</th><th>SKS</th><th>Semester</th>';
		$col = 5;
		if($this->privilege == ADM){
			$display .= '<th colspan="3">Pilihan</th>';
			$col = 7;
		}
		else{
			$display .= '<th>Detail</th>';
		}
		$display .= '</thead>';
		$no_jenis = 0;
		while ($row = $res->fetch()) {
			if($no_jenis != $row['no_jenis']){
				$display .= '<tr><td colspan="'.$col.'"><b style="text-transform:uppercase"><br>'.$row['jenis'].'</b></td></tr>';
				$no_jenis = $row['no_jenis'];
			}
			$display .= '<tr><td>'.$row['mk_kode'].'</td>';
			$display .= '<td>'.$row['mk_nama'].'</td>';
			$display .= '<td>'.$row['mk_sks'].'</td>';
			$display .= '<td align="center">'.$row['mk_semester'].'</td>';
			$display .= '<td><a href="'.$self.'?page=matakuliah&view=detail&id='.$row['mk_id'].'">';
			$display .= '<img src="img/detail.png" title="detail"/></a></td>';
			if($this->privilege == ADM){
				$display .= '<td><a href="'.$self.'?page=matakuliah&view=edit&id='.$row['mk_id'].'">';
				$display .= '<img src="img/edit.png" title="ubah"/></a></td>';
				$display .= '<td><a href="'.$self.'?page=matakuliah&view=delete&id='.$row['mk_id'].'">';
				$display .= '<img src="img/delete.png" title="hapus"/></a></td>';
			}
			$display .= '</tr>';
		}
		$display .= '</table>';
		return $display;
	}
	function detailMatakuliah($id){
		$display = '';
		$sql_detail = <<<EOD
			SELECT mk_id,mk_kode,mk_nama,mk_sks,mk_semester,mk_desc,
			CASE mk_jenis
				WHEN 'MKU' THEN 'Mata Kuliah Umum'
				WHEN 'MKK' THEN 'Mata Kuliah Keahlian Fakultas'
				WHEN 'MKK-PRODI' THEN 'Mata Kuliah Keahlian Program Studi'
				WHEN 'MKP' THEN 'Mata Kuliah Pilihan'
				WHEN 'MKDA' THEN 'Mata Kuliah Dasar Akademik'
				WHEN 'MKKA' THEN 'Mata Kuliah Keahlian Akademik'
				WHEN 'MKLA' THEN 'Mata Kuliah Latihan Akademik'
				END AS jenis,
				CASE mk_jenis
				WHEN 'MKP' THEN 'Pilihan'
				ELSE 'Wajib'
				END AS sifat
			FROM tbl_matakuliah
			WHERE mk_id= $id
EOD;
		$login = $this->auth->session->get(POST_LOGIN_VAR);
		$res_user = $this->db->query("SELECT usr_id FROM tbl_users WHERE usr_login = '$login'");
		$ruid = $res_user->fetch();
		$uid = $ruid['usr_id'];
		$res = $this->db->query($sql_detail);
		$row = $res->fetch();
		$display .= '<h2 align="center">Detail Matakuliah </h2>';
		$display .= '<table border="0">';
		$display .= '<tr><td>Nama Matakuliah</td><td>:</td><td>'.$row['mk_nama'].'</td></tr>';
		$display .= '<tr><td>Kode Matakuliah</td><td>:</td><td>'.$row['mk_kode'].'</td></tr>';
		$display .= '<tr><td>Jenis Matakuliah</td><td>:</td><td>'.$row['jenis'].'</td></tr>';
		$display .= '<tr><td>Sifat Matakuliah</td><td>:</td><td>'.$row['sifat'].'</td></tr>';
		if($this->privilege == MHS){
			$display .= '<tr><td>Status Kontrak</td><td>:</td>';
			if($this->isKontrak($uid,$row['mk_id'])){
				$display .= '<td>Sudah dikontrak</td></tr>';
			}
			else{
				$display .= '<td>Belum atau Sedang dikontrak</td></tr>';
			}
		}
		$display .= '<tr><td>Deskripsi</td><td>:</td><td>'.$row['mk_desc'].'</td></tr>';
		$display .= '<tr><td colspan="3"><b>Persyaratan matakuliah</b></td></tr>';
		$display .= $this->getSyarat($id);
		$display .= '<tr><td colspan="3"><b>Info Dosen Pengajar</b></td></tr>';
		$display .= $this->getPengajar($id);
		if($this->privilege == MHS){
			$display .= '<tr><td colspan="3" align="center">';
			$display .= '<br><a href="'.$_SERVER['PHP_SELF'].'?page=matakuliah&view=kontrak&id='.$id.'">';
			$display .= '<button>Kontrak Matakuliah ini</button></a>';
			$display .= '</td></tr>';
		}
		$display .= '</table><br>'; 
		$display .= '<p align="center">Kembali ke : ';
		$display .= '<a href="'.$_SERVER['PHP_SELF'].'?page=matakuliah"> matakukuliah tersedia | </a>';
		$display .= '<a href="'.$_SERVER['PHP_SELF'].'?page=matakuliah&view=all"> Semua matakuliah </a></p>';
		
		return $display;
	}
	function getPengajar($id){
		if($this->privilege == ADM && isset($_POST['add'])){
			$dsn_id = mysql_real_escape_string($_POST['dsn_id']);
			$this->db->query("INSERT INTO tbl_mk_dsn(mk_id,dsn_id) VALUE($id,$dsn_id)");
		}
		if($this->privilege == ADM && isset($_POST['delete'])){
			$dsn_id = mysql_real_escape_string($_POST['dsn_id']);
			$this->db->query("DELETE FROM tbl_mk_dsn WHERE mk_id= $id AND dsn_id = $dsn_id");
		}
		$sql = <<<EOD
			SELECT 
				tbl_mk_dsn.dsn_id,
				tbl_dosen.dsn_kode,
				tbl_users.usr_nama
			FROM tbl_mk_dsn,tbl_users,tbl_dosen
			WHERE
				tbl_mk_dsn.dsn_id =  tbl_dosen.dsn_id
				AND tbl_users.usr_id = tbl_dosen.usr_id
				AND tbl_mk_dsn.mk_id = $id
EOD;
		$res = $this->db->query($sql);
		$display = '';
		if($res->size() != 0){
			while($row = $res->fetch()){
				$display .= '<tr><td colspan="2">'.$row['dsn_kode'].'</td><td>'.$row['usr_nama'].'</td><td>';
				if($this->privilege == ADM){
					$display .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="post">';
					$display .= '<input type="hidden" value="'.$row['dsn_id'].'" name="dsn_id">';
					$display .= '<input type="submit" name="delete" value="hapus">';
					$display .= '</form>';
				}
			$display .= '</td></tr>';
			}
		}
		else{
			$display .= '<tr><td colspan="3"><small> tidak tersedia pengajar untuk matakuliah ini</small></td></tr>';
		}
		if($this->privilege == ADM){
				$rp = $this->db->query("SELECT tbl_dosen.dsn_id,tbl_dosen.dsn_kode,tbl_users.usr_nama 
				FROM tbl_users,tbl_dosen WHERE tbl_users.usr_id = tbl_dosen.usr_id
				ORDER BY tbl_users.usr_nama");
				$display .= '<tr><td>Tambah Pengajar</td><td>:</td><td>';
				$display .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="post">';
				$display .= '<select name="dsn_id">';
				while ($r = $rp->fetch()){
					$display.= '<option value="'.$r['dsn_id'].'">'.$r['dsn_kode'].' -- '.$r['usr_nama'].'</option>';
				}
				$display .= '</select></td>';
				$display .= '<td><input type="submit" name="add" value="Tambah"></form></td></tr>';
			}
		return $display;
	}
	function getSyarat($id){
		if(isset($_POST['sy_delete'])){
			$syarat_id = mysql_real_escape_string($_POST['id']);
			$this->db->query("DELETE FROM tbl_mk_syarat WHERE id=$syarat_id");
		}
		if(isset($_POST['add_syarat'])){
			$mk_syarat_id = mysql_real_escape_string($_POST['mk_syarat_id']);
			$this->db->query("INSERT INTO tbl_mk_syarat(mk_id,mk_syarat_id) VALUE($id,$mk_syarat_id)");
		}
		$display = '';
		$sql = <<<EOD
			SELECT tbl_mk_syarat.id, tbl_mk_syarat.mk_syarat_id,tbl_matakuliah.mk_kode,tbl_matakuliah.mk_nama
			FROM 
				tbl_mk_syarat,
				tbl_matakuliah
			WHERE 
				tbl_mk_syarat.mk_syarat_id = tbl_matakuliah.mk_id
				AND tbl_mk_syarat.mk_id = $id 
EOD;
		$res = $this->db->query($sql);
		$size = $res->size();
		if($size == 0){
			$display .= '<tr><td colspan="3"><small>Tidak ada syarat untuk mata kuliah ini</small></td></tr>';
		}
		else{
			$login = $this->auth->session->get(POST_LOGIN_VAR);
			$res_user = $this->db->query("SELECT usr_id FROM tbl_users WHERE usr_login = '$login'");
			$ruid = $res_user->fetch();
			$uid = $ruid['usr_id'];
			while($row = $res->fetch()){
				$display .= '<tr><td colspan="2">'.$row['mk_kode'].'</td>';
				$display .= '<td><a href="'.$_SERVER['PHP_SELF'].'?page=matakuliah&view=detail&id='.$row['mk_syarat_id'].'">'.$row['mk_nama'].'</a></td><td>';
				if($this->privilege == MHS){
					if($this->isKontrak($uid,$row['mk_syarat_id'])){
						$display .= '<small style="color:#003BFF"> Sudah di Kontrak</small>';
					}
					else{
						$display .= '<a href="'.$_SERVER['PHP_SELF'].'?page=matakuliah&view=kontrak&id='.$row['mk_syarat_id'].'"><button>Kontrak</button></a></a>';
					}
				}
				if($this->privilege == ADM){
					$display .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="post">';
					$display .= '<input type="hidden" name="id" value="'.$row['id'].'">';
					$display .= '<input type="submit" name="sy_delete" value="hapus">';
					$display .= '</form>';
				}
				$display .= '</td></tr>';
			}
		}
		if($this->privilege == ADM){
			$res_syarat = $this->db->query("SELECT mk_id,mk_kode,mk_nama FROM tbl_matakuliah ORDER BY mk_nama");
			$display .= '<tr><td>Tambah Syarat</td><td>:</td><td>';
			$display .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="post">';
			$display .= '<select name="mk_syarat_id">';
			while($r = $res_syarat->fetch()){
				$display .= '<option value="'.$r['mk_id'].'">'.$r['mk_kode'].'--'.$r['mk_nama'].'</option>';
			}
			$display .= '</select></td>';
			$display .= '<td><input type="submit" name="add_syarat" value="Tambah"></form>';
			$display .= '</td></tr>';
		}
		return $display;
	}
	function isKontrak($uid,$mk_id,$status = 3){
		$res = $this->db->query("SELECT COUNT(*) as nums FROM tbl_kontrak WHERE mk_id=$mk_id 
		AND mhs_id = (SELECT mhs_id FROM tbl_mahasiswa WHERE usr_id = $uid) AND kontrak_status=$status");
		$row = $res->fetch();
		if($row['nums'] != 0){
			return true;
		}
		else{
			return false;
		}
	}
	function kontrak($mk_id){
		if($this->isKontrak($this->usr_id,$mk_id,0)){
			header('Location:'.$_SERVER['PHP_SELF'].'?page=matakuliah');
		}
		$mhs_res = $this->db->query("SELECT mhs_id FROM tbl_mahasiswa WHERE usr_id = $this->usr_id");
		$r = $mhs_res->fetch();
		$mhs_id = $r['mhs_id'];
		if(isset($_POST['ok'])){
			$dsn_id = mysql_real_escape_string($_POST['dsn_id']);
			$this->db->query("INSERT INTO tbl_kontrak(mhs_id,mk_id,dsn_id,kontrak_semester)
			VALUE($mhs_id, $mk_id,$dsn_id,(SELECT mhs_semester FROM tbl_mahasiswa WHERE mhs_id=$mhs_id)+1)
			");
			header('Location:'.$_SERVER['PHP_SELF'].'?page=matakuliah');
		}
		$display = '';
		$sql = <<<EOD
			SELECT tbl_mk_syarat.id, tbl_mk_syarat.mk_syarat_id,tbl_matakuliah.mk_kode,tbl_matakuliah.mk_nama
			FROM 
				tbl_mk_syarat,
				tbl_matakuliah
			WHERE 
				tbl_mk_syarat.mk_syarat_id = tbl_matakuliah.mk_id
				AND tbl_mk_syarat.mk_id = $mk_id
EOD;
		$res = $this->db->query($sql);
		$display .= '<h3>Kontrak Matakuliah</h3>';
		$mk_id_blm = array();
		$mk_kode_blm = array();
		$mk_nama_blm = array();
		while($row = $res->fetch()){
			if(!$this->isKontrak($this->usr_id, $row['mk_syarat_id'])){
				$mk_id_blm[] = $row['mk_syarat_id'];
				$mk_kode_blm[] = $row['mk_kode'];
				$mk_nama_blm[] = $row['mk_nama'];
			}
		}
		if(count($mk_id_blm) > 0){
			$display .= '<p>Maaf, anda tidak bisa mengontrak matakuliah ini karena syarat belum terpenuhi, silahkan kontrak matakuliah dibawah ini terlebih dahulu:</p>';
			$display .= '<table border="1">';
			for($i = 0; $i < count($mk_id_blm);$i++){
				$display .= '<tr><td>'.$mk_kode_blm[$i].'</td>';
				$display .= '<td>'.$mk_nama_blm[$i].'</td>';
				$display .= '<td><a href="'.$self.'?page=matakuliah&view=detail&id='.$mk_id_blm[$i].'">Detail</a></td>';
				$display .= '<td><a href="'.$_SERVER['PHP_SELF'].'?page=matakuliah&view=kontrak&id='.$mk_id_blm[$i].'"><button>Kontrak</button></a></td>';
				$display .= '</tr>';
			}
			$display .= '</table>';
			$display .= '<p><a href="'.$_SERVER['HTTP_REFERER'].'">&lt;&lt;kembali</a></p>';
		}
		else{
			$sql = <<<EOD
				SELECT 
					tbl_mk_dsn.dsn_id,
					tbl_dosen.dsn_kode,
					tbl_users.usr_nama
				FROM tbl_mk_dsn,tbl_users,tbl_dosen
				WHERE
					tbl_mk_dsn.dsn_id =  tbl_dosen.dsn_id
					AND tbl_users.usr_id = tbl_dosen.usr_id
					AND tbl_mk_dsn.mk_id = $mk_id
EOD;
			$res = $this->db->query($sql);
			if($res->size() == 0){
				$display .= '<p>Maaf Matakuliah ini tidak tersedia</p>';
				$display .= '<p><a href="'.$_SERVER['HTTP_REFERER'].'">&lt;&lt;kembali</a></p>';
			}
			else{
				$display .= '<form action="'.$_SERVER['REQUEST_URI'].'" method="post">';
				$display .= '<p>Pilih dosen : ';
				$display .= '<select name="dsn_id">';
				while($row = $res->fetch()){
					$display .= '<option value="'.$row['dsn_id'].'">'.$row['dsn_kode'].' -- '.$row['usr_nama'].'</option>';
				}
				$display .= '</select>&nbsp;<input type="submit" name="ok" value="ok">';
				$display .= '</form></p><br><br>';
			}
		}
		if($this->isKontrak($this->usr_id,$mk_id,1)){
			$display = '<h3>Kontrak Matakuliah</h3>';
			$display .= '<p>Maaf, matakuliah ini sudah anda ajukan ke dosen wali anda</p>';
			$display .= '<p><a href="'.$_SERVER['HTTP_REFERER'].'">&lt;&lt;kembali</a></p>';
		}
		if($this->isPengajuan()){
			$display = '<h3>Kontrak Matakuliah</h3>';
			$display .= '<p>Maaf, Anda sudah mengajukan kontrak. Mohon tunggu konfirmasi dari dosen wali anda </p>';
			$display .= '<p><a href="'.$_SERVER['HTTP_REFERER'].'">&lt;&lt;kembali</a></p>';
		}
		if($this->isPengajuan(2)){
			$display = '<h3>Kontrak Matakuliah</h3>';
			$display .= '<p>Maaf, Anda sudah mengajukan kontrak. Dan disetujui oleh dosen</p>';
			$display .= '<p><a href="'.$_SERVER['HTTP_REFERER'].'">&lt;&lt;kembali</a></p>';
		}
		return $display;
	}
	function isPengajuan($status=1){
		$res = $this->db->query("SELECT kontrak_id FROM tbl_kontrak WHERE kontrak_status=$status AND mhs_id=(SELECT mhs_id FROM tbl_mahasiswa WHERE usr_id = $this->usr_id)");
		if($res->size() > 0){
			return true;
		}
		else{
			return false;
		}
	}
}

?>
