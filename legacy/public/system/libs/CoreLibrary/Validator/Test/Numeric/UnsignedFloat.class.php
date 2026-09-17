<?php
/**
 * Valid: 1.23, 0.123456789, 123456.7890 - Innvalid: .789, 123., etc.
 */
class Validator_Test_Numeric_UnsignedFloat extends Validator_Test_RegEx implements Validator_Test {
	
	public function __construct(){
		$regex = '/^\d+\.?\d*$/';
		parent::__construct( $regex );
	}
	
}
?>