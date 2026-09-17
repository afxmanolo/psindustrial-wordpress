<?php
class ProjectLibrary_SDO_User {
	const ADMIN_ROLE = ProjectLibrary_SDO_Core_Application_User::ADMIN_ROLE;
	const USER_ROLE = ProjectLibrary_SDO_Core_Application_User::USER_ROLE;
	const SUPER_ROLE = ProjectLibrary_SDO_Core_Application_User::SUPER_ROLE;
	const MEMBER_ROLE = ProjectLibrary_SDO_Core_Application_User::MEMBER_ROLE;
	/**
	 * Retrieves all the User Profiles filtered by an specific user role and search parameters. 
	 * If the role is Null or an empty string, 
	 *
	 * @param ProjectLibrary_Entity_Search $search - The search parameters
	 * @param string $role                    - The Role to filter by. Empty for all roles.
	 * @return ProjectLibrary_Entity_Search_Result_Profile
	 */
	public static function SearchUsers( ProjectLibrary_Entity_Search $search, $role = null, $extraConditions = array() ){
		//$extraConditions = array();
	    
		if(!empty($role))
			$extraConditions[] = array( "columName"=>"role","value"=>$role,"isInteger"=>false);
			
		return ProjectLibrary_SDO_Core_Application_User::Search( $search, $extraConditions );
	}
	
	/**
	 * Load the User Profile from the given ID. Note an empty object is returned if the ID doesn't exist
	 *
	 * @param int $categoryId
	 * @return ProjectLibrary_Entity_User
	 */
	public static function LoadUser( $profileId ){
		return ProjectLibrary_SDO_Core_Application_User::LoadById( $profileId ); 
	}
	
	public static function GetRoles( ){
		return ProjectLibrary_SDO_Core_Application_User::GetRoles();
	}
	


	/**
	 * Saves a new or updates an existing User Profile object
	 *
	 * @param ProjectLibrary_Entity_User $profile
	 * @return ProjectLibrary_Entity_User
	 * @throws ProjectLibrary_SDO_Application_Exception
	 */
	public static function SaverUserProfile( ProjectLibrary_Entity_User $profile ){
		return ProjectLibrary_SDO_Core_Application_User::Save( $profile );
	}
	

	/**
	 * Load the User Profile from the given email. Note an empty object is returned if the email isn't found
	 *
	 * @param string $email
	 * @return ProjectLibrary_Entity_User
	 */
	public static function LoadUserProfileByEmail( $email ){
		return ProjectLibrary_SDO_Core_Application_User::LoadByEmail( $email ); 
	}
	//-------------------------------------------------
	//-------------------------------------------------
	//-------------------------------------------------
		

	
	
	/**
	 * Deletes a User Profile from the System
	 *
	 * @param int $profileId                            - The ID of the user profile to be deleted
	 * @return ProjectLibrary_Entity_Catalog_Category        - The recently deleted category
	 * @throws ProjectLibrary_SDO_Core_Application_Exception - When the category is not found or couldn't be deleted
	 */
	public static function DeleteUserProfile( $profileId ){
		return ProjectLibrary_SDO_Core_Application_Profile::Delete( $profileId ); 
	}
	
	/**
	 * Retrieves all the Profiles available.
	 * 
	 * @return ProjectLibrary_Entity_Util_ProfileArray
	 */
	public static function GetAllUserProfiles(){
		return ProjectLibrary_SDO_Core_Application_Profile::GetAll();
	}
	
	
	
	

	/**
	 * Saves a new or existing Profile Address (validation is part of the process )
	 *
	 * @param ProjectLibrary_Entity_User_Address $address - The object to be saved
	 * @return ProjectLibrary_Entity_User_Address         - The recently saved object
	 * @throws ProjectLibrary_SDO_Core_Validator_Exception, SQLException
	 */
	public static function SaveUserAddress( ProjectLibrary_Entity_User_Address $address ){
		return ProjectLibrary_SDO_Core_Application_UserAddress::Save( $address );
	}
	
	/**
	 * Retrieves the Address for the given User Profile ID. NOTE: Empty object returned if Address doesn't exist.
	 *
	 * @param int $profileId
	 * @return ProjectLibrary_Entity_User_Address
	 */
	public static function LoadUserAddress( $profileId ){
		return ProjectLibrary_SDO_Core_Application_UserAddress::LoadById( $profileId );
	}
	
	/**
	 * Deletes the Address for the given User Profile ID.
	 *
	 * @param int $profileId
	 * @return ProjectLibrary_Entity_Profile_Address - The recently deleted address
	 * @throws ProjectLibrary_SDO_Core_Application_Exception - When the address is not found or couldn't be deleted
	 */
	public static function DeleteUserAddress( $profileId ){
		return ProjectLibrary_SDO_Core_Application_UserAddress::Delete( $profileId );
	}
}
?>