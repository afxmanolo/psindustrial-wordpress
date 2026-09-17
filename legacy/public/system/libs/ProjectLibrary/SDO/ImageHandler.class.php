<?php
class ProjectLibrary_SDO_ImageHandler{
	
	public static function GetValidAccessType( $accessType ){
		return ProjectLibrary_SDO_Core_IO_FileApplication::GetAccessType( $accessType );
	}
	
	public static function GetImageHanlderObject( $imgName ){
		return new ProjectLibrary_SDO_Core_Application_ImageHandler($imgName);
	}
	
	public static function createBOImages( $arrImages ){
		return ProjectLibrary_SDO_Core_Application_ImageHandler::createBOImages( $arrImages );
	}
	/**
	 * Retrieves all the User Profiles filtered by an specific user role and search parameters. 
	 * If the role is Null or an empty string, 
	 *
	 * @param ProjectLibrary_Entity_Search $search - The search parameters
	 * @param string $role                    - The Role to filter by. Empty for all roles.
	 * @return ProjectLibrary_Entity_Search_Result_Profile
	 */
	public static function SearchCustomer( ProjectLibrary_Entity_Search $search, $extraConditions = array() ){
		return ProjectLibrary_SDO_Core_Application_Customer::Search( $search, $extraConditions );
	}
	
	public static function SearchProjects( ProjectLibrary_Entity_Search $search, $extraConditions = array() ){
		return ProjectLibrary_SDO_Core_Application_Project::Search( $search, $extraConditions );
	}
	
	public static function LoadProject( $projectId ){
		return ProjectLibrary_SDO_Core_Application_Project::LoadById( $projectId ); 
	}
	
	public static function SaverProject( ProjectLibrary_Entity_Project $project ){
		return ProjectLibrary_SDO_Core_Application_Project::Save( $project );
	}
	/**
	 * Load the User Profile from the given ID. Note an empty object is returned if the ID doesn't exist
	 *
	 * @param int $categoryId
	 * @return ProjectLibrary_Entity_User
	 */
	public static function LoadCustomer( $customerId ){
		return ProjectLibrary_SDO_Core_Application_Customer::LoadById( $customerId ); 
	}
	
	
	/**
	 * Saves a new or updates an existing User Profile object
	 *
	 * @param ProjectLibrary_Entity_User $profile
	 * @return ProjectLibrary_Entity_User
	 * @throws ProjectLibrary_SDO_Application_Exception
	 */
	public static function SaverCustomer( ProjectLibrary_Entity_Customer $customer ){
		return ProjectLibrary_SDO_Core_Application_Customer::Save( $customer );
	}
	
	/**
	 * Deletes a User Profile from the System
	 *
	 * @param int $profileId                            - The ID of the user profile to be deleted
	 * @return ProjectLibrary_Entity_Catalog_Category        - The recently deleted category
	 * @throws ProjectLibrary_SDO_Core_Application_Exception - When the category is not found or couldn't be deleted
	 */
	public static function DeleteCustomer( $customerId ){
		return ProjectLibrary_SDO_Core_Application_Customer::Delete( $customerId ); 
	}
	
	public static function DeleteProject( $projectId ){
		return ProjectLibrary_SDO_Core_Application_Project::Delete( $projectId ); 
	}
	
	public static function GetTotalVotes( $surveyId ){
		$search = new ProjectLibrary_Entity_Search(
			'',
			'order_response ASC' ,
			1,
			-1
		);
		$extraConditions = array();
		$extraConditions[] = array( "columName"=>"survey_id","value"=>$surveyId,"isInteger"=>false);
		$result = self::SearchSurveyResponse( $search, $extraConditions );
		$total = 0;
		
		$surveyResponses = $result->getResults();
		foreach( $surveyResponses as $surveyResponse){
			$total += $surveyResponse->getTotal();
		}
		return $total;
	}
	
	//-------------------------------------------------
	//-------------------------------------------------
	//-------------------------------------------------
	
	
	
	public static function GetRoles( ){
		return ProjectLibrary_SDO_Core_Application_User::GetRoles();
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
	

		
	/**
	 * Retrieves all the Profiles available.
	 * 
	 * @return ProjectLibrary_Entity_Util_ProfileArray
	 */
	public static function GetAllUserProfiles(){
		return ProjectLibrary_SDO_Core_Application_Profile::GetAll();
	}
	
	public static function SavePressImage( ProjectLibrary_Entity_Util_FileUpload $imageFile ){
		return ProjectLibrary_SDO_Core_Application_FileManagement::SaveImageUpload( $imageFile, "Image for Press", "files/images/press/" );
	}

	public static function PrintJSLibs(){
		return ProjectLibrary_SDO_Core_Application_ImageHandler::printJSLibs();
	}
	public static function PrintImageMenu($images,$imageHandlerId,$imagePreviewId,$linkImagePreviewId,$width=400,$height=400){
		return ProjectLibrary_SDO_Core_Application_ImageHandler::printImageMenu($images,$imageHandlerId,$imagePreviewId,$linkImagePreviewId,$width,$height);
	}
}
?>