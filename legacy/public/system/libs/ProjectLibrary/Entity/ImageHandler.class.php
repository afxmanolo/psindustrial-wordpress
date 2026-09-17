<?php
class ProjectLibrary_Entity_ImageHandler extends ProjectLibrary_Entity {
		
public $arrayImages;
public $name;

public function __construct(){
		parent::__construct();
		$arrayImages = array();
}

public function getArrayImages(){ 
	return $this->arrayImages;
}

public function setArrayImages( $ArrayImages){ 
	return $this->arrayImages = $ArrayImages;
}

public function getName(){ 
	return $this->name;
}

public function setName( $name){ 
	return $this->name= $name;
}

//----------------------------------------------
//----------------------------------------------

public function resetImages(){
	$this->arrayImages = array();
}

public function getNextImage(){
	return array_pop($this->arrayImages);
}

public function addImage( $image ){
	array_push($this->arrayImages, $image);
}

}
?>