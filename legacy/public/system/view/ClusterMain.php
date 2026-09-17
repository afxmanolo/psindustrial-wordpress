<?php
//ProjectLibrary_FrontEnd_FO_Authentification::verifyAuthentification();
require_once('header.php');

$result	  = $this->result;
$Cluster = $result->getResults();
$hiddenFields = empty($this->hiddenFields) ? array() : $this->hiddenFields;


debug($Cluster);
foreach( $Cluster as $Cluster_item){
	echo "<br /><a href='?cmd=loadCluster&id=" . $Cluster_item->getClusterId()."'>" . $Cluster_item->getClusterId()."</a> ";
}

echo "<br /><br /><br />";
//print paginator

ProjectLibrary_SDO_Core_Application_Form::foPrintPager($result,"FormCluster","classResultPages","classActualPage",5,$hiddenFields);


require_once('footer.php');
?>
