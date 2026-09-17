<?php
class Validator_Test_FileUpload extends Objeto implements Validator_Test {

	protected $errors = array();
	protected $fileTypeRegex;
	protected $fileExtensionRegex;
	protected $matchAll;
	
	public function __construct( $fileTypeRegex, $fileExtensionRegex = '', $matchAll = true ){
		parent::__construct();
		$this->fileTypeRegex = $fileTypeRegex;
		$this->fileExtensionRegex = $fileExtensionRegex;
		$this->matchAll = $matchAll;
	}
	
	/**
	 * @param string $value - The name of the $_FILES structure that contains the file to validate
	 */
	public function isValid( $value ){
		$file = $_FILES[ $value ];
		$matchType = preg_match( $this->fileTypeRegex,      $file['type'] );
		$matchExt  = preg_match( $this->fileExtensionRegex, $file['name'] );  
		
		if ( $this->matchAll && ( $matchExt && $matchType ) ){		// Both conditions are required
			return true;
		}
		elseif ( !$this->matchAll && ( $matchExt || $matchType ) ){	// At least one condition is required
			return true;
		}
		else {
			return false;
		}
	}
	
}
?>