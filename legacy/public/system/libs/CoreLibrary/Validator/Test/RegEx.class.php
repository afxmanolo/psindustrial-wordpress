<?php
class Validator_Test_RegEx extends Objeto implements Validator_Test {
	
	protected $regex;
	
	public function __construct( $regex ){
		parent::__construct();
		$this->regex = $regex;
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