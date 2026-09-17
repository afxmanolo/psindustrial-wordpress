<?php
abstract class Util_Server extends Objeto {

	public static function getScript($value = 'basename'){
		$script = $_SERVER['PHP_SELF'];
		$path_info = pathinfo($script);
		switch($value){
			case 'dirname':return $path_info['dirname'];break; 
			case 'basename':return $path_info['basename'];break;
			case 'extension':return $path_info['extension'];break;
		}
	}
	
}
?>