<?php
class Util_CountryISOCodes extends Objeto {

	public static function getCountries(){
	$coutries = array();
    $countries['AF'] = 'Afghanistan';
    $countries['AL'] = 'Albania';
    $countries['DZ'] = 'Algeria';
    $countries['AD'] = 'Andorra';
    $countries['AO'] = 'Angola';
    $countries['AI'] = 'Anguilla';
    $countries['AG'] = 'Antigua and Barbuda';
    $countries['AR'] = 'Argentina';
    $countries['AM'] = 'Armenia';
    $countries['AW'] = 'Aruba';
    $countries['AU'] = 'Australia';
    $countries['AT'] = 'Austria';
    $countries['BS'] = 'Bahamas';
    $countries['BH'] = 'Bahrain';
    $countries['BB'] = 'Barbados';
    $countries['BD'] = 'BBangladesh';
    $countries['BE'] = 'Belgium';
    $countries['BZ'] = 'Belize';
    $countries['BM'] = 'Bermuda';
    $countries['BT'] = 'Bhutan';
    $countries['BO'] = 'Bolivia';
    $countries['BW'] = 'Botswana';
    $countries['BR'] = 'Brazil';
    $countries['BN'] = 'Brunei';
    $countries['BG'] = 'Bulgaria';
    $countries['BI'] = 'Burundi';
    $countries['KH'] = 'Cambodia';
    $countries['CA'] = 'Canada';
    $countries['CV'] = 'Cape Verde';
    $countries['KY'] = 'Cayman Islands';
    $countries['CF'] = 'Central African Republic';
    $countries['CL'] = 'Chile';
    $countries['CN'] = 'China';
    $countries['CO'] = 'Colombia';
    $countries['KM'] = 'Comoros';
    $countries['CR'] = 'Costa Rica';
    $countries['HR'] = 'Croatia';
    $countries['CU'] = 'Cuba';
    $countries['CY'] = 'Cyprus';
    $countries['CZ'] = 'Czech Republic';
    $countries['DK'] = 'Denmark';
    $countries['DJ'] = 'Djibouti';
    $countries['DO'] = 'Dominican Republic';
    $countries['EC'] = 'Ecuador';
    $countries['EG'] = 'Egypt';
    $countries['SV'] = 'El Salvador';
    $countries['EE'] = 'Estonia';
    $countries['ET'] = 'Ethiopia';
    $countries['FK'] = 'Falkland Islands';
    $countries['FJ'] = 'Fiji';
    $countries['FI'] = 'Finland';
    $countries['FR'] = 'France';
    $countries['GM'] = 'Gambia';
    $countries['DE'] = 'Germany';
    $countries['GH'] = 'Ghana';
    $countries['GI'] = 'Gibraltar';
    $countries['GR'] = 'Greece';
    $countries['GT'] = 'Guatemala';
    $countries['GN'] = 'Guinea';
    $countries['GY'] = 'Guyana';
    $countries['HT'] = 'Haiti';
    $countries['HN'] = 'Honduras';
    $countries['HK'] = 'Hong Kong';
    $countries['HU'] = 'Hungary';
    $countries['IS'] = 'Iceland';
    $countries['IN'] = 'India';
    $countries['ID'] = 'Indonesia';
    $countries['IR'] = 'Iran';
    $countries['IQ'] = 'Iraq';
    $countries['IE'] = 'Ireland';
    $countries['IL'] = 'Israel';
    $countries['IT'] = 'Italy';
    $countries['JM'] = 'Jamaica';
    $countries['JP'] = 'Japan';
    $countries['JO'] = 'Jordan';
    $countries['KZ'] = 'Kazakhstan';
    $countries['KE'] = 'Kenya';
    $countries['KW'] = 'Kuwait';
    $countries['LA'] = 'Laos';
    $countries['LV'] = 'Latvia';
    $countries['LB'] = 'Lebanon';
    $countries['LS'] = 'Lesotho';
    $countries['LR'] = 'Liberia';
    $countries['LY'] = 'Libya';
    $countries['LT'] = 'Lithuania';
    $countries['LU'] = 'Luxembourg';
    $countries['MO'] = 'Macao';
    $countries['MG'] = 'Madagascar';
    $countries['MW'] = 'Malawi';
    $countries['MY'] = 'Malaysia';
    $countries['MV'] = 'Maldives';
    $countries['MT'] = 'Malta';
    $countries['MR'] = 'Mauritania';
    $countries['MU'] = 'Mauritius';
    $countries['MX'] = 'Mexico';
    $countries['MN'] = 'Mongolia';
    $countries['MA'] = 'Morocco';
    $countries['MZ'] = 'Mozambique';
    $countries['MM'] = 'Myanmar';
    $countries['NA'] = 'Namibia';
    $countries['NP'] = 'Nepal';
    $countries['AN'] = 'Netherlands Antilles';
    $countries['NL'] = 'Netherlands';
    $countries['NZ'] = 'New Zealand';
    $countries['NI'] = 'Nicaragua';
    $countries['NG'] = 'Nigeria';
    $countries['KP'] = 'North Korea';
    $countries['NO'] = 'Norway';
    $countries['OM'] = 'Oman';
    $countries['PK'] = 'Pakistan';
    $countries['PA'] = 'Panama';
    $countries['PG'] = 'Papua New Guinea';
    $countries['PY'] = 'Paraguay';
    $countries['PE'] = 'Peru';
    $countries['PH'] = 'Philippines';
    $countries['PL'] = 'Poland';
    $countries['PT'] = 'Portugal';
    $countries['QA'] = 'Qatar';
    $countries['RO'] = 'Romania';
    $countries['RU'] = 'Russia';
    $countries['SH'] = 'Saint Helena';
    $countries['WS'] = 'Samoa';
    $countries['ST'] = 'Sao Tome/Principe';
    $countries['SA'] = 'Saudi Arabia';
    $countries['SC'] = 'Seychelles';
    $countries['SL'] = 'Sierra Leone';
    $countries['SG'] = 'Singapore';
    $countries['SK'] = 'Slovakia';
    $countries['SI'] = 'Slovenia';
    $countries['SB'] = 'Solomon Islands';
    $countries['SO'] = 'Somalia';
    $countries['ZA'] = 'South Africa';
    $countries['KR'] = 'South Korea';
    $countries['ES'] = 'Spain';
    $countries['LK'] = 'Sri Lanka';
    $countries['SD'] = 'Sudan';
    $countries['SR'] = 'Suriname';
    $countries['SZ'] = 'Swaziland';
    $countries['SE'] = 'Sweden';
    $countries['CH'] = 'Switzerland';
    $countries['SY'] = 'Syria';
    $countries['TW'] = 'Taiwan';
    $countries['TZ'] = 'Tanzania';
    $countries['TH'] = 'Thailand';
    $countries['TO'] = 'Tonga';
    $countries['TT'] = 'Trinidad/Tobago';
    $countries['TN'] = 'Tunisia';
    $countries['TR'] = 'Turkey';
    $countries['UG'] = 'Uganda';
    $countries['UA'] = 'Ukraine';
    $countries['AE'] = 'United Arab Emirates';
    $countries['GB'] = 'United Kingdom';
    $countries['US'] = 'United States';
    $countries['UY'] = 'Uruguay';
    $countries['VU'] = 'Vanuatu';
    $countries['VE'] = 'Venezuela';
    $countries['VN'] = 'Viet Nam';
    $countries['ZM'] = 'Zambia';
    $countries['ZW'] = 'Zimbabwe';
    		
		return $countries;
	}

	public static function getHTMLSelect( $selectName, $selectedCountry = '', $id = '', $CSSclass='', $selectParams ='' ){
		$countries = self::getCountries();
		$id = ( $id = '' ) ? $selectName : $id;
		
		$html = '<select name="' . $selectName . '" id="' . $id . '" class="' . $CSSclass . '" ' . $selectParams . '>';
		foreach( $countries as $code => $name ){
			$selected = ( $selectedCountry == $code || $selectedCountry == $name ) ? 'selected="SELECTED"' : '';
			$html .= '<option class="' . $CSSclass . '" value="' . $code . '" ' . $selected .' >' . $name . '</option>';
		}
		$html .= '</select>';
		return $html;
	}
	
	public static function getCountryName( $code ){
		$countries = self::getCountries();
		return ( isset( $countries[ $code ] ) ? $countries[ $code ] : '' );
	}
	
	public static function getCountryCode( $name ){
		$countries = self::getCountries();
		return array_search( $name, $countries );
	}
}
?>