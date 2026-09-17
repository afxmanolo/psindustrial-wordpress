<?php $Padres = ProjectLibrary_SDO_Core_Application_Categorias::GetCategoriasPadre();
$arrayIconos = array('icon-feather-briefcase','icon-feather-shopping-bag', 'icon-feather-truck', 'icon-feather-users', 'icon-feather-disc', 'icon-feather-plus', 'icon-feather-home');
?>
<div class="mb-30px"> 
	<ul class="p-0 m-0 list-style-02 fs-18 fw-500">
	<?php $i=0; foreach($Padres as $Padre){
	           $cName = $Padre->getNombre();
	           $cLink = $Padre->getFriendlyNameUrlPadre();
	       ?>
		<li class="pb-15px mb-15px border-bottom border-color-extra-medium-gray"><a href="<?=$cLink?>" class="text-dark-gray text-base-color-hover menu-it"><?=$cName?></a><i class="feather <?=$arrayIconos[$i]?> fs-22 ms-auto text-dark-gray"></i></li>
		<?php $i++; }?>                                      
<li class="pb-15px mb-15px border-bottom border-color-extra-medium-gray"><a href="marcasv1.php" class="text-dark-gray text-base-color-hover menu-it">Marcas</a><i class="feather icon-feather-star fs-22 ms-auto text-dark-gray"></i></li>
                                </ul>
                            </div>
                            <div class="ps-14 pe-14 pt-10 pb-10 lg-p-25px bg-dark-gray border-radius-6px text-center text-lg-start">
                                <span class="fs-20 fw-500 text-white mb-10px d-inline-block">Cotiza tu proyecto</span>
                                <p>Envíanos un mensaje, cuéntanos de tu proyecto.</p>
                                <a href="<?=ABS_HTTP_URL?>contacto.php" class="btn btn-extra-large btn-base-color btn-hover-animation-switch btn-round-edge btn-box-shadow d-block btn-icon-left">
                                    <span> 
                                        <span class="btn-text">Contacto</span>
                                        <span class="btn-icon"><i class="feather icon-feather-mail"></i></span>
                                        <span class="btn-icon"><i class="feather icon-feather-mail"></i></span>
                                    </span>
                                </a>
                            </div>