<?php 
require_once("system/common.php");
//ProjectLibrary_FrontEnd_FO_Authentification::verifyAuthentification();
$app = new ProjectLibrary_FrontEnd_FO_Productos();
$app->execute();
die;
?>
include_once("system/common.php");
$productos = ProjectLibrary_SDO_Core_Application_Productos::LoadProductos();
$title = "Overhead Door | Marcas ";
$description = "Productos Marca Overhead Door";
include 'header.php';?>
<style>
    .page-title-extra-small h2 { font-size: 2.8rem; line-height: 2.8rem;}
</style>
        <!-- start page title -->
        <section class="tit-section page-title-big-typography bg-dark-gray ipad-top-space-margin xs-py-0 cover-background background-position-center-top" style="background-image: url(images/banner1.jpg)">
            <div class="opacity-medium bg-gradient-sherpa-blue-black"></div>
            <div class="container">
                <div class="row align-items-center justify-content-center small-screen-int">
                    <div class="col-xl-6 col-lg-7 col-sm-8 position-relative text-center page-title-extra-small" data-anime='{ "el": "childs", "translateY": [30, 0], "opacity": [0,1], "duration": 400, "delay": 0, "staggervalue": 100, "easing": "easeOutQuad" }'>
                        <div><h1 class="text-uppercase mb-15px alt-font text-white opacity-6 fw-500 ls-2px" data-fancy-text='{ "opacity": [0, 1], "translateY": [50, 0], "filter": ["blur(20px)", "blur(0px)"], "string": ["Marcas"], "duration": 400, "delay": 0, "speed": 50, "easing": "easeOutQuad" }'></h1></div>
                        <h2 class="m-auto text-white alt-font text-shadow-double-large fw-700 w-100 xl-w-100" data-fancy-text='{ "opacity": [0, 1], "translateY": [50, 0], "filter": ["blur(20px)", "blur(0px)"], "string": ["Overhead Door"], "duration": 400, "delay": 0, "speed": 50, "easing": "easeOutQuad" }'></h2>
                    </div>
                </div>
            </div>
        </section>
        <!-- end page title -->


       <!-- start section -->
        <section class="overlap-height position-relative">
            <div class="container overlap-gap-section">
                <div class="row">
                    <?php include 'menu-marcas.php';?>
                    <div class="col-lg-8 order-1 order-lg-2 md-mb-50px">
                        <ul class="shop-boxed shop-wrapper grid-loading grid grid-3col xxl-grid-3col xl-grid-3col lg-grid-4col md-grid-2col sm-grid-2col xs-grid-1col gutter-large text-center" data-anime='{ "el": "childs", "translateY": [50, 0], "opacity": [0,1], "duration": 600, "delay":100, "staggervalue": 150, "easing": "easeOutQuad" }'>
                            <li class="grid-sizer"></li>                                                      
							 <?php
                            if(count($productos) > 0){
                                foreach ($productos as $producto_item) {
                                    $not_titulo      = Util_String::subText($producto_item->getNombre(),35);
                                    $not_imagen      = $producto_item->getUrlArrayImages('medium',0);
                                    $not_descripcion = Util_String::subText($producto_item->getDescripcion(),180);
                                    $not_link        = $producto_item->getFriendlyNameUrl();
                    
                            ?>
                            <!-- start shop item -->
                            <li class="grid-item">
                                <div class="shop-box pb-25px">
                                    <div class="shop-image">
                                        <a href="cortina-serie-625.php">
                                            <img src="<?=$not_imagen?>" alt="<?=$not_titulo?>" />
                                            <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div> 
                                        </a>
                                        <div class="shop-hover d-flex justify-content-center">
                                            <a href="<?=$not_link?>" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top"><i class="feather icon-feather-arrow-right fs-15"></i></a>
                                        </div>
                                    </div>
                                    <div class="shop-footer text-center pt-20px">
                                        <a href="<?=$not_link?>" class="text-dark-gray fs-15 alt-font fw-600"><?=$not_titulo?></a>
                                    </div>
                                </div>
                            </li>
                            <!-- end shop item -->
							<?php 
                                }//end foreach
                            }
                            ?>
                            
                        </ul>
                        
                    </div>                    
                </div>                
            </div>
        </section>
        <!-- end section -->


         
<?php include 'footer.php';?>

