<?php
abstract class DB_DBConnection extends Objeto{

	protected $hostName = null;
	protected $username = null;
	protected $password = null;
	protected $dbName = null; 
	
	public function __construct(){
		parent::__construct();
	}

	public function GetHostName(){
		return $this->hostName;
	}

	public function GetUserName(){
		return $this->username;
	}

	public function GetPassword(){
		return $this->password;
	}

	public function GetDBName(){
		return $this->dbName;
	}

	public function GetDSN( $protocol = 'mysql'){
		return $protocol . "://" . 
		       $this->GetUserName() . ":" . $this->GetPassword() . 
		       "@" . $this->GetHostName() . "/" . 
		       $this->GetDBName();
	}
}
?>