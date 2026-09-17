<?php
abstract class ProjectLibrary_FrontEnd_FO extends Application {

	/**
	 * Template Engine System provided by PEAR::Savant3
	 *
	 * @var Savant3
	 */
	protected $tpl;

	public function __construct(){
		parent::__construct();
		define('FRONT_END','FO');
		
		self::validateURL(true);
		
		//$this->setPermissionStrategy( new ProjectLibrary_Security_FO_PermissionStrategy( $this, '' ) );
		//$this->tpl = new Savant3();
		$lang = $this->getParameter('lang',false,NULL);
		if(!empty($lang)){
			ProjectLibrary_SDO_Core_Application_LanguageManager::SetActualLanguage( $lang );
		}
		$lang = ProjectLibrary_SDO_Core_Application_LanguageManager::GetActualLanguage();
		$this->tpl = new ProjectLibrary_Savant();
	}
	
	public function validateURL($redirect404 = false){
		$status 				= true;
		$url 					= $_SERVER['REQUEST_URI'];
		$path					= str_replace('/', '\/', ABS_HTTP_PATH);
		$er['fotos_list']		= "/^{$path}fotos\/([\d]+)\/$/";
		$er['fotos_view']		= "/^{$path}([\d]+)\/fotos\/(.*)\/$/";
		$er['noticias_list']	= "/^{$path}noticias\/([\d]+)\/$/";
		$er['noticias_view']	= "/^{$path}([\d]+)\/noticia\/(.*)\/$/";
		$er['eventos_list']		= "/^{$path}eventos\/([\d]+)\/$/";
		$er['eventos_view']		= "/^{$path}([\d]+)\/evento\/(.*)\/$/";
		$er['calendario_list']	= "/^{$path}calendario-institucional\/([\d]+)\/$/";
		$er['calendario_view']	= "/^{$path}([\d]+)\/calendario-institucional\/(.*)\/$/";
		
		
		$i =0;
		foreach($er as $seccion => $expresion){
			if(preg_match($expresion, $url)){$ok = true;break;}
			$i++;
		}
		
		$seccion = $ok ? $seccion : NULL;
		
		switch ($seccion){
			case 'fotos_list':
				$urlReal = str_replace(ABS_HTTP_URL, ABS_HTTP_PATH, ABS_HTTP_URL."fotos/{$this->getParameter('p',true,1)}/");
				break;
			case 'fotos_view':
				$urlReal = str_replace(ABS_HTTP_URL, ABS_HTTP_PATH, ProjectLibrary_SDO_Fotogaleria::LoadFotogaleria($this->getParameter())->getFriendlyNameUrl());
				break;
			case 'noticias_list':
				$urlReal = str_replace(ABS_HTTP_URL, ABS_HTTP_PATH, ABS_HTTP_URL."noticias/{$this->getParameter('p',true,1)}/");
				break;
			case 'noticias_view':
				$urlReal = str_replace(ABS_HTTP_URL, ABS_HTTP_PATH, ProjectLibrary_SDO_Noticia::LoadNoticia($this->getParameter())->getFriendlyNameUrl());
				break;
			case 'eventos_list':
				$urlReal = str_replace(ABS_HTTP_URL, ABS_HTTP_PATH, ABS_HTTP_URL."eventos/{$this->getParameter('p',true,1)}/");
				break;
			case 'calendario_list':
				$urlReal = str_replace(ABS_HTTP_URL, ABS_HTTP_PATH, ABS_HTTP_URL."calendario-institucional/{$this->getParameter('p',true,1)}/");
				break;
			case 'eventos_view':case 'calendario_view':
				$urlReal = str_replace(ABS_HTTP_URL, ABS_HTTP_PATH, ProjectLibrary_SDO_Evento::LoadEvento($this->getParameter())->getFriendlyNameUrl());
				break;
			default:
				$urlReal = $url; 
				break;
		}
		
		if($url != $urlReal){
			if($redirect404){
				header('HTTP/1.0 404 Not Found');
  				include_once ('404page.php');
				die;
			}
			else $status = false;
		}
		
		return $status;
	}
	
	public function checkPermission(){
		/*
		$cmd = $this->getCommand();
		if ( !$this->hasPermission( $cmd ) ){
			echo '<script language="javascript">'
			   . 'alert("You need to be logged in to proceed");' . "\n"
			   . 'window.location.href="index.php";' . "\n"
			   . '</script>';
			die();
		}*/
	}
}
?>