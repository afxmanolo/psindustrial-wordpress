<?php
class Validator_Test_Numeric_Between implements Validator_Test {
	
	protected $min;
	protected $max;
	
	public function __construct( $min, $max ){
		$this->min = $min;
		$this->max = $max;
	}
	
	public function isValid( $variable ){
		if ( $variable >= $this->min && $variable <= $this->max ){
			return true;
		}
		else {
			return false;
		}
	}
	
}
?>