<?php
/**
 * Numeric Validator that uses PHP is_numeric() function  
 * 
 * Note: Numeric strings consist of optional sign, any number of digits, optional decimal part and optional exponential part. Thus +0123.45e6 is a valid numeric value. Hexadecimal notation (0xFF) is allowed too but only without sign, decimal and exponential part.
 *
 * @see is_numeric()
 */
class Validator_Test_ReturnFalse extends Objeto implements Validator_Test {
	
	public function __construct(){
		parent::__construct();
	}
	
	public function isValid( $variable ){
		return false;
	}
	
}
?>