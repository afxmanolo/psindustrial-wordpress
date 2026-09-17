<?$audios = $this->audios;?>
<div id="audio">
	<div id="multiaudio">
		<div id="multimedia-tit"><h2 class="blanco20"><a href="<?=ABS_HTTP_URL?>audios/1/" class="blanco20">Audio</a></h2></div>
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
	</div>  
	<?if(count($audios)>0){?><div class="rojo12" id="paginas1"><strong><a href="<?=ABS_HTTP_URL?>audios/1/" class="rojo12">escuchar todo el audio  &gt;</a></strong></div><?}?>
</div>