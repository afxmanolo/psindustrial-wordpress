<?php
require_once("../common.php");

ProjectLibrary_FrontEnd_BO_Authentification::verifyAuthentification();

$app = new ProjectLibrary_FrontEnd_BO_User();

$app->execute();

?>