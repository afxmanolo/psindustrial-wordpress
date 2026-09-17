<?php
class ProjectLibrary_SDO_Core_DAO_User extends ProjectLibrary_SDO_Core_DAO {
	
	protected $table = 'user';
	protected $id_field = 'user_id';
	public $md5_fields = 'password';
	
	public function __construct(){
		parent::__construct( new ProjectLibrary_SDO_Core_DB() );
		$this->setSearchFields( array( 'first_name', 'last_name', 'email', 'role' ) );
	}
	
	public function loadByEmail( $email ){
		
		$sql = "SELECT *"
		     . " FROM   " . $this->table
		     . " WHERE  email = '" . $this->db->escapeString( $email ) . "'";
		return $this->db->sqlGetRecord( $sql ); 
	}
	
	
}
?>