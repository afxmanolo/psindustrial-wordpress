<?php
class ProjectLibrary_Entity_Marcas extends ProjectLibrary_Entity {

	public function __construct(){
		parent::__construct();
	}

public $marcas_id;
public $nombre;
public $imagen;


public function getMarcasId(){ 
return $this->marcas_id;
}

public function setMarcasId( $MarcasId){ 
return $this->marcas_id = $MarcasId;
}

public function getNombre(){ 
return $this->nombre;
}

public function setNombre( $Nombre){ 
return $this->nombre = $Nombre;
}

public function getImagen($position = null){
    $image = !empty($this->imagen) ? explode(',', $this->imagen) : [];
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

    public function getUrlArrayImagen($version,$pos = NULL, $description = false){
        return $this->getUrlArrayFiles($this->imagen,$version,$pos, $description);
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
    public function getFriendlyNameUrl(){return ProjectLibrary_SDO_Core_Application_Marcas::getFriendlyNameUrl($this);}
}
?>