<?php
/**
 * Valid: 23, 1, 132.50, 123. - Innvalid: -123, -.5, .5, .789, 123., etc.
 */
class Validator_Test_Numeric_Unsigned extends Validator_Test_RegEx implements Validator_Test {
	
	public function __construct(){
		$regex = '/^\d+\.?\d*$/';
		parent::__construct( $regex );
	}
	
}
?>