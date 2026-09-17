<?php
class Validator_Test_Date extends Objeto implements Validator_Test {
	
	protected $format;
	
	public function __construct( $format = 'Y-m-d' ){
		parent::__construct();
		$this->format = $format;
	}
	
	public function isValid( $variable ){
		if ( preg_match( $this->regex, $variable ) ){
			return true;
		}
		else {
			return false;
		}
	}

}
?>