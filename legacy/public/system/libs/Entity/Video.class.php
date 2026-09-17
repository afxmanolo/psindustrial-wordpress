<?php
class ProjectLibrary_Entity_Video extends ProjectLibrary_Entity {

	public function __construct(){
		parent::__construct();
	}

public $video_id;
public $titulo;
public $descripcion;
public $codigo_video;
public $imagen;


public function getVideoId(){ 
return $this->video_id;
}

public function setVideoId( $VideoId){ 
return $this->video_id = $VideoId;
}

public function getTitulo(){ 
return $this->titulo;
}

public function setTitulo( $Titulo){ 
return $this->titulo = $Titulo;
}

public function getDescripcion(){ 
return $this->descripcion;
}

public function setDescripcion( $Descripcion){ 
return $this->descripcion = $Descripcion;
}

public function getCodigoVideo(){ 
return $this->codigo_video;
}

public function setCodigoVideo( $CodigoVideo){ 
return $this->codigo_video = $CodigoVideo;
}

public function getImagen($position = null){ 
$image = explode(',',$this->imagen);
return !is_numeric($position) ? $image : (!empty($image[$position]) ? $image[$position] : 0 );}

public function getHTMLImagen($width = null,$height= null, $nimage = 0, $target_blank = false, $urlEffect = false, $imgParam = 'border="0"'){
 $imgParam2 = empty($width) ? '' : ' width="'.$width.'"';
 $imgParam2 .= empty($height) ? '' : ' height="'.$height.'"';
 $src = $this->getImagen( $nimage );
 
 $effect = $urlEffect;
 if(empty($src)){
 	$src = 'ima/fotomiembro.jpg';
 	$imgParam .= $imgParam2;
 	$urlImage .= $src;
 	$title = '';
 }else{
 $file = ProjectLibrary_SDO_Core_Application_FileManagement::LoadById($src);
 $title = $file->getDescription();
 $urlImage = BO_DIRECTORY . 'file.php?id=' . $src . '&type=image';
 	$src = BO_DIRECTORY . 'file.php?id=' . $src . '&type=image&img_size=predefined&width='.$width.'&height=' . $height;
 }
 $img = $target_blank ? '<a '. ($effect ? 'rel="jquery-lightbox"' : '' ) .' href="'.$urlImage.'" id="ImagePreviewId" target="_blank">' : '';
 $imgParam .= !$target_blank ? ' title="'.$title.'"' : '';
 $img .= "<img src='".$src."' ".$imgParam.">";
  $img .= ( $target_blank ? '</a>' : '');
 return $img;
 } 

public function setImagen( $Imagen){ 
return $this->imagen = $Imagen;
}

}
?>