<?php

class ProjectLibrary_Access_User extends ProjectLibrary_Access {
	
	
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
			case self::USER_DELETE:
				$result = true;
				$entity = $app->getParameter('entity',false,array());
				//we need to check if the user is of the supervisor aloe
				if( !empty($entity["user_id"]) ){
					$users = ProjectLibrary_SDO_User::getUsersIdBySupervisorAloe( $userProfile->getUserId() );
					$resultTmp = in_array($entity["user_id"], $users) || $userProfile->getUserId() == $entity["user_id"];
					$result = $result && $resultTmp;
				}
			break;
			case self::USER_ADD:
				$result = true;
			break;
			case self::USER_EDIT:
				$result = true;
			break;
			case self::USER_SAVE:
				$result = true;
				//only supervisor aloe can save users of course administrator too, but this validation is on the parent class in checkPermission method
				$resultTmp = $userProfile->getRole() == ProjectLibrary_SDO_User::SUPERVISOR_ALOE;
				$result = $result && $resultTmp;
				
				
				$userRoles = ProjectLibrary_SDO_Core_Application_User::getUsuariosColaboradores();
				$entity = $app->getParameter('entity',false,array());

				$resultTmp = in_array($entity["role"], $userRoles) ||
				($entity["role"] == ProjectLibrary_SDO_User::SUPERVISOR_ALOE && $userProfile->getUserId() == $entity["id"]);
				
				$result = $result && $resultTmp;
				
				//validate the sucursal id
				$sucursales = ProjectLibrary_SDO_Sucursal::getSucursalesIdBySupervisorAloe( $userProfile->getUserId() );
				$resultTmp = in_array($entity["sucursal_id"], $sucursales) || empty($entity["sucursal_id"]);
				$result = $result && $resultTmp;
				
				//we need to check the reconocimiento is empty
				$resultTmp = empty($entity["reconocimiento"]);
				$result = $result && $resultTmp;
				
				//we need to check the status is empty
				$resultTmp = empty($entity["status"]);
				$result = $result && $resultTmp;
				
				//if edit check if the user is of the supervisor aloe
				if( !empty($entity["user_id"]) ){
					$users = ProjectLibrary_SDO_User::getUsersIdBySupervisorAloe( $userProfile->getUserId() );
					$resultTmp = in_array($entity["user_id"], $users) || $userProfile->getUserId() == $entity["user_id"];
					$result = $result && $resultTmp;
				}
				
			break;
			case self::USER_UPDATE_SUCURSAL:
				$sucursalId = $app->getParameter("value");
				$userId = $app->getParameter();
				
				//validate the sucursal id
				$sucursales = ProjectLibrary_SDO_Sucursal::getSucursalesIdBySupervisorAloe( $userProfile->getUserId() );
				$result = in_array($sucursalId, $sucursales) || empty($sucursalId);

				//validate user id is of the supervisor aloe
				$users = ProjectLibrary_SDO_User::getUsersIdBySupervisorAloe( $userProfile->getUserId() );
				$resultTmp = in_array($userId, $users);
				$result = $result && $resultTmp;
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
