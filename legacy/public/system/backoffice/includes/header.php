<!DOCTYPE html>
<?php
date_default_timezone_set("America/Mexico_City");
require_once( "../common.php" );

$userProfile = ProjectLibrary_FrontEnd_Util_UserSession::GetIdentity();
$config = Config::getGlobalConfiguration();


// secciones del BackOffice Menú
/** images icons @link icons http://fontawesome.io/icons/ */
$sections             = array();
//$sections["index"]    = array("include" => 1, "name" => $this->trans('home'),'icon'=>'fa fa-home');
//$sections["user"]     = array("include" => 1, "name" => $this->trans('user').'s','icon'=>'fa fa-users');
//$sections["Fotogaleria"]       = array("include" => 1, "name" => 'Galería','icon'=>'fa-picture-o');

if(!empty($userProfile)){    
    $sections["index"]    = array("include" => 1, "name" => "Inicio",'icon'=>'fa fa-home');
        $sections["user"]  = array("include" => 1, "name" => 'Usuarios','icon'=>'fa fa-users');
        $sections["Categorias"]  = array("include" => 2, "name" => 'Categorias','icon'=>'fa-list');
        //$sections["Marcas"]  = array("include" => 2, "name" => 'Marcas','icon'=>'fa-copyright');
        $sections["Productos"]  = array("include" => 2, "name" => 'Productos','icon'=>'fa-folder');
    
}
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?=$config["project_name"]?></title>
	<!-- Tell the browser to be responsive to screen width -->
	<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
	<!-- Bootstrap 3.3.5 -->
	<link rel="stylesheet" href="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/bootstrap/css/bootstrap.min.css">
	<!-- Font Awesome -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
	<!-- Ionicons -->
	<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
	<!-- DataTables -->
	<link rel="stylesheet" href="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/datatables/dataTables.bootstrap.css">
	<!-- Theme style -->
	<link rel="stylesheet" href="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/dist/css/AdminLTE.min.css">
	<!-- AdminLTE Skins. Choose a skin from the css/skins folder instead of downloading all of them to reduce the load. -->
	<link rel="stylesheet" href="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/dist/css/skins/_all-skins.min.css">	
	<!-- iCheck -->
	<link rel="stylesheet" href="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/iCheck/square/blue.css">
	<!-- iCheck for checkboxes and radio inputs -->
    <link rel="stylesheet" href="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/iCheck/all.css">
	<!-- Morris chart -->
	<!-- <link rel="stylesheet" href="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/morris/morris.css"> -->
	<!-- jvectormap -->
	<link rel="stylesheet" href="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/jvectormap/jquery-jvectormap-1.2.2.css">
	<!-- Date Picker -->
	<link rel="stylesheet" href="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/datepicker/datepicker3.css">
	<!-- Daterange picker -->
	<link rel="stylesheet" href="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/daterangepicker/daterangepicker-bs3.css">
	<!-- bootstrap wysihtml5 - text editor -->
	<link rel="stylesheet" href="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">
	<!-- Select2 -->
	<link rel="stylesheet" href="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/select2/select2.min.css">
	<!-- Fancybox -->
	<link rel="stylesheet" href="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/fancybox/jquery.fancybox.min.css">
	<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
		<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
		<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
		<![endif]-->
	<style type="text/css">
		.error{color: #A60000; font-size: 13px; font-family: Arial;}
		input.error, input.error:focus, input.check.error{border: 1px solid #A60000 !important;}
		textarea.error, textarea.error:focus, textarea.check.error{border: 1px solid #A60000 !important;}
		select.error, select.error:focus, select.check.error{border: 1px solid #A60000 !important;}
	</style>
	<script src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/jQuery/jQuery-2.1.4.min.js"></script>
	<script type="text/javascript" src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/validate/jquery.validate.js"></script>
	<script type="text/javascript" src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/validate/dist/additional-methods.min.js"></script>
	<script type="text/javascript" src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/validate/dist/localization/messages_es.js"></script>
	<!-- CK Editor -->
    <script src="<?php echo ABS_HTTP_URL;?>system/backoffice/includes/plugins/ckeditor/ckeditor.js"></script>
</head>

	<?php 
	if( isset( $userProfile )  && ProjectLibrary_FrontEnd_BO_Authentification::verifyAuthentification(false)  ){
		?>
		<!-- skin colors @link https://adminlte.io/themes/AdminLTE/documentation/index.html
		skin-blue
		skin-blue-light
		skin-yellow
		skin-yellow-light
		skin-green
		skin-green-light
		skin-purple
		skin-purple-light
		skin-red
		skin-red-light
		skin-black
		skin-black-light
		-->
<body class="hold-transition <?php echo $config["skin_color"]; ?> sidebar-mini">
			<div class="wrapper">
				<header class="main-header">
					<!-- Logo -->
					<a href="<?php echo ABS_HTTP_URL ?>system/backoffice/index.php" class="logo" style="background-color: <?php echo $config['bakground_logo']; ?>;">
						<!-- mini logo for sidebar mini 50x50 pixels -->
						<span class="logo-mini">
							<img src="<?php echo $config['logo_mobile_project']; ?>" class="img-responsive">
						</span>
						<!-- logo for regular state and mobile devices -->
						<span class="logo-lg">
							<img src="<?php echo $config['logo_project']; ?>" class="img-responsive">
						</span>
					</a>
					<!-- Header Navbar: style can be found in header.less -->
					<nav class="navbar navbar-static-top" role="navigation">
						<!-- Sidebar toggle button-->
						<a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
							<span class="sr-only">Toggle navigation</span>
						</a>
						<div class="navbar-custom-menu">
							<ul class="nav navbar-nav">
								<!-- User Account: style can be found in dropdown.less -->
								<li class="user user-menu">
									<a href="#" class="dropdown-toggle" data-toggle="dropdown">
										<!-- <img src="<?php echo ABS_HTTP_URL; ?>system/backoffice/includes/dist/img/user2-160x160.jpg" class="user-image" alt="User Image"> -->
										<span class="hidden-xs">Bienvenido <strong><?php echo  $userProfile->getFirstName(); ?></strong></span>
									</a>
								</li>
								<!-- Control Sidebar Toggle Button -->
								<li>
									<a href="<?php echo ABS_HTTP_URL;?>system/backoffice/index.php?cmd=logout" title="Cerrar Sesi&oacute;n"><i class="fa fa-sign-out"></i></a>
								</li>
							</ul>
						</div>	
					</nav>
				</header>
					<!-- Left side column. contains the logo and sidebar -->
					<aside class="main-sidebar">
						<!-- sidebar: style can be found in sidebar.less -->
						<section class="sidebar">
							<!-- sidebar menu: : style can be found in sidebar.less -->
							<ul class="sidebar-menu">
								<li class="header" style="text-align: center; color: #FFF; font-weight: bold;">
									<h5>MAIN MENU</h5>
								</li>

								<?php
								foreach ($sections as $key => $section) {
									if($key == "parent"){
										foreach ($section as $pos => $value) {
											foreach ($sections["child"][$pos] as $value_) {
												$links[$pos][] = $value_["link"];
											}//end foreach

											if(in_array(Util_Server::getScript(),$links[$pos])){
												$active_parent = "active";
											}else{
												$active_parent = "";
											}//end if
											?>
											<li class="treeview <?php echo $active_parent;?>">
												<a href="">
													<i class="fa <?php echo $value['icon'];?>"></i> <span><?php echo $value["name"];?></span>
													<i class="fa fa-angle-left pull-right"></i>
												</a>
												<ul class="treeview-menu">
											<?php

											foreach ($sections["child"][$pos] as $pos_ => $value) {
												$active = $value["link"] == Util_Server::getScript() ? 'active' : '';
												?>
												<li class="<?php echo $active;?>"><a href="<?php echo $value["link"];?>"><i class="fa <?php echo $value["icon"];?>"></i> <?php echo $value["name"];?></a></li>
												<?php
											}//end foreach
												?>
												</ul>
												<?php
											//$lastkey =  $key;
										}//end foreach
									}else if($key != "parent" && $key != "child"){
										$active = $key.'.php' == Util_Server::getScript() ? 'active' : '';
											?>
											<li class="<?php echo $active;?> treeview">
												<a href="<?php echo $key?>.php">
													<i class="fa <?php echo $section["icon"];?>"></i>
													<span><?php echo $section["name"];?></span><i class="fa fa-angle-right pull-right"></i>
												</a>
											</li>
										<?php
									}//end if
								}//end foreach
								
								?>
							</ul>
						</section>
						<!-- /.sidebar -->
					</aside>			
		<?php 
	}else{
		?>
		<body class="hold-transition login-page">
			<div class="login-box">	
<?php
	}//end if
	?>