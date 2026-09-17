<?php
/**
 * Integer test. Accepted values are: 23, -1235, +945
 *
 */
class Validator_Test_Numeric_Integer extends Validator_Test_RegEx implements Validator_Test {
	
	public function __construct(){
		$regex = '/^[+|-]{0,1}\d+$/';
		parent::__construct( $regex );
	}
	
}
?>