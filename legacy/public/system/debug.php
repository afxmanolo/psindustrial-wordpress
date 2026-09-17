<?php
include_once 'common.php';
ProjectLibrary_FrontEnd_Util_Session::Set('debug', true);
if(ProjectLibrary_FrontEnd_Util_Session::Get('debug')){
	echo "El sitio puede ser testeado";
}else{
	echo "Error al aceptar solicitud de testeo";
}