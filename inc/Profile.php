<?php

class Profile extends Mahasiswa{
	
	function addContent(){
		$usr_login = $this->auth->session->get(POST_LOGIN_VAR);
		$result = $this->db->query("SELECT usr_id FROM tbl_users WHERE usr_login ='$usr_login'");
		$row = $result->fetch();
		if($this->privilege == MHS) {
			$content .= '<a href="'.$SERVER['PHP_SELF'].'?page=profile&view=nilai">lihat nilai</a>';
			$content .= $this->displayDetailMhs($row['usr_id']);
			if(isset($_GET['view']) && $_GET['view']=='nilai'){
				$content = $this->viewNilai($this->usr_id);
			}
			if(isset($_GET['view']) && $_GET['view']=='history_nilai'){
				$mk_id = $_GET['mk_id'];
				$content = $this->historyNilai($this->usr_id,$mk_id);
			}
		}
		else{
			$content = $this->displayDetailDosen($row['usr_id']);
		}
		$this->page .= <<<EOD
			<div class="maincontent">
				<div id="content">
					$content
				</div>
			</div>
EOD;
	}
}

?>
