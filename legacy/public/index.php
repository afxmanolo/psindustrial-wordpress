<?php include 'headerv1.php';
$Industrial = ProjectLibrary_SDO_Core_Application_Categorias::GetSubCategorias(1);
$Comercial = ProjectLibrary_SDO_Core_Application_Categorias::GetSubCategorias(2);
$Equipos = ProjectLibrary_SDO_Core_Application_Categorias::GetSubCategorias(3);
$Puertas = ProjectLibrary_SDO_Core_Application_Categorias::GetSubCategorias(4);
$Incendio = ProjectLibrary_SDO_Core_Application_Categorias::GetSubCategorias(5);
$Hospitales = ProjectLibrary_SDO_Core_Application_Categorias::GetSubCategorias(6);
$Residencial = ProjectLibrary_SDO_Core_Application_Categorias::GetSubCategorias(7);
?>
        <!-- start banner -->
        <section class="section-dark p-0 bg-dark-gray"> 
            <div class="swiper lg-no-parallax full-screen md-h-600px sm-h-500px ipad-top-space-margin swiper-light-pagination" data-slider-options='{ "slidesPerView": 1, "loop": true, "parallax": true, "speed": 1000, "pagination": { "el": ".swiper-pagination-bullets", "clickable": true }, "navigation": { "nextEl": ".slider-one-slide-next-1", "prevEl": ".slider-one-slide-prev-1" }, "autoplay": { "delay": 4000, "disableOnInteraction": false },  "keyboard": { "enabled": true, "onlyInViewport": true }, "effect": "slide" }'>
                <div class="swiper-wrapper">
                    <!-- start slider item -->
                    <div class="swiper-slide overflow-hidden">
                        <div class="cover-background position-absolute top-0 start-0 w-100 h-100" data-swiper-parallax="500" style="background-image:url('images/banner1.jpg');">
                            <div class="opacity-extra-medium bg-gradient-sherpa-blue-black"></div>
                            <div class="container h-100" data-swiper-parallax="-500">
                                <div class="row align-items-center h-100">
                                    <div class="col-xl-7 col-lg-8 col-md-10 swiper-slide-cont position-relative text-white text-center text-md-start" data-anime='{ "el": "childs", "translateX": [100, 0], "opacity": [0,1], "duration": 600, "delay": 0, "staggervalue": 300, "easing": "easeOutQuad" }'>
                                        <div>
                                            <span class="fs-20 opacity-6 d-inline-block fw-300">Conócenos.</span>
                                        </div>
                                        <h1 class="alt-font fs-30  w-90 xl-w-100 text-shadow-double-large ls-minus-2px mb-0">Bienvenidos a <span class="fw-600">nuestro sitio web .</span></h1>
                                        <a href="soluciones.php" target="_blank" class="btn btn-extra-large btn-rounded with-rounded btn-base-color btn-box-shadow box-shadow-extra-large mt-10px sm-mt-0">Ver más<span class="bg-white text-base-color"><i class="fas fa-arrow-right"></i></span></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end slider item -->
                    <!-- start slider item -->
                    <div class="swiper-slide overflow-hidden">
                        <div class="cover-background position-absolute top-0 start-0 w-100 h-100" data-swiper-parallax="500" style="background-image:url('images/banner2.jpg');">
                            <div class="opacity-extra-medium bg-gradient-sherpa-blue-black"></div>
                            <div class="container h-100" data-swiper-parallax="-500">
                                <div class="row align-items-center h-100">
                                    <div class="col-xl-7 col-lg-8 col-md-10 swiper-slide-cont position-relative text-white text-center text-md-start" data-anime='{ "el": "childs", "translateX": [100, 0], "opacity": [0,1], "duration": 600, "delay": 0, "staggervalue": 300, "easing": "easeOutQuad" }'> 
                                        <div>
                                            <span class="fs-20 opacity-6 d-inline-block fw-300">Empresa dedicada a la </span>
                                        </div>
                                        <h1 class="alt-font  fs-30 w-90 xl-w-100 text-shadow-double-large ls-minus-2px mb-0">  <span class="fw-600"> Venta, instalación y mantenimiento </span>de puertas automáticas</h1> 
                                        <a href="#" target="_blank" class="btn btn-extra-large btn-rounded with-rounded btn-base-color btn-box-shadow box-shadow-extra-large mt-120px sm-mt-0">Ver más<span class="bg-white text-base-color"><i class="fa-solid fa-arrow-right"></i></span></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end slider item -->
                     <!-- start slider item -->
                    <div class="swiper-slide overflow-hidden">
                        <div class="cover-background position-absolute top-0 start-0 w-100 h-100" data-swiper-parallax="500" style="background-image:url('images/banner3.jpeg');">
                            <div class="opacity-extra-medium bg-gradient-sherpa-blue-black"></div>
                            <div class="container h-100" data-swiper-parallax="-500">
                                <div class="row align-items-center h-100">
                                    <div class="col-xl-7 col-lg-8 col-md-10 swiper-slide-cont position-relative text-white text-center text-md-start" data-anime='{ "el": "childs", "translateX": [100, 0], "opacity": [0,1], "duration": 600, "delay": 0, "staggervalue": 300, "easing": "easeOutQuad" }'> 
                                        <div>
                                            <span class="fs-20 opacity-6 d-inline-block fw-300">Empresa dedicada a la </span>
                                        </div>
                                        <h1 class="alt-font  fs-30 w-90 xl-w-100 text-shadow-double-large ls-minus-2px mb-0">  <span class="fw-600"> Venta, instalación y mantenimiento </span>de rampas para Taller o Niveladoras de Andén</h1> 
                                        <a href="#" target="_blank" class="btn btn-extra-large btn-rounded with-rounded btn-base-color btn-box-shadow box-shadow-extra-large mt-10px sm-mt-0">Ver más<span class="bg-white text-base-color"><i class="fa-solid fa-arrow-right"></i></span></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end slider item -->
                      <!-- start slider item -->
                    <div class="swiper-slide overflow-hidden">
                        <div class="cover-background position-absolute top-0 start-0 w-100 h-100" data-swiper-parallax="500" style="background-image:url('images/banner4.jpg');">
                            <div class="opacity-extra-medium bg-gradient-sherpa-blue-black"></div>
                            <div class="container h-100" data-swiper-parallax="-500">
                                <div class="row align-items-center h-100">
                                    <div class="col-xl-7 col-lg-8 col-md-10 swiper-slide-cont position-relative text-white text-center text-md-start" data-anime='{ "el": "childs", "translateX": [100, 0], "opacity": [0,1], "duration": 600, "delay": 0, "staggervalue": 300, "easing": "easeOutQuad" }'> 
                                        <div>
                                            <span class="fs-20 opacity-6 d-inline-block fw-300">Empresa dedicada a la </span>
                                        </div>
                                        <h1 class="alt-font  fs-30 w-90 xl-w-100 text-shadow-double-large ls-minus-2px mb-0">  <span class="fw-600"> Venta, instalación y mantenimiento </span>de rampas para Taller o Niveladoras de Andén</h1> 
                                        <a href="#" target="_blank" class="btn btn-extra-large btn-rounded with-rounded btn-base-color btn-box-shadow box-shadow-extra-large mt-10px sm-mt-0">Ver más<span class="bg-white text-base-color"><i class="fa-solid fa-arrow-right"></i></span></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end slider item -->
                </div>
                <!-- start slider pagination -->
                <div class="swiper-pagination swiper-pagination-clickable swiper-pagination-bullets"></div>
                <!-- end slider pagination -->
                <!-- start slider navigation -->
                <!--<div class="slider-one-slide-prev-1 icon-extra-large text-white swiper-button-prev slider-navigation-style-06 d-none d-sm-inline-block"><i class="line-icon-Arrow-OutLeft"></i></div>
                    <div class="slider-one-slide-next-1 icon-extra-large text-white swiper-button-next slider-navigation-style-06 d-none d-sm-inline-block"><i class="line-icon-Arrow-OutRight"></i></div>-->
                <!-- end slider navigation -->
            </div>
        </section>
        <!-- end banner -->

        <!-- marcas -->

        <section class="section-marcas position-relative bg-very-light-gray overflow-hidden"> 
            <div class="container">
           
                <div class="row justify-content-center mb-2">
                    <div class="col-xl-7 col-lg-9 col-md-10 text-center">
                        <h3 class="alt-font text-base-color fw-600 ls-minus-1px fs-28 mb-0" data-anime='{ "translateY": [30, 0], "opacity": [0,1], "duration": 600, "delay": 0, "staggervalue": 300, "easing": "easeOutQuad" }'>Marcas</h3>
                        <p>Trabajamos con los mejores de la industria.</p> 
                    </div>

                     <?php include 'iconos-marcasv1.php';?>
                </div>
            </div>
       </section>

       <!-- end marcas -->

       <!-- start section -->
<style>
    .col-lg-6 {float:left}
    </style>



       <section class="pb-0">
            <div class="container">
                <div class="row justify-content-center mb-4">
                    <div class="col-xl-7 col-lg-9 col-md-10 text-center">
                        <h3 class="alt-font text-dark-gray fw-600 ls-minus-1px appear anime-complete" data-anime="{ &quot;translateY&quot;: [30, 0], &quot;opacity&quot;: [0,1], &quot;duration&quot;: 600, &quot;delay&quot;: 0, &quot;staggervalue&quot;: 300, &quot;easing&quot;: &quot;easeOutQuad&quot; }" style="">Conoce nuestros Productos</h3>
                    </div>
                </div>
            </div>
        </section>        
        <!-- start section industriales --> 
        <section class="big-section bg-solitude-blue position-relative z-index-0 overflow-hidden">
            <div class="container">
                <div class="row align-items-center position-relative justify-content-center justify-content-lg-start">
                    <div class="col-xl-5 col-lg-6 col-md-11 ps-8 md-ps-15px" data-anime='{ "el": "childs", "translateY": [30, 0], "opacity": [0,1], "duration": 600, "delay": 0, "staggervalue": 300, "easing": "easeOutQuad" }'>
                        <h3 class="fw-600 ls-minus-1px text-dark-gray">Industriales</h3>
                        <ul class="p-0 list-style-01 fw-500 mb-40px">
                        	<?php foreach($Industrial as $sub){
                        	       $subName = $sub->getNombre();
                        	       $subLink = $sub->getFriendlyNameUrl();
                        	       ?>
                            <li class="border-color-transparent-dark-light pt-10px pb-10px text-dark-gray"><a href="<?=$subLink?>" ><i class="fa-solid fa-plus fs-14 me-10px"></i> <?=$subName?> </a></li>
                            <?php }?>                            
                        </ul>
                        <div class="d-inline-block w-100">
                            <a href="<?=ABS_HTTP_URL?>1/categoria/industrial/" class="btn btn-small btn-double-border btn-border-color-transparent-dark fw-700">
                                <span>
                                    <span class="btn-double-text" data-text="Ver todos">Ver todos</span>
                                    <span><i class="fa-solid fa-arrow-right"></i></span>
                                </span>
                            </a>
                        </div> 
                    </div>  
                    <div class="col-lg-5 col-md-11 position-relative offset-lg-1 md-mb-35px">
                        <img src="images/industrial-home.jpg" class="w-100 border-radius-4px" alt="Industriales">  
                    </div>
                </div>
            </div>
        </section>
        <!-- end section -->

         <!-- start section --> 
        <section class="big-section background-repeat position-relative z-index-0 overflow-hidden" style="background-image:url('images/home-bg-01.jpg');">
            <div class="container">
                <div class="row align-items-center position-relative justify-content-center justify-content-lg-start">
                    <div class="col-lg-5 col-md-11 position-relative offset-lg-1 md-mb-35px">
                        <img src="images/home-comercial.jpg" class="w-100 border-radius-4px" alt="Comercial">  
                    </div>
                    <div class="col-xl-5 col-lg-6 col-md-11 ps-8 md-ps-15px" data-anime='{ "el": "childs", "translateY": [30, 0], "opacity": [0,1], "duration": 600, "delay": 0, "staggervalue": 300, "easing": "easeOutQuad" }'>
                        <h3 class="fw-600 ls-minus-1px text-dark-gray">Comercial</h3>
                        <ul class="p-0 list-style-01 fw-500 mb-40px">
                        <?php foreach($Comercial as $sub){
                        	       $subName = $sub->getNombre();
                        	       $subLink = $sub->getFriendlyNameUrl();
                        	       ?>
                            <li class="border-color-transparent-dark-light pt-10px pb-10px text-dark-gray"><a href="<?=$subLink?>" ><i class="fa-solid fa-plus fs-14 me-10px"></i> <?=$subName?> </a></li>
                            <?php }?>                                
                        </ul>
                        <div class="d-inline-block w-100">
                            <a href="<?=ABS_HTTP_URL?>2/categoria/comercial/" class="btn btn-small btn-double-border btn-border-color-transparent-dark fw-700">
                                <span>
                                    <span class="btn-double-text" data-text="Ver todos">Ver todos</span>
                                    <span><i class="fa-solid fa-arrow-right"></i></span>
                                </span>
                            </a>
                        </div> 
                    </div>  
                </div>
            </div>
        </section>
        <!-- end section -->

        <!-- start section --> 
        <section class="big-section bg-solitude-blue position-relative z-index-0 overflow-hidden">
            <div class="container">
                <div class="row align-items-center position-relative justify-content-center justify-content-lg-start">
                    <div class="col-xl-5 col-lg-6 col-md-11 ps-8 md-ps-15px" data-anime='{ "el": "childs", "translateY": [30, 0], "opacity": [0,1], "duration": 600, "delay": 0, "staggervalue": 300, "easing": "easeOutQuad" }'>
                        <h3 class="fw-600 ls-minus-1px text-dark-gray">Equipos y Accesorios para Anden de Carga</h3>
                        <ul class="p-0 list-style-01 fw-500 mb-40px">
                            <?php foreach($Equipos as $sub){
                        	       $subName = $sub->getNombre();
                        	       $subLink = $sub->getFriendlyNameUrl();
                        	       ?>
                            <li class="border-color-transparent-dark-light pt-10px pb-10px text-dark-gray"><a href="<?=$subLink?>" ><i class="fa-solid fa-plus fs-14 me-10px"></i> <?=$subName?> </a></li>
                            <?php }?> 
                        </ul>
                        <div class="d-inline-block w-100">
                            <a href="<?=ABS_HTTP_URL?>3/categoria/equipos-y-accesorios-para-anden-de-carga/" class="btn btn-small btn-double-border btn-border-color-transparent-dark fw-700">
                                <span>
                                    <span class="btn-double-text" data-text="Ver todos">Ver todos</span>
                                    <span><i class="fa-solid fa-arrow-right"></i></span>
                                </span>
                            </a>
                        </div> 
                    </div>  
                    <div class="col-lg-5 col-md-11 position-relative offset-lg-1 md-mb-35px">
                        <img src="images/equipos-accesorios-anden-carga.jpeg" class="w-100 border-radius-4px" alt="Equipos y Accesorios para Anden de Carga">  
                    </div>
                </div>
            </div>
        </section>
        <!-- end section -->

        <!-- start section --> 
        <section class="big-section background-repeat position-relative z-index-0 overflow-hidden" style="background-image:url('images/home-bg-01.jpg');">
            <div class="container">
                <div class="row align-items-center position-relative justify-content-center justify-content-lg-start">
                    <div class="col-lg-5 col-md-11 position-relative offset-lg-1 md-mb-35px">
                        <img src="images/salida-emergencia.png" class="w-50 border-radius-4px" alt="Puertas peatonales de Salida de Emergencia">  
                    </div>
                    <div class="col-xl-5 col-lg-6 col-md-11 ps-8 md-ps-15px" data-anime='{ "el": "childs", "translateY": [30, 0], "opacity": [0,1], "duration": 600, "delay": 0, "staggervalue": 300, "easing": "easeOutQuad" }'>
                        <h3 class="fw-600 ls-minus-1px text-dark-gray">Puertas peatonales de Salida de Emergencia</h3>
                        <ul class="p-0 list-style-01 fw-500 mb-40px">
                            <?php foreach($Puertas as $sub){
                        	       $subName = $sub->getNombre();
                        	       $subLink = $sub->getFriendlyNameUrl();
                        	       ?>
                            <li class="border-color-transparent-dark-light pt-10px pb-10px text-dark-gray"><a href="<?=$subLink?>" ><i class="fa-solid fa-plus fs-14 me-10px"></i> <?=$subName?> </a></li>
                            <?php }?> 
                        </ul>
                        <div class="d-inline-block w-100">
                            <a href="<?=ABS_HTTP_URL?>4/categoria/puertas-peatonales-de-salida-de-emergencia/" class="btn btn-small btn-double-border btn-border-color-transparent-dark fw-700">
                                <span>
                                    <span class="btn-double-text" data-text="Ver todos">Ver todos</span>
                                    <span><i class="fa-solid fa-arrow-right"></i></span>
                                </span>
                            </a>
                        </div> 
                    </div>  
                </div>
            </div>
        </section>
        <!-- end section -->

        <!-- start section --> 
        <section class="big-section bg-solitude-blue position-relative z-index-0 overflow-hidden">
            <div class="container">
                <div class="row align-items-center position-relative justify-content-center justify-content-lg-start">
                    <div class="col-xl-5 col-lg-6 col-md-11 ps-8 md-ps-15px" data-anime='{ "el": "childs", "translateY": [30, 0], "opacity": [0,1], "duration": 600, "delay": 0, "staggervalue": 300, "easing": "easeOutQuad" }'>
                        <h3 class="fw-600 ls-minus-1px text-dark-gray">Puertas peatonales contra Incendio, Contra Explosión y Blindadas</h3>
                        <ul class="p-0 list-style-01 fw-500 mb-40px">
                            <?php foreach($Incendio as $sub){
                        	       $subName = $sub->getNombre();
                        	       $subLink = $sub->getFriendlyNameUrl();
                        	       ?>
                            <li class="border-color-transparent-dark-light pt-10px pb-10px text-dark-gray"><a href="<?=$subLink?>" ><i class="fa-solid fa-plus fs-14 me-10px"></i> <?=$subName?> </a></li>
                            <?php }?>  
                        </ul>
                        <div class="d-inline-block w-100">
                            <a href="<?=ABS_HTTP_URL?>5/categoria/puertas-peatonales-contra-incendio-contra-explosion-y-blindadas/" class="btn btn-small btn-double-border btn-border-color-transparent-dark fw-700">
                                <span>
                                    <span class="btn-double-text" data-text="Ver todos">Ver todos</span>
                                    <span><i class="fa-solid fa-arrow-right"></i></span>
                                </span>
                            </a>
                        </div> 
                    </div>  
                    <div class="col-lg-5 col-md-11 position-relative offset-lg-1 md-mb-35px">
                        <img src="images/PuertasIncendios.jpeg" class="w-100 border-radius-4px" alt="Puertas peatonales Blindadas">  
                    </div>
                </div>
            </div>
        </section>
        <!-- end section -->


        <!-- start section --> 
        <section class="big-section bg-solitude-blue position-relative z-index-0 overflow-hidden" style="background-image:url('images/home-bg-01.jpg');">
            <div class="container">
                <div class="row align-items-center position-relative justify-content-center justify-content-lg-start">
                    <div class="col-lg-5 col-md-11 position-relative offset-lg-1 md-mb-35px">
                        <img src="images/puerta-metalica-hospital.jpg" class="w-50 border-radius-4px" alt="Puertas peatonales para Hospitales">  
                    </div>
                    <div class="col-xl-5 col-lg-6 col-md-11 ps-8 md-ps-15px" data-anime='{ "el": "childs", "translateY": [30, 0], "opacity": [0,1], "duration": 600, "delay": 0, "staggervalue": 300, "easing": "easeOutQuad" }'>
                        <h3 class="fw-600 ls-minus-1px text-dark-gray">Puertas peatonales para Hospitales</h3>
                        <ul class="p-0 list-style-01 fw-500 mb-40px">
                            <?php foreach($Hospitales as $sub){
                        	       $subName = $sub->getNombre();
                        	       $subLink = $sub->getFriendlyNameUrl();
                        	       ?>
                            <li class="border-color-transparent-dark-light pt-10px pb-10px text-dark-gray"><a href="<?=$subLink?>" ><i class="fa-solid fa-plus fs-14 me-10px"></i> <?=$subName?> </a></li>
                            <?php }?> 
                        </ul>
                        <div class="d-inline-block w-100">
                            <a href="<?=ABS_HTTP_URL?>6/categoria/puertas-peatonales-para-hospitales/" class="btn btn-small btn-double-border btn-border-color-transparent-dark fw-700">
                                <span>
                                    <span class="btn-double-text" data-text="Ver todos">Ver todos</span>
                                    <span><i class="fa-solid fa-arrow-right"></i></span>
                                </span>
                            </a>
                        </div> 
                    </div>  
                    
                </div>
            </div>
        </section>
        <!-- end section -->
        <!-- start section Residenciales --> 
        <section class="big-section background-repeat position-relative z-index-0 overflow-hidden" style="background-image:url('images/home-bg-01.jpg');">
            <div class="container">
                <div class="row align-items-center position-relative justify-content-center justify-content-lg-start">                   
                    <div class="col-xl-5 col-lg-6 col-md-11 ps-8 md-ps-15px" data-anime='{ "el": "childs", "translateY": [30, 0], "opacity": [0,1], "duration": 600, "delay": 0, "staggervalue": 300, "easing": "easeOutQuad" }'>
                        <h3 class="fw-600 ls-minus-1px text-dark-gray">Residenciales</h3>
                        <ul class="p-0 list-style-01 fw-500 mb-40px">
                            <?php foreach($Residencial as $sub){
                        	       $subName = $sub->getNombre();
                        	       $subLink = $sub->getFriendlyNameUrl();
                        	       ?>
                            <li class="border-color-transparent-dark-light pt-10px pb-10px text-dark-gray"><a href="<?=$subLink?>" ><i class="fa-solid fa-plus fs-14 me-10px"></i> <?=$subName?> </a></li>
                            <?php }?> 
                        </ul>
                        <div class="d-inline-block w-100">                            
                            <a href="<?=ABS_HTTP_URL?>7/categoria/residenciales/" class="btn btn-small btn-double-border btn-border-color-transparent-dark fw-700">
                                <span>
                                    <span class="btn-double-text" data-text="Ver todos">Ver todos</span>
                                    <span><i class="fa-solid fa-arrow-right"></i></span>
                                </span>
                            </a>
                        </div> 
                    </div>  
                     <div class="col-lg-5 col-md-11 position-relative offset-lg-1 md-mb-35px">
                        <img src="images/residencial-2.png" class="w-100 border-radius-4px" alt="Residenciales">  
                    </div>
                </div>
            </div>
        </section>
        <!-- end section -->

        <!-- end section -->
            <!-- start nosotros -->
        <section class="half-section overlap-height position-relative overflow-hidden  pb-3"> 
            <div class="container overlap-gap-section">
                <div class="row align-items-center justify-content-md-center">
                    <div class="col-lg-4 col-md-10 position-relative md-mb-50px sm-mb-40px"> 
                        <figure class="position-relative m-0 text-center" data-anime='{ "effect": "slide", "color": "#fff2ef", "direction":"lr", "easing": "easeOutQuad", "delay":50}'>
                            <img src="images/quienes1.jpg" alt="Quienes Somos">
                            <!--<figcaption class="position-absolute bottom-90px left-0px" data-anime='{ "translateY": [-50, 0], "opacity": [0,1], "duration": 800, "delay": 1000, "staggervalue": 300, "easing": "easeOutQuad" }'>
                                <img src="images/quienes2.jpg" class="animation-float box-shadow-quadruple-large" alt="" style="border: solid 6px #ffffff;">
                            </figcaption>-->
                        </figure>
                    </div>
                    <div class="col-lg-5 offset-lg-1 col-md-12" data-anime='{ "el": "childs", "translateX": [50, 0], "opacity": [0,1], "duration": 1200, "delay": 0, "staggervalue": 150, "easing": "easeOutQuad" }'> 
                        <span class="fs-15 text-uppercase text-base-color fw-600 ls-1px mb-15px d-block">Acerca de nosotros</span>
                        <h3 class="fw-300 text-dark-gray fs-19 fancy-text-style-4">Empresa dedicada a la venta, instalación y mantenimiento de puertas automáticas para diferentes sectores de la industria.</h3>
                        <a href="nosotros.php" class="btn btn-large btn-dark-gray btn-hover-animation-switch btn-round-edge btn-box-shadow me-30px">
                            <span> 
                                <span class="btn-text">Conócenos</span>
                                <span class="btn-icon"><i class="feather icon-feather-arrow-right"></i></span>
                                <span class="btn-icon"><i class="feather icon-feather-arrow-right"></i></span>
                            </span>
                        </a>
                        <a href="soluciones.php" class="btn btn-link btn-hover-animation-switch btn-extra-large text-dark-gray fw-600 p-0">
                            <span>
                                <span class="btn-text">Nuestros servicios</span>
                                <span class="btn-icon"><i class="bi bi-caret-right-fill"></i></span>
                                <span class="btn-icon"><i class="bi bi-caret-right-fill"></i></span>
                            </span> 
                        </a>
                    </div> 
                </div>
            </div>
        </section>
        <!-- end nosotros -->        

        
         
<?php include 'footerv1.php';?>