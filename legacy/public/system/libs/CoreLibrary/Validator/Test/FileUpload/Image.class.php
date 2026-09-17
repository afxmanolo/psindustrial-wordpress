<?php
class Validator_Test_FileUpload_Image extends Validator_Test_FileUpload implements Validator_Test {
	
	public function __construct( $fileType = '/^image\/.*$/', 
	                             $fileExt = array( 'jpe?g', 'gif', 'png', 'bmp', 'png', 'tiff' ), 
	                             $matchAll = true ){
	  // Conver the list of extensions to a regular expression
	  $fileExt = '/\.(' . join( '|', $fileExt ) . ')$/i';	// Regex /i = Case insensitive
		parent::__construct( $fileType, $fileExt, $matchAll ); 
	}
	
}
?>