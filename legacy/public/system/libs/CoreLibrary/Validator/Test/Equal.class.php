<?php
class Validator_Test_Equal extends Objeto implements Validator_Test {
	
	protected $value;
	
	public function __construct( $value ){
		parent::__construct();
		$this->value = $value;
	}
	
	public function isValid( $variable ){
		return ( $this->value == $variable );
	}

}
?>