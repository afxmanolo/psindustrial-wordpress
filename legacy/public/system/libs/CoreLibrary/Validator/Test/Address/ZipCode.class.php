<?php
/**
 * Test validator for a 5 digit Zip Code.
 */
class Validator_Test_Address_ZipCode extends Validator_Test_RegEx {
	
	public function __construct(){
		parent::__construct( '/^[\d]{5}([-]{0,1}[\d]{4}){0,1}$/' );  // MX & US zip codes 
	}
	
}
?>