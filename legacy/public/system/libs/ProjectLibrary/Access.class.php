<?php
/**
 * This class defines the accesses
 * 
 */

class ProjectLibrary_Access extends Objeto{
	const USER_ADD = 1;
	const USER_EDIT = 2;
	const USER_SAVE =3;
	const USER_DELETE =4;
	const USER_UPDATE_SUCURSAL = 5;
	
	const RECONOCIMIENTO_ALL_PERMISSIONS = 20;
	
	public $permission;
	public $entity; 
	
	public function loadPermissions(){
		$userProfile = ProjectLibrary_FrontEnd_Util_UserSession::GetIdentity();
		$dao = new ProjectLibrary_SDO_Core_DAO_Permission();
		return $dao->loadProfile( $userProfile->getUserId() );
	}	

	public function deletePermission($arrPermissions){
		$permission = $this->permission;
		$arrP = array();
		foreach($arrPermissions as $permi){
			if( $permi != $permission){
				$arrP[] = $permi;
			}
		}
		return $arrP;
	}
	
	public function checkPermission($permit, $getOut = true){
		$this->permission = $permit;
		$access = $this->loadPermissions();
		$userProfile = ProjectLibrary_FrontEnd_Util_UserSession::GetIdentity();

		if($userProfile->getRole() == ProjectLibrary_SDO_User::ADMIN_ROLE || array_search($permit, $access) !== false){
		//if(array_search($permit, $access) !== false)
			return true;
		}else{
			if($getOut){
				//header( 'Location:index.php?redirect=' . urlencode( $_SERVER['REQUEST_URI'] ), false );
				ProjectLibrary_FrontEnd_BO_Authentification::goToFirstPage();
				die();
			}
			else return false;
		}
	}
}
?>