<?php
class ProjectLibrary_FrontEnd_BO_Authentification extends ProjectLibrary_FrontEnd_BO{
	const ERR_RESTRICTED_ACCESS = 303;
	public const ADMIN_ROLE = "Admin";
	const USER_ROLE = "User";
	const SUPER_ROLE = "Super";
	const NAMESPACE_SESSION = "ProjectLibrary_url_redirect_admin";
	
	public function __construct(){
		parent::__construct();
	}
	
	public function execute(){
		$cmd = $this->getCommand();		
		switch( $cmd ){			
			case 'login':
				$this->login( $_POST["username"], $_POST["password"]);
			break;
			
			case 'logout':
			ProjectLibrary_FrontEnd_Util_UserSession::DestroyIdentity();
			$this->redirect("index.php",false);
			break;
			
			default:
				if($this->verifyAuthentification(false)) {
					$this->tpl->display('main.php');
				} else{
					if( $this->isValidCode( $_REQUEST["errorCode"] ) ){
						
						$message = '<p><font color="#fff"><b>';
	
						switch( $_REQUEST["errorCode"] ){
							case ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_INVALID_PASSWORD:
							case ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_USER_NOT_FOUND:
								$message .= $this->tpl->trans('user_password_incorrect');
							break;
							
							case ProjectLibrary_FrontEnd_BO_Authentification::ERR_RESTRICTED_ACCESS:
								$message .= $this->tpl->trans('restricted_access');
							break;
							default:
								$message .= $this->tpl->trans('system_problem');
							break;
						}
						$message .= "</b></font></p>";
						
						$this->tpl->assign( 'error', $_REQUEST["errorCode"] );
						$this->tpl->assign( 'message', $message );
					}
	
					$this->tpl->display( "user/user_login.php" );
					break;
				}
		}
	}
	
	public function isValidCode( &$code ){
		$valid_codes = array();

		$valid_codes[] = ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_CASE_NOT_CONTEMPLATED;
		$valid_codes[] = ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_INVALID_PASSWORD;
		$valid_codes[] = ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_SQL_EXCEPTION;
		$valid_codes[] = ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_USER_NOT_FOUND;
		$valid_codes[] = ProjectLibrary_FrontEnd_BO_Authentification::ERR_RESTRICTED_ACCESS;
				
		return ( isset($code) & is_numeric( $code ) && in_array( $code, $valid_codes ) ); 	
	}
	
	public function login( $user, $password){
		try {		    
			$password = md5($password);					
			$id = ProjectLibrary_SDO_Security::Authenticate( $user, $password);	
			
			$userProfile = new ProjectLibrary_Entity_User();			
			$userProfile = ProjectLibrary_SDO_User::LoadUser( $id );
			
			if( $userProfile->getRole() == ProjectLibrary_FrontEnd_BO_Authentification::ADMIN_ROLE || $userProfile->getRole() == ProjectLibrary_FrontEnd_BO_Authentification::USER_ROLE || $userProfile->getRole() == ProjectLibrary_FrontEnd_BO_Authentification::SUPER_ROLE ){
				ProjectLibrary_FrontEnd_Util_UserSession::SetIdentity( $userProfile );								
				$this->goToFirstPage();
			}
			else{			    
				$this->redirect("index.php?errorCode=" . ProjectLibrary_FrontEnd_BO_Authentification::ERR_RESTRICTED_ACCESS,false);
			}
		}
		catch(ProjectLibrary_SDO_Core_Application_Security_Exception $e){
			$this->redirect("index.php?errorCode=" . $e->getCode(),false);
		}
		
	}
	
	public static function verifyAuthentification( $dieAndRedirect = true ){
		
		$userProfile = ProjectLibrary_FrontEnd_Util_UserSession::GetIdentity();
		
		if( !isset($userProfile)  || ($userProfile->getRole() != ProjectLibrary_FrontEnd_BO_Authentification::ADMIN_ROLE && $userProfile->getRole() != ProjectLibrary_FrontEnd_BO_Authentification::USER_ROLE && $userProfile->getRole() != ProjectLibrary_FrontEnd_BO_Authentification::SUPER_ROLE )) {  			
  			if( $dieAndRedirect ){
  				ProjectLibrary_FrontEnd_Util_Session::Set( ProjectLibrary_FrontEnd_BO_Authentification::NAMESPACE_SESSION,  "http://" . $_SERVER['SERVER_NAME'] . $_SERVER["PHP_SELF"] . ( ( isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']) ) ?  "?" . $_SERVER['QUERY_STRING'] : "" )  );
  				ProjectLibrary_FrontEnd_BO_Authentification::redirect("index.php?errorCode=" . ProjectLibrary_FrontEnd_BO_Authentification::ERR_RESTRICTED_ACCESS,false);
  			}
  			else{
  				return false;
  			}
  		}  		
		return true;
		
	}

	public function goToFirstPage(){
	   
		$url = ProjectLibrary_FrontEnd_Util_Session::Get( ProjectLibrary_FrontEnd_BO_Authentification::NAMESPACE_SESSION );
		
		if( ProjectLibrary_FrontEnd_BO_Authentification::verifyAuthentification( false ) && isset( $url ) ){
			ProjectLibrary_FrontEnd_Util_Session::Delete( ProjectLibrary_FrontEnd_BO_Authentification::NAMESPACE_SESSION );
		}
		else{
			$url = "index.php";			
		}

		ProjectLibrary_FrontEnd_BO_Authentification::redirect( $url,false);
	}
	
}
?>