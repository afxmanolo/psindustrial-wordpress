<?php

abstract class GUI_Calendar_CalendarTools extends Objeto{

	

	public static function GetMonthOfDate( $date ){

		return date("m",strtotime($date) );

	}

	public static function GetYearOfDate( $date ){

		return date("Y",strtotime($date) );

	}

	

	public static function GetDayOfDate( $date ){

		return date("d",strtotime($date) );

	}

	

	public static function GetValidDate( $date ){

		return date("Y-m-d",strtotime($date) );

	}

	

	public static function GetMonthName( $month, $lang = "EN" ){

		$month = (!is_numeric( $month ) || $month < 1 || $month >12) ? 1 : $month;

		$monthsSP = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

		$monthsEN = array("January","February","March","April","May","June","July","August","September","Octuber","November","December");

		$months = $lang == "SP" ? $monthsSP : $monthsEN; 

		return $months[ ($month-1) ];

	}

		

}

?>