<?php
class Settings extends Dashboard{
	
	function addContent(){
		if($this->privilege != ADM){
			header('Location:'.$_SERVER['PHP_SELF'].'?page=notfound');
		}
		if(isset($_POST['save'])){
			$this->storeSettings();
			header('Location:'.$_SERVER['REQUEST_URI']);
			$status='<div id="saveedit">Setting Tersimpan!</div>';
		}
		else{
			$_POST = $this->getValueSettings();
		}
		$uri = $_SERVER['REQUEST_URI'];
		$this->page .= '<div class="maincontent"><div id="content">';
		$this->page .= '<h2 align="center">Setting Website</h2>'.$status;
		$this->page .= '<form id="form" action="'.$uri.'" method="post" enctype="multipart/form-data">';
		$this->page .= '<table>';
		$this->page .= '<tr><td>Nama prodi/Jurusan</td>';
		$this->page .= '<td><input type="text" name="prodi" value="'.$_POST['prodi'].'"></td></tr>';
		$this->page .= '<tr><td>Nama Lembaga/Pendidikan</td>';
		$this->page .= '<td><input type="text" name="universitas" value="'.$_POST['universitas'].'"></td></tr>';
		$this->page .= '<tr><td>Logo</td>';
		$this->page .= '<td><input type="file" name="url_logo"></td></tr>';
		$this->page .= '<tr><td colspan="2">Sambutan halaman depan:</td></tr>';
		$this->page .= '<tr><td colspan="2"><textarea id="lebar" name="sambutan">'.$_POST['sambutan'].'</textarea></td></tr>';
		$this->page .= '<tr><td colspan="2" style="text-align:center"><input type="submit" name="save" value="Simpan Settings"></td></tr>';
		$this->page .= '</table>';
		$this->page .= '</form></div></div>';
	}
	function getValueSettings(){
		
		$res = $this->db->query("SELECT * FROM tbl_settings");
		while($row = $res->fetch()){
			$val[] = $row['set_value'];
		}
		$set['prodi'] = $val[0];
		$set['universitas'] = $val[1];
		$set['url_logo'] = $val[2];
		$set['sambutan'] = $val[3];
		return $set;
	}
	function storeSettings(){
		$input = array_map('mysql_real_escape_string',$_POST);
		
		$this->db->query("UPDATE tbl_settings SET set_value='".$input['prodi']."' WHERE set_option='prodi'");
		$this->db->query("UPDATE tbl_settings SET set_value='".$input['universitas']."' WHERE set_option='universitas'");
		$this->db->query("UPDATE tbl_settings SET set_value='".$input['sambutan']."' WHERE set_option='sambutan'");
		if($_FILES['url_logo']['type']== 'image/jpeg'){
			move_uploaded_file($_FILES['url_logo']['tmp_name'],'img/misc/logo.jpg');
		}
	}

}

?>
