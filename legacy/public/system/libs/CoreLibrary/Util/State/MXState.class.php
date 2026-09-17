<?php
class Util_State_MXState extends Util_State {
	
	
	public function GetStates(){
		$states = array();
   	$states[ 'AGS'  ] = array( 'state_short_name' => 'AGS',  'name'=> 'Aguascalientes', 'zone'=>0);
    $states[ 'BCN' ] = array( 'state_short_name' => 'BCN', 'name'=> 'Baja California', 'zone'=>0);
		$states[ 'BCS' ] = array( 'state_short_name' => 'BCS', 'name'=> 'Baja California Sur', 'zone'=>0);
		$states[ 'CAM' ] = array( 'state_short_name' => 'CAM', 'name'=> 'Campeche', 'zone'=>0);
		$states[ 'CHI' ] = array( 'state_short_name' => 'CHI', 'name'=> 'Chiapas', 'zone'=>0);
		$states[ 'CHI' ] = array( 'state_short_name' => 'CHU', 'name'=> 'Chihuahua', 'zone'=>0);
		$states[ 'COA' ] = array( 'state_short_name' => 'CHU', 'name'=> 'Coahuila', 'zone'=>0);
		$states[ 'COL' ] = array( 'state_short_name' => 'COL', 'name'=> 'Colima', 'zone'=>0);		
		$states[ 'DF' ] = array( 'state_short_name' => 'DF', 'name'=> 'Distrito Federal', 'zone'=>0);
		$states[ 'DUG' ] = array( 'state_short_name' => 'DUG', 'name'=> 'Durango', 'zone'=>0);
		$states[ 'GTO' ] = array( 'state_short_name' => 'GTO', 'name'=> 'Guanajuato', 'zone'=>0);
		$states[ 'GRO' ] = array( 'state_short_name' => 'GRO', 'name'=> 'Guerrero', 'zone'=>0);
		$states[ 'HGO' ] = array( 'state_short_name' => 'HGO', 'name'=> 'Hidalgo', 'zone'=>0);
		$states[ 'JAL' ] = array( 'state_short_name' => 'JAL', 'name'=> 'Jalisco', 'zone'=>0);
		$states[ 'EMX' ] = array( 'state_short_name' => 'EMX', 'name'=> 'Estado de M&eacute;xico', 'zone'=>0);
		$states[ 'MIC' ] = array( 'state_short_name' => 'MIC', 'name'=> 'Michoac&aacute;n', 'zone'=>0);
		$states[ 'MOR' ] = array( 'state_short_name' => 'MOR', 'name'=> 'Morelos', 'zone'=>0);
		
		$states[ 'NAY' ] = array( 'state_short_name' => 'NAY', 'name'=> 'Nayarit', 'zone'=>0); 
		$states[ 'NUE' ] = array( 'state_short_name' => 'NUE', 'name'=> 'Nuevo Le&oacute;n', 'zone'=>0); 
		$states[ 'OAX' ] = array( 'state_short_name' => 'OAX', 'name'=> 'Oaxaca', 'zone'=>0); 
		$states[ 'PUE' ] = array( 'state_short_name' => 'PUE', 'name'=> 'Puebla', 'zone'=>0); 
		$states[ 'QUE' ] = array( 'state_short_name' => 'QUE', 'name'=> 'Quer&eacute;taro', 'zone'=>0); 
		$states[ 'QUI' ] = array( 'state_short_name' => 'QUI', 'name'=> 'Quintana Roo', 'zone'=>0); 
		$states[ 'SAN' ] = array( 'state_short_name' => 'SAN', 'name'=> 'San Luis Potos&iacute;', 'zone'=>0); 
		$states[ 'SIN' ] = array( 'state_short_name' => 'SIN', 'name'=> 'Sinaloa', 'zone'=>0); 
		$states[ 'SON' ] = array( 'state_short_name' => 'SON', 'name'=> 'Sonora', 'zone'=>0); 
		$states[ 'TAB' ] = array( 'state_short_name' => 'TAB', 'name'=> 'Tabasco', 'zone'=>0); 
		$states[ 'TAM' ] = array( 'state_short_name' => 'TAM', 'name'=> 'Tamaulipas', 'zone'=>0); 
		$states[ 'TLA' ] = array( 'state_short_name' => 'TLA', 'name'=> 'Tlaxcala', 'zone'=>0); 
		$states[ 'VER' ] = array( 'state_short_name' => 'VER', 'name'=> 'Veracruz', 'zone'=>0); 
		$states[ 'YUC' ] = array( 'state_short_name' => 'YUC', 'name'=> 'Yucat&aacute;n', 'zone'=>0); 
		$states[ 'ZAC' ] = array( 'state_short_name' => 'ZAC', 'name'=> 'Zacatecas', 'zone'=>0); 
		
		
		return $states;
	}
	
	public function GetHTMLSelect( $selectName, $selected = '', $id = '', $CSSclass='', $selectParams ='', $inAjax = false, $defaultText = '' ){
		$states = self::GetStates();
		return ($inAjax) ? parent::GetHTMLDivAndAjax( $selectName, $selected, $id, $CSSclass, $selectParams, $states, $defaultText) :
		parent::GetHTMLSelect( $selectName, $selected, $id, $CSSclass, $selectParams, $states, $defaultText);
	}
	
	public static function GetStateName( $state ){
		$states = self::GetStates();
		return $states[$state]['name'];
	}
}
?>