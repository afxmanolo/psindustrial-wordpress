<?php

class DB_DAO_FileDAO extends DB_DAO_AdvancedDAO {
	protected $table = 'file';
	protected $id_field	= 'id';
	
	public function __construct( DBPEAR $db ){
		parent::__construct( $db );
	}
	
	public function fileExists( $strSha1 ){
		$sql = "SELECT id 
						FROM " . $this->table . 
					" WHERE filename='" . $strSha1 . "'";
		
		$id = $this->db->sqlGetField( $sql );
		return ( !empty ( $id ) ) ? $id : 0 ;
		
	}
	
	public function delete( $ids, $deletePhysically = true ){
		if ( !is_array( $ids ) ){
			$ids = array( $ids ); 
		}
		
		foreach ( $ids as $id ){
			$file = $this->loadById( $id );
			if ( empty( $file ) ){
				continue;
			}
			$filename = '../' . $file['path'] . $file['filename']; 
			if ( $deletePhysically && file_exists( $filename ) ){
				// Delete file physically and from DB
				if ( unlink( $filename ) ){
					parent::delete( $id );
				}
				else {
					// Could not delete physically. Report error
					throw new Exception( "Could not delete file physically:" . $filename ); 
				}
			}
			else {
				// Delete file from DB only
				parent::delete( $id );
			}
		}
		
	}
	
	public function loadVersion( $id, $version = null, $loadStructureIfEmpty = false ){
		$sql = "SELECT 	* 
						FROM		`" . $this->table . "`
						WHERE	original_id = $id	";
		$sql .= $version ? " AND version = '$version'" : '';
		$obj = $this->db->sqlGetRecord( $sql );
		
		if ( empty( $obj ) && $loadStructureIfEmpty ){
			return $this->loadStructure();
		}
		else {
			return $obj;			
		}
	}
}
?>