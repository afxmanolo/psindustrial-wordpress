<?php
class Authentification extends Application {

	protected $deny_url = 'login_form.php';
	protected $granted_url;
	
	public function __construct( $granted_url ){
		parent::__construct();
		$this->granted_url = $granted_url;
		
	}
	
	public function execute(){
		$cmd = parent::getCommand();
		switch ( $cmd ){
			case 'logout':
				$this->logout();
				break;
			case 'Remind Password':
			case 'remind':
				$this->remindPassword();
				break;
			case 'Log in':
			case 'login':
			default: 
				$this->login();
				break;
		}
	}
	
	protected function login(){
		if ( $this->auth() ){
			$this->redirect( $this->granted_url, false );
		}
		else {
			$this->denyAcces();
		}
	}
	
	protected function showForm(){
		$this->redirect( $this->deny_url, false );
	}
	
	protected function denyAcces(){
		Request::setAttribute( "Error", "Authentification failed. Please try again." );
		$this->redirect( $this->deny_url, false );
	}
	
	protected function auth(){
		$user = $_REQUEST[ 'username' ];
		$pass = $_REQUEST[ 'password' ];
		if ( $user == 'admin' && $pass == 'admin' ){
			$_SESSION[ 'user' ] = array();
			$_SESSION[ 'user' ][ 'username' ] = $user;
			$_SESSION[ 'user' ][ 'password' ] = sha1( $pass );
			$_SESSION[ 'user' ]['IP'] = $_SERVER['REMOTE_ADDR'];
			return true;
		}
		return false;
	}
	
	protected function logout(){
		session_destroy();
		header( 'Location: ' . $this->deny_url );
	}
	
	protected function remindPassword(){
		// This function should be implemented in a child object. 
		// Abstract function here declaration corrupted the whole class 
	}
}
?>