<?php
require_once( "common.php" );
$app = ProjectLibrary_SDO_Core_IO_FileApplication::getIOFileApplication($_REQUEST['type']);
$app->execute();
?>