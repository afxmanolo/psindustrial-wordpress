<?php
class ProjectLibrary_SDO_IdHandler{
	
	public static function GetIdsHanlderObject( $imgName ){
		return new ProjectLibrary_SDO_Core_Application_IdsHandler($imgName);
	}
	
}
?>