<?php
class Validator_Test_FileUpload_PPT extends Validator_Test_FileUpload implements Validator_Test {
	
	public function __construct( $fileType = '/^application\/vnd\.ms-powerpoint$/',
	                             $fileExt = array( 'ppt' ), 
	                             $matchAll = true ){
	  // Conver the list of extensions to a regular expression
	  $fileExt = '/\.(' . join( '|', $fileExt ) . ')$/i';	// Regex /i = Case insensitive
		parent::__construct( $fileType, $fileExt, $matchAll ); 
	}
	
}
?>