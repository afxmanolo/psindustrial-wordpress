<?php
class ProjectLibrary_SDO_Core_Application_Security_Authentication extends ProjectLibrary_SDO_Core_Application {
	
	/**
	 * Authentication for an user
	 *
	 * @param string $username
	 * @param string $password
	 * @return int - Profile ID
	 */
	public static function Auth( $username, $password ){		
		try {
		  echo "<pre>";
			$dao = new ProjectLibrary_SDO_Core_DAO_User();
			
			$arrProfile = $dao->loadByEmail( $username );
			
			if ( $arrProfile == null ){ 
				$arrProfile = array();
			}
			
			$objProfile = ProjectLibrary_SDO_Core_Util_EntityManager::ParseArrayToObject(
				$arrProfile, new ProjectLibrary_Entity_User()
			);
			
			// Auth logic
			$profileId = $objProfile->getUserId();
			$profilePassword = $objProfile->getPassword();
			//$ip_conexion = ProjectLibrary_SDO_Core_Application_Log::getIP(); 
			//user exits condition			
			if ( empty( $profileId ) ){
			    //ProjectLibrary_SDO_Core_Application_Log::doLog($ip_conexion,$username,"auth","UserNotfound",'1');
				throw new ProjectLibrary_SDO_Core_Application_Security_Exception( 
					'Username not found', 
					ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_USER_NOT_FOUND 
				);
				
			}
			elseif( !( $profilePassword === $password ) ){			    
				//password incorrect condition
				throw new ProjectLibrary_SDO_Core_Application_Security_Exception( 
					'Password invalid', 
					ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_INVALID_PASSWORD 
				);
			}
			elseif ( !empty($profileId) && $profilePassword === $password ){
				return $profileId;
			}
			else {
			    //ProjectLibrary_SDO_Core_Application_Log::doLog($ip_conexion,$username,"auth","LoginFail",'1');
				//user exits condition
				throw new ProjectLibrary_SDO_Core_Application_Security_Exception( 
					'Case not contemplated', 
					ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_CASE_NOT_CONTEMPLATED
				);
			}
		}
		catch( SQLException $e ){		   
	 		throw new ProjectLibrary_SDO_Core_Application_Security_Exception( 'Authentication process faield. See nested Exception', 
			ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_SQL_EXCEPTION, $e );
		}
		catch( ProjectLibrary_SDO_Core_Application_Security_Exception $e ){
			throw $e;
		}
		catch( Exception $e ){
			throw new ProjectLibrary_SDO_Core_Application_Security_Exception( 'Auth exception not expected. See nested Exception', 
				ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_CASE_NOT_CONTEMPLATED, $e );
		}
	}
	

	/**
	 * Authentication for an user
	 *
	 * @param string $username
	 * @param string $password
	 * @return int - Profile ID
	 */
	public static function AuthGenericUser( $username, $password, $typeOfUser ){
		
		try {
			
			if( $typeOfUser=='profile'){
				$dao = new ProjectLibrary_SDO_Core_DAO_Profile();
			}else{
				$dao = new ProjectLibrary_SDO_Core_DAO_Member();				
			}
			
			$arrUser = $dao->loadByEmail( $username );
			if ( $arrUser == null ){ 
				$arrUser = array();
			}
			
			if( $typeOfUser=='profile'){
				$objUser = ProjectLibrary_SDO_Core_Util_EntityManager::ParseArrayToObject(
					$arrUser, new ProjectLibrary_Entity_User_Profile()
				);
				// Auth logic
				$userId = $objUser->getProfileId();
				$userPassword = $objUser->getPassword();
			}
			else{
				$objUser = ProjectLibrary_SDO_Core_Util_EntityManager::ParseArrayToObject(
					$arrUser, new ProjectLibrary_Entity_User_Member()
				);
				$userId = $objUser->getMemberId();
				$userPassword = $objUser->getPassword();
			}

			//user exits condition
			if ( empty( $userId ) ){
				
				throw new ProjectLibrary_SDO_Core_Application_Security_Exception( 
					'Username not found', 
					ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_USER_NOT_FOUND 
				);
				
			}
			elseif( !( $userPassword === $password ) ){
				//password incorrect condition
				throw new ProjectLibrary_SDO_Core_Application_Security_Exception( 
					'Password invalid', 
					ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_INVALID_PASSWORD 
				);
			}
			elseif ( !empty($userId) && $userPassword === $password ){
				return $userId;
			}
			else {
				//user exits condition
				throw new ProjectLibrary_SDO_Core_Application_Security_Exception( 
					'Case not contemplated', 
					ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_CASE_NOT_CONTEMPLATED
				);
			}
		}
		catch( SQLException $e ){
	 		throw new ProjectLibrary_SDO_Core_Application_Security_Exception( 'Authentication process faield. See nested Exception', 
			ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_SQL_EXCEPTION, $e );
		}
		catch( ProjectLibrary_SDO_Core_Application_Security_Exception $e ){
			throw $e;
		}
		catch( Exception $e ){
			throw new ProjectLibrary_SDO_Core_Application_Security_Exception( 'Auth exception not expected. See nested Exception', 
				ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_CASE_NOT_CONTEMPLATED, $e );
		}
	}
}
?>
