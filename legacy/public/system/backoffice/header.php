<?php
require_once( "../common.php" );

$userProfile = ProjectLibrary_FrontEnd_Util_UserSession::GetIdentity();
$config = Config::getGlobalConfiguration();

// secciones del BackOffice
$sections = array();
$sections["index"] 			= array("include" => 1, "name" => $this->trans('home'));
$sections["user"] 			= array("include" => 1, "name" => $this->trans('user').'s');
$sections["Noticia"] 		= array("include" => 1, "name" => "Clientes");
$sections["Fotogaleria"]    = array("include" => 1, "name" => "Proyectos");
$sections["Multimedia"] 		= array("include" => 1, "name" => "Multimedia");
$sections["Proveedores"] 		= array("include" => 1, "name" => "Proveedores");
$sections["Promociones"] 		= array("include" => 1, "name" => "Promociones");
?>
<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?=$config["project_name"]?></title>
<style type="text/css">
@import url('css/estilo.css');
@import url('css/form.css');
@import url('css/listPanel.css');
</style>
<!-- calendar stylesheet -->
	<link rel="stylesheet" type="text/css" href="calendar/css/jscal2.css" />
    <link rel="stylesheet" type="text/css" href="calendar/css/border-radius.css" />
    <link rel="stylesheet" type="text/css" href="calendar/css/steel/steel.css" />
    <script type="text/javascript" src="calendar/js/jscal2.js"></script>
    <script type="text/javascript" src="calendar/js/lang/en.js"></script>
	<script type="text/javascript" src="tiny_mce/tiny_mce.js"></script>
</head>
<body bgcolor="#fefefe">
<div align="center">
<table border="0" id="table1" cellspacing="0" bgcolor="#EFEFEF" width="779" align="center">
  <tr>
    <td bgcolor="#FFFFFF" valign="top">
    <table border="0" width="100%" id="table2" cellspacing="0" cellpadding="0" style="border: 1px solid #fff">
      <tr>
        <td bgcolor="#ffffff">
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
            	<td colspan="2" bgcolor="<?=$config["bakground_logo"]?>" align="left" >
                
                <img src="<?=$config["logo_project"]?>" border="0" >            	</td>
            </tr>
            <?php
        	
			if( isset( $userProfile )  && ProjectLibrary_FrontEnd_BO_Authentification::verifyAuthentification(false) ){
			?>
            <tr bgcolor="#eeeeee">
            	<td align="left" valign="bottom" width="30%" style="padding: 5px;">
            		<b><?php echo $this->trans('welcome').' '.$userProfile->getFirstName(); ?></b> | 
                	<a href="index.php?cmd=logout"><?=$this->trans('logout')?></a>
                &nbsp;&nbsp;&nbsp;
            	</td>
                <td align="right" valign="top">
              	<?php
              	foreach($sections as $key => $section) {
              		if($section["include"]) {?>
              	<a href='<?=$key?>.php'><?=$section["name"]?></a> |
              	<?}}?>
      		</td>
            </tr>
           <?php } 
           else{
           	echo '
           		<tr bgcolor="#ED1C24">
            	<td align="left" valign="bottom" width="30%" style="padding: 5px;" colspan="2">&nbsp;</td>
            	</tr>';	
           }
           ?>
          </table>
        </td>
      </tr>
      <tr>
        <td bgcolor="#EFEFEF"><img border="0" src="ima/1px.gif" width="1" height="1"></td>
      </tr>
      <tr>
        <td align="center" style="padding: 20px">
        	<h1 align="center" style="margin:0px;color: #164399; border-bottom: 1px solid #eee"><?=$this->trans('administration_panel')?></h1>
        	</td>
      </tr>      
      <tr>
      	<td align="center" style="padding: 20px"><? if(Util_Server::getScript() != 'index.php'){?>
      		<table width="100%">
      			<tr>
      				<td width="1" valign="top"><?include_once 'menu.php';?></td>
      				<td valign="top" style="padding-left:15px;"><?}?>