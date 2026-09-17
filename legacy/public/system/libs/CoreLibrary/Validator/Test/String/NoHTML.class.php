<?php
/**
 * Numeric Validator that uses PHP is_numeric() function  
 * 
 * Note: Numeric strings consist of optional sign, any number of digits, optional decimal part and optional exponential part. Thus +0123.45e6 is a valid numeric value. Hexadecimal notation (0xFF) is allowed too but only without sign, decimal and exponential part.
 *
 * @see is_numeric()
 */
class Validator_Test_String_NoHTML extends Objeto implements Validator_Test {
	
	protected $validTags;
	
	public function __construct( array $validTags = array() ){
		parent::__construct();
		$this->validTags = $validTags;
	}
	
	public function isValid( $variable ){
		if ( !empty( $this->validTags ) ){
			$tags = '<' . join( '><', $this->validTags ) . '>';
		}
		else {
			$tags = null;
		}
		if ( strcmp( $variable, strip_tags( $variable, $tags ) ) == 0 ){
			return true;
		}
		else {
			return false; 
		}
	}
	
}
?>