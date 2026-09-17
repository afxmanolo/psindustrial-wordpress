<?php
//$CHARSET = 'utf-8';
require_once("../common.php");

ProjectLibrary_FrontEnd_BO_Authentification::verifyAuthentification();

$app = new ProjectLibrary_FrontEnd_BO_Productos();

$app->execute();

?>