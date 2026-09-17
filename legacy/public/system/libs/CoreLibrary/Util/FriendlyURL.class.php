<?php

abstract class Util_FriendlyURL {
	
	public static function formatText($string = '', $accept_ntilde = false, $allow_uppercase = false, $no_title = 'no-title') {
		if(!empty($string)) {
			
			$arr_str = preg_split("/[][¿¡!\"#$%&'()*+,\-.\/:;<=>?@\\\\^_`{|}~\s{´]/", $string);
			foreach($arr_str as $key => $val) {
				if(!empty($val)) {
					$str = iconv(CHARSET_PROJECT,'ASCII//TRANSLIT',$val);
					$str = $accept_ntilde ? str_replace(array('~n','~N'), array('ñ','Ñ'), $str) : $str;
					$regex = $accept_ntilde ? '/[^a-zA-Z0-9ñÑ]/' : '/[^a-zA-Z0-9]/';
					$str = preg_replace($regex, '', $str);
					$arr_url[] = $allow_uppercase ? $str : strtolower($str);
				}
			}
			$string = implode('-', $arr_url);
		}
		return empty($string) ? $no_title : $string;
	}
	
}

?>
