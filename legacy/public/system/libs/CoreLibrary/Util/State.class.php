<?php
class Util_State extends Objeto {
	
	public function GetStates(){
		return array();
	}

	public function GetHTMLSelect( $selectName, $selected = '', $id = '', $CSSclass='', $selectParams ='', $states =array(), $defaultText = ''){
		if( !empty($states) ){
			asort($states);
			
			$id = ( $id == '' ) ? $selectName : $id;
			$html = '<select name="' . $selectName . '" id="' . $id . '" class="' . $CSSclass . '" ' . $selectParams . '>';
			if(!empty($defaultText)) {
				$html .= "<option value=''>$defaultText</option>";
			}
			foreach( $states as $code => $state ){
				
				$name = $state["name"];
				
				$selectedCodeHTML = ( $selected == $code || $selected == $name ) ? 'selected="SELECTED"' : '';
				$html .= '<option class="' . $CSSclass . '" value="' . $code . '" ' . $selectedCodeHTML .' >' . $name . '</option>';
			}
			$html .= '</select>';
		}else{
			$html = '<input type="text" class="' . $CSSclass . '" name="' . $selectName . '" id="' . $id . '" value="' . $selected .'" >';	
		}
		
		return $html;
	}

	public function GetHTMLDivAndAjax( $selectName, $selected = '', $id = '', $CSSclass='', $selectParams ='', $states =array(), $defaultText = '' ){
		$htmlReturn = array();
		
		$html = self::GetHTMLSelect( $selectName, $selected, $id, $CSSclass, $selectParams, $states, $defaultText );
		
		$html = "<div id='country_states_select_container'>" . $html . "</div>";
				
		$html .= 
		"<script type='text/javascript'>".
			"function getCountryStatesSelect( country ) {".
				"$('#country_states_select_container').load(".
					"'" . ABS_HTTP_URL . BO_DIRECTORY . "ajax/CreateStateSelect.php',".
					"{selectName : '$selectName',".
						"selectedCountry : country,".
						"id : '$id',".
						"CSSclass : '$CSSclass',".
						"defaultText : '$defaultText',".
					"}".
				");".
			"}".
		"</script>";
		
		return $html; 
	} 
}
?>