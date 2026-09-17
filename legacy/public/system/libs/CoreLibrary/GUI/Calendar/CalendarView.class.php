<?php
class GUI_Calendar_CalendarView extends Objeto {
	
	private $calendarApplication;
	private $lang; 
	private $date;
  private $dao;
	
	private $name;
	
	const VIEW_SMALL = 'small';
	const VIEW_MONTH = 'month';
	const VIEW_DAY   = 'day';
	
  private $views = array( 'small' => null,
  												'month' => null ); 
	
	public function __construct( $name = '' ){
		parent::__construct();
		$this->name = $name;
		$this->init();
	}
	
	protected function init(){
		$this->setLanguage( new GUI_Calendar_CalendarLanguage );
		$this->setView( self::VIEW_MONTH, new GUI_Calendar_CalendarMonthView( $this->name, $this ) );
		$this->setView( self::VIEW_DAY, new GUI_Calendar_CalendarMonthDayView( $this ) );
		$this->setDate( date('Y-m-d') ); // Date = today;
	}
	
	public function setLanguage( GUI_Calendar_CalendarLanguage $lang ){
		$this->lang = $lang;
	}
	public function getLanguage(){
		return $this->lang;
	}
	
	public function connectToDB( DB $db ){
		$this->dao = new GoPuebla_DAO_EventDAO( $db );
	}
	
	public function getDAO(){
		return $this->dao;
	}
	
	public function setView( $strViewType, Object $view ){
		$this->views[ $strViewType ] = $view;
	}
	public function getView( $strViewType ){
		return $this->views[ $strViewType ];
	}
	
	public function displayView( $strViewType ){
		$this->views[ $strViewType ]->display( $this->date );
	}
		
	public function setDate( $date = 'now'){
		if ( is_string( $date ) ){
			$date = strtotime( $date );
		}
		$day = date('d', $date );
		$month = date( 'n', $date );
		$year = date( 'Y', $date );

		if ( isset( $_REQUEST[ 'calendar_' . $this->name . '_day' ] ) ) {
			$day = $_REQUEST[ 'calendar_' . $this->name . '_day' ];
		}
		if ( isset( $_REQUEST[ 'calendar_' . $this->name . '_month' ] ) ){
			$month = $_REQUEST[ 'calendar_' . $this->name . '_month' ];
		}
		if ( isset( $_REQUEST[ 'calendar_' . $this->name . '_year'] ) ){
			$year = $_REQUEST[ 'calendar_' . $this->name . '_year'];
		}
		$date2 = $year . '-' . $month . '-' . $day;
		$date2 = strtotime( $date2 );
		$this->date = ( $date2 != false )? $date2 : $date;
		
		if ( isset( $_REQUEST[ 'calendar_' . $this->name . '_prev_month' ] ) ){
			$this->date = strtotime( date( 'Y-m-d', $this->date ) . ' -1 months' );
		}
		elseif ( isset( $_REQUEST[ 'calendar_' . $this->name . '_next_month' ] ) ){
			$this->date = strtotime( date( 'Y-m-d', $this->date ) . ' +1 months' );
		}
		elseif( isset( $_REQUEST[ 'calendar_' . $this->name . '_curr_month'] ) ){
			$this->date = strtotime( 'now' );
		}
	}
	public function getDate(){
		return $this->date;
	}
	
}
?>