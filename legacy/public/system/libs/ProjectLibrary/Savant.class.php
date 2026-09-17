<?php
class ProjectLibrary_Savant extends Savant3 {
	
	/**
	 * Zend Translation engine
	 *
	 * @var Zend_Translate
	 */
	protected $translate;
	protected $pathExecute = '';
	
	public function __construct() {
		parent::__construct ();
		
		$lang = ProjectLibrary_SDO_Core_Application_LanguageManager::LanguageToZendLang ();
		//$this->setPath( 'template', array( ABS_PATH . '/view/'  ) );
		$this->setPath ( 'template', array (ABS_PATH . '/view' ) );
		
		$this->translate = new Zend_Translate ( 'tmx', ABS_PATH . '/_languages/lang.xml', $lang );
		
		try {
			$this->translate->setLocale ( $lang );
		} catch ( Zend_Translate_Exception $e ) {
			// The given language does not exist
			$this->translate->setLocale ( 'en' );
		}
	}
	/*
	 public function __construct( ){
		parent::__construct();
		
		$lang = ProjectLibrary_SDO_Core_Application_LanguageManager::LanguageToZendLang();
		//$this->setPath( 'template', array( ABS_PATH . '/view/'  ) );
		$this->setPath( 'template', array( ABS_PATH . '/backoffice'  ) );
		
		$this->translate = new Zend_Translate( 'tmx', ABS_PATH . '/_languages/lang.xml', $lang);
		
		try {
			$this->translate->setLocale( $lang );
		}
		catch( Zend_Translate_Exception $e ){
			// The given language does not exist
			$this->translate->setLocale( 'en' );
		}
	}
	 */
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
	
	public function __toString() {
		$string = parent::__toString ();
		if ($string == $this->escape ( $this->__config ['error_text'] )) {
			throw new Exception ( 'Template "' . $string . '" not found' );
		} else {
			return $string;
		}
	}
	
	/*
	 public function __toString( $tpl = null ){
		$string = parent::__toString( $tpl );
		if ( $string == $this->escape( $this->__config['error_text'] ) ){
			throw new Exception( 'Template "'. $tpl . '" not found' );
		}
		else {
			return $string;
		}
	}
	 */
	
	public function setFileTranslate($filePath) {
		$this->translate = new Zend_Translate ( 'tmx', ABS_PATH . '/_languages/' . $filePath, 'en' );
	}
	public function setLanguage($lang) {
		$lang = ProjectLibrary_SDO_Core_Application_LanguageManager::LanguageToZendLang ( $lang );
		
		try {
			$this->translate->setLocale ( $lang );
		} catch ( Zend_Translate_Exception $e ) {
			// The given language does not exist
			$this->translate->setLocale ( 'en' );
		}
	}
	public function trans($name) {
		return $this->translate->_ ( $name );
	}

}
?>