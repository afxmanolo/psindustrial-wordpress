<?php
abstract class ProjectLibrary_SDO_Core_DAO extends DB_DAO_AdvancedDAO{
	public function __construct(){
		parent::__construct( new ProjectLibrary_SDO_Core_DB() );
	}
	
}
?>