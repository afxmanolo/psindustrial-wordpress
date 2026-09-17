<?php

class ProjectLibrary_Access_Categorias extends ProjectLibrary_Access {
	
	
	protected $application;
	function __construct( ProjectLibrary_FrontEnd_BO $app ){
		$this->application = $app;
	}

	public function loadPermissions(){
		$userProfile = ProjectLibrary_FrontEnd_Util_UserSession::GetIdentity();
		$permissions = parent::loadPermissions();
		$result = false;
		$app = $this->application;
		
		switch( $this->permission ){
			case self::CATEGORIAS_ALL_PERMISSIONS:
				$result = false;
			break;
			break;
			default:
			break;
		}
		
		if( $result ){
			$permissions[] = $this->permission;
		}else{
			$permissions = self::deletePermission($permissions);
		}
		return $permissions;
	}
	
	
}

?>
