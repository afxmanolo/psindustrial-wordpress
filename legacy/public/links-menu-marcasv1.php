<?php $Marcas = ProjectLibrary_SDO_Core_Application_Marcas::GetAllMarcas();?>
<div class="mb-30px">
    <h5>Marcas</h5>
                                <ul class="p-0 m-0 list-style-02 fs-18 fw-500">
                                 <?php $cont = 0;
          foreach($Marcas as $Marca){
            $marcaName = $Marca->getNombre();
            $marcasLink = $Marca->getFriendlyNameUrl();
        ?>
<li class="pb-15px mb-15px border-bottom border-color-extra-medium-gray">
<a href="<?=$marcasLink?>" class="text-dark-gray text-base-color-hover"><?=$marcaName?></a><i class="feather icon-feather-arrow-right fs-14 ms-auto text-dark-gray"></i></li>
<?php }?>
                                </ul>
                            </div>
                            <div class="ps-14 pe-14 pt-10 pb-10 lg-p-25px bg-dark-gray border-radius-6px text-center text-lg-start">
                                <span class="fs-20 fw-500 text-white mb-10px d-inline-block">Cotiza tu proyecto</span>
                                <p>Envíanos un mensaje, cuéntanos de tu proyecto.</p>
                                <a href="contacto.php" class="btn btn-extra-large btn-base-color btn-hover-animation-switch btn-round-edge btn-box-shadow d-block btn-icon-left">
                                    <span> 
                                        <span class="btn-text">Contacto</span>
                                        <span class="btn-icon"><i class="feather icon-feather-mail"></i></span>
                                        <span class="btn-icon"><i class="feather icon-feather-mail"></i></span>
                                    </span>
                                </a>
                            </div>