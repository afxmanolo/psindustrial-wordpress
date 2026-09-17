<?php
//ProjectLibrary_FrontEnd_FO_Authentification::verifyAuthentification();

$result	  = $this->result;
$audios = $result->getResults();
$hiddenFields = empty($this->hiddenFields) ? array() : $this->hiddenFields;

$title_header='Audios | ';include"header.php";?>
    <div id="content">
    	<div id="content-home">
   	    <div id="pleca-int"></div>
   	    <div id="contenido-interior">
          <div id="multimedia-int">
       	    <h1 class="rojo27">Audio</h1>
			<?if(count($audios)>0){
				foreach($audios as $audio){
					$titulo	= Util_String::subText($audio->getTitulo(),57);
					$link	= ABS_HTTP_URL . "system/file.php?id={$audio->getArchivo(0)}&cmd=download";
					$title	= "Descargar $titulo";
					$alt	= "Audio $titulo";?>
					<div class="video-tumbs">
						<div class="foto-multimedia"><a href="<?=$link?>" title="<?=$title?>"><img src="<?=ABS_HTTP_URL?>ima/int/tumb-audio.jpg" alt="<?=$alt?>" width="184" height="120" border="0" /></a></div>
						<div class="gris12"><strong><a href="<?=$link?>" title="<?=$title?>" class="gris12"><?=$titulo?></a></strong></div>	
					</div>
				<?}
			}/*else{?>Muy pronto...<?}*/?>
			<div style="clear:both;"></div>
			<div class="gris12" id="paginas"><?ProjectLibrary_SDO_Core_Application_Form::foPrintPager($result,"FormAudio","gris12","rojo12",5,$hiddenFields,'',ABS_HTTP_URL.'audios/{p}/');?></div>
          </div>
          <?include_once 'int_redes.php';?>
          </div>
    	</div>
    </div>
<? include"footer.php";?>