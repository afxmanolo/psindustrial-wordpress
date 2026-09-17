<?php
class ProjectLibrary_Entity_Util_VideoArray extends ArrayObject {
	
	public function __construct( array $array = array() ){
		parent::__construct( $array, 0, "ProjectLibrary_Entity_Util_VideoIterator" );
	}
	
	/**
	 * @return ProjectLibrary_Entity_Util_UserIterator
	 */
	public function getIterator(){
		return parent::getIterator();
	}
	
}
?>