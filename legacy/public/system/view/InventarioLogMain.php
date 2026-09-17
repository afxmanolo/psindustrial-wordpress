<?php
//ProjectLibrary_FrontEnd_FO_Authentification::verifyAuthentification();
require_once('header.php');

$result	  = $this->result;
$InventarioLog = $result->getResults();
$hiddenFields = empty($this->hiddenFields) ? array() : $this->hiddenFields;


debug($InventarioLog);
foreach( $InventarioLog as $InventarioLog_item){
	echo "<br /><a href='?cmd=loadInventarioLog&id=" . $InventarioLog_item->getInventariologId()."'>" . $InventarioLog_item->getInventariologId()."</a> ";
}

echo "<br /><br /><br />";
//print paginator

ProjectLibrary_SDO_Core_Application_Form::foPrintPager($result,"FormInventarioLog","classResultPages","classActualPage",5,$hiddenFields);


require_once('footer.php');
?>
