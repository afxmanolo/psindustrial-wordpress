<?php
class ProjectLibrary_Entity_Util_MarcasArray extends ArrayObject {
	
	public function __construct( array $array = array() ){
		parent::__construct( $array, 0, "ProjectLibrary_Entity_Util_MarcasIterator" );
	}
	
	/**
	 * @return ProjectLibrary_Entity_Util_UserIterator
	 */
	public function getIterator(){
		return parent::getIterator();
	}
	
}
?>