<?php
//ProjectLibrary_FrontEnd_FO_Authentification::verifyAuthentification();
require_once('header.php');

$result	  = $this->result;
$InventarioSucursal = $result->getResults();
$hiddenFields = empty($this->hiddenFields) ? array() : $this->hiddenFields;


debug($InventarioSucursal);
foreach( $InventarioSucursal as $InventarioSucursal_item){
	echo "<br /><a href='?cmd=loadInventarioSucursal&id=" . $InventarioSucursal_item->getInventariosucursalId()."'>" . $InventarioSucursal_item->getInventariosucursalId()."</a> ";
}

echo "<br /><br /><br />";
//print paginator

ProjectLibrary_SDO_Core_Application_Form::foPrintPager($result,"FormInventarioSucursal","classResultPages","classActualPage",5,$hiddenFields);


require_once('footer.php');
?>
