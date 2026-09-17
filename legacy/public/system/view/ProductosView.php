<?php 
$Productos = $this->Productos;
$nombre = $Productos->getNombre();
$productDescription = $Productos->getDescripcion();
$imagenes = $Productos->getImagenes();
$fichas = $Productos->getFichas();
$categoriaId = $Productos->getCategoria();
$Categoria = ProjectLibrary_SDO_Core_Application_Categorias::LoadById($categoriaId);
$marcaId = $Productos->getMarca();
$Marca = ProjectLibrary_SDO_Core_Application_Marcas::LoadById($marcaId);
$title = $nombre." | ".$Categoria->getNombre()." | ".$Marca->getNombre();
$description = "Productos ".$nombre." Marca ".$Marca->getNombre();
require_once('headerv1.php');
?>
<style>
    .page-title-extra-small h2 { font-size: 2.8rem; line-height: 2.8rem;}
</style>
        <!-- start page title -->
        <section class="tit-section page-title-big-typography bg-dark-gray ipad-top-space-margin xs-py-0 cover-background background-position-center-top" style="background-image: url(<?=ABS_HTTP_URL?>images/banner1.jpg)">
            <div class="opacity-medium bg-gradient-sherpa-blue-black"></div>
            <div class="container">
                <div class="row align-items-center justify-content-center small-screen-int">
                    <div class="col-xl-6 col-lg-7 col-sm-8 position-relative text-center page-title-extra-small" data-anime='{ "el": "childs", "translateY": [30, 0], "opacity": [0,1], "duration": 400, "delay": 0, "staggervalue": 100, "easing": "easeOutQuad" }'>
                        <div><h1 class="text-uppercase mb-15px alt-font text-white opacity-6 fw-500 ls-2px" data-fancy-text='{ "opacity": [0, 1], "translateY": [50, 0], "filter": ["blur(20px)", "blur(0px)"], "string": ["<?=$Categoria->getNombre()?> | <?=$Marca->getNombre()?>"], "duration": 400, "delay": 0, "speed": 50, "easing": "easeOutQuad" }'></h1></div>
                        <h2 class="m-auto text-white alt-font text-shadow-double-large fw-700 w-100 xl-w-100" data-fancy-text='{ "opacity": [0, 1], "translateY": [50, 0], "filter": ["blur(20px)", "blur(0px)"], "string": ["<?=$nombre?>"], "duration": 400, "delay": 0, "speed": 50, "easing": "easeOutQuad" }'></h2>
                    </div>
                </div>
            </div>
        </section>
        <!-- end page title -->
       <!-- start section -->
        <section class="overlap-height position-relative">
            <div class="container overlap-gap-section">
                <div class="row">
                    <?php include 'menu-solucionesv1.php';?>                    
                    <div class="col-lg-8 order-1 order-lg-2 md-mb-50px" data-anime='{ "el": "childs", "translateY": [50, 0], "opacity": [0,1], "duration": 1200, "delay": 0, "staggervalue": 150, "easing": "easeOutQuad" }'>
                        <p class="text-end pb-0">
                        <?php foreach($fichas as $ficha){
                            $fichaInfo = ProjectLibrary_SDO_Core_Application_FileManagement::LoadById( $ficha );	                            
                            ?>
                        	<a href="<?=ABS_HTTP_URL.SYSTEM_DIRECTORY?>file.php?id=<?=$ficha?>&type=<?=$fichaInfo->getType()?>" target="_blank"><img src="<?=ABS_HTTP_URL?>images/verficha.png" class="w-13"  alt="Ver ficha t&eacute;cnica <?=$fichaInfo->getDescription()?>" data-no-retina=""></a>
                        <?php }?>
                        </p>
                        <h5 class="fw-700 text-dark-gray ls-minus-1px mb-30px sm-mb-20px"><?=$nombre?></h5>
                        <div class="row">
                            <div class="col-md-6" style="">
                                <?=$productDescription?>
                            </div>
                            <div class="col-md-6 mb-30px" style="">
                            <?php
                            $cont = 0;
                            foreach($imagenes as $imagen){
                                $image = $Productos->getUrlArrayImages('large',$cont);
                                ?>
                                <p><img src="<?=$image?>" alt="AccessPRO FS1000SPEED" data-no-retina=""><p>
                                <?php 
                                $cont++;
                            }?>
                            </div>                            
                        </div>                                                
                    </div>                    
                </div>                
            </div>
        </section>
        <!-- end section -->
<?php 
require_once('footerv1.php');
?>