<?php
class ProjectLibrary_SDO_Core_DAO_Categorias extends ProjectLibrary_SDO_Core_DAO {
	
	protected $table = 'categorias';
	protected $id_field = 'categorias_id';
	
	public function __construct(){
		parent::__construct( new ProjectLibrary_SDO_Core_DB() );
		$this->setSearchFields( array( "nombre","padre_id") );
	}
	
//	public function loadByColum1AndOtherColum2( $email ){
//		
//		$sql = "SELECT *"
//		     . " FROM   " . $this->table
//		     . " WHERE  colum1 = '" . $this->db->escapeString( $colum1 ) . "' 
//			AND colum2 = '" . . $this->db->escapeString( $colum2 ) . . "'";
//		return $this->db->sqlGetRecord( $sql ); 
//	}
	
	
}
?>