<?php



class GUI_Calendar_CalendarMonthSmallView extends Objeto {







	private $name;



	private $date;



	private $html = '';



	



	protected $viewController;



	protected $jsNavigationPrev = '';

	protected $jsNavigationNext = '';

	protected $hiddenFields;

	protected $arrowLeft = '';

	protected $arrowRight = '';

	protected $language = 'EN';

	

	protected $width = '750px';



	



	protected $displayNav = true;



	protected $displayMonth = true;



	protected $displayYear = true;







	protected $navigationYearsDiff = 20;



	protected $dayView;







	public function __construct( $name = 'cal1' , GUI_Calendar_CalendarView $viewController ){



		$this->name = $name;



		$this->viewController = $viewController;

		

		$this->hiddenFields = array();

		

		$this->arrowRight = '>';

		$this->arrowLeft = '<';

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





	public function addHiddenField($fieldName, $fieldValue){

		$this->hiddenFields[ $fieldName ] = $fieldValue;

	}

	

	public function setLanguage( $lan ){

		$this->language = $lan;

	}

	

	public function createCalendar( ){



		$month = date( 'm', $this->date );



		$year = date( 'Y', $this->date );



		$firstDayOfMonth = mktime(0,0,0, $month, 1, $year );



		$lastDayOfMonth = date( 't', $firstDayOfMonth );



		$lastDate = mktime( 0,0,0, $month, $lastDayOfMonth, $year );







		$dayOfFirstDay = date( 'w', $firstDayOfMonth );







		$this->writeHTML( '<table width="' . $this->width . '" border="0" cellspacing="0" cellpadding="0" class="calendar">' );







		// Write Navigation and Month/Year stuff



		$this->writeHTML( '<tr ><td colspan="7" class="rowHeader">' );



		$this->writeHTML( $this->getHeader() );



		$this->writeHTML( '</td></tr>');







		$this->writeHTML( '<tr class="dayOfWeek">' );



		foreach( range(0,6) as $i ){



			$this->writeHTML( '<th >' . $this->getLang()->getDayLabel( $i, GUI_Calendar_CalendarLanguage::TYPE_TWOLETTERS_DESCRIPTION, $this->language ) . "</th>" );



		}



		$this->writeHTML( '</tr>');











		if ( $dayOfFirstDay != 0 ){



  		$this->writeHTML( '<tr>' );



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



			//$class = ( $weekday == 0 || $weekday == 6 ) ? 'weekendDaySmall' : 'weekDaySmall';



			$this->writeHTML( '<td class="disabled">' .



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

				$this->writeHTML( '</tr><tr>' );

			}

				



			$class = ( $weekday == 0 || $weekday == 6 ) ? 'weekend' : '';



			$class = ( date( 'Y-m-d' ) == date( 'Y-m-d', $day ) ) ? 'today' : $class;



			



			//$dayLink = '<a href="events.php?date='. ( date( 'Y-m-d', $day ) ).'" title="View all the events from this day">'. $dayNumber . '</a>';

	

			$eventsHtml = "" ;

			$dayLink = "" . $dayNumber;

			/*

			if ( $this->viewController->getDAO() != null ){

	  			$dayView = $this->viewController->getView( GUI_Calendar_CalendarView::VIEW_DAY );

	  			$dayView->setDate( $day );

	  			$eventsHtml = $dayView->getContent() ;

			}*/



			//$class = ($eventsHtml != '<div class="calendar_day_wrapper"></div>') ? 'event': '';



			$daysEvents = $this->viewController->getEventDays();

			

			$class ="normal_day";

		if( in_array($dayNumber, $daysEvents) ){

				$class ="event";

				
				
				$addParams = '';
				foreach( $this->hiddenFields as $key => $value ){
					$addParams .= '&' . $key . '=' . $value;
				}
		$dayLink = '<a href="noticias.php?DCalendar='. ( date( 'Y-m-d', $day ) ).''.$addParams.'" title="View all the events from this day" target="_top">'. $dayNumber . '</a>';

			}

			

			$this->writeHTML( '<td class="' . $class . '">' . $dayLink . '' );



			$this->writeHTML( '</td>');



		}

		



	}







	public function displayNavigation( $val ){



		$this->displayNav = $val;



	}



	

	public function setArrowLeft( $html ){

		$this->arrowLeft = $html;

	}

	

	public function setArrowRight( $html ){

		$this->arrowRight = $html;

	}



	public function getHeader(){



		$html = '<table border="0" cellspacing="0" cellpadding="0" class="header" width="100%">';

		$html .= '<tr class="headerMonthRows">';

		

		if ( $this->displayNav ){

			$html .= '<th class="prev_month">';

			$html .=  	'<form name="calendar_' . $this->name . 'navigation" id="calendar_' . $this->name . 'navigation" method="get" action="noticias.php" 

			 style="margin:0px">';

			$html .= '<input type="hidden" name="calendar_' . $this->name . '_month" id="calendar_events_month" value="'.date( 'm', $this->date ).'">';

			$html .= '<input type="hidden" name="calendar_' . $this->name . '_year" id="calendar_events_year" value="'.date( 'Y', $this->date ).'">';

			$html .= '<input type="hidden" name="calendar_' . $this->name . '_prev_month" id="calendar_' . $this->name . '_prev_month" value="1" disabled>';

			$html .= '<input type="hidden" name="calendar_' . $this->name . '_next_month" id="calendar_' . $this->name . '_next_month" value="1" disabled>';



			// Include all values



			foreach ( $this->hiddenFields as $key => $value ){



				$html .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';



			}

			$html .= 		'</form>';

			//Navigation left

			//$html .= '<input type="submit" name="calendar_' . $this->name . '_prev_month" value="' . htmlentities( $this->getLang()->getButtonPreviousLabel(), ENT_QUOTES ) . '" class="calendar_nav_button" />';

			$html .= '<a href="#" onclick="'.$this->jsNavigationPrev.'document.getElementById(\'calendar_' . $this->name . '_prev_month\').disabled = false;

					document.getElementById(\'calendar_' . $this->name . 'navigation\').submit();" > ' . $this->arrowLeft . ' </a>';

			$html .= '</th>';

		}		



		if ( $this->displayMonth || $this->displayYear ){

			$html .= '<td class="month"><p>';

			

			if ( $this->displayMonth ){



				$html .= $this->getLang()->getMonthLabel( date('n', $this->date ),1, $this->language ) . " ";



			}



			if ( $this->displayYear ){



				$html .= date( 'Y', $this->date );



			}

			$html .= '</p></td>';

			

		}



		if ( $this->displayNav ){



			$html .= '<th class="next_month">';

			



			// Navigation right



			//$html .= 		'<input type="submit" name="calendar_' . $this->name . '_prev_month" value="' . htmlentities( $this->getLang()->getButtonNextLabel(), ENT_QUOTES ) . '" class="calendar_nav_button" />';

			$html .= '<a href="#" onclick="'.$this->jsNavigationNext.'

					document.getElementById(\'calendar_' . $this->name . '_next_month\').disabled = false;

					document.getElementById(\'calendar_' . $this->name . 'navigation\').submit()"> ' . $this->arrowRight . '</a>';

			

			$html .= 	'</th>';

			

			//$html .= 		'</form>';

			



		}



		$html .= '</tr>';

		$html .= '<tr><td colspan="3" height="10"></td></tr>';

		$html .= '</table>';



		return $html;



	}

	

	public function setJsNavigationNext( $html ){

		$this->jsNavigationNext = $html;

	}

	

	public function setJsNavigationPrev( $html ){

		$this->jsNavigationPrev = $html;

	}

}

?>