<?php
abstract class Util_HTML extends Objeto {

	public static function CreateDropDown( $name, $id, $options, $selected, $cssClass = '', $cssStyle = '', $return = false ){
		$html = "<select name=\"$name\" id=\"$id\" class=\"$cssClass\" style=\"$cssStyle\">";
		$html .= self::CreateDropDownContent( $options, $selected, $cssClass, true );
		$html .= "</select>";
		if ( $return ){
			return $html;
		}
		else {
			echo $html;
		}
	}
	
	public static function CreateDropDownContent( $options, $selectedIndex, $class = '', $return = false ){
		$html = '';
		foreach( $options as $id => $label ){
			$selected = ( $id == $selectedIndex ) ? 'selected="SELECTED"' : '';
			$html .= "<option value=\"$id\" class=\"$class\" $selected>$label</option>";
		}
		if ( $return ){
			return $html;
		}
		else {
			echo $html;
		}
	}
	
}
?>