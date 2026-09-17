<?php
//ProjectLibrary_FrontEnd_FO_Authentification::verifyAuthentification();
require_once('header.php');

$result	  = $this->result;
$Benefits = $result->getResults();
$hiddenFields = empty($this->hiddenFields) ? array() : $this->hiddenFields;


debug($Benefits);
foreach( $Benefits as $Benefits_item){
	echo "<br /><a href='?cmd=loadBenefits&id=" . $Benefits_item->getBenefitsId()."'>" . $Benefits_item->getBenefitsId()."</a> ";
}

echo "<br /><br /><br />";
//print paginator

ProjectLibrary_SDO_Core_Application_Form::foPrintPager($result,"FormBenefits","classResultPages","classActualPage",5,$hiddenFields);


require_once('footer.php');
?>
