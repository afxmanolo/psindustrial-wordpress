<?php
class Validator_Test_FileUpload_Excel extends Validator_Test_FileUpload implements Validator_Test {
	//application/vnd.ms-excel
	//
	public function __construct( $fileType = '/^application\/vnd.ms-excel|application\/download$/',
	                             $fileExt = array( 'xls' ), 
	                             $matchAll = true ){
	  // Conver the list of extensions to a regular expression
	  $fileExt = '/\.(' . join( '|', $fileExt ) . ')$/i';	// Regex /i = Case insensitive
		parent::__construct( $fileType, $fileExt, $matchAll ); 
	}
	
}
?>