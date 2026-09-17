<?php include 'headerv1.php';
include_once "enviaContacto.php";
?>
       <style>
            .page-title-extra-small h1 {
                font-size: 3.8rem!important;
                line-height: 3.8rem!important;
            }
        </style>

       <!-- start page title -->
        <section class="tit-section page-title-big-typography bg-dark-gray ipad-top-space-margin xs-py-0 cover-background background-position-center-top" style="background-image: url(images/banner1.jpg)">
            <div class="opacity-medium bg-gradient-sherpa-blue-black"></div>
            <div class="container">
                <div class="row align-items-center justify-content-center small-screen">
                    <div class="col-xl-6 col-lg-7 col-sm-8 position-relative text-center page-title-extra-small" data-anime='{ "el": "childs", "translateY": [30, 0], "opacity": [0,1], "duration": 400, "delay": 0, "staggervalue": 100, "easing": "easeOutQuad" }'>
                        <h1 class="m-auto text-white alt-font text-shadow-double-large fw-700 w-90 xl-w-100" data-fancy-text='{ "opacity": [0, 1], "translateY": [50, 0], "filter": ["blur(20px)", "blur(0px)"], "string": ["Contacto"], "duration": 400, "delay": 0, "speed": 50, "easing": "easeOutQuad" }'></h1>
                    </div>
                </div>
            </div>
        </section>
        <!-- end page title -->


       <!-- start section -->
        <section class="overflow-hidden">
            <div class="container">
                <div class="row justify-content-center align-items-center mb-5 sm-mb-45px"> 
                    <div class="col-xxl-5 col-lg-6 md-mb-50px" data-anime='{ "el": "childs", "translateX": [-50, 0], "opacity": [0,1], "duration": 1200, "delay": 0, "staggervalue": 150, "easing": "easeOutQuad" }'> 
                        <span class="fs-15 text-uppercase text-base-color fw-600 mb-15px d-block ls-1px">Ponte en contacto con nosotros</span> 
                        <h3 class="fw-700 text-dark-gray ls-minus-1px mb-50px sm-mb-35px">¿Necesitas ayuda? ¡Contáctanos ahora!</h3>
                        <!-- start features box item -->
                        <div class="icon-with-text-style-01 mb-10 md-mb-35px" style="display:none;">
                            <div class="feature-box feature-box-left-icon last-paragraph-no-margin">
                                <div class="feature-box-icon me-10px">
                                    <img src="images/contact-03.jpg" class="h-80px" alt="Dirección">
                                </div>
                                <div class="feature-box-content last-paragraph-no-margin">
                                    <span class="d-block text-dark-gray fw-600 fs-18 ls-minus-05px mb-5px">Dirección</span>
                                    <p class="w-60 md-w-100">Blvd. Estrella #323 local 5-A ,Fracc. Estrella, C.P. 36566, Irapuato, Gto</p>
                                </div>
                            </div>
                        </div>
                        <!-- end features box item -->
                        <!-- start features box item -->
                        <div class="icon-with-text-style-01 mb-10 md-mb-35px">
                            <div class="feature-box feature-box-left-icon last-paragraph-no-margin">
                                <div class="feature-box-icon me-10px">
                                    <img src="images/contact.jpg" class="h-80px" alt="Teléfono">
                                </div>
                                <div class="feature-box-content">
                                    <span class="d-block text-dark-gray fw-600 fs-18 ls-minus-05px mb-5px">Teléfono</span>
                                    <div class="w-100 d-block">
                                        <span class="d-block fs-15">Teléfono: <a href="tel:477 176 3046" class="fs-15">477 176 3046</a></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- end features box item -->
                        <!-- start features box item -->
                        <div class="icon-with-text-style-01">
                            <div class="feature-box feature-box-left-icon last-paragraph-no-margin">
                                <div class="feature-box-icon me-10px">
                                    <img src="images/contact-04.jpg" class="h-80px" alt="Email">
                                </div>
                                <div class="feature-box-content">
                                    <span class="d-block text-dark-gray fw-600 fs-18 ls-minus-05px mb-5px">Email</span>
                                    <div class="w-100 d-block">
                                        <a href="mailto:overheaddoor@hotmail.com" class="fs-15">overheaddoor@hotmail.com</a><br>
                                        <a href="mailto:vicenteaguilarleon@gmail.com" class="fs-15">vicenteaguilarleon@gmail.com</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- end features box item -->
                    </div> 
                    <div class="col-lg-6 offset-xxl-1" data-anime='{ "el": "childs", "translateX": [50, 0], "opacity": [0,1], "duration": 1200, "delay": 0, "staggervalue": 150, "easing": "easeOutQuad" }'> 
                        <div class="contact-form-style-03 position-relative border-radius-10px bg-white p-14 lg-p-10 box-shadow-double-large overflow-hidden last-paragraph-no-margin">
                            <h2 class="fw-700 text-dark-gray mb-30px sm-mb-20px fancy-text-style-4 ls-minus-2px fs-30">Envíanos un mensaje</h2> 
                            <form method="post" action="" name="contactForm" id="contactForm">
                                <input type="hidden" value="send" name="cmd">
                                <?php getMSG($msg);?>
                                <div class="position-relative form-group mb-20px">
                                    <span class="form-icon text-dark-gray"><i class="bi bi-person icon-extra-medium"></i></span>
                                    <input class="ps-0 border-radius-0px medium-gray bg-transparent border-color-extra-medium-gray form-control required" type="text" name="entity[Nombre]"  value="<?=getValue('Nombre')?>" placeholder="Introduce tu nombre*" />
                                </div>
                                <div class="position-relative form-group mb-20px">
                                    <span class="form-icon text-dark-gray"><i class="bi bi-envelope icon-extra-medium"></i></span>
                                    <input class="ps-0 border-radius-0px medium-gray bg-transparent border-color-extra-medium-gray form-control required" type="email" name="entity[Email]"  value="<?=getValue('Email')?>"  placeholder="Introduce tu email*" />
                                </div>
                                <div class="position-relative z-index-1 form-group form-textarea mt-15px mb-0"> 
                                    <textarea class="ps-0 border-radius-0px medium-gray bg-transparent border-color-extra-medium-gray form-control" name="entity[Mensaje]" placeholder="Tu mensaje" rows="3"><?=getValue('Mensaje')?></textarea>
                                    <span class="form-icon text-dark-gray"><i class="bi bi-chat-square-dots icon-extra-medium"></i></span>
                                    <input type="hidden" name="redirect" value="">
                                    <button class="btn btn-large btn-dark-gray btn-round-edge btn-box-shadow mb-20px mt-25px w-100" type="submit">Enviar</button>
                                    <p class="fs-13 lh-22 w-90 md-w-100">Entiendo que mis datos se mantendrán de forma segura de acuerdo con la <a class="text-decoration-line-bottom text-dark-gray fw-500" href="politica-privacidad.php">Política de privacidad.</a></p>
                                     <div class="g-recaptcha" data-sitekey="6LdMAVEqAAAAABC_HVzxneNimdwyu2NL1JITmhQf" class="form-control"></div>
                                    <div class="form-results mt-20px d-none"></div>
                                </div>
                            </form> 
                        </div>
                    </div>
                </div> 
                <!--<div class="row align-items-center justify-content-center mb-3">
                    <div class="col-md-auto text-center text-md-end sm-mb-20px" data-anime='{ "translateX": [-50, 0], "opacity": [0,1], "duration": 1200, "delay": 0, "staggervalue": 150, "easing": "easeOutQuad" }'>
                        <h6 class="text-dark-gray fw-600 mb-0 ls-minus-1px">Síguenos en</h6>
                    </div>
                    <div class="col-2 d-none d-lg-inline-block" data-anime='{ "translateX": [0, 0], "opacity": [0,1], "duration": 1200, "delay": 0, "staggervalue": 150, "easing": "easeOutQuad" }'>
                        <span class="w-100 h-1px bg-dark-gray opacity-2 d-flex mx-auto"></span>
                    </div>
                    <div class="col-md-auto elements-social social-icon-style-04 text-center text-md-start ps-lg-0" data-anime='{ "translateX": [50, 0], "opacity": [0,1], "duration": 1200, "delay": 0, "staggervalue": 150, "easing": "easeOutQuad" }'>
                        <ul class="large-icon dark">
                            <li class="m-0"><a class="facebook" href="https://www.facebook.com/" target="_blank"><i class="fa-brands fa-facebook-f"></i><span></span></a></li>    
                            <li class="m-0"><a class="instagram" href="http://www.instagram.com" target="_blank"><i class="fa-brands fa-instagram"></i><span></span></a></li>
                            <li class="m-0"><a class="linkedin" href="http://www.linkedin.com" target="_blank"><i class="fa-brands fa-linkedin-in"></i><span></span></a></li>
                        </ul>                  
                    </div>
                </div>-->
            </div>
        </section>
        <!-- end section -->


         
<?php include 'footer.php';?>