<?php
//ProjectLibrary_FrontEnd_FO_Authentification::verifyAuthentification();
require_once('header.php');

$result	  = $this->result;
$Reporte = $result->getResults();
$hiddenFields = empty($this->hiddenFields) ? array() : $this->hiddenFields;


debug($Reporte);
foreach( $Reporte as $Reporte_item){
	echo "<br /><a href='?cmd=loadReporte&id=" . $Reporte_item->getReporteId()."'>" . $Reporte_item->getReporteId()."</a> ";
}

echo "<br /><br /><br />";
//print paginator

ProjectLibrary_SDO_Core_Application_Form::foPrintPager($result,"FormReporte","classResultPages","classActualPage",5,$hiddenFields);


require_once('footer.php');
?>
