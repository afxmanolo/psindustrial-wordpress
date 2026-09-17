<?php
class Util_State_USState extends Util_State {
	
	
	public function GetStates(){
$states = array();
$states[ 'ALA' ] = array( 'state_short_name' => 'AL', 'name'=> 'Alaska', 'zone'=>0);
$states[ 'ABA' ] = array( 'state_short_name' => 'ABA', 'name'=> 'Alabama', 'zone'=>0);
$states[ 'ARI' ] = array( 'state_short_name' => 'ARI', 'name'=> 'Arizona', 'zone'=>0); 
$states[ 'ARK' ] = array( 'state_short_name' => 'ARK', 'name'=> 'Arkansas', 'zone'=>0); 
$states[ 'CAL' ] = array( 'state_short_name' => 'CAL', 'name'=> 'California', 'zone'=>0); 
$states[ 'COL' ] = array( 'state_short_name' => 'COL', 'name'=> 'Colorado', 'zone'=>0); 
$states[ 'CON' ] = array( 'state_short_name' => 'CON', 'name'=> 'Connecticut', 'zone'=>0); 
$states[ 'DEL' ] = array( 'state_short_name' => 'DEL', 'name'=> 'Delaware', 'zone'=>0); 
$states[ 'DIS' ] = array( 'state_short_name' => 'DIS', 'name'=> 'District of Columbia', 'zone'=>0); 
$states[ 'FLO' ] = array( 'state_short_name' => 'FLO', 'name'=> 'Florida', 'zone'=>0); 
$states[ 'GEO' ] = array( 'state_short_name' => 'GEO', 'name'=> 'Georgia', 'zone'=>0); 
$states[ 'HAW' ] = array( 'state_short_name' => 'HAW', 'name'=> 'Hawaii', 'zone'=>0); 
$states[ 'IDA' ] = array( 'state_short_name' => 'IDA', 'name'=> 'Idaho', 'zone'=>0); 
$states[ 'ILL' ] = array( 'state_short_name' => 'ILL', 'name'=> 'Illinois', 'zone'=>0); 
$states[ 'IND' ] = array( 'state_short_name' => 'IND', 'name'=> 'Indiana', 'zone'=>0); 
$states[ 'IOW' ] = array( 'state_short_name' => 'IOW', 'name'=> 'Iowa', 'zone'=>0); 
$states[ 'KAN' ] = array( 'state_short_name' => 'KAN', 'name'=> 'Kansas', 'zone'=>0); 
$states[ 'KEN' ] = array( 'state_short_name' => 'KEN', 'name'=> 'Kentucky', 'zone'=>0); 
$states[ 'LOU' ] = array( 'state_short_name' => 'LOU', 'name'=> 'Louisiana', 'zone'=>0); 
$states[ 'MAI' ] = array( 'state_short_name' => 'MAI', 'name'=> 'Maine', 'zone'=>0); 
$states[ 'MAR' ] = array( 'state_short_name' => 'MAR', 'name'=> 'Maryland', 'zone'=>0); 
$states[ 'MAS' ] = array( 'state_short_name' => 'MAS', 'name'=> 'Massachusetts', 'zone'=>0); 
$states[ 'MIC' ] = array( 'state_short_name' => 'MIC', 'name'=> 'Michigan', 'zone'=>0); 
$states[ 'MIN' ] = array( 'state_short_name' => 'MIN', 'name'=> 'Minnesota', 'zone'=>0); 
$states[ 'MIS' ] = array( 'state_short_name' => 'MIS', 'name'=> 'Mississippi', 'zone'=>0); 
$states[ 'MIS' ] = array( 'state_short_name' => 'MIS', 'name'=> 'Missouri', 'zone'=>0); 
$states[ 'MON' ] = array( 'state_short_name' => 'MON', 'name'=> 'Montana', 'zone'=>0); 
$states[ 'NEB' ] = array( 'state_short_name' => 'NEB', 'name'=> 'Nebraska', 'zone'=>0); 
$states[ 'NEV' ] = array( 'state_short_name' => 'NEV', 'name'=> 'Nevada', 'zone'=>0); 
$states[ 'NEW' ] = array( 'state_short_name' => 'NEW', 'name'=> 'New Hampshire', 'zone'=>0); 
$states[ 'NEW' ] = array( 'state_short_name' => 'NEW', 'name'=> 'New Jersey', 'zone'=>0); 
$states[ 'NEW' ] = array( 'state_short_name' => 'NEW', 'name'=> 'New Mexico', 'zone'=>0); 
$states[ 'NEW' ] = array( 'state_short_name' => 'NEW', 'name'=> 'New York', 'zone'=>0); 
$states[ 'NOR' ] = array( 'state_short_name' => 'NOR', 'name'=> 'North Carolina', 'zone'=>0); 
$states[ 'NOR' ] = array( 'state_short_name' => 'NOR', 'name'=> 'North Dakota', 'zone'=>0); 
$states[ 'OHI' ] = array( 'state_short_name' => 'OHI', 'name'=> 'Ohio', 'zone'=>0); 
$states[ 'OKL' ] = array( 'state_short_name' => 'OKL', 'name'=> 'Oklahoma', 'zone'=>0); 
$states[ 'ORE' ] = array( 'state_short_name' => 'ORE', 'name'=> 'Oregon', 'zone'=>0); 
$states[ 'PEN' ] = array( 'state_short_name' => 'PEN', 'name'=> 'Pennsylvania', 'zone'=>0); 
$states[ 'RHO' ] = array( 'state_short_name' => 'RHO', 'name'=> 'Rhode Island', 'zone'=>0); 
$states[ 'SOU' ] = array( 'state_short_name' => 'SOU', 'name'=> 'South Carolina', 'zone'=>0); 
$states[ 'SOU' ] = array( 'state_short_name' => 'SOU', 'name'=> 'South Dakota', 'zone'=>0); 
$states[ 'TEN' ] = array( 'state_short_name' => 'TEN', 'name'=> 'Tennessee', 'zone'=>0); 
$states[ 'TEX' ] = array( 'state_short_name' => 'TEX', 'name'=> 'Texas', 'zone'=>0); 
$states[ 'UTA' ] = array( 'state_short_name' => 'UTA', 'name'=> 'Utah', 'zone'=>0); 
$states[ 'VER' ] = array( 'state_short_name' => 'VER', 'name'=> 'Vermont', 'zone'=>0); 
$states[ 'VIR' ] = array( 'state_short_name' => 'VIR', 'name'=> 'Virginia', 'zone'=>0); 
$states[ 'WAS' ] = array( 'state_short_name' => 'WAS', 'name'=> 'Washington', 'zone'=>0); 
$states[ 'WES' ] = array( 'state_short_name' => 'WES', 'name'=> 'West Virginia', 'zone'=>0); 
$states[ 'WIS' ] = array( 'state_short_name' => 'WIS', 'name'=> 'Wisconsin', 'zone'=>0); 
$states[ 'WYO' ] = array( 'state_short_name' => 'WYO', 'name'=> 'Wyoming', 'zone'=>0); 
    	
    
		
		
		return $states;
	}
	
	public function GetHTMLSelect( $selectName, $selected = '', $id = '', $CSSclass='', $selectParams ='', $inAjax = false, $defaultText = ''){
		$states = self::GetStates();
		return ($inAjax) ? parent::GetHTMLDivAndAjax( $selectName, $selected, $id, $CSSclass, $selectParams, $states, $defaultText) :
		parent::GetHTMLSelect( $selectName, $selected, $id, $CSSclass, $selectParams, $states, $defaultText);
	}

}
?>