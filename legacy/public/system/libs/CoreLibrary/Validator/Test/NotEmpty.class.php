<?php
class Validator_Test_NotEmpty extends Objeto implements Validator_Test {
	
	protected $trim;
	
	public function __construct( $trimString = true ){
		parent::__construct();
		$this->trim = $trimString;
	}
	
	public function isValid( $variable ){
		if ( $this->trim ){
			$variable = trim( $variable );
		}
		if ( $variable == null || $variable == '' ){
			return false;
		}
		else {
			return true;
		}
	}
	
	
}
?>