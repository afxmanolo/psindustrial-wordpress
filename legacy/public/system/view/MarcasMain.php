<?php
//ProjectLibrary_FrontEnd_FO_Authentification::verifyAuthentification();
require_once('header.php');

$result	  = $this->result;
$Marcas = $result->getResults();
$hiddenFields = empty($this->hiddenFields) ? array() : $this->hiddenFields;


debug($Marcas);
foreach( $Marcas as $Marcas_item){
	echo "<br /><a href='?cmd=loadMarcas&id=" . $Marcas_item->getMarcasId()."'>" . $Marcas_item->getMarcasId()."</a> ";
}

echo "<br /><br /><br />";
//print paginator

ProjectLibrary_SDO_Core_Application_Form::foPrintPager($result,"FormMarcas","classResultPages","classActualPage",5,$hiddenFields);


require_once('footer.php');
?>
