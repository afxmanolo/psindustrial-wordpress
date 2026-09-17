       <a href="https://wa.me/+524791071234?text=PSI" class="whatsapp" target="_blank"> <i class="fa-brands fa-whatsapp whatsapp-icon"></i></a>
       
       <!-- start footer -->
        <footer class="bg-dark-gray background-position-center-top" style="background-image: url('<?=ABS_HTTP_URL?>images/demo-marketing-footer-dot.svg')"> 
            <div class="container overlap-section">
                <div class="row g-0 justify-content-center align-items-center bg-base-color border-radius-6px ps-7 pe-7 pt-4 pb-4 lg-p-30px sm-p-20px mb-7">
                    <div class="col-lg-5 col-md-9 text-lg-start md-mb-20px">
                        <h4 class="text-white fw-600 mb-0 ls-minus-1px fs-28">¡Platícanos de tu proyecto!</h4>
                    </div>
                    <div class="col-auto col-lg-7 icon-with-text-style-08">
                        <div class="feature-box feature-box-left-icon-middle overflow-hidden">
                            <div class="feature-box-icon feature-box-icon-rounded w-80px h-80px rounded-circle bg-dark-gray-transparent-light me-25px lg-me-20px">
                                <i class="bi bi-envelope icon-very-medium text-white"></i>
                            </div>
                            <div class="feature-box-content last-paragraph-no-margin">
                                <span class="text-white fs-18 lh-22 mb-5px d-block">Envíanos un mensaje</span>
                                <h6 class="d-inline-block fw-600 mb-0"><a href="mailto:administracion@puertasyserviciosindustriales.com" class="text-white text-white-hover fs-19 text-decoration-none">administracion@puertasyserviciosindustriales.com</a></h6> 
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container footer-dark text-center text-sm-start"> 
                <div class="row mb-5 sm-mb-30px">
                    <!-- start footer column -->
                    <div class="col-lg-4 col-md-4 col-sm-6 d-flex flex-column last-paragraph-no-margin">
                        <a href="<?=ABS_HTTP_URL?>nosotros.php" class="footer-logo d-inline-block">
                            <img src="<?=ABS_HTTP_URL?>images/logo-white@2x.png" data-at2x="<?=ABS_HTTP_URL?>images/logo-white@2x.png" alt="PSI">
                             <p class="w-90 sm-w-100 txtfooter d-inline-block mt-20px lh-20">Venta, instalación y mantenimiento de puertas automáticas para diferentes sectores de la industria <br><br><span class="text-white">Ver más →</span></p>
                        </a>

                        
                        
                    </div>
                    <!-- end footer column -->  
                    <!-- start footer column -->
<?php $Padres = ProjectLibrary_SDO_Core_Application_Categorias::GetCategoriasPadre();

?>
                    <div class="col-lg-4 col-md-4 col-sm-6 d-flex flex-column last-paragraph-no-margin md-mb-35px"> 
                        <span class="alt-font d-block text-white mb-5px fs-20">Soluciones</span>
                        <ul>
<?php foreach($Padres as $Padre){
        $cName = $Padre->getNombre();
        $cLink = $Padre->getFriendlyNameUrlPadre();
?>                        
<li><a href="<?=$cLink?>">• <?=$cName?></a></li>
<?php }?>                                                        
                            <li><a href="<?=ABS_HTTP_URL?>marcasv1.php">• Marcas</a></li>   
                        </ul>
                    </div>
                    <!-- end footer column -->
                     <!-- start footer column -->
                    <div class="col-lg-4 col-md-4 col-sm-6 d-flex flex-column last-paragraph-no-margin md-mb-35px"> 
                        <span class="alt-font d-block text-white mb-5 fs-20 ">Contacto</span>
                        <p class="lh-22">Blvd. Estrella #323 local 5-A ,Fracc. Estrella, C.P. 36566, Irapuato, Gto</p>
                        <div><i class="feather icon-feather-phone-call icon-very-small text-white me-10px"></i><a href="tel:4791071234" class="text-white">479 107 12 34</a></div>
                        <div><a href="mailto:overheaddoor@hotmail.com" class="text-white text-decoration-none">overheaddoor@hotmail.com</a><br>
                        <a href="mailto:vicenteaguilarleon@gmail.com" class="text-white text-decoration-none">vicenteaguilarleon@gmail.com</a></div>
                        
                        <span class="alt-font d-block text-white mb-5px"></span>
                        <ul class="mt-30px">                           
                            <li><a href="<?=ABS_HTTP_URL?>politica-privacidad.php" class="text-white">Política de privacidad →</a></li>
                             <li><p class="fs-15">Puertas y Servicios Industriales &copy; <?=(date('Y'))?></p> </li>
                        </ul>
                    </div>
                    <!-- end footer column -->
                     
                     
                </div>
            </div>
        </footer>
        <!-- end footer -->

        <!-- javascript libraries -->
        <script type="text/javascript" src="<?=ABS_HTTP_URL?>js/jquery.js"></script>
        <script type="text/javascript" src="<?=ABS_HTTP_URL?>js/vendors.min.js"></script>
        <script type="text/javascript" src="<?=ABS_HTTP_URL?>js/main.js"></script>
    </body>
</html>