<?php
class Validator_Test_Date_YYYYMMDD extends Validator_Test_RegEx {

	protected $separator;
	
	public function __construct( $separator = '-' ){
		$this->separator = $separator;
		$regEx = '/^\d{4}' . $separator . '\d{2}' . $separator . '\d{2}$/';
		parent::__construct( $regEx );
	}
	
	public function isValid( $variable ){
		if ( parent::isValid( $variable ) ){
			$format = 'Y' . $this->separator . 'm' . $this->separator . 'd';
			if ( $variable == date( $format, strtotime( $variable ) ) ){
				// Valid date in the calendar
				return true;
			}
			else {
				// Invalid date in the calendar
				return false;
			}
		}
		else {
			// Invalid format
			return false;
		}
	}
	
}
?>