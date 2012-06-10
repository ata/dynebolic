<?php

class MySQL {
	var $host;
	var $dbUser;
	var $dbPass;
	var $dbName;
	var $dbConn;
	var $connectError;
	var $dbFalse;
	var $displayError;
	function MySQL($host,$dbUser,$dbPass,$dbName, $displayError = false){
		$this->host		= $host;
		$this->dbUser	= $dbUser;
		$this->dbPass	= $dbPass;
		$this->dbName	= $dbName;
		$this->displayError =  $displayError;
		$this->connectToDb();
	}
	function connectToDb(){
		if(!$this->dbConn = @mysql_connect($this->host,$this->dbUser,$this->dbPass)){
			if($this->displayError){
				trigger_error('tidak bisa connect ke Database');
			}
			$this->connectError = true;
		}
		else if(!@mysql_select_db($this->dbName,$this->dbConn)){
			if($this->displayError){
				trigger_error('data Base salah');
			}
			$this->dbFalse = true;
			$this->connectError = true;
		}
	}
	function &query($sql){
		if(!$result = mysql_query($sql,$this->dbConn)){
			trigger_error('Kesalahan Query: '.mysql_error($this->dbConn).' SQL: '.$sql);
			echo 'Query input:' . $sql;
		}
		return new MySQLResult($this, $result);
	}
	function isError(){
		if($this->connectError){
			return true;
		}
		$error = mysql_error($this->dbConn);
		if(empty($error)){
			return false;
		}
		else{
				return true;
		}
	}
}
class MySQLResult{
	var $mysql;
	var $query;
	var $result;
	function MySQLResult(&$mysql,$query){
		$this->mysql	= &$mysql;
		$this->query	= $query;
		$this->result	= '';
	}
	function fetch(){
		if($row = mysql_fetch_assoc($this->query)){
			return $row;
		}
		else if($this->size() > 0){
			return false;
		}
		else{
			return false;
		}
	}
	function fetch_row(){
		if($row = mysql_fetch_row($this->query)){
			return $row;
		}
		else if($this->size() > 0){
			return false;
		}
		else{
			return false;
		}
	}
	function lenght_field(){
		return  mysql_num_fields($this->query);;
	}
	function field_name($no){
		return mysql_field_name($this->query,$no);
	}
	function size(){
		return mysql_num_rows($this->query);
	}
	function tableResult(){
		$this->result = '<table><thead>';
		for ($i = 0; $i < $this->lenght_field(); $i++){
			$this->result .= '<th>'.$this->field_name($i).'</th>';
		}
		$this->result .= '</thead>';
		while ($row = $this->fetch_row()){
			$this->result .= '<tr>';
			for($i = 0; $i < $this->lenght_field();$i++){
				$this->result .= '<td>'.$row[$i].'</td>';
			}
			$this->result .= '</tr>';
		}
		$this->result .= '</tr></table>';
		return $this->result;
	}
	function isError(){
		return $this->mysql->isError();
	}
}
?>
