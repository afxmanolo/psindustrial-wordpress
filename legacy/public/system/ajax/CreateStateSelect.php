<?php
require_once("../common.php");

$selectName = $_REQUEST["selectName"];
$selectedCountry = $_REQUEST["selectedCountry"];

$id = $_REQUEST["id"];
$CSSclass = $_REQUEST["CSSclass"];
$defaultText = $_REQUEST["defaultText"];
$defaultValue = $_REQUEST["defaultValue"];

$selectName = empty($selectName) ? 'entity[estado]' : $selectName;
$selectedCountry = empty($selectedCountry) ? 'MX' : $selectedCountry;
$id = empty($id) ? 'estado' : $id;

switch($selectedCountry) {
	case 'MX' :
		echo Util_State::GetHTMLSelect( $selectName, '', $id, $CSSclass, null, $defaultText, $defaultValue );
		break;
	case 'US' :
		echo Util_USStates::GetHTMLSelect( $selectName, '', $id, $CSSclass, '- Please select a state -', '' );
		break;
	default :
		echo '<input type="text" size="30" name="'.$selectName.'" id="'.$id.'" value="" />';
}
?>