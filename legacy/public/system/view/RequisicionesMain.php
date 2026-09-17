<?php
//ProjectLibrary_FrontEnd_FO_Authentification::verifyAuthentification();
require_once('header.php');

$result	  = $this->result;
$Requisiciones = $result->getResults();
$hiddenFields = empty($this->hiddenFields) ? array() : $this->hiddenFields;


debug($Requisiciones);
foreach( $Requisiciones as $Requisiciones_item){
	echo "<br /><a href='?cmd=loadRequisiciones&id=" . $Requisiciones_item->getRequisicionesId()."'>" . $Requisiciones_item->getRequisicionesId()."</a> ";
}

echo "<br /><br /><br />";
//print paginator

ProjectLibrary_SDO_Core_Application_Form::foPrintPager($result,"FormRequisiciones","classResultPages","classActualPage",5,$hiddenFields);


require_once('footer.php');
?>
