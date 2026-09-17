<?php
class GUI_Calendar_CalendarMonthDayView extends Objeto {
	
	protected $date;
	protected $coloring;
	protected $viewController;
	
	public function __construct( GUI_Calendar_CalendarView $viewController ){
		parent::__construct();
		$this->init();
		$this->viewController = $viewController;
	}
	
	public function init(){
		$this->coloring = new GUI_Calendar_CalendarMonthDayColoring();
	}

	public function getDAO(){
		return $this->viewController->getDAO();
	}
	
	public function setDate( $date ){
		if( is_string( $date ) ){
			$date = strtotime( $date );
		}
		$this->date = $date;
	}
	
	public function getContent(){
		$rs = $this->getDAO()->getEventsByDate( $this->date );
		$html = '<div class="calendar_day_wrapper">';
		foreach ( $rs as $event ){
			$html .= $this->getInlineContent( $event );
		}
		$html.= '</div>';
		return $html;
	}
	
	protected function getInlineContent( $event ){
		$color = $this->getEventColor( $event );
		return ( '<div class="eventTitle" style="border-left:4px solid ' . $color . '" ' . 
							'onMouseOver=\'Tip("' . htmlentities( addslashes(	 $this->getPopupContent( $event ) ), ENT_QUOTES ) . '" );\' ' .
							'onClick="window.location.href=\'evento.php?cmd=view&id=' . $event['event_id'] . '\'"' .
							'>' . utf8_encode( $event['name'] ) . '</div>');
	}

	protected function getEventColor( $arrEvent ){
		return $this->coloring->getColor( $arrEvent );
		
	}
	
	protected function getPopupContent( $event ){
		
		$start = strtotime( $event[ 'start_date'] );
		
		$end =  strtotime( $event[ 'end_date'] );
		
		$strStart = $this->viewController->getLanguage()->getFullDateString( $start );
		$strEnd = ( $event['show_end_date'] ) ? $this->viewController->getLanguage()->getFullDateString( $end ) : '';
		
		$event['description'] = nl2br( $event['description'] );
		
		$html = '';
		$html .= '<div class="calendar_popup">';
		$html .= 	'<div class="calendar_popup_date"><img src="ima/history.png" border="0" align="right" />' . $strStart . ( ( !empty( $strEnd ) ) ? ' - ' . $strEnd : '' ) . '</div>';
		$html .= 	'<div class="calendar_popup_name">' . $event['name'] . '</div>';
		$html .= 	'<div class="calendar_popup_location">' . '@' . $event['zone'] . '</div>';
		$html .= 	'<div class="calendar_popup_description">' . $event['description'] . '</div>';
		$html .= '</div>';
		
		return $html;
	}
	
	public function display(){
	}
	
	public function displayFrontPageEvents( $limit = 3, $maxChars = 150 ){
		$events = $this->viewController->getDAO()->getUpcomingEvents( $limit, $maxChars );
		foreach ( $events as $event ){
			echo $this->getFrontPageEvent( $event );
		}
	}
	
	public function getFrontPageEvent( $event, $maxChars = 150 ){
		if ( empty( $event ) ) return null;
		
		$start = strtotime( $event['start_date'] );
		$desc = $event['more'];
		if ( strlen( $desc ) > $maxChars ){
			$desc = substr( $desc, 0, $maxChars ) . '...';
		}
		$desc = nl2br( $desc );
		$html = '<div class="calendar_fp_event">';
		$html .= '<div class="calendar_fp_event_date">' . $this->viewController->getLanguage()->getFullDateString( $start )  . '</div>';
		$html .= '<div class="calendar_fp_event_name">' . $event['name']  . '</div>';
		$html .= '<div class="calendar_fp_event_desc">' . $desc . '</div>';
		$html .= '<div class="calendar_fp_event_more"><a href="calendar2.php?cmd=view&id=' . $event['id'] . '"><img src="ima/more.gif" border="0" alt="View details"></a></div>';		
		$html .= '</div>';
		return $html;
	}

}
?>