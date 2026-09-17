<?php
//ProjectLibrary_FrontEnd_FO_Authentification::verifyAuthentification();
require_once('header.php');

$result	  = $this->result;
$Sucursales = $result->getResults();
$hiddenFields = empty($this->hiddenFields) ? array() : $this->hiddenFields;


debug($Sucursales);
foreach( $Sucursales as $Sucursales_item){
	echo "<br /><a href='?cmd=loadSucursales&id=" . $Sucursales_item->getSucursalesId()."'>" . $Sucursales_item->getSucursalesId()."</a> ";
}

echo "<br /><br /><br />";
//print paginator

ProjectLibrary_SDO_Core_Application_Form::foPrintPager($result,"FormSucursales","classResultPages","classActualPage",5,$hiddenFields);


require_once('footer.php');
?>
