<?php
ini_set("display_errors",0);
error_reporting(0);

include_once 'sendMail/sendMail.php';
/*
 * VALORES FUNCIONALIDAD
 */ 
$CMD 					= 'send';
$VARIABLE_POST 			= "entity";
$ENVIAR_POR_SMTP 		= true;
$ENVIAR_EMAIL_A_USUARIO = false;
$TEST 					= false;
$EMAIL_TEST 			= 'jcog21983@hotmail.com';
/*
 * FORMULARIO
 */
$fields[] = array("name" =>"Nombre",		"type" => "string", 	"required" => true);
$fields[] = array("name" =>"Email", 		"type" => "email", 		"required" => true);
$fields[] = array("name" =>"Mensaje", 		"type" => "string", 	"required" => false);
/*
 * MENSAJES VALIDACION
 */
$messages['ok'] 		= "Los datos se enviaron correctamente, Gracias.";
$messages['empty'] 		= "El campo {field} no debe estar vac&iacute;o.";
$messages['incorrect'] 	= "El campo {field} debe ser un valor v&aacute;lido.";
$messages['outlimit'] 	= "Field {field} should be a value between {min} and {max}";
/*
 * VALORES E-MAIL
 */
$redirect 		= "";
$subject_title 	= "Puertas y Servicios Industriales - Formulario de Contacto";
$asunto_title 	= "Puertas y Servicios Industriales - Formulario de Registro";
$domain 	= "puertasyserviciosindustriales.com";
$to 		= $TEST ? $EMAIL_TEST : 'administracion@puertasyserviciosindustriales.com';
$from 		= "Puertas y Servicios Industriales";
$img 		= "";
$colorth 	= '#000000';
$colortd 	= '#000000';
$backimg 	= '#FFFFFF';
$subject 		= "$subject_title - $domain";
$email_noreply 	= "administracion@$domain";
$asunto			= "$asunto_title - $domain";
$comentarios = 'Una persona ha completado su formulario de contacto con la siguiente informacion : <br />';

if($_POST['cmd'] == $CMD){
	$entity = $_POST[$VARIABLE_POST];	
	$result = validar($VARIABLE_POST,$fields,$messages,$redirect);	
	if (empty($_REQUEST['g-recaptcha-response']) || $_REQUEST['g-recaptcha-response'] == '' || $_REQUEST['g-recaptcha-response'] == NULL) {
			$result['status'] = false;
			$result['msg'] = 'C&oacute;digo de Seguridad Incorrecto';
	}
	/*include_once "cx.php";
	//VALIDAMOS QUE NO EXISTA UN REGISTRO PREVIO PARA CONTINUAR
	$sql_1 = "SELECT * FROM masterclass WHERE email = '".$entity['Email']."' LIMIT 1";
	$response = $mysqli->execute_query($sql_1);
	$exist = $response->num_rows;		
	if ($exist > 0) {
		$result['status'] = false;
		$result['msg'] = 'Ya existe un registro con ese EMAIL';
	}*/
	if($result['status']){
		// REGISTRO EN LA BASE DE DATOS DE LA FORMA		
		//$sql_0 = "INSERT INTO `masterclass`( `nombre`, `apellido`, `email`, `telefono`, `fecha`) VALUES ('".$entity['Nombre']."', '".$entity['Apellido']."', '".$entity['Email']."', '".$entity['Telefono']."', '".date('Y-m-d H:i:s')."');";
    	//$mysqli->execute_query($sql_0);

	function getRealIP() {
		if (!empty($_SERVER['HTTP_CLIENT_IP']))
			return $_SERVER['HTTP_CLIENT_IP'];
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
			return $_SERVER['HTTP_X_FORWARDED_FOR'];
		return $_SERVER['REMOTE_ADDR'];
	}		
		///////////////////////////////////////////////////
		$array_clean = array_filter($_POST[$VARIABLE_POST]);
		$FieldsTable = genericHTMLFieldsTable($array_clean,$colorth,$colortd);		
		$HTML = genericHTMLMail($img,$backimg,$comentarios,$FieldsTable);		
		sendMail($HTML,$from,$to,$subject,$email_noreply,$ENVIAR_POR_SMTP);
		/*if($ENVIAR_EMAIL_A_USUARIO){
			ob_start();
			include('autoreplay/email3.php');
			$body = ob_get_contents();
			ob_end_clean();
	        mail( $_POST['entity']["email"], $asunto, $body, $header);
		}*/
		//  ALERT DE CONFIRMACION  //
		//$_SESSION['valor'] = true;			
		echo $result['msg'];
	}else{
		//  ASIGNACION DE VARIABLES DE VALIDACION  //
		$empty = $result['empty'];
		$msg = $result['msg'];	
		$campos = $_POST[$VARIABLE_POST];
	}
}
?>