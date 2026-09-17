<?php
/**
 * This class provides access to manipulation of Users in the System.
 * 
 */	  
class ProjectLibrary_SDO_Core_Application_Productos extends ProjectLibrary_SDO_Core_Application {
	/**
	 * Retrieves the appropriate DAO
	 *
	 * @return ProjectLibrary_SDO_Core_DAO_Category
	 */
	protected static function GetDAO(){
		return new ProjectLibrary_SDO_Core_DAO_Productos();
	}
	public static function GetEntitySearchResultObject( $search ){
		return new ProjectLibrary_Entity_Search_Result_Productos( $search );
	}

	public static function GetEntityUtilObjectArray(){
		return new ProjectLibrary_Entity_Util_ProductosArray();
	}
	
	public static function GetEntityObject(){
		return new ProjectLibrary_Entity_Productos();
	}
	
	/*
	protected function GetObjectId( $obj ){
		return $obj->getProductosId();
	}
	*/
	
	public static function GetSDOCoreValidatorObject( $profile ){
		return new ProjectLibrary_SDO_Core_Validator_Productos( $profile );
	}
	
//*****************************************************************************************
//*****************************************************************************************
//*****************************************************************************************

/**
	 * Saves a new or existing Profile into the System (validation is part of the process )
	 *
	 * @param ProjectLibrary_Entity_User $category - The object to be saved
	 * @return ProjectLibrary_Entity_User           - The recently saved object
	 * @throws ProjectLibrary_SDO_Core_Validator_Exception, SQLException
	 */
	//public static function Save( ProjectLibrary_Entity_Press $profile ){
	public static function Save( $profile ){
		// 1. Validate object
		//$validator = new ProjectLibrary_SDO_Core_Validator_User( $profile );
		
		$validator = self::GetSDOCoreValidatorObject( $profile );
		
		$validator->validate();
		
		if ( !$validator->isValid() ){
			throw new ProjectLibrary_SDO_Core_Validator_Exception( $validator ); 
		}
		
		// 2. Save or Update Object
		$entity = new ArrayObject( $profile );        // Convert Object to Array
		$dao = self::GetDAO();
		$dao->saveOrUpdate( $entity );
		
		// 3. Retrieve record from DB
		$idsFields = $dao->getIdFields();
		if(is_array($idsFields)){
			$arrIds = array();
			foreach($idsFields as $idField){
				$arrIds[ $idField ] = $entity[ $idField ];
			}
			$idsFields = $arrIds;
			$profile = self::LoadById( $idsFields );
		}else{
			$profile = self::LoadById( $entity[ $dao->getIdField() ] );
		}
		
		if ( $profile == self::GetEntityObject() ){
			throw new Exception( "El registro guardado no existe." );      
		}
		// 4. Return recently saved object
		return $profile;
		
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
			if ( !empty( $extraConditions ) && is_array($extraConditions) ){
			
					foreach( $extraConditions as $extraCondition){
						if( !empty( $extraCondition["value"] ) && !empty($extraCondition["columName"]) ){
							$condition = $extraCondition["columName"] . " = ";
							if( empty($extraCondition["SQL"]) && empty($extraCondition['isInteger']) || $extraCondition['isInteger'] === false){
								$condition .="'"; 
							}

							$condition .= $dao->getDB()->escapeString( $extraCondition['value'] );

							if( empty($extraCondition['isInteger']) || $extraCondition['isInteger'] === false){
								$condition .="'"; 
							}

							$dao->addExtraCondition( $condition );
						}
					  else {
						$dao->addExtraCondition( $extraCondition["SQL"] );
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
			
			$result = self::GetEntitySearchResultObject( $search );

			
			$result->setTotalPages( $totalPages );
			//debug( $result );
			$result->setResults( self::ParseArrayToObjectArray( $arrProfiles ) ); 
			return $result;
		}
		catch( SQLException $e ){
			throw new ProjectLibrary_SDO_Core_Application_Exception( 
				'Error al procesar la b&uacute;squeda',
				0, $e );
		}
	}



	/**
	 * @param array $arrProfiles
	 * @return ProjectLibrary_Entity_Util_ProfileArray
	 */
	protected static function ParseArrayToObjectArray( array $arrObjects ){
		// Create the array that will hold the Category objects
		$objProfiles = self::GetEntityUtilObjectArray();
		
		foreach( $arrObjects as $arrObject ){
			// Transform each array into object

			$obj = ProjectLibrary_SDO_Core_Util_EntityManager::
			               ParseArrayToObject( $arrObject, self::GetEntityObject() );
			// Add object to category array  
			//$objProfiles[ self::GetObjectId( $obj ) ] = $obj;
			$objProfiles[] = $obj;  
		}
		return $objProfiles;
	}

	

	/**
	 * Load the Profile from the given ID. Note: an empty object is returned if the ID doesn't exist
	 *
	 * @param int $profileId
	 * @return ProjectLibrary_Entity_User
	 */
	public static function LoadById( $profileId ){
		
		$dao = self::GetDAO();
		$entity = $dao->loadById( $profileId, false );
 	
		$profile = self::GetEntityObject();   // Create new clean object to prevent old values being conserved
		$profile = ProjectLibrary_SDO_Core_Util_EntityManager::
		            ParseArrayToObject( $entity, $profile );  // Convert array to Object
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
		
		if ( $profile == self::GetEntityObject() ){
			throw new ProjectLibrary_SDO_Core_Application_Exception( 'El registro que desea eliminar no existe.' );
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
				throw new ProjectLibrary_SDO_Core_Application_Exception( 'Ocurri&oacute; un error inesperado al intentar borrar el registro', 0, $e );
			}
		}
	}
//--------------------------------------------------------------------------------------------------------
//--------------------------------------------------------------------------------------------------------
//--------------------------------------------------------------------------------------------------------
	
	
	
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
	
	public static function GetAllInArray($includeNull = false ){
		$arr = array();
		$search = new ProjectLibrary_Entity_Search('','',1,-1);
		$extraCond = array();
		$registers = self::Search( $search, $extraCond)->getResults();
		
		if($includeNull){
			$arr[0] = "";
		}
		foreach($registers as $register){
			$arr[ $register->getProductosId() ] = $register->getNombre();
		}
		return $arr;
	}
	public static function GetAllProductos(){
	    $arr = array();
	    $search = new ProjectLibrary_Entity_Search('','obligatorio ASC, nombre ASC',1,-1);
	    $extraCond = array();
	    $registers = self::Search( $search, $extraCond)->getResults();	    	    
	    return $registers;
	}

/**
	 * Retrieves the list of Roles available to users
	 *
	 * @return array<string>
	 * @todo Retrieve the list from the actual DB (enum)
	 */
	public static function GetEnumValues( $colum ){
		$ArrRoles = array();
		
		$db = new ProjectLibrary_SDO_Core_DB();
		
		//we create the instance here because eCommerce/SDO/Core
		//is restringed to access directly for the FrontEnd
		$daoUserProfile = self::GetDAO();
		
		$enumvalues = $db->sqlGetRecord( "SHOW COLUMNS FROM " . $daoUserProfile->getTable() . " LIKE '".$colum."'" );
		
		//$enumvalues[ "type" ] contents text similar like 
		// enum( 'value1', 'value2' ... 'valueN' ) we need only the values
		// 'value1', 'value2' ... 'valueN'
		$enumvalues = substr( $enumvalues[ "Type" ], strlen('enum(')  , -1 );
		
		$enumvalues = explode(',', utf8_encode($enumvalues));
		
		foreach( $enumvalues as $enumvalue ){
			//$enumvalue contents text similar 
			// 'valueN' we need only valueN
			$ArrRoles[ substr( $enumvalue, 1, -1 ) ] = substr( $enumvalue, 1, -1 );
			
		}
		return $ArrRoles;
	}
	
	public static function LoadProductos(){	    
	    $dao = self::GetDAO();	    
	    $arrProfiles = $dao->loadProductos();// Load all categories as a 2D array	    
	    $objProfiles = self::ParseArrayToObjectArray( $arrProfiles );	    
	    return $objProfiles;	    
	}
	
	public static function getFriendlyNameUrl($obj){	    
	    $url 	= ABS_HTTP_URL . "{$obj->getProductosId()}/producto/{$obj->getFriendlyName()}/";	    
	    return strtolower($url);
	}
	
}
?>