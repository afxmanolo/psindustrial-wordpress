<?php
class GUI_Calendar_CalendarLanguage extends Objeto {
	
	const TYPE_SHORT_DESCRIPTION = 0;
	const TYPE_FULL_DESCRIPTION = 1; 
	
	protected $days = array();
	protected $months = array();
	protected $btnGo;
	protected $btnPrev;
	protected $btnNext;
	protected $btnToday;
	
	
	public function __construct(){
		parent::__construct();	
		
		$this->days[ self::TYPE_SHORT_DESCRIPTION ] = 
				array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' );
		$this->days[ self::TYPE_FULL_DESCRIPTION  ] = 
				array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );

		$this->months[ self::TYPE_SHORT_DESCRIPTION ] = 
				array( 1 => 'Jan', 	2 => 'Feb', 	3 => 'Mar', 
							 4 => 'Apr', 	5 => 'May', 	6 => 'Jun', 
							 7 => 'Jul', 	8 => 'Aug', 	9 => 'Sep', 
							10 => 'Oct', 11 => 'Nov', 12 => 'Dec' );
		$this->months[ self::TYPE_FULL_DESCRIPTION  ] = 
				array( 1 => 'January', 	2 => 'February', 	3 => 'March', 
							 4 => 'April', 		5 => 'May', 			6 =>  'Jun', 
							 7 => 'July', 		8 => 'August', 		9 => 'September', 
							10 => 'October', 11 => 'November', 12 => 'December' );

		$this->btnGo = 'Go';
		$this->btnPrev = '<<';
		$this->btnNext = '>>';
		$this->btnToday = 'Today';
	}
	
	public function getDayLabel( $intDay, $type = 1 ){
		
		if( !in_array( $type, array( self::TYPE_FULL_DESCRIPTION, self::TYPE_SHORT_DESCRIPTION ) ) ){
			throw new Exception( "Type must be a valid integer. See class CONSTANTS." );
		}
		if ( !is_int( $intDay ) && !is_numeric( $intDay ) ) {
			throw new Exception( "Parameter must be an number between 0-6." );
		}
		
		if ( $intDay >= 0 && $intDay <= 6) {
			return $this->days[ $type ][ $intDay ];
		}
		else {
			throw new Exception( "Parameter must be an number between 0-6." );
		}
	}
	
	public function getMonthLabel( $intMonth, $type = 1 ){
		if( !in_array( $type, array( self::TYPE_FULL_DESCRIPTION, self::TYPE_SHORT_DESCRIPTION ) ) ){
			throw new Exception( "Type must be a valid integer. See class CONSTANTS." );
		}
		if ( !is_int( $intMonth ) && !is_numeric( $intMonth ) ) {
			throw new Exception( "Parameter must be an number between 1-12." );
		}
		
		if ( $intMonth >= 1 && $intMonth <= 12) {
			return $this->months[ $type ][ (int) $intMonth ];
		}
		else {
			throw new Exception( "Parameter must be an number between 1-12." );
		}
	}

	public function getMonths( $type ){
		if( !in_array( $type, array( self::TYPE_FULL_DESCRIPTION, self::TYPE_SHORT_DESCRIPTION ) ) ){
			throw new Exception( "Type must be a valid integer. See class CONSTANTS." );
		}
		else {
			return $this->months[ $type ];
		}
	}
	
	public function getDays( $type ){
		if( !in_array( $type, array( self::TYPE_FULL_DESCRIPTION, self::TYPE_SHORT_DESCRIPTION ) ) ){
			throw new Exception( "Type must be a valid integer. See class CONSTANTS." );
		}
		else {
			return $this->days[ $type ];
		}
	}

	public function getButtonGoLabel(){
		return $this->btnGo;
	}
	
	public function getButtonPreviousLabel(){
		return $this->btnPrev;
	}
	
	public function getButtonNextLabel(){
		return $this->btnNext;
	}

	public function getButtonTodayLabel(){
		return $this->btnToday;
	}

	public function getFullDateString( $date ){
		if ( is_string( $date ) ){
			$date = strtotime( $date );
		}
		
		$strText = date( 'F dS, Y', $date );
		return $strText;
								
							 
	}
}
?>