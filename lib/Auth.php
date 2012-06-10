<?php
@define('POST_LOGIN_VAR','username');
@define('POST_LOGIN_PASS','password');
@define('TABLE_USERS','tbl_users');
@define('USER_LOGIN','usr_login');
@define('USER_PASS','usr_password');
@define('USER_PRIV','usr_privilege');
@define('USER_LAST_LOGIN','usr_last_login');
class Auth{
	var $db;
	var $session;
	var $login_page;
	var $hash_key;
	
	function Auth(&$db, $login_page, $hash_key) {
		$this->db = &$db;
		$this->login_page = $login_page;
		$this->session = &new Session();
		$this->login();
	}
	
	function login() {
		if($this->session->get('login_hash')){
			$this->confirmAuth();
			return;
		}
		if(isset($_POST[POST_LOGIN_VAR]) && isset($_POST[POST_LOGIN_PASS])){
			$login = mysql_escape_string(strtolower($_POST[POST_LOGIN_VAR]));
			$password = mysql_escape_string(md5($_POST[POST_LOGIN_PASS]));
			$sql = "SELECT COUNT(*) AS num_users,".USER_PRIV." FROM " . TABLE_USERS ."
					WHERE
					" . USER_LOGIN . " = '$login' AND
					" . USER_PASS . " = '$password'
					GROUP BY usr_id";
					//echo $sql;
			$result = $this->db->query($sql);
			$row = $result->fetch();
			$privilege = $row[USER_PRIV];
			if($row['num_users'] != 1){
				$this->redirect();
			}
			else{
				$this->storeAuth($login,$password,$privilege);
			}
		}
		else{
			$this->redirect();
		}
	}
	
	function storeAuth($login, $password, $privilege){
		$sql = "UPDATE ". TABLE_USERS . " SET " . USER_LAST_LOGIN . "=NOW() 
		WHERE ".USER_LOGIN." = '$login' AND " .USER_PASS ."='$password'";
		$this->db->query($sql);
		$this->session->set(POST_LOGIN_VAR, $login);
		$this->session->set(POST_LOGIN_PASS, $password);
		$this->session->set(USER_PRIV, $privilege);
		$hash_key = md5($this->hash_key . $login . $password . $privilege);
		$this->session->set('login_hash',$hash_key);
	}
	
	function confirmAuth(){
		$login = $this->session->get(POST_LOGIN_VAR);
		$password = $this->session->get(POST_LOGIN_PASS);
		$privilege = $this->session->get(USER_PRIV);
		$hash_key = $this->session->get('login_hash');
		if(md5($this->hash_key . $login . $password . $privilege) != $hash_key) {
			$this->logout(true);
		}
	}
	
	function getPrivilege(){
		return $this->session->get(USER_PRIV);
	}
	
	function logout($from = false) {
		$this->session->del(POST_LOGIN_VAR);
		$this->session->del(USER_PASSW_VAR);
		$this->session->del('login_hash');
		$this->session->del(USER_PRIV);
		$this->session->destroy();
		$this->redirect($from);
	}
	
	function redirect($from = true){
		if ($from){
			header('Location:'.$this->login_page.'?from='.$_SERVER['REQUEST_URI']);
		}
		else{
			header('Location:'.$this->login_page);
		}
		exit();
	}

} 
