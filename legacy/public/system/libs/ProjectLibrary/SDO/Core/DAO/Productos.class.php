<?php
class ProjectLibrary_SDO_Core_DAO_Productos extends ProjectLibrary_SDO_Core_DAO {
	
	protected $table = 'productos';
	protected $id_field = 'productos_id';
	
	public function __construct(){
		parent::__construct( new ProjectLibrary_SDO_Core_DB() );
		$this->setSearchFields( array( "nombre","descripcion") );
	}
	
	public function loadProductos(){	    
	    $sql = "SELECT * FROM ".$this->table." ORDER BY productos_id DESC LIMIT 0,9";	    
	    return $this->db->sqlGetResult( $sql );	   
	}
}
?>