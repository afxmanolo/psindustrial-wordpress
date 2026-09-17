<?php
class GUI_Calendar_CalendarMonthView extends Objeto {

	private $name;
	private $date;
	private $html = '';
	
	protected $viewController;
	
	protected $width = '750px';
	
	protected $displayNav = true;
	protected $displayMonth = true;
	protected $displayYear = true;

	protected $navigationYearsDiff = 20;
	protected $dayView;

	public function __construct( $name = 'cal1' , GUI_Calendar_CalendarView $viewController ){
		$this->name = $name;
		$this->viewController = $viewController;
	}

	public function display( $date = 'now' ){
		$this->setDate( $date );
		$this->createCalendar();
		$html = str_replace( array( "\n", "\r" ), '', $this->getHTML() ); 
		echo $html;
	}

	public function setDate( $date = 'now' ){
		if ( is_string( $date ) ){
			$date = strtotime( $date );
		}
		$this->date = $date;
	}

	public function createCalendar( ){
		$month = date( 'm', $this->date );
		$year = date( 'Y', $this->date );
		$firstDayOfMonth = mktime(0,0,0, $month, 1, $year );
		$lastDayOfMonth = date( 't', $firstDayOfMonth );
		$lastDate = mktime( 0,0,0, $month, $lastDayOfMonth, $year );

		$dayOfFirstDay = date( 'w', $firstDayOfMonth );

		$this->writeHTML( '<table width="' . $this->width . '" border="0" cellspacing="1" cellpadding="0" class="mainTableTOC">' );

		// Write Navigation and Month/Year stuff
		$this->writeHTML( '<tr><td colspan="7">' );
		$this->writeHTML( $this->getHeader() );
		$this->writeHTML( '</td></tr>');

		$this->writeHTML( '<tr class="dayNamesTextTOC">' );
		foreach( range(0,6) as $i ){
			$this->writeHTML( '<td class="dayNamesRowTOC">' . $this->getLang()->getDayLabel( $i, GUI_Calendar_CalendarLanguage::TYPE_FULL_DESCRIPTION ) . "</td>" );
		}
		$this->writeHTML( '</tr>');


		if ( $dayOfFirstDay != 0 ){
  		$this->writeHTML( '<tr class="rowsTOC">' );
  		$prevMonth = strtotime( date( 'Y-m-d', $this->date ) . ' -1 month' );
  		$daysInPrevMonth = date( 't', $prevMonth ); 
			$this->writeDaysPadding( 0, $dayOfFirstDay, $daysInPrevMonth - $dayOfFirstDay + 1);
		}
		$this->writeMonth();

		$this->writeDaysPadding( date('w', $lastDate ), 6-date('w', $lastDate ), 1 );

		$this->writeHTML( '</tr></table>');
	}

	public function getHTML(){
		return $this->html;
	}

	private function writeHTML( $str ){
		$this->html .= $str;
	}
	
	private function getLang(){
		return $this->viewController->getLanguage(); 
	}

	public function setWidth( $strWidth ){
		$this->width = $strWidth;
	}

	private function writeDaysPadding( $start, $numDays, $firstDayNumber ){
		for( $weekday = $start; $weekday < $start + $numDays; $weekday++){
			$class = ( $weekday == 0 || $weekday == 6 ) ? 'weekendDay' : 'weekDay';
			$this->writeHTML( '<td class="' . $class . ' disabledDays">' .
													( $firstDayNumber + $weekday - $start ) .
													'&nbsp;'.
												 '</td>');
		}
	}

	private function writeMonth(){
		$lastDayOfMonth = date('t', $this->date );
		$month = date('m', $this->date );
		$year = date('Y', $this->date );
		foreach( range( 1, $lastDayOfMonth ) as $dayNumber ){
			$day = mktime(0,0,0, $month, $dayNumber, $year );
			$weekday = date( 'w', $day );
				
			// Write a new row if necessary
			if ( $weekday == 0 ){
				$this->writeHTML( '</tr>
  													 <tr class="rowsTOC">' );
			}
				
			$class = ( $weekday == 0 || $weekday == 6 ) ? 'weekendDay' : 'weekDay';
			$class = ( date( 'Y-m-d' ) == date( 'Y-m-d', $day ) ) ? 'todayDay' : $class;
			
			$dayLink = '<a href="eventos.php?date='. ( date( 'Y-m-d', $day ) ).'" title="Ver todos los eventos de este día">'. $dayNumber . '</a>';
			$this->writeHTML( '<td class="' . $class . '">
                      			<div class="daynumTOC">' . $dayLink . '</div>' );
				
			if ( $this->viewController->getDAO() != null ){
  			$dayView = $this->viewController->getView( GUI_Calendar_CalendarView::VIEW_DAY );
  			$dayView->setDate( $day );
  			$this->writeHTML( $dayView->getContent() );
			}
			$this->writeHTML( '</td>');
		}
	}

	public function displayNavigation( $val ){
		$this->displayNav = $val;
	}

	public function getHeader(){
		$html = '<table border="0" cellspacing="0" cellpadding="0" class="calendar_header" width="100%">';
		$html .= '<tr class="calendar_header_row">';
		if ( $this->displayMonth || $this->displayYear ){
			$html .= 	'<td class="calendar_curr_date">';
			if ( $this->displayMonth ){
				$html .= $this->getLang()->getMonthLabel( date('n', $this->date ) ) . " ";
			}
			if ( $this->displayYear ){
				$html .= date( 'Y', $this->date );
			}
			$html .= '</td>';
		}
		if ( $this->displayNav ){
			$html .= 	'<td class="calendar_navigation">';
			
			$params = $_GET;
			foreach( $params as $key => $value ){
				if ( strpos( $key, 'calendar_' . $this->name ) === 0 ){
					unset( $params[ $key ] );
				}
			}
			$html .=  	'<form name="calendar_' . $this->name . 'navigation" method="get" action="' . $_SERVER['PHP_SELF'] . '?' . join( "&", $params ) . '">';
			
			// Drop down for months
			$html .= 			'<select name="calendar_' . $this->name . '_month" class="calendar_month_select">';
			foreach ( $this->getLang()->getMonths( GUI_Calendar_CalendarLanguage::TYPE_FULL_DESCRIPTION ) as $i => $month ){
				$selected = ( $i == date( 'n', $this->date ) ) ? 'selected="SELECTED"' : '';
				$html .= 		'<option value="' . $i . '" class="calendar_month_options" ' . $selected . '>' . $month . '</option>';
			}
			$html .= 			'</select>';
			
			// Drop down for years
			$html .=  	'<select name="calendar_' . $this->name . '_year" class="calendar_year_select">';
			$firstYear = date('Y', $this->date ) - $this->navigationYearsDiff;
			$lastYear = $firstYear + ( $this->navigationYearsDiff * 2 );
			foreach ( range( $firstYear, $lastYear ) as $y ){
				$selected = ( $y == date( 'Y', $this->date ) ) ? 'selected="SELECTED"' : '';
				$html .= 	'<option ' . $selected . ' class="calendar_year_options">' . $y .'</option>';
			}
			$html .= 		'</select>';
			
			// Include all values
			foreach ( $params as $key => $value ){
				$html .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
			}
			// Navigation buttons
			$html .= 		'<input type="submit" value="' . htmlentities( $this->getLang()->getButtonGoLabel(), ENT_QUOTES ) . '" class="calendar_nav_button" />';
			$html .= 		'<input type="submit" name="calendar_' . $this->name . '_prev_month" value="' . htmlentities( $this->getLang()->getButtonPreviousLabel(), ENT_QUOTES ) . '" class="calendar_nav_button" />';
			$html .= 		'<input type="submit" name="calendar_' . $this->name . '_curr_month" value="' . htmlentities( $this->getLang()->getButtonTodayLabel(), ENT_QUOTES ) . '" class="calendar_nav_button" />';
			$html .= 		'<input type="submit" name="calendar_' . $this->name . '_next_month" value="' . htmlentities( $this->getLang()->getButtonNextLabel(), ENT_QUOTES ) . '" class="calendar_nav_button" />';
			$html .= 		'</form>';
			$html .= 	'</td>';
		}
		$html .= '</tr></table>';
		return $html;
	}
}
?>