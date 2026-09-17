<?php

class ProjectLibrary_SDO_Core_Application_User extends ProjectLibrary_SDO_Core_Application {
	const ADMIN_ROLE = "Admin";
	const USER_ROLE = "User";
	const SUPER_ROLE = "Super";
	//const MEMBER_ROLE = "Member";
	
	/**
	 * Load the Profile from the given ID. Note: an empty object is returned if the ID doesn't exist
	 *
	 * @param int $profileId
	 * @return ProjectLibrary_Entity_User
	 */
	public static function LoadById( $profileId ){
		
		$dao = self::GetDAO();
		$entity = $dao->loadById( $profileId, false );
 
		$profile = new ProjectLibrary_Entity_User();   // Create new clean object to prevent old values being conserved
		$profile = ProjectLibrary_SDO_Core_Util_EntityManager::
		            ParseArrayToObject( $entity, $profile );  // Convert array to Object
		return $profile;
	}
	
	/**
	 * Load the Profile from the given email. Note: an empty object is returned if the email isn't found
	 *
	 * @param string $email
	 * @return ProjectLibrary_Entity_User
	 */
	public static function LoadByEmail( $email ){
		
		$dao = self::GetDAO();
		$entity = $dao->loadByEmail( $email );
		if ( empty( $entity ) ){
			$entity = $dao->loadById( 0, false );       
		}
		$profile = new ProjectLibrary_Entity_User();   // Create new clean object to prevent old values being conserved
		$profile = ProjectLibrary_SDO_Core_Util_EntityManager::
		            ParseArrayToObject( $entity, $profile );  // Convert array to to Object
		return $profile;
	}

	/**
	 * Deletes a Category from the System
	 *
	 * @param int $categoryId                    - The ID of the category to be deleted
	 * @return ProjectLibrary_Entity_Catalog_Category - The recently deleted category
	 * @throws ProjectLibrary_SDO_Core_Application_Exception - When the category is not found or couldn't be deleted
	 */
	public static function Delete( $profileId ){
		// 1. Ensure the category actually exists
		$profile = self::LoadById( $profileId );
		
		if ( $profile == new ProjectLibrary_Entity_User() ){
			throw new ProjectLibrary_SDO_Core_Application_Exception( 'The profile to be deleted doesn\'t exist.' );
		}
		else {
			try {
				// 2. Delete the Category
				$dao = self::GetDAO();
				$dao->delete( $profileId );
				
				// 3. Return the recently deleted Category
				return $profile;
			}
			catch ( SQLException $e ){
				throw new ProjectLibrary_SDO_Core_Application_Exception( 'There was an unexpected error while trying to delete the Profile', 0, $e );
			}
		}
	}
	
/**
	 * Retrieves the appropriate DAO
	 *
	 * @return ProjectLibrary_SDO_Core_DAO_Category
	 */
	protected static function GetDAO(){
		return new ProjectLibrary_SDO_Core_DAO_User();
	}

	/**
	 * Retrieves all the Categories available.
	 * 
	 * @return ProjectLibrary_Entity_Util_CategoryArray
	 */
	public static function GetAll(){
		$dao = self::GetDAO();
		$arrProfiles = $dao->loadAll();                           // Load all categories as a 2D array
		$objProfiles = self::ParseArrayToObjectArray( $arrProfiles );
		return $objProfiles;
	}
	
/**
	 * Performs a paged search for Categories
	 *
	 * @param ProjectLibrary_Entity_Search $search - The search parameters
	 * @param string $role                    - The role to filter by. Null for all user roles	
	 * @return ProjectLibrary_Entity_Search_Result_Category
	 */
	public static function Search( ProjectLibrary_Entity_Search $search, $extraConditions = null ){
	
		try {
			$dao = self::GetDAO();
			
			if ( !empty( $extraConditions ) ){
			    
					foreach( $extraConditions as $extraCondition){
						
						if( !empty( $extraCondition["value"] ) && !empty($extraCondition["columName"]) ){
							$condition = $extraCondition["columName"] . " = ";
							if( empty($extraCondition['isInteger']) || $extraCondition['isInteger'] === false){
								$condition .="'"; 
							}

							$condition .= $dao->getDB()->escapeString( $extraCondition['value'] );

							if( empty($extraCondition['isInteger']) || $extraCondition['isInteger'] === false){
								$condition .="'"; 
							}
							
							$dao->addExtraCondition( $condition );
						}	
					}
				}
			// Retrieve categories
				
			$arrProfiles = $dao->loadAllByParameters( $search->getKeywords(), $search->getSearchAsPhrase(),
			                                             $search->getOrderBy(), $search->getPage(),
			                                          $search->getResultsPerPage() );
			
			// Retrieve total pages
			$totalPages = $dao->loadAllByParameters( $search->getKeywords(), $search->getSearchAsPhrase(),
			                                         $search->getOrderBy(), $search->getPage(),
			                                         $search->getResultsPerPage(),
			                                         true );
			
			$result = new ProjectLibrary_Entity_Search_Result_User( $search );
			
			$result->setTotalPages( $totalPages );
			$result->setResults( self::ParseArrayToObjectArray( $arrProfiles ) );
			
			return $result;
		}
		catch( SQLException $e ){
			throw new ProjectLibrary_SDO_Core_Application_Exception( 
				'Error processing the search request. Please see the nested exception for details',
				0, $e );
		}
	}
	

	/**
	 * @param array $arrProfiles
	 * @return ProjectLibrary_Entity_Util_ProfileArray
	 */
	protected static function ParseArrayToObjectArray( array $arrProfiles ){
		// Create the array that will hold the Category objects
		$objProfiles = new ProjectLibrary_Entity_Util_UserArray(); 
		
		foreach( $arrProfiles as $arrProfile ){
			// Transform each array into object
$objProfile = ProjectLibrary_SDO_Core_Util_EntityManager::ParseArrayToObject( $arrProfile, new ProjectLibrary_Entity_User() );
			// Add object to category array
			              
			$objProfiles[ $objProfile->getUserId() ] = $objProfile;  
		}
		
		return $objProfiles;
	}
	
/**
	 * Retrieves the list of Roles available to users
	 *
	 * @return array<string>
	 * @todo Retrieve the list from the actual DB (enum)
	 */
	public static function GetRoles(){
		$ArrRoles = array();
		
		$db = new ProjectLibrary_SDO_Core_DB();
		
		//we create the instance here because eCommerce/SDO/Core
		//is restringed to access directly for the FrontEnd
		$daoUserProfile = self::GetDAO();
		
		$enumvalues = $db->sqlGetRecord( "SHOW COLUMNS FROM " . $daoUserProfile->getTable() . " LIKE 'role'" );
		
		//$enumvalues[ "type" ] contents text similar like 
		// enum( 'value1', 'value2' ... 'valueN' ) we need only the values
		// 'value1', 'value2' ... 'valueN'
		$enumvalues = substr( $enumvalues[ "Type" ], strlen('enum(')  , -1 );
		
		$enumvalues = explode(',', $enumvalues);
		
		foreach( $enumvalues as $enumvalue ){
			//$enumvalue contents text similar 
			// 'valueN' we need only valueN
			$ArrRoles[] = substr( $enumvalue, 1, -1 );
			
		}
		return $ArrRoles;
	}
	//------------------------------------------
	//------------------------------------------
	//------------------------------------------
	/**
	 * Saves a new or existing Profile into the System (validation is part of the process )
	 *
	 * @param ProjectLibrary_Entity_User $category - The object to be saved
	 * @return ProjectLibrary_Entity_User           - The recently saved object
	 * @throws ProjectLibrary_SDO_Core_Validator_Exception, SQLException
	 */
	public static function Save( ProjectLibrary_Entity_User $profile ){
		
		// 1. Validate object
		$validator = new ProjectLibrary_SDO_Core_Validator_User( $profile );
		$validator->validate();
		
		if ( !$validator->isValid() ){
			throw new ProjectLibrary_SDO_Core_Validator_Exception( $validator ); 
		}
		
		// 2. Save or Update Object
		$entity = new ArrayObject( $profile );        // Convert Object to Array
		$dao = self::GetDAO();
		
		//2.5 Identificar campos a encriptar con MD5
		$psw 	= $profile->getPassword();
		$pswDB 	= ProjectLibrary_SDO_User::LoadUser($profile->getUserId())->getPassword();
		if($psw == $pswDB){
			$md5_fields = explode(',',$dao->md5_fields);
			$md5_fields_str = '';
			foreach ($md5_fields as $field)$md5_fields_str .= ($field != 'password' ? $field : '') . ',';
			$dao->md5_fields = substr($md5_fields_str, 0, -1);
		}
		$dao->saveOrUpdate( $entity );
		
		// 3. Retrieve record from DB
		//$profile = self::LoadById( $entity[ 'user_id' ] );
		$profile = self::LoadById( $entity[ $dao->getIdField() ] );
		if ( $profile == new ProjectLibrary_Entity_User() ){
			throw new Exception( "The Product saved could not be retrieved." );      
		}
		// 4. Return recently saved object
		return $profile;
		
	}
	
	
	
	

	
	
	
	
}
?>