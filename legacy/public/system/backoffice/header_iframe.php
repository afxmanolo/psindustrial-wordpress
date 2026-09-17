<?php
require_once( "../common.php" );

$userProfile = ProjectLibrary_FrontEnd_Util_UserSession::GetIdentity();
?>
<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Administration Panel </title>
<style type="text/css">
@import url('css/estilo.css');
@import url('css/form.css');
@import url('css/listPanel.css');
</style>
<script type="text/javascript" src="tinymce/tiny_mce.js"></script>
</head>
<body bgcolor="#fefefe">
<div align="center">
<table border="0" id="table1" cellspacing="0" bgcolor="#EFEFEF" width="650" align="center">
  <tr>
    <td bgcolor="#FFFFFF" valign="top">