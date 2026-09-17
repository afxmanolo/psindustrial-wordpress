<?php
class ProjectLibrary_Entity_IdsHandler extends ProjectLibrary_Entity {
		
public $arrayIds;
public $name;

public function __construct(){
		parent::__construct();
		$this->arrayIds = array();
}

public function getArrayIds(){ 
	return $this->arrayIds;
}

public function setArrayIds( $ArrayIds){
	return $this->arrayIds = $ArrayIds;
}

public function getName(){ 
	return $this->name;
}

public function setName( $name){ 
	return $this->name= $name;
}

//----------------------------------------------
//----------------------------------------------

public function resetIds(){
	$this->arrayIds = array();
}

public function getNextImage(){
	return array_pop($this->arrayIds);
}

public function addId( $id ){
	array_push($this->arrayIds, $id);
}

}
?>