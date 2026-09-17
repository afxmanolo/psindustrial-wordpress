<?php 
include_once "system/common.php";
$title = empty($title)?'':$title." | ";
$description = empty($description)?'':$description." | ";
?>
<!doctype html>
<html class="no-js" lang="en">
    <head>
        <title><?=$title?> PSI | Puertas y Servicios Industriales</title>       
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="author" content="ThemeZaa">
        <meta name="viewport" content="width=device-width,initial-scale=1.0" />
        <meta name="description" content="<?=$description?> Venta, instalación y mantenimiento de puertas automáticas para diferentes sectores de la industria">
        <!-- favicon icon -->
        <link rel="shortcut icon" href="<?=ABS_HTTP_URL?>images/favicon.png">
        <link rel="apple-touch-icon" href="<?=ABS_HTTP_URL?>images/apple-touch-icon-57x57.png">
        <link rel="apple-touch-icon" sizes="72x72" href="<?=ABS_HTTP_URL?>images/apple-touch-icon-72x72.png">
        <link rel="apple-touch-icon" sizes="114x114" href="<?=ABS_HTTP_URL?>images/apple-touch-icon-114x114.png">
        <!-- google fonts preconnect -->
        <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <!-- style sheets and font icons  -->
        <link rel="stylesheet" href="<?=ABS_HTTP_URL?>css/vendors.min.css"/>
        <link rel="stylesheet" href="<?=ABS_HTTP_URL?>css/icon.min.css"/>
        <link rel="stylesheet" href="<?=ABS_HTTP_URL?>css/style.css"/>
        <link rel="stylesheet" href="<?=ABS_HTTP_URL?>css/responsive.css"/>
        <link rel="stylesheet" href="<?=ABS_HTTP_URL?>css/marketing.css" />
        <link rel="stylesheet" href="<?=ABS_HTTP_URL?>css/business.css" />
        <link rel="stylesheet" href="<?=ABS_HTTP_URL?>css/custom.css" />
        <script src="https://www.google.com/recaptcha/api.js"></script>
        <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-C5XTMKDPM3"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-C5XTMKDPM3');
</script>

    </head>
    <body data-mobile-nav-style="classic" class="custom-cursor">
        <!-- start cursor -->
        <div class="cursor-page-inner">
            <div class="circle-cursor circle-cursor-inner"></div>
            <div class="circle-cursor circle-cursor-outer"></div>
        </div>
        <!-- end cursor -->
        <!-- start header -->
        <header>
            <!-- start navigation -->
            <nav class="navbar navbar-expand-lg header-light header-transparent bg-transparent disable-fixed">
                <div class="container">
                    <div class="col-auto col-lg-4 me-lg-0 me-auto">
                        <a class="navbar-brand" href="<?=ABS_HTTP_URL?>index.php">
                            <img src="<?=ABS_HTTP_URL?>images/logo-white@2x.png" data-at2x="<?=ABS_HTTP_URL?>images/logo-white@2x.png" alt="PSI | Puertas y Servicios Industriales" class="default-logo">
                            <img src="<?=ABS_HTTP_URL?>images/logo-white@2x.png" data-at2x="<?=ABS_HTTP_URL?>images/logo-white@2x.png" alt="PSI | Puertas y Servicios Industriales" class="alt-logo">
                            <img src="<?=ABS_HTTP_URL?>images/logo-black-big@2x.png" data-at2x="<?=ABS_HTTP_URL?>images/logo-black-big@2x.png" alt="PSI | Puertas y Servicios Industriales" class="mobile-logo"> 
                        </a>
                    </div>
                    <div class="col-auto menu-order position-static">
                        <button class="navbar-toggler float-start" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-label="Toggle navigation">
                            <span class="navbar-toggler-line"></span>
                            <span class="navbar-toggler-line"></span>
                            <span class="navbar-toggler-line"></span>
                            <span class="navbar-toggler-line"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="navbarNav"> 
                            <ul class="navbar-nav"> 
                                <li class="nav-item"><a href="<?=ABS_HTTP_URL?>" class="nav-link">Inicio</a></li>
                                <li class="nav-item"><a href="<?=ABS_HTTP_URL?>nosotros.php" class="nav-link">Nosotros</a></li>
                                <li class="nav-item dropdown dropdown-with-icon">
                                    <a href="#" class="nav-link">Soluciones</a>
                                    <i class="fa-solid fa-angle-down dropdown-toggle" id="navbarDropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false"></i>
                                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
<?php
$Padres = ProjectLibrary_SDO_Core_Application_Categorias::GetCategoriasPadre();
foreach($Padres as $Padre){
    $cName = $Padre->getNombre();
    $cLink = $Padre->getFriendlyNameUrlPadre();
?>
<li>
<a href="<?=$cLink?>">
	<div class="submenu-icon-content">
	<span><?=$cName?></span>                                                    
	</div>
</a>
</li>
<?php }?>                                        
                                    </ul>
                                </li>
                                <li class="nav-item"><a href="contacto.php" class="nav-link">Contacto</a></li>
                            </ul>
                        </div>
                    </div>                   
                </div>
            </nav>
            <!-- end navigation -->
        </header>
        <!-- end header -->