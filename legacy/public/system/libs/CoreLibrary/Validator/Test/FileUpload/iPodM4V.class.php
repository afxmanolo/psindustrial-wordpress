<?php
class Validator_Test_FileUpload_iPodM4V extends Validator_Test_FileUpload implements Validator_Test {
	
	public function __construct( $fileType = '/^video\/x-m4v$/', 
	                             $fileExt = array( 'm4v' ), 
	                             $matchAll = true ){
	  // Conver the list of extensions to a regular expression
	  $fileExt = '/\.(' . join( '|', $fileExt ) . ')$/i';	// Regex /i = Case insensitive
		parent::__construct( $fileType, $fileExt, $matchAll ); 
	}
	
}
?>