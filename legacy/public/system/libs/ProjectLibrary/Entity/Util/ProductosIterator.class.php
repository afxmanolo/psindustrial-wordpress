<?php
class ProjectLibrary_Entity_Util_ProductosIterator extends ArrayIterator {
	
	/**
	 * Returns the current User entity in the array
	 * @return ProjectLibrary_Entity_User
	 */
	public function current(){
		return parent::current();
	}
	
}
?>