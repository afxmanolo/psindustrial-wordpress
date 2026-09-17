<?php
class Util_Date extends Objeto {

	public static function FormatTime( $hr, $min, $am_pm = false, $time = null){
		$hr = empty($time) ? $hr : date("H", $time);
		$min = empty($time) ? $min : date("m", $time);
		
		if($am_pm){
			if( $hr > 12 ){
				$am_pm = ' PM';
				$hr -= 12; 
			}else{
				$am_pm = ' AM';
			}
		}else{
			$am_pm = '';
		}
		if($hr < 10){$hr = '0' . (int)$hr; }
		if($min < 10){$min = '0' . (int)$min; }
		
		return $hr . ':' . $min . $am_pm;
		
	}
	
	public static function GetDateFormat($date,$format = '%d de %M de %Y', $lang = 'SP'){
		$sql = "SELECT DATE_FORMAT('".$date."','".$format."') AS date";
		$result = mysql_query($sql);
		$result = mysql_fetch_assoc($result);
		$date = $result['date'];
		$arraySearch = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December','Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
		
		switch($lang){
			case 'SP':
				$arrayReplace = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre','Lunes','Martes','Miercoles','Jueves','Viernes','Sabado','Domingo');
				$date = str_replace($arraySearch,$arrayReplace,$date);
				break;
		}
		return $date;
	}
	
/*
	 * @format: 
	 * %F - Replace to Month name
	 * %w - Replace to day name
	 */
	public function GetDate( $date, $time, $spanish = true){
		$months = array(1=>'Enero','Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
		$monthsAbv = array(1=>'Ene','Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic');
		$week = array('Domingo','Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado');

	    if( $spanish ){
	    	//month name January through December
			$date = str_replace('%F',$months[date("n", $time)], $date);
			//day name 0 (for Domingo) through 6 (for Sábado)
			//w | Numeric representation of the day of the week
			$date = str_replace('%w',$week[date("w", $time)], $date);
			
			$date = str_replace('%M',$monthsAbv[date("n", $time)], $date);
			
			
	    }else{
	    	//month name January through December
	    	$date = str_replace('%F',date("n", $time), $date);
	    	//day name 0 (for Sunday) through 6 (for Saturday)
			//w | Numeric representation of the day of the week
	    	$date = str_replace('%w',date("w", $time), $date);
	    }
	    
	    
	    //Day of the month without leading zeros
	    $date = str_replace('%j',date("j", $time), $date);
		
	    
	    //Year
	    //2009
	    $date = str_replace('%Y',date("Y", $time), $date);
	    //09
	    $date = str_replace('%y',date("y", $time), $date);
	    
	    //Numeric Month
	    $date = str_replace('%m',date("m", $time), $date);
	    
	    //Numeric Day
	    $date = str_replace('%d',date("d", $time), $date);

	    //Hour (00-23)
	    $date = str_replace('%H',date("H", $time), $date);
	    
	    //Minutes
	    $date = str_replace('%i',date("i", $time), $date);
      
	    //Seconds
	    $date = str_replace('%s',date("s", $time), $date);
	    return $date;
		
	}
	
	public static function GetDaysOfWeek($language) {
		$weekdays = array(
			"SP" => array(1=>'Domingo', 'Lunes',  'Martes',  'Miércoles', 'Jueves',   'Viernes', 'Sábado'),
			"EN" => array(1=>'Sunday',  'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday',  'Saturday')
		);
		return $weekdays[$language];
	}
}
?>