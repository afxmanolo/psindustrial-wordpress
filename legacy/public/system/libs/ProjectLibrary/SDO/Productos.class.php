<?php
class ProjectLibrary_SDO_Productos{
	
	/**
	 * Retrieves all the User Profiles filtered by an specific user role and search parameters. 
	 * If the role is Null or an empty string, 
	 *
	 * @param ProjectLibrary_Entity_Search $search - The search parameters
	 * @param string $role                    - The Role to filter by. Empty for all roles.
	 * @return ProjectLibrary_Entity_Search_Result_Profile
	 */
	public static function SearchProductos( ProjectLibrary_Entity_Search $search, $extraConditions = array() ){
		return ProjectLibrary_SDO_Core_Application_Productos::Search( $search, $extraConditions );
	}
	

	/**
	 * Load the User Profile from the given ID. Note an empty object is returned if the ID doesn't exist
	 *
	 * @param int $categoryId
	 * @return ProjectLibrary_Entity_User
	 */
	public static function LoadProductos( $ProductosId ){
		return ProjectLibrary_SDO_Core_Application_Productos::LoadById( $ProductosId ); 
	}
	
	/**
	 * Saves a new or updates an existing User Profile object
	 *
	 * @param ProjectLibrary_Entity_User $profile
	 * @return ProjectLibrary_Entity_User
	 * @throws ProjectLibrary_SDO_Application_Exception
	 */
	public static function SaverProductos( ProjectLibrary_Entity_Productos $Productos ){
		
		return ProjectLibrary_SDO_Core_Application_Productos::Save( $Productos );
	}

	/**
	 * Deletes a User Profile from the System
	 *
	 * @param int $profileId                            - The ID of the user profile to be deleted
	 * @return ProjectLibrary_Entity_Catalog_Category        - The recently deleted category
	 * @throws ProjectLibrary_SDO_Core_Application_Exception - When the category is not found or couldn't be deleted
	 */
	public static function DeleteProductos( $ProductosId ){
		return ProjectLibrary_SDO_Core_Application_Productos::Delete( $ProductosId ); 
	}
	
	/**
	 * Retrieves all the Productos available.
	 * 
	 * @return ProjectLibrary_Entity_Util_Productosrray
	 */
	public static function GetAllProductos(){
		return ProjectLibrary_SDO_Core_Application_Productos::GetAll();
	}
	
	public static function GetAllInArray(){
		return ProjectLibrary_SDO_Core_Application_Productos::GetAllInArray();
	}
}
?>