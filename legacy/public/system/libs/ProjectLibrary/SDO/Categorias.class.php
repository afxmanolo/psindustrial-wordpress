<?php
class ProjectLibrary_SDO_Categorias{
	
	/**
	 * Retrieves all the User Profiles filtered by an specific user role and search parameters. 
	 * If the role is Null or an empty string, 
	 *
	 * @param ProjectLibrary_Entity_Search $search - The search parameters
	 * @param string $role                    - The Role to filter by. Empty for all roles.
	 * @return ProjectLibrary_Entity_Search_Result_Profile
	 */
	public static function SearchCategorias( ProjectLibrary_Entity_Search $search, $extraConditions = array() ){
		return ProjectLibrary_SDO_Core_Application_Categorias::Search( $search, $extraConditions );
	}
	

	/**
	 * Load the User Profile from the given ID. Note an empty object is returned if the ID doesn't exist
	 *
	 * @param int $categoryId
	 * @return ProjectLibrary_Entity_User
	 */
	public static function LoadCategorias( $CategoriasId ){
		return ProjectLibrary_SDO_Core_Application_Categorias::LoadById( $CategoriasId ); 
	}
	
	/**
	 * Saves a new or updates an existing User Profile object
	 *
	 * @param ProjectLibrary_Entity_User $profile
	 * @return ProjectLibrary_Entity_User
	 * @throws ProjectLibrary_SDO_Application_Exception
	 */
	public static function SaverCategorias( ProjectLibrary_Entity_Categorias $Categorias ){
		
		return ProjectLibrary_SDO_Core_Application_Categorias::Save( $Categorias );
	}

	/**
	 * Deletes a User Profile from the System
	 *
	 * @param int $profileId                            - The ID of the user profile to be deleted
	 * @return ProjectLibrary_Entity_Catalog_Category        - The recently deleted category
	 * @throws ProjectLibrary_SDO_Core_Application_Exception - When the category is not found or couldn't be deleted
	 */
	public static function DeleteCategorias( $CategoriasId ){
		return ProjectLibrary_SDO_Core_Application_Categorias::Delete( $CategoriasId ); 
	}
	
	/**
	 * Retrieves all the Categorias available.
	 * 
	 * @return ProjectLibrary_Entity_Util_Categoriasrray
	 */
	public static function GetAllCategorias(){
		return ProjectLibrary_SDO_Core_Application_Categorias::GetAll();
	}
	
	public static function GetAllInArray(){
		return ProjectLibrary_SDO_Core_Application_Categorias::GetAllInArray();
	}
}
?>