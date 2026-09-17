<?php
class LoginApplication extends Objeto {

	public function __construct(){
		parent::__construct();
	}
	
	public function execute(){
		
		$urlGranted = isset( $_REQUEST['redirect'] ) ? $_REQUEST['redirect'] : 'main.php';
		
		$tpl = new Savant3();
		$tpl->assign( 'error', '' );
		$tpl->assign( 'urlGranted', $urlGranted );
		
		$tpl->display( 'user_login.php' );
	}
}

?>
