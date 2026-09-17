<?php
abstract class ProjectLibrary_FrontEnd_BO extends Application {

	/**
	 * Template Engine System provided by PEAR::Savant3
	 *
	 * @var Savant3
	 */
	protected $tpl;

	public function __construct(){
		parent::__construct();
		define('FRONT_END','BO');
		//$this->setPermissionStrategy( new ProjectLibrary_Security_BO_PermissionStrategy( $this, '' ) );
		
		$lang = $this->getParameter('lang',false,NULL);
		if(!empty($lang)){
			ProjectLibrary_SDO_Core_Application_LanguageManager::SetActualLanguage( $lang );
		}
		$lang = ProjectLibrary_SDO_Core_Application_LanguageManager::GetActualLanguage( );
		
		$this->tpl = new ProjectLibrary_Savant();
		
		//$this->tpl = new Savant3();
	}

	public function checkPermission(){
		//ProjectLibrary_FrontEnd_BO_Authentification::verifyAuthentification();
		/*$cmd = $this->getCommand();
		if ( !$this->hasPermission( $cmd ) ){
			$this->redirect( 'index.php?redirect=' .
			                 urlencode( $_SERVER['REQUEST_URI'] ), false );
		}*/
	}
}
?>