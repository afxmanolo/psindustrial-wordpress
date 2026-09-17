<?php
class ProjectLibrary_Savant extends Savant3 {
	
	/**
	 * Zend Translation engine
	 *
	 * @var Zend_Translate
	 */
	protected $zendTranslate;
	protected $pathExecute = '';
	
	public function __construct( ){
		parent::__construct();
		
		$lang = ProjectLibrary_SDO_Core_Application_LanguageManager::LanguageToZendLang();
		//$this->setPath( 'template', array( ABS_PATH . '/view/'  ) );
		$this->setPath( 'template', array( ABS_PATH . '/view'  ) );

		$this->zendTranslate = new Zend_Translate( 'tmx', ABS_PATH . '/_languages/lang.xml', $lang);
		
		try {
			$this->zendTranslate->setLocale( $lang );
		}
		catch( Zend_Translate_Exception $e ){
			// The given language does not exist
			$this->zendTranslate->setLocale( 'en' );
		}
	}
	
	/*
	public function display($tpl = null)
	{
		$output = $this->__toString($tpl);

		if( $this->ABS_PATH_FROM_TMP ){
			$path = array_pop( $this->__basePathHttp );
		
			$patrones = array();
			$reemplazos = array();
			
			
			$patronHTTP = '^\/&^http:\/\/&^https:\/\/';
			
			$patrones[]  = '/(src=\")([' . $patronHTTP . '])/';
			$reemplazos[]= "src=\"" . $path . "\$2";
			
			
			$patrones[]  = '/(src=\')([' . $patronHTTP . '])/';
			$reemplazos[]= "src='" . $path . "\$2";
			
			$patrones[]  = '/(href=\")([' . $patronHTTP . '])/';
			$reemplazos[]= "src='" . $path . "\$2";
			
			$patrones[]  = '/(href=\')([' . $patronHTTP . '])/';
			$reemplazos[]= "src='" . $path . "\$2";
			
			
			$patrones[]  = '/(url\(\")([' . $patronHTTP . '])/';
			$reemplazos[]= "url(\"" . $path . "\$2";
			
			$patrones[]  = '/(url\(\')([' . $patronHTTP . '])/';
			$reemplazos[]= "url('" . $path . "\$2";
			
			$patrones[]  = '/(background=\")([' . $patronHTTP . '])/';
			$reemplazos[]= "background=\"" . $path . "\$2";
			
			$patrones[]  = '/(background=\')([' . $patronHTTP . '])/';
			$reemplazos[]= "background='" . $path . "\$2";
			
			
			$patrones[]  = '/(action=\")([' . $patronHTTP . '])/';
			$reemplazos[]= "action=\"" . $path . "\$2";
			
			$patrones[]  = '/(action=\')([' . $patronHTTP . '])/';
			$reemplazos[]= "action='" . $path . "\$2";
			
			$output = preg_replace($patrones, $reemplazos, $output);
		}		
		echo $output;
	}
	*/
	
	/**
	 * @param string $tpl
	 */
	public function __toString( $tpl = null ){
		$string = parent::__toString( $tpl );
		if ( $string == $this->escape( $this->__config['error_text'] ) ){
			throw new Exception( 'Template "'. $tpl . '" not found' );
		}
		else {
			return $string;
		}
	}

	public function setFileTranslate( $filePath ){
		$this->zendTranslate = new Zend_Translate( 'tmx', ABS_PATH . '/_languages/' . $filePath, 'en' );
	}
	public function setLanguage( $lang ){
		$lang = ProjectLibrary_SDO_Core_Application_LanguageManager::LanguageToZendLang( $lang );
		
		try {
			$this->zendTranslate->setLocale( $lang );
		}
		catch( Zend_Translate_Exception $e ){
			// The given language does not exist
			$this->zendTranslate->setLocale( 'en' );
		}
	}
	public function trans( $name ){
		$trans = $this->zendTranslate->_( $name );
		if(CHARSET_PROJECT == 'ISO-8859-1') {
			return utf8_decode($trans);
		}
		return $trans;
	}
}
?>