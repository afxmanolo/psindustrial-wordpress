<?php
/**
 * Integer test. Accepted values are: 23, -1235, +945
 *
 */
class Validator_Test_Numeric_UnsignedInteger extends Validator_Test_RegEx implements Validator_Test {
	
	public function __construct(){
		$regex = '/^\d+$/';
		parent::__construct( $regex );
	}
	
}
?>