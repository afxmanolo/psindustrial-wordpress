<?php
class PermissionStrategy extends Objeto {
	
	protected $application;
	protected $cmd;
	
	public function __construct( Application $app, $cmd = '' ){
		parent::__construct();
		$this->application = $app;
		$this->cmd = $cmd;
	}
	
	public function setCommand( $cmd = '' ){
		$this->cmd = $cmd;
	}
	
	public function isGranted(){
		$user     = isset( $_SESSION[ 'user' ][ 'username' ] ) ? 
		                   $_SESSION[ 'user' ][ 'username'] : '';
		$password = isset( $_SESSION[ 'user' ][ 'password' ] ) ? 
		                   $_SESSION[ 'user' ][ 'password'] : '';
		$ip       = isset( $_SESSION[ 'user' ][ 'IP' ] ) ? 
		                   $_SESSION[ 'user' ][ 'IP'] : '';
		
		if ( $user == 'admin' && $password == sha1( 'admin' ) && 
		     $ip == $_SERVER['REMOTE_ADDR'] ){
			return true;
		}
		else {
			return false;
		}
	}
	
}
?>