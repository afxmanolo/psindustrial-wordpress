<?php
class ProjectLibrary_SDO_Core_DB extends DBPEAR {
	public function __construct(){
		if ( SERVER_DEVELOPMENT ){
			$dsn = "mysqli://root@localhost/desarrollo";
		} 
		elseif ( SERVER_DEMO ){
		    $dsn = "mysqli://sneintel_machete:macheteria2020$.@localhost/sneintel_macheteria";			
		} 
		elseif ( SERVER_PRODUCTION ){
		    $dsn = "mysqli://psindustrial_db:qwdfvb@localhost/psindustrial_db";
		} 
		else{
			$dsn = "";
		}
		
		parent::__construct( $dsn . "?new_link=true" );
	}
}
?>