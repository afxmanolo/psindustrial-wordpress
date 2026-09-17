<?php include 'headerv1.php';
$Marcas = ProjectLibrary_SDO_Core_Application_Marcas::GetAllMarcas();
$arrayMarcas = array('tg.png','wayne-dayton.png','clopay.png','blue.png','kelley.png','door.png','rytec.png','infraca.png','glg-porte-industriali.png','dockman.png','lift-master.png','bft.png');
?>
       <style>
            .feature-box-icon img {width: 80%; margin-bottom: 30px;}
            .shop-box { border: 6px solid #fff; -webkit-transition-duration: 0.4s;  transition-duration: 0.4s; margin-bottom: 30px; }
            .shop-image {width: 80%; margin:auto; }
        </style>

        <!-- start page title -->
        <section class="tit-section page-title-big-typography bg-dark-gray ipad-top-space-margin xs-py-0 cover-background background-position-center-top" style="background-image: url(images/banner1.jpg)">
            <div class="opacity-medium bg-gradient-sherpa-blue-black"></div>
            <div class="container">
                <div class="row align-items-center justify-content-center small-screen">
                    <div class="col-xl-6 col-lg-7 col-sm-8 position-relative text-center page-title-extra-small" data-anime='{ "el": "childs", "translateY": [30, 0], "opacity": [0,1], "duration": 400, "delay": 0, "staggervalue": 100, "easing": "easeOutQuad" }'>
                        <div><h1 class="text-uppercase mb-15px alt-font text-white opacity-6 fw-500 ls-2px" data-fancy-text='{ "opacity": [0, 1], "translateY": [50, 0], "filter": ["blur(20px)", "blur(0px)"], "string": ["Soluciones"], "duration": 400, "delay": 0, "speed": 50, "easing": "easeOutQuad" }'></h1></div>
                        <h2 class="m-auto text-white alt-font text-shadow-double-large fw-700 w-90 xl-w-100" data-fancy-text='{ "opacity": [0, 1], "translateY": [50, 0], "filter": ["blur(20px)", "blur(0px)"], "string": ["Marcas"], "duration": 400, "delay": 0, "speed": 50, "easing": "easeOutQuad" }'></h2>
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
                        <div class="row  justify-content-center mb-4 appear anime-child anime-complete" data-anime="{ &quot;el&quot;: &quot;childs&quot;, &quot;translateY&quot;: [50, 0], &quot;opacity&quot;: [0,1], &quot;duration&quot;: 1200, &quot;delay&quot;: 0, &quot;staggervalue&quot;: 150, &quot;easing&quot;: &quot;easeOutQuad&quot; }">                  
                            <?php $cont = 0;
                              foreach($Marcas as $Marca){
                                $marcaName = $Marca->getNombre();
                                $marcasLink = $Marca->getFriendlyNameUrl();
                            ?>
                           <div class="col-lg-4">
                                <div class="shop-box">
                                    <div class="shop-image">
                                        <a href="<?=$marcasLink?>"><img src="images/<?=$arrayMarcas[$cont]?>" alt="<?=$marcaName?>" /></a>
                                    </div>
                                </div>
                            </div>
                             <?php $cont++;
                                }?>                           
                        </div>
                    </div>                    
                </div>                
            </div>
        </section>
        <!-- end section -->


         
<?php include 'footer.php';?>