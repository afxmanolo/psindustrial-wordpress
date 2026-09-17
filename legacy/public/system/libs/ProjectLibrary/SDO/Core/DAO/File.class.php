<?php
class ProjectLibrary_SDO_Core_DAO_File extends DB_DAO_FileDAO {
	
	public function __construct(){
		parent::__construct( new ProjectLibrary_SDO_Core_DB() );
	}
	
	public function getFileByVersion($id,$version = 'small'){
		$sql = "SELECT * 
				FROM `{$this->table}` 
				WHERE version = '$version' AND original_id = $id";
		return $this->db->sqlGetRecord($sql);
	}
}
?>