<?php
class Util_USStates extends Objeto {
	
	public static function getStates(){
		$state_list = array('AL'=>"Alabama",  
                  			'AK'=>"Alaska",  
                  			'AZ'=>"Arizona",  
                  			'AR'=>"Arkansas",  
                  			'CA'=>"California",  
                  			'CO'=>"Colorado",  
                  			'CT'=>"Connecticut",  
                  			'DE'=>"Delaware",  
                  			'DC'=>"District Of Columbia",  
                  			'FL'=>"Florida",  
                  			'GA'=>"Georgia",  
                  			'HI'=>"Hawaii",  
                  			'ID'=>"Idaho",  
                  			'IL'=>"Illinois",  
                  			'IN'=>"Indiana",  
                  			'IA'=>"Iowa",  
                  			'KS'=>"Kansas",  
                  			'KY'=>"Kentucky",  
                  			'LA'=>"Louisiana",  
                  			'ME'=>"Maine",  
                  			'MD'=>"Maryland",  
                  			'MA'=>"Massachusetts",  
                  			'MI'=>"Michigan",  
                  			'MN'=>"Minnesota",  
                  			'MS'=>"Mississippi",  
                  			'MO'=>"Missouri",  
                  			'MT'=>"Montana",
                  			'NE'=>"Nebraska",
                  			'NV'=>"Nevada",
                  			'NH'=>"New Hampshire",
                  			'NJ'=>"New Jersey",
                  			'NM'=>"New Mexico",
                  			'NY'=>"New York",
                  			'NC'=>"North Carolina",
                  			'ND'=>"North Dakota",
                  			'OH'=>"Ohio",  
                  			'OK'=>"Oklahoma",  
                  			'OR'=>"Oregon",  
                  			'PA'=>"Pennsylvania",  
												'PR'=>"Puerto Rico",
                  			'RI'=>"Rhode Island",  
                  			'SC'=>"South Carolina",  
                  			'SD'=>"South Dakota",
                  			'TN'=>"Tennessee",  
                  			'TX'=>"Texas",  
                  			'UT'=>"Utah",  
                  			'VT'=>"Vermont",  
                  			'VA'=>"Virginia",  
                  			'WA'=>"Washington",  
                  			'WV'=>"West Virginia",  
                  			'WI'=>"Wisconsin",  
                  			'WY'=>"Wyoming");
		return $state_list;
	}

	public static function getHTMLSelect( $selectName, $selectedStateCode = '', $id = '', $CSSclass='' ){
		$states = self::getStates();
		$id = ( $id = '' ) ? $selectName : $id;
		
		$html = '<select name="' . $selectName . '" id="' . $id . '" class="' . $CSSclass . '">';
		foreach( $states as $code => $name ){
			$selected = ( $selectedStateCode == $code || $selectedStateCode == $name ) ? 'selected="SELECTED"' : '';
			$html .= '<option class="' . $CSSclass . '" value="' . $code . '" ' . $selected .' >' . $name . '</option>';
		}
		$html .= '</select>';
		return $html;
	}
	
	public static function getStateName( $code ){
		$states = self::getStates();
		return ( isset( $states[ $code ] ) ? $states[ $code ] : '' );
	}
	
	public static function getStateCode( $name ){
		$states = self::getStates();
		return array_search( $name, $states );
	}
}
?>