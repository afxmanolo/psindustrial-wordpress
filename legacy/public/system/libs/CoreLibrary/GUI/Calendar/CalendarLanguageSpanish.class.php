<?php
require_once( "CalendarLanguage.class.php" );

class GUI_Calendar_CalendarLanguageSpanish extends GUI_Calendar_CalendarLanguage {
	
	public function __construct(){
		parent::__construct();
		$this->days[ self::TYPE_SHORT_DESCRIPTION ] = 
				array( 'Dom', 'Lun', 'Mar', 'Mier', 'Jue', 'Vie', 'Sab' );
		$this->days[ self::TYPE_FULL_DESCRIPTION ] = 
				array( 'Domingo', 'Lunes', 'Martes', 'Mi&eacute;rcoles', 'Jueves', 'Viernes', 'S&aacute;bado' );

		$this->months[ self::TYPE_SHORT_DESCRIPTION ] = 
				array( 1 => 'Ene', 	2 => 'Feb', 	3 => 'Mar', 
							 4 => 'Abr', 	5 => 'May', 	6 => 'Jun', 
							 7 => 'Jul', 	8 => 'Ago', 	9 => 'Sep', 
							10 => 'Oct', 11 => 'Nov',  12 => 'Dic' );
		$this->months[ self::TYPE_FULL_DESCRIPTION  ] = 
				array( 1 => 'Enero', 		2 => 'Febrero', 	 3 => 'Marzo', 
							 4 => 'Abril', 		5 => 'Mayo', 			 6 =>  'Junio', 
							 7 => 'Julio', 		8 => 'Agosto', 		 9 => 'Septiembre', 
							10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre' );

		$this->btnGo = 'Go';
		$this->btnPrev = '<<';
		$this->btnNext = '>>';
		$this->btnToday = 'Hoy';
		
	}
	
}
?>