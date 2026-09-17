<?php
class Util_String extends Objeto {
	public static function validString($cadena, $adScapeFrom = array(), $adScapeTo = array() ){
		$tofind = explode(",","À,Á,Â,Ã,Ä,Å,à,á,â,ã,ä,å,Ò,Ó,Ô,Õ,Ö,Ø,ò,ó,ô,õ,ö,ø,È,É,Ê,Ë,è,é,ê,ë,Ç,ç,Í,Ì,À?,Î,À?,ì,í,î,ï,Ù,Ú,Û,Ü,ù,ú,û,ü,ÿ,Ñ,ñ");
		$replac = explode(",","A,A,A,A,A,A,a,a,a,a,a,a,O,O,O,O,O,O,o,o,o,o,o,o,E,E,E,E,e,e,e,e,C,c,I,I,I,I,I,i,i,i,i,U,U,U,U,u,u,u,u,y,N,n");
		$result = str_replace($tofind,$replac,$cadena);
		
		$result = ( !empty($adScapeFrom) &&  !empty($adScapeTo) && (count($adScapeFrom) == count($adScapeTo)) ) 
		? str_replace($adScapeFrom,$adScapeTo,$result) : $result;
		
		return $result;
	}
	
	public static function validStringForUrl($cad, $scapeUnderLine = false,$exception=false){
		$str = self::validString($cad,array(" ", "/",'"', "'",".", ",",'"',"'","?","�" ),(($exception) ? array("-", "/",  "",  "", "",  "", "", "","" ,""  )	  : array("-", "",  "",  "", "",  "", "", "","" ,""  )));
		$str = $scapeUnderLine ? str_replace("_","",$str) : $str;
		return empty($str) ? 'sintitulo' : $str;
	}
	
	function validStringForMap($cad, $scapeUnderLine = false){
	
		$str = self::validString($cad,
				array(" ", "/",'"', "'",".", ",",'"',"'","?","-","&","´","4","!","(",")" ),
				array("_", "",  "",  "", "",  "", "", "","" ,"","","","","","","")
	
		);
	
		$str = $scapeUnderLine ? str_replace("_","",$str) : $str;
	
		return empty($str) ? 'sintitulo' : $str;
	
	}
	
	function divideInColumns($text = '', $columns = 2, $atom = 'paragraphs', $minCharsToSplit = 0){
		$text = trim($text);
		if(!empty($text)) {
			$tempText = $text;
			$textInColumns = array();
			if($columns > 1 && strlen($text) > $minCharsToSplit) {
				if($atom == "paragraphs") {
					$split = split("\n", $text);
					// remove empty lines due to \n\n
					foreach($split as $key => $value) {
						if(preg_match("/^\s*$/", $value)) {
							unset($split[$key]);
						}
					}
					$maxItemsPerCol = ceil(count($split)/$columns);
					$col = 0;
					$i = 0;
					foreach($split as $parag) {
						if(++$i%$maxItemsPerCol == 0) {
							$textInColumns[$col++] .= $parag."\n\n";
						} else {
							$textInColumns[$col] .= $parag."\n\n";
						}
					}
				} else {
					for($col=0; $col<$columns-1; $col++) {
						$cutPoint = ceil(strlen($tempText) / ($columns-$col));
						switch($atom) {
							case 'chars':	$cutPoint = $cutPoint;	break;
							case 'words':
								$nearestRightSpace = strpos($tempText, ' ', $cutPoint) - $cutPoint;
								$nearestLeftSpace = abs(strpos(strrev($tempText), ' ', $cutPoint) - $cutPoint);
								if($nearestLeftSpace < $nearestRightSpace)
									$cutPoint = $cutPoint - ($nearestLeftSpace + 1);
								else
									$cutPoint = $cutPoint + $nearestRightSpace + 1;
								break;
						}
						$textInColumns[] = trim(substr($tempText, 0, $cutPoint));
						$tempText = substr($tempText, $cutPoint);
					}
					$textInColumns[] = trim($tempText);
				}
			} else {
				$textInColumns[0] = $text;
			}
		}
		return $textInColumns;
	}
	
	public static function subText($text = '', $length = 0, $atom = 'chars', $concatEnd = '...') {
		if($text && $length) {
			if($atom == 'chars') {
				if($length < strlen($text)) {
					$text = strrev(substr($text, 0, $length));
					$text = strrev(substr($text, strpos($text, ' ')+1)).$concatEnd;
				}
			} elseif($atom == 'words') {
				$arr = split(' ', trim($text));
				if($length < count($arr)) {
					$text = "";
					for($i = 0; $i < $length; $i++) {
						$text .= $arr[$i].' ';
					}
					$text = trim($text).$concatEnd;
				}
			}
		}
		return $text;
	}

	public static function textByHyperlink($string, $clase = 'gris13'){
		/*
		$proto = "((ht|f)tps)";
	    $host = "([a-z\d][-a-z\d]*[a-z\d]\.)+[a-z][-a-z\d]*[a-z]";
	    $port = "(:\d{1,})?";
	    $path = "(\/[^?<>\#\"\s]+)?";
	    $query = "(\?[^<>\#\"\s]+)?";
	    $url = "#(((www\.)?)\w+\.\w+)#i";
	    $link = "";
	    $link = preg_replace($url, "<a href='http://$1' target='_blank'><b>$1</b></a>", $string);
	    if($link == ""){
	    	$link = preg_replace("#((ht|f)tps?:\/\/{$host}{$port}{$path}{$query})#i", "<a href='$1' target='_blank'><b>$1</b></a>", $string);
	    }
	    return $link;
	  }*/
		$host = "([a-z\d][-a-z\d]*[a-z\d]\.)+[a-z][-a-z\d]*[a-z]";
	    $port = "(:\d{1,})?";
	    $path = "(\/[^?<>\#\"\s]+)?";
	    $query = "(\?[^<>\#\"\s]+)?";
	    return preg_replace("#((ht|f)tps?:\/\/({$host}){$port}{$path}{$query})#i", "<a class='".$clase."' target='_blanck' href='$1'>$1</a>", $string);
	}
	
	public static function generarClave($str_time = NULL){
		$str_time 		= empty($str_time) ? (string) time() : $str_time;
		$array_str_time = str_split($str_time);
	
		//$BASE_OPER 	= array(8,2,6,9,4,1,7,3,7,5);
		/*
		 * Seria la semilla a la hora de generar las claves
		*/
		$BASE_OPER = array(7,2,9,6,1,3);
	
		$BASE_CHAR	= str_split('NWXHPQNRSTUAYZKEFGHIOBCPQLMDVJILWNF');
		$BASE_DIGI	= str_split('5045782323867916419');
		$t			= count($BASE_OPER);
	
		$clave		= '';
		$tipo_char 	= 'letra';
		$tipo_oper	= '+';
		for($i=0; $i<$t; $i++){
			switch ($tipo_char){
				case 'letra':
					$clave .= $BASE_CHAR[self::operacion($array_str_time[$i] + 9, $BASE_OPER[$i], $tipo_oper)];
					break;
				case 'digito':
					$clave .= $BASE_DIGI[self::operacion($array_str_time[$i] + 9, $BASE_OPER[$i], $tipo_oper)];
					break;
			}
			$tipo_char = $tipo_char == 'letra' ? 'digito' : 'letra';
			$tipo_oper = $tipo_oper == '+' ? '-' : '+';
		}
	
		$clave_array = str_split($clave);
		$i = 1;
		$clave = '';
		foreach($clave_array as $char){
			if($i%2 == 0 ){
				$clave .= $BASE_CHAR[rand(0,34)];
			}
			$clave .= $char;
			$i++;
		}
	
		return $clave;
	}
	
	function operacion($valor1, $valor2, $tipo){
		switch ($tipo){
			case '+': return $valor1 + $valor2;
			case '-': return $valor1 - $valor2;
		}
	}

	public static function getFile(){
		$url = $_SERVER['PHP_SELF'];
		$url = explode('/',$url);
		$url = end($url);
		return $url;
	}

	public static function splitCamelCase($str){
		return preg_split('/(?<=\\w)(?=[A-Z])/', $str);
	}

	public static function Makecamelcase($str)
	{
		$upper=ucwords($str);
		$str=str_replace(' ', '', $upper);
		return self::validString($str);
	}
}

?>
