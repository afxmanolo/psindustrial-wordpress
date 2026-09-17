<?php
class Validator_Test_FileUpload_PDF extends Validator_Test_FileUpload implements Validator_Test {
	
	public function __construct( $fileType = '/^application\/pdf|application\/octet$/', 
	                             $fileExt = array( 'pdf' ), 
	                             $matchAll = true ){
	  // Conver the list of extensions to a regular expression
	  $fileExt = '/\.(' . join( '|', $fileExt ) . ')$/i';	// Regex /i = Case insensitive
		parent::__construct( $fileType, $fileExt, $matchAll ); 
	}
	
}
?>