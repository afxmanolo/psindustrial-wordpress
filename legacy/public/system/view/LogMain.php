<?php
//ProjectLibrary_FrontEnd_FO_Authentification::verifyAuthentification();
require_once('header.php');

$result	  = $this->result;
$Log = $result->getResults();
$hiddenFields = empty($this->hiddenFields) ? array() : $this->hiddenFields;


debug($Log);
foreach( $Log as $Log_item){
	echo "<br /><a href='?cmd=loadLog&id=" . $Log_item->getLogId()."'>" . $Log_item->getLogId()."</a> ";
}

echo "<br /><br /><br />";
//print paginator

ProjectLibrary_SDO_Core_Application_Form::foPrintPager($result,"FormLog","classResultPages","classActualPage",5,$hiddenFields);


require_once('footer.php');
?>
