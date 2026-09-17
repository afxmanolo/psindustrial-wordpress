<?php

class DB_Connections_LocalHostDB extends DB_DBConnection  {

	protected $hostName = 'localhost';
	protected $username = 'root';
	protected $password = 'cyberal';
	protected $dbName = 'gopuebla';

	public function __construct(){
		parent::__construct();
	}
	
}

?>