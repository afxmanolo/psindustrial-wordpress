<?php
//ProjectLibrary_FrontEnd_FO_Authentification::verifyAuthentification();
require_once('header.php');

$result	  = $this->result;
$Inventario = $result->getResults();
$hiddenFields = empty($this->hiddenFields) ? array() : $this->hiddenFields;


debug($Inventario);
foreach( $Inventario as $Inventario_item){
	echo "<br /><a href='?cmd=loadInventario&id=" . $Inventario_item->getInventarioId()."'>" . $Inventario_item->getInventarioId()."</a> ";
}

echo "<br /><br /><br />";
//print paginator

ProjectLibrary_SDO_Core_Application_Form::foPrintPager($result,"FormInventario","classResultPages","classActualPage",5,$hiddenFields);


require_once('footer.php');
?>
