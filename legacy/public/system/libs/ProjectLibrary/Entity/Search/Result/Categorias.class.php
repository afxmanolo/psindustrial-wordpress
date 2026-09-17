<?php
class ProjectLibrary_Entity_Search_Result_Categorias extends ProjectLibrary_Entity_Search_Result {

	public function __construct( ProjectLibrary_Entity_Search $search ){
		parent::__construct( $search );
	}	

	/**
	 * @see ProjectLibrary_Entity_Search_Result::getResults()
	 *
	 * @return ProjectLibrary_Entity_Util_ProfileArray
	 */
	public function getResults(){
		return parent::getResults();
	}

	/**
	 * @see ProjectLibrary_Entity_Search_Result::setResults()
	 *
	 * @param ProjectLibrary_Entity_Util_UserArray $results
	 */
	/*public function setResults( ProjectLibrary_Entity_Util_CategoriasArray $results ){
		parent::setResults( $results );
	}*/

}
?>