<?php
class ProjectLibrary_Entity_Productos extends ProjectLibrary_Entity {

	public function __construct(){
		parent::__construct();
	}

public $productos_id;
public $nombre;
public $descripcion;
public $imagenes;
public $fichas;
public $video;
public $categoria;
public $marca;
public $fecha;

public function getProductosId(){ 
return $this->productos_id;
}

public function setProductosId( $ProductosId){ 
return $this->productos_id = $ProductosId;
}

public function getNombre(){ 
return $this->nombre;
}

public function setNombre( $Nombre){ 
return $this->nombre = $Nombre;
}

public function getDescripcion(){ 
return $this->descripcion;
}

public function setDescripcion( $Descripcion){ 
return $this->descripcion = $Descripcion;
}

public function getImagenes($position = null){
    $image = !empty($this->imagenes) ? explode(',', $this->imagenes) : [];
    return !is_numeric($position) ? $image : (!empty($image[$position]) ? $image[$position] : 0 );
}
    
public function getHTMLImagen($width = null,$height= null, $nimage = 0, $target_blank = false, $urlEffect = false, $imgParam = 'border="0"'){
        $imgParam2 = empty($width) ? '' : ' width="'.$width.'"';
        $imgParam2 .= empty($height) ? '' : ' height="'.$height.'"';
        $src = $this->getImagenes( $nimage );
        
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
    
public function setImagenes( $Imagenes){
        return $this->imagenes = $Imagenes;
}

public function getFichas($position = null){
    $image = !empty($this->fichas) ? explode(',', $this->fichas) : [];
    return !is_numeric($position) ? $image : (!empty($image[$position]) ? $image[$position] : 0 );   
}

public function setFichas( $Fichas){
    return $this->fichas = $Fichas;
}

public function getVideo(){
    return $this->video;
}

public function setVideo( $Video){
    return $this->video = $Video;
}

public function getCategoria(){
    return $this->categoria;
}

public function setCategoria( $Categoria){
    return $this->categoria = $Categoria;
}

public function getMarca(){
    return $this->marca;
}

public function setMarca( $Marca){
    return $this->marca = $Marca;
}

public function getFecha(){
    return $this->fecha;
}

public function setFecha( $Fecha){
    return $this->fecha = $Fecha;
}

public function getUrlArrayImages($version,$pos = NULL, $description = false){
    return $this->getUrlArrayFiles($this->imagenes,$version,$pos, $description);
}

public function getUrlArrayFiles($array_files,$version,$pos = NULL, $description = false){
    $ret = $arr = explode(',',$array_files);
    if(count($ret)>0 && !empty($ret[0])){
        $search = new ProjectLibrary_Entity_Search('','',1,-1);
        if( is_numeric($pos) ){
            if($arr[ $pos ]){
                $img = ProjectLibrary_SDO_Core_Application_FileManagement::getFileByVersion($arr[ $pos ],$version);
                $ret = $description ? array(ABS_HTTP_URL . "multimedia/{$img->getPath()}{$img->getFilename()}", $img->getDescription()) : ABS_HTTP_URL . "multimedia/{$img->getPath()}{$img->getFilename()}";
            }else{
                $ret = $description ? array() : null;
            }
        }else{
            $ret = array();
            foreach($arr as $p){
                $img = ProjectLibrary_SDO_Core_Application_FileManagement::getFileByVersion($p,$version);
                $orginalId = $img->getOriginalId();
                $orginalId = empty($orginalId) ? $img->getId() : $orginalId;
                if($orginalId)
                    $ret[$orginalId] = $description ? array(ABS_HTTP_URL . "multimedia/{$img->getPath()}{$img->getFilename()}", $img->getDescription()) : ABS_HTTP_URL . "multimedia/{$img->getPath()}{$img->getFilename()}";
                    else
                        $ret[] = $description ? array(ABS_HTTP_URL . "multimedia/{$img->getPath()}{$img->getFilename()}", $img->getDescription()) : ABS_HTTP_URL . "multimedia/{$img->getPath()}{$img->getFilename()}";
            }
        }
        return $ret;
    }else{
        switch($version){
            case 'small':$size 	= 'width=90&height=73';break;
            case 'medium':$size = 'width=430&height=430';break;
            case 'large':$size 	= 'width=700&height=600';break;
        }        
        return false;
    }
}


public function getFriendlyName(){return Util_String::validStringForUrl( $this->nombre ) ;}
public function getFriendlyNameUrl(){return ProjectLibrary_SDO_Core_Application_Productos::getFriendlyNameUrl($this);}

}
?>