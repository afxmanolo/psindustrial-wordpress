<?php $Marcas = ProjectLibrary_SDO_Core_Application_Marcas::GetAllMarcas();
$arrayMarcas = array('tg.png','wayne-dayton.png','clopay.png','blue.png','kelley.png','door.png','rytec.png','infraca.png','glg-porte-industriali.png','dockman.png','lift-master.png','bft.png');
?>
<ul class="shop-boxed shop-wrapper grid-loading grid grid-6col xxl-grid-6col xl-grid-6col lg-grid-6col md-grid-2col sm-grid-2col xs-grid-1col gutter-large text-center" data-anime='{ "el": "childs", "translateY": [50, 0], "opacity": [0,1], "duration": 600, "delay":100, "staggervalue": 150, "easing": "easeOutQuad" }'>
    <li class="grid-sizer"></li>
    <!-- start shop item -->
    <?php $cont = 0;
          foreach($Marcas as $Marca){
            $marcaName = $Marca->getNombre();
            $marcasLink = $Marca->getFriendlyNameUrl();
        ?>
    <li class="grid-item">
        <div class="shop-box">
            <div class="shop-image">
            	<a href="<?=$marcasLink?>"><img src="images/<?=$arrayMarcas[$cont]?>" alt="<?=$marcaName?>" /></a>
            </div>
        </div>
    </li>
    <?php $cont++;
          }?>
<!-- end shop item -->                                                                                                                                                                    
 </ul>