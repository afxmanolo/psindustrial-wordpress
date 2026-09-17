<?php

class ProjectLibrary_SDO_Core_Application_LanguageManager extends ProjectLibrary_SDO_Core_Application {
	
	const DEFAULT_LANGUAGE = 'SP';
	const SESSION_NAMESPACE = 'LanguageManager';
	const LANGUAGE_SPANISH ='SP';
	const FILE_MANAGER = 'changeLanguage.php';
	
	
	public static function SetActualLanguage( $language ){
		$language = self::GetValidLanguage( $language );
		ProjectLibrary_FrontEnd_Util_Session::Set( self::SESSION_NAMESPACE, $language['id'] );
	}
	
	public static function GetActualLanguage(){
		//get the actual language of the session 
		//if session says null then return the defaultLanguage
		//pendient to programming
		$tmp = ProjectLibrary_FrontEnd_Util_Session::Get( self::SESSION_NAMESPACE );
		if ( !empty( $tmp ) ){
			$language = self::GetValidLanguage( $tmp );
			$language = $language[ "id" ];
		}else{
			$language = self::DEFAULT_LANGUAGE;
		}
		$language = ( empty($language) ) ? self::DEFAULT_LANGUAGE : $language;
		return $language;
	}
	
	public static function GetValidLanguage( $language ){
		$languages = self::GetLanguages();
		return ( !array_key_exists( $language, $languages) ) ? $languages[ self::DEFAULT_LANGUAGE ] : $languages[ $language ];
	}
	
	public static function LanguageToZendLang( $lang = 'EN' ){
		$lang = empty($lang) ? self::GetActualLanguage() : $lang;
		switch( $lang ){
			case "SP":$lang = "es";break;
			case "EN":
			default:
				$lang = "en";
			break;
		}
		return $lang;
	}
	
	public static function GetLanguages(){
		return array( 'EN'=>array("id"=>"EN", "name"=>"English"), 
					  'SP'=>array("id"=>"SP", "name"=>"Espa&ntilde;ol")
		);
	}
	
	public static function CreateSelectHTML( $params , $class ){
		$ArrLanguages = self::GetLanguages();
		$actualLanguage = self::GetActualLanguage();
		
		//$relativeReference = $relativeReference . self::FILE_MANAGER;
		
		$html = '<select name="language" ' . $params . ' class="' . $class . '">';
		foreach( $ArrLanguages as $language){
			$html .= "<option value='{$language["id"]}'";
			if( $actualLanguage == $language["id"] ){
				$html .= " selected='selected'";
				
			}
			$html .= ">{$language["name"]}";
			if($language["id"] == self::DEFAULT_LANGUAGE){
				$html .=" - Default";
			}
			$html .= "</option>";
		}
		$html .= "</select>";
		
		return $html;
	}
	
		public static function GetLanguageActualIP(){
		$ip = new Net_IPInfo();
		$lang = "SP";
		
		if( $ip->getCountryCode() == 'US' || $ip->getCountryCode() == 'CA'){
			$lang = "EN";
		}
		return $lang;
	}
}
?>