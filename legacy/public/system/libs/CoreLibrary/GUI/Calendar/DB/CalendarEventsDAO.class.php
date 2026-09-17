<?php
final class GUI_Calendar_DB_CalendarEventsDAO extends DB_DAO_GenericDAO {
	
	protected $table = 'va_event';
	protected $id_field  = 'id';
	protected $autoIncrement = true;
	
	public function __construct( DB $db ){
		parent::__construct( $db );
	}
	
	public function loadAll(){
		$sql = 'SELECT * FROM ' . $this->table . 
						' ORDER BY start_date, start_time, name';
		return $this->db->sqlGetResult( $sql ); 
	}

	public function getEventsByDate( $date ){
		if( is_string( $date ) ){
			$date = strtotime( $date );
		}
		$sql = "SELECT 	*
						FROM 		" . $this->table . "
						WHERE 	start_date = '" . date( 'Y-m-d', $date ) . "'";
		$sql2 = "SELECT *
						FROM		" . $this->table .  "
						WHERE start_date <= '" . date( 'Y-m-d', $date ) . "'
						AND end_date >= '" . date( 'Y-m-d', $date ) . "'";
		
		$sql = "SELECT t1.*, z.name AS zone 
		        FROM (" . $sql . " UNION " . $sql2 . ") AS t1
		        LEFT JOIN zone AS z ON z.zone_id = t1.zone_id 
		        ORDER BY name";
		$rs = $this->db->sqlGetResult( $sql );
		return $rs;
	}
	
	public function getUpcomingEvents( $limit = 5){
		return $this->getEventsAfter( 'now', true, $limit );
	}
	
	public function getPastEvents( $limit = 5){
		return $this->getEventsBefore( 'now', false, $limit );
	}
	
	public function getEventsAfter( $date = 'now', $inclusive = true, $eventsLimit = 0 ){
		if( is_string( $date ) ){
			$date = strtotime( $date );
		}
		$limit = ( $eventsLimit > 0 ) ? 'LIMIT 0,' . $eventsLimit : '';
		$op = ( $inclusive )? '>=' : '>';
		$sql = "SELECT *
						FROM " . $this->table . "
						WHERE start_date " . $op . " '" . date('Y-m-d',$date) . "'
						ORDER BY start_date ASC
						" . $limit;
		return $this->db->sqlGetResult( $sql );
	}

	public function getEventsBefore( $date = 'now', $inclusive = true, $eventsLimit = 0 ){
		if( is_string( $date ) ){
			$date = strtotime( $date );
		}
		$limit = ( $eventsLimit > 0 ) ? 'LIMIT 0,' . $eventsLimit : '';
		$op = ( $inclusive )? '<=' : '<';
		$sql = "SELECT *
						FROM " . $this->table . "
						WHERE start_date " . $op . " '" . date('Y-m-d',$date) . "'
						ORDER BY start_date DESC
						" . $limit;
		return $this->db->sqlGetResult( $sql );
	}

	public function save( $event ){
		if ( $event[ $this->id_field ] != 0 ){
			$this->delete( $event[ $this->id_field ] );
			$this->saveNew( $event );
		}
		else {
			$this->saveNew( $event );
		}
	}
}
?>