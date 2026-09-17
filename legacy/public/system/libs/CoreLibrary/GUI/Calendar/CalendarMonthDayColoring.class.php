<?php
class GUI_Calendar_CalendarMonthDayColoring extends Objeto {

	private $colors = array();

	public function __construct(){
		parent::__construct();
	}

	public function getColor( $event ){
		if ( !isset( $this->colors[ $event['event_id' ] ] ) ){
			$rgb = array();
			foreach( range( 1, 3) as $i ){
				$hex = dechex( rand( 0, 255 ) );
				if ( strlen( $hex ) == 1 ){
					$hex = "0" . $hex;
				}
				$rgb[ $i ] = $hex;
			}
			$this->colors[ $event['event_id' ] ] = "#" . join( $rgb );
		}
		return $this->colors[ $event[ 'event_id' ] ];
		
	}
}
?>