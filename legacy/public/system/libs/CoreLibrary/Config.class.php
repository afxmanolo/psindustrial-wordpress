<?php
/**
 * This class contains default configuration options
 * 
 * @author 
 * @copyright 
 */
class Config extends Objeto {

	public static function Initialize(){
		self::SetConstants();
		//self::redirecDomain();
	}
	
	public function redirecDomain(){
		$protocol 	= strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') === FALSE ? 'http' : 'https';
		$host		=  $_SERVER['HTTP_HOST'];
		$uri		= $_SERVER['REQUEST_URI'];
		$inFO		= strpos($uri, "backoffice") === false;
		
		$conWWW		= false;
		$conIndex	= false;
		
		$redirect	= false;
		
		if($conWWW && (strpos($host, 'www.') === false)){
			$host = "www.$host";
			$redirect = true;
		}elseif(!$conWWW && (strpos($host, 'www.') !== false)){
			$host =  str_replace('www.', '', $host);
			$redirect = true;
		}
		
		if($conIndex && array_search($uri, array(ABS_HTTP_PATH,'')) !== false && $inFO){
			$uri = ABS_HTTP_PATH."index.php";
			$redirect = true;
		}elseif(!$conIndex && strpos($uri, "index.php") !== false && $inFO){
			$uri = ABS_HTTP_PATH;
			$redirect = true;
		}
		
		if($redirect){
			$url = "{$protocol}://{$host}{$uri}";
			Header( "{$_SERVER['SERVER_PROTOCOL']} 301 Moved Permanently" );
			Header( "Location: $url" );
		}
	}
	
	public static function getGlobalValue($value = 'project_name'){
		$config = self::getGlobalConfiguration();
		return $config[$value];
	}
	
	public static function getGlobalConfiguration(){	    
	    if ( isset( $_SERVER['HTTP_HOST'] ) ){
	        $protocol 	= strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') === FALSE ? 'https' : 'https';
	    }else{
	        $protocol = 'https';
	    }
	    if (!defined ( 'TEST_EMAIL' )){
	        define('TEST_EMAIL', true);
	    }	    
		if(SERVER_DEVELOPMENT){
			$config["ABS_HTTP_URL"] = $protocol."://{$_SERVER['HTTP_HOST']}/macheteria/";
		}elseif(SERVER_DEMO){
			$config["ABS_HTTP_URL"] = $protocol."://{$_SERVER['HTTP_HOST']}/";
		}else{					
			$config["ABS_HTTP_URL"] = $protocol."://{$_SERVER['HTTP_HOST']}/";
		}
		
		$config["project_name"] 	= 'Puertas y Servicios Industriales | Admin Panel';
		$config["project_email"] 	= TEST_EMAIL ? 'user@domain.com' : 'user@domain.com';
		$config["email_root"] 		= 'user@domain.com';

		/*
		skin colors @link https://adminlte.io/themes/AdminLTE/documentation/index.html
		skin-blue, skin-blue-light, skin-yellow, skin-yellow-light, skin-green, skin-green-light, skin-purple, skin-purple-light, skin-red, skin-red-light, skin-black ,skin-black-light
		
		main bg color @link https://adminlte.io/themes/AdminLTE/documentation/index.html#component-info-box
		bg-yellow, bg-aqua, bg-green, bg-red, bg-blue, bg-orange, bg-purple, bg-gray
		*/
		$config["skin_color"]    = "skin-yellow";
		$config["main_bg_color"] = "bg-yellow";
		$config["box_color"]     = "box-primary";
		$config["info_color"]    = "bg-yellow";
		$config["btn-color"]     = "btn-success";
		
		/* Smtp Email Config */
		$config["smpt_server"]         = '';
		$config["smpt_user"]           = '';
		$config["smpt_pwd"]            = '';
		$config["smpt_mail_from"]      = '';
		$config["smpt_name_from"]      = '';
		$config["smpt_port_conection"] = '';
		$config["smpt_secure"]         = "";
		
		$config["project_domain"]      = $_SERVER['HTTP_HOST'];
		//$config["logo_login"]          = ABS_HTTP_URL."system/backoffice/img/logo_login.png";
		$config["logo_project"]        = ABS_HTTP_URL."system/backoffice/img/pleca.jpg";
		$config["logo_mobile_project"] = ABS_HTTP_URL."system/backoffice/img/logo-backoffice-mobile.jpg";
		$config["color_login"]         = "#1B2226";
		$config["bakground_logo"]      = "#1B2226";
		$config["color_th"]            = "#0050A5";
		$config["color_td"]            = "#008FD5";
				return $config;
	}
	
	protected  static function SetConstants(){
	    if ( isset( $_SERVER['HTTP_HOST'] ) ){
	        $protocol 	= strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') === FALSE ? 'https' : 'https';
	    }else{
	        $protocol = 'https';
	    }
	    
	    if ( isset( $_SERVER['HTTP_HOST'] ) ){
	        $serverDev =  self::IsServer( 'localhost', true )
	        || self::IsServer( '127.0.0.1', true )
	        || self::IsServer( 'juancarlos-pc', true );
	    }
	    else $serverDev = true;
		
		define( "SERVER_DEVELOPMENT", $serverDev );
		define( "SERVER_DEMO", self::IsServer( 'dominio.com.mx', true ) );
		define( "SERVER_PRODUCTION", self::IsServer( 'puertasyserviciosindustriales.com', true ));
		
		if ( SERVER_DEVELOPMENT ){
			define('ABS_HTTP_URL', $protocol."://{$_SERVER['HTTP_HOST']}/macheteria/" );
			define('ABS_HTTP_PATH', "/macheteria/" );
		}
		elseif ( SERVER_DEMO ){
			define('ABS_HTTP_URL', $protocol."://{$_SERVER['HTTP_HOST']}/macheteria/" );
			define('ABS_HTTP_PATH', "/macheteria/" );
			
		}elseif ( SERVER_PRODUCTION ){			
			define('ABS_HTTP_URL', $protocol."://$_SERVER[HTTP_HOST]/" );
			define('ABS_HTTP_PATH', "/" );		
			define('ABS_HTTP_IMAGEURL', $protocol."://$_SERVER[HTTP_HOST]/" );					
		}	
	}

	public static function IsServer( $httpHost = 'localhost', $isEqual = true ){
		if ( strpos( $_SERVER[ 'HTTP_HOST'], $httpHost ) !== false ){
			return ( $isEqual ) ? true : false;
		}
		else {
			return ( $isEqual ) ? false : true;
		}
	}
	
	
	public function sendEmail( $subject, $message, $email, $html = true){
		$config = config::getGlobalConfiguration();
		
		$asunto=$subject;
		
		$header ="From: " . $config['project_name'] . "<" . $config['project_email'] . ">\n";
		
		
		
		if($html){
			$header .="Content-Type: text/html; charset=utf-8\n";
			$body ="<font style='font-family:Arial,Helvetica,sans-serif;font-size:18px;color:#CC0000;font-weight:bold;'>". $config['project_name'] . "</font>\n" . $message;
		
			$body = '<p style="font-family:Arial;">' . nl2br($body) . '</p>';
		}else{
			$body =$message
			. "
			============================
			". $config['project_name'] ;
		} 
		$arrEmails = array();
		if( strpos($email, ',') ){
			$arrEmails = split(',',$arrEmails);
		}else{
			$arrEmails[] = $email;
			
		}
		$validator = new Validator_Test_Email();
		$result = count($arrEmails) > 0;
		foreach($arrEmails as $email){
			$email = strtolower($email);
			if( $validator->isValid($email) ){
				$result = $result && @mail($email, $asunto, $body, $header);
			}else{
				$result = false;
			}
		} 
		return $result;
	}
	public function sendAdminEmail( $subject, $message, $html = true){
		$config = config::getGlobalConfiguration();
		$email = $config["project_email"];
		
		return self::sendEmail( $subject, $message, $email, $html);
	}
	
}
?>