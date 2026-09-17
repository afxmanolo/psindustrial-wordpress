<?php 
$title = "Industrial, Residencial y Marcas | Soluciones ";
$description = "Soluciones de Productos Industriales, Residenciales trabajando con las mejores marcas.";
include 'header.php';?>
       
<!-- start page title -->
        <section class="tit-section page-title-big-typography bg-dark-gray ipad-top-space-margin xs-py-0 cover-background background-position-center-top" style="background-image: url(images/banner1.jpg)">
            <div class="opacity-medium bg-gradient-sherpa-blue-black"></div>
            <div class="container">
                <div class="row align-items-center justify-content-center small-screen">
                    <div class="col-xl-6 col-lg-7 col-sm-8 position-relative text-center page-title-extra-small" data-anime='{ "el": "childs", "translateY": [30, 0], "opacity": [0,1], "duration": 400, "delay": 0, "staggervalue": 100, "easing": "easeOutQuad" }'>
                        <div><h1 class="text-uppercase mb-15px alt-font text-white opacity-6 fw-500 ls-2px" data-fancy-text='{ "opacity": [0, 1], "translateY": [50, 0], "filter": ["blur(20px)", "blur(0px)"], "string": ["Servicios"], "duration": 400, "delay": 0, "speed": 50, "easing": "easeOutQuad" }'></h1></div>
                        <h2 class="m-auto text-white alt-font text-shadow-double-large fw-700 w-90 xl-w-100" data-fancy-text='{ "opacity": [0, 1], "translateY": [50, 0], "filter": ["blur(20px)", "blur(0px)"], "string": ["Soluciones"], "duration": 400, "delay": 0, "speed": 50, "easing": "easeOutQuad" }'></h2>
                    </div>
                </div>
            </div>
        </section>
        <!-- end page title -->



<!-- start Servicios section -->
        <section class="position-relative mt-0 pt-20px overflow-hidden sm-pb-20px soluciones"> 
            <div class="separator-line-9px bg-base-color position-absolute top-0px right-0px" data-bottom-top="width: 15%" data-center-top="width: 50%;"></div>
            <div class="container">
                <div class="row">
                    <div class="col-12 px-md-0">
                        <ul class="blog-classic blog-wrapper grid-loading grid grid-3col xl-grid-3col lg-grid-3col md-grid-2col sm-grid-2col xs-grid-1col gutter-extra-large" data-anime='{ "el": "childs", "translateY": [50, 0], "opacity": [0,1], "duration": 600, "delay": 0, "staggervalue": 300, "easing": "easeOutQuad" }'>
                            <li class="grid-sizer"></li>
                            <!-- start blog item -->
                            <li class="grid-item">
                               <div class="card bg-transparent border-0 h-100">
                                    <div class="blog-image position-relative overflow-hidden border-radius-6px">
                                        <a href="industrial.php"><img src="images/puerta432.jpg" alt="" /></a>
                                    </div>
                                    <div class="card-body px-0 pb-30px pt-30px xs-pb-15px text-center">
                                        <a href="industrial.php" class="card-title mb-0 fw-500 fs-28 lh-30 text-base-color d-inline-block">Industrial</a>
                                        <p>Soluciones Industriales</p> 
                                    </div>
                                </div>
                            </li>
                            <!-- end blog item -->
                            <!-- start blog item -->
                            <li class="grid-item">
                                <div class="card bg-transparent border-0 h-100">
                                    <div class="blog-image position-relative overflow-hidden border-radius-6px">
                                        <a href="residencial.php"><img src="images/operadores.jpg" alt="" /></a>
                                    </div>
                                    <div class="card-body px-0 pb-30px pt-30px xs-pb-15px text-center">
                                        <a href="residencial.php" class="card-title mb-0 fw-500 fs-28 lh-30 text-base-color d-inline-block">Residencial</a>
                                        <p>Soluciones Residenciales</p> 
                                    </div>
                                </div>
                            </li>
                            <!-- end blog item -->
                            
                             <!-- start blog item -->
                            <li class="grid-item">
                               <div class="card bg-transparent border-0 h-100">
                                    <div class="blog-image position-relative overflow-hidden border-radius-6px">
                                        <a href="marcas.php"><img src="images/marcas.png" alt="" /></a>
                                    </div>
                                    <div class="card-body px-0 pb-30px pt-30px xs-pb-15px text-center">
                                        <a href="marcas.php" class="card-title mb-0 fw-500 fs-28 lh-30 text-base-color d-inline-block">Marcas</a>
                                        <p>Trabajamos con los mejores de la industria.</p> 
                                    </div>
                                </div>
                            </li>
                            <!-- end blog item -->
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        <!-- end servicios section -->


         
<?php include 'footer.php';?>