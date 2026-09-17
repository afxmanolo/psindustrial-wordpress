<?php
class Validator_Test_AlwaysNotValid extends Objeto implements Validator_Test {
	

	public function __construct(){
		parent::__construct();
	}
	
	public function isValid( $variable ){
			return false;
	}

}
?>