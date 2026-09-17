<?php
class ProjectLibrary_FrontEnd_FO_Authentification extends ProjectLibrary_FrontEnd_BO{
	const ERR_RESTRICTED_ACCESS = 303;
	const ADMIN_ROLE = ProjectLibrary_SDO_Core_Application_User::MEMBER_ROLE;
	const NAMESPACE_SESSION = "ProjectLibrary_url_redirect_member";
	const NOT_EQUAL_PASSWORDS = 1010;
	const PASSWORDS_OK = 1011;
	
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
				
				if( $this->isValidCode( $_REQUEST["errorCode"] ) ){
					
					$message = '<p><font color="#AA0000"><b>';

					switch( $_REQUEST["errorCode"] ){
						case ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_INVALID_PASSWORD:
						case ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_USER_NOT_FOUND:
							$message .= 'Usuario o Contraseña incorrecta';
						break;
						
						case self::ERR_RESTRICTED_ACCESS:
							$message .= 'Acceso Restringido';
						break;
						default:
							$message .= 'Hay un problema con el sistema intente nuevamente';
						break;
					}
					$message .= "</b></font></p>";
					
					$this->tpl->assign( 'error', $_REQUEST["errorCode"] );
					$this->tpl->assign( 'message', $message );
				}

				$this->tpl->display( "user_login.php" );
			break;
			
			case 'send_password':
				$this->recoverPassword( $_POST["username"]);
			case 'recoverPwd':
				$this->tpl->display( "send_pwd.php" );
			break;
			
			case 'savePwd':
				$this->savePwd();

				if( $this->isValidCode( $this->tpl->errorCode ) || $this->tpl->errorCode == self::NOT_EQUAL_PASSWORDS || $this->tpl->errorCode == self::PASSWORDS_OK){
					
					$message = ($this->tpl->errorCode == self::PASSWORDS_OK) ? '<p><font color="#00AA00"><b>' : '<p><font color="#AA0000"><b>';
					
					
					switch( $this->tpl->errorCode ){
						case self::PASSWORDS_OK:
							$message .= 'Su contraseña ha sido cambiada';
						break;
						case self::NOT_EQUAL_PASSWORDS:
							$message .= 'Su contraseña es incorrecta o no coinciden las contraseñas nuevas';
						break;
						case ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_INVALID_PASSWORD:
						case ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_USER_NOT_FOUND:
							$message .= 'Contraseña incorrecta';
						break;
						
						case self::ERR_RESTRICTED_ACCESS:
							$message .= 'Acceso Restringido';
						break;
						default:
							$message .= 'Hay un problema con el sistema intente nuevamente';
						break;
					}
					$message .= "</b></font></p>";
					
					$this->tpl->assign( 'error', $this->tpl->errorCode );
					$this->tpl->assign( 'message', $message );
				}
			case 'ChangePwd':
				$this->tpl->display( "user_changepwd.php" );
			break;
		}
	}
	
	public function savePwd(){
		
		try {
			$userProfile = ProjectLibrary_FrontEnd_Util_UserSession::GetIdentity();

			$pass = $_POST["password"];
			$pass1 = $_POST["password1"];			
			$pass2 = $_POST["password2"];			

			if( $userProfile->getPassword() == $pass && $pass2 == $pass1 && !empty($pass2) ){
				$userProfile->setPassword( $pass1 );
				$result = ProjectLibrary_SDO_User::SaverUserProfile( $userProfile );
				ProjectLibrary_FrontEnd_Util_UserSession::SetIdentity( $result );

				$this->tpl->assign("errorCode", self::PASSWORDS_OK );
			}else{
				
				$this->tpl->assign("errorCode", self::NOT_EQUAL_PASSWORDS );
			}
		}
		catch(Exception $e){
			$this->tpl->assign("errorCode", $e->getCode() );
		}
		
	}
	
	public function recoverPassword( $userEMail ){
		$member = ProjectLibrary_SDO_User::LoadUserByEmail( $userEMail );
		
		$asunto='Restauracion de clave';
		$header ="From: Montessori <admin@montessori.com.mx>\n";
		$body ="Sus datos de acceso son los siguientes\n\n"
		. "Usuario: ".$member->getEmail()."\n"
		. "Clave: ".$member->getPassword()."\n\n"
		. "============================\n"
		. "Sistema Montessori";
		if( @mail($member->getEmail(), $asunto, $body, $header) ){
			$this->tpl->assign("message","Su contrase&ntilde;a ha sido recuperada, en unos minutos recibir&aacute; un email en su correo electr&oacute;nico<br /><br />");
		}else{
			$this->tpl->assign("message","Intente Nuevamente");
		}
	}
	
	public function isValidCode( &$code ){
		$valid_codes = array();

		$valid_codes[] = ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_CASE_NOT_CONTEMPLATED;
		$valid_codes[] = ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_INVALID_PASSWORD;
		$valid_codes[] = ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_SQL_EXCEPTION;
		$valid_codes[] = ProjectLibrary_SDO_Core_Application_Security_Exception::ERR_USER_NOT_FOUND;
		$valid_codes[] = self::ERR_RESTRICTED_ACCESS;
				
		return ( isset($code) & is_numeric( $code ) && in_array( $code, $valid_codes ) ); 	
	}
	
	public function login( $user, $password){
		
		try {
			$id = ProjectLibrary_SDO_Security::Authenticate( $user, $password);
			
			$userProfile = new ProjectLibrary_Entity_User();
			
			$userProfile = ProjectLibrary_SDO_User::LoadUser( $id );

			if( $userProfile->getRole() == self::ADMIN_ROLE ){
				ProjectLibrary_FrontEnd_Util_UserSession::SetIdentity( $userProfile );
				$this->goToFirstPage();
			}
			else{
				$this->redirect("index.php?errorCode=" . self::ERR_RESTRICTED_ACCESS,false);
			}
		}
		catch(ProjectLibrary_SDO_Core_Application_Security_Exception $e){
			$this->redirect("index.php?errorCode=" . $e->getCode(),false);
		}
		
	}
	
	public function verifyAuthentification( $dieAndRedirect = true ){
		
		$userProfile = ProjectLibrary_FrontEnd_Util_UserSession::GetIdentity();
		
  		if( !isset($userProfile)  || $userProfile->getRole() != self::ADMIN_ROLE ) {
  			
  			if( $dieAndRedirect ){
  				ProjectLibrary_FrontEnd_Util_Session::Set( self::NAMESPACE_SESSION,  "http://" . $_SERVER['SERVER_NAME'] . $_SERVER["PHP_SELF"] . ( ( isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']) ) ?  "?" . $_SERVER['QUERY_STRING'] : "" )  );
  				self::redirect("index.php?errorCode=" . self::ERR_RESTRICTED_ACCESS,false);
  			}
  			else{
  				return false;
  			}
  		}
		return true;
		
	}

	public function goToFirstPage(){
		$url = ProjectLibrary_FrontEnd_Util_Session::Get( self::NAMESPACE_SESSION );
	
		if( self::verifyAuthentification( false ) 
		&& isset( $url ) ){
			ProjectLibrary_FrontEnd_Util_Session::Delete( self::NAMESPACE_SESSION );
		}
		else{
			$url = "mainMember.php";			
		}

		self::redirect( $url,false);
	}
	
}
?>