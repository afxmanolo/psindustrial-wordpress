<?php
/**
 * Test validator for a 2 letter country code.
 */
class Validator_Test_Address_CountryCode extends Validator_Test_String {
	
	public function __construct(){
		parent::__construct();
	}
	
	public function isValid( $variable ){
		$countries = Util_CountryISOCodes::getCountries();
		$codes = array_keys( $countries );
		
		return in_array( $variable, $codes, true );
	}
	
}
?>