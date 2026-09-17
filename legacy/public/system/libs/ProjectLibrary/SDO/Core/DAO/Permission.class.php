<?php
class ProjectLibrary_SDO_Core_DAO_Permission extends ProjectLibrary_SDO_Core_DAO {

	protected $table = 'permission';
	protected $id_field = 'permission_id';

	public function __construct(){
		parent::__construct( new ProjectLibrary_SDO_Core_DB() );
	}
	public function loadProfile($userId = 0){
		$ssql = "SELECT permission_id FROM {$this->table} WHERE user_id = '$userId'";
		
		return $this->db->sqlGetArray($ssql);		
	}
	public function savePermition($userId = 0, $permits = ''){
		if(!empty($userId)){
			$ssql = "DELETE FROM {$this->table} WHERE user_id = $userId";
			if(count($permits)) $ssql .= " AND permission_id NOT IN (".implode(',',$permits) . ")";
			
			$this->db->sqlExecute($ssql);			
			
			foreach ($permits as $permit){
				$ssql = "REPLACE INTO {$this->table} SET user_id = $userId, permission_id = $permit;";
				$this->db->sqlExecute($ssql);			
			}
		}
	}	
}
?>