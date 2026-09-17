<?php

class Objeto {
		
  public function __construct(){
  }
  
  public function trans( $name ){
		$zendTranslate = new Zend_Translate( 'tmx', ABS_PATH . '/_languages/lang.xml', ProjectLibrary_SDO_Core_Application_LanguageManager::LanguageToZendLang());
  	$trans = $zendTranslate->_( $name );
		if(CHARSET_PROJECT == 'ISO-8859-1') {
			return utf8_decode($trans);
		}
		return $trans;
	}
  
}
?>