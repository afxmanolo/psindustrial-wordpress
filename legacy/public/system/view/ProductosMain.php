<?php
$marca = !empty($this->Marca)?$this->Marca:'';
$categoria = !empty($this->Categoria)?$this->Categoria:'';
$strTitle = !empty($marca)? "Marca ".$marca." ":'';
$strTitle .= !empty($categoria)? "Productos ".$categoria:'';
$title = $strTitle;
$description = "Productos ".$strTitle;
require_once('headerv1.php');
$result	  = $this->result;
$Productos = $result->getResults();
$hiddenFields = empty($this->hiddenFields) ? array() : $this->hiddenFields;
?>
<style>
    .page-title-extra-small h2 { font-size: 2.8rem; line-height: 2.8rem;}
</style>
 <?php if(empty($marca)){?>
        <!-- start page title -->
        <section class="tit-section page-title-big-typography bg-dark-gray ipad-top-space-margin xs-py-0 cover-background background-position-center-top" style="background-image: url(<?=ABS_HTTP_URL?>images/banner1.jpg)">
            <div class="opacity-medium bg-gradient-sherpa-blue-black"></div>
            <div class="container">
                <div class="row align-items-center justify-content-center small-screen-int">
                    <div class="col-xl-6 col-lg-7 col-sm-8 position-relative text-center page-title-extra-small" data-anime='{ "el": "childs", "translateY": [30, 0], "opacity": [0,1], "duration": 400, "delay": 0, "staggervalue": 100, "easing": "easeOutQuad" }'>
                        <div><h1 class="text-uppercase mb-15px alt-font text-white opacity-6 fw-500 ls-2px" data-fancy-text='{ "opacity": [0, 1], "translateY": [50, 0], "filter": ["blur(20px)", "blur(0px)"], "string": ["<?=(!empty($marca)?"Marca":"Categoria")?>"], "duration": 400, "delay": 0, "speed": 50, "easing": "easeOutQuad" }'></h1></div>
                        <h2 class="m-auto text-white alt-font text-shadow-double-large fw-700 w-100 xl-w-100" data-fancy-text='{ "opacity": [0, 1], "translateY": [50, 0], "filter": ["blur(20px)", "blur(0px)"], "string": ["<?=(!empty($marca)?$marca:$categoria)?>"], "duration": 400, "delay": 0, "speed": 50, "easing": "easeOutQuad" }'></h2>
                    </div>
                </div>
            </div>
        </section>
        <!-- end page title -->
<?php }else{?>      
    <!-- start page title -->
        <section class="tit-section page-title-big-typography bg-dark-gray ipad-top-space-margin xs-py-0 cover-background background-position-center-top" style="background-image: url(<?=ABS_HTTP_URL?>images/banner1.jpg)">
            <div class="opacity-medium bg-gradient-sherpa-blue-black"></div>
            <div class="container">
                <div class="row align-items-center justify-content-center small-screen-int">
                    <div class="col-xl-6 col-lg-7 col-sm-8 position-relative text-center page-title-extra-small" data-anime='{ "el": "childs", "translateY": [30, 0], "opacity": [0,1], "duration": 400, "delay": 0, "staggervalue": 100, "easing": "easeOutQuad" }'>
                        <div><h1 class="text-uppercase mb-15px alt-font text-white opacity-6 fw-500 ls-2px" data-fancy-text='{ "opacity": [0, 1], "translateY": [50, 0], "filter": ["blur(20px)", "blur(0px)"], "string": ["<?=(!empty($marca)?"Marca":"Categoria")?>"], "duration": 400, "delay": 0, "speed": 50, "easing": "easeOutQuad" }'></h1></div>
                        <h2 class="m-auto text-white alt-font text-shadow-double-large fw-700 w-90 xl-w-100" data-fancy-text='{ "opacity": [0, 1], "translateY": [50, 0], "filter": ["blur(20px)", "blur(0px)"], "string": ["<?=(!empty($marca)?$marca:$categoria)?>"], "duration": 400, "delay": 0, "speed": 50, "easing": "easeOutQuad" }'></h2>
                    </div>
                </div>
            </div>
        </section>
        <!-- end page title -->   
    <?php }?>

<?php if(!empty($marca)){?>
       <!-- start section -->
        <section class="overlap-height position-relative">
            <div class="container overlap-gap-section">
                <div class="row">
                    <?php 
                    if(!empty($marca)){
                        include 'menu-marcasv1.php';
                    }
                    ?>
                    <div class="col-lg-8 order-1 order-lg-2 md-mb-50px">
                        <ul class="shop-boxed shop-wrapper grid-loading grid grid-3col xxl-grid-3col xl-grid-3col lg-grid-4col md-grid-2col sm-grid-2col xs-grid-1col gutter-large text-center" data-anime='{ "el": "childs", "translateY": [50, 0], "opacity": [0,1], "duration": 600, "delay":100, "staggervalue": 150, "easing": "easeOutQuad" }'>
                            <li class="grid-sizer"></li>                                                      
							 <?php
							 if(count($Productos) > 0){
							     foreach ($Productos as $Productos_item) {
							         $not_titulo      = $Productos_item->getNombre();
							         $not_imagen      = $Productos_item->getUrlArrayImages('medium',0);							        
							         $not_link        = $Productos_item->getFriendlyNameUrl();                    
                            ?>
                            <!-- start shop item -->
                            <li class="grid-item">
                                <div class="shop-box pb-25px">
                                    <div class="shop-image">
                                        <a href="<?=$not_link?>">
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
                        }else{?>
                         <li class="grid-item">
                                <div class="shop-box pb-25px">PROXIMAMENTE
                                </div>
                         </li>
                            <?php }?>
                        </ul>
                        
                    </div>                    
                </div>                
            </div>
        </section>
        <!-- end section -->  
<?php }else{?>    
    <section class="ps-6 pe-6 lg-ps-3 lg-pe-3 sm-ps-0 sm-pe-0">
            <div class="container-fluid">
                <div class="row flex-row-reverse"> 
                    <div class="col-xxl-12 col-lg-9 ps-5 md-ps-15px md-mb-60px">
                        <ul class="shop-boxed shop-wrapper grid-loading grid grid-4col xxl-grid-4col xl-grid-4col lg-grid-4col md-grid-2col sm-grid-2col xs-grid-1col gutter-large text-center" data-anime='{ "el": "childs", "translateY": [50, 0], "opacity": [0,1], "duration": 600, "delay":100, "staggervalue": 150, "easing": "easeOutQuad" }'>
                            <li class="grid-sizer"></li>
                            <!-- start shop item -->
                              <?php
							 if(count($Productos) > 0){
							     foreach ($Productos as $Productos_item) {
							         $not_titulo      = $Productos_item->getNombre();
							         $not_imagen      = $Productos_item->getUrlArrayImages('medium',0);							        
							         $not_link        = $Productos_item->getFriendlyNameUrl();                    
                            ?>
                            <li class="grid-item">
                                <div class="shop-box pb-25px">
                                    <div class="shop-image">
                                        <a href="<?=$not_link?>">
                                            <img src="<?=$not_imagen?>" alt="<?=$not_titulo?>" />
                                            <div class="product-overlay bg-gradient-extra-midium-gray-transparent"></div> 
                                        </a>
                                        <div class="shop-hover d-flex justify-content-center">
                                            <a href="<?=$not_link?>" class="bg-white w-45px h-45px text-dark-gray d-flex flex-column align-items-center justify-content-center rounded-circle ms-5px me-5px box-shadow-medium-bottom" data-bs-toggle="tooltip" data-bs-placement="top"><i class="feather icon-feather-arrow-right fs-15"></i></a>
                                        </div>
                                    </div>
                                    <div class="shop-footer text-center pt-20px">
                                        <a href="<?=$not_link?>" class="text-dark-gray fs-17 alt-font fw-600"><?=$not_titulo?></a>
                                    </div>
                                </div>
                            </li>
                            <?php 
                                }//end foreach
                            }else{
                            ?>
                             <li class="grid-item">
                                <div class="shop-box pb-25px">PROXIMAMENTE
                                </div>
                         </li>
                            <?php }?>
                            <!-- end shop item -->                            
                        </ul>
                        
                    </div>
                </div>
            </div>
        </section>
        <!-- end section -->
    <?php }?>     
<?php include 'footerv1.php';?>