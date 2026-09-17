<?php
class ProjectLibrary_SDO_Core_Application_FileManagement extends ProjectLibrary_SDO_Core_Application {
	
	/**
	 * Load the File from the given ID. Note an empty object is returned if the ID doesn't exist
	 *
	 * @param int $fileId
	 * @return ProjectLibrary_Entity_Util_File
	 */
	public static function LoadById( $fileId ){
		$dao = self::GetDAO();
		$entity = $dao->loadById( $fileId, true );       
		$file = new ProjectLibrary_Entity_Util_File();   // Create new clean object to prevent old values being conserved
		$file = ProjectLibrary_SDO_Core_Util_EntityManager::
		           ParseArrayToObject( $entity, $file );  // Convert array to to Object
		return $file;
	}
	
	/**
	 * Saves an uploaded file (should only be accessed from within this class).
	 *
	 * @param ProjectLibrary_Entity_Util_FileUpload $file
	 * @return ProjectLibrary_Entity_Util_File
	 * @throws ProjectLibrary_SDO_Core_Validator_Exception, ProjectLibrary_SDO_Core_Application_Exception
	 */
	protected static function SaveFileUpload( ProjectLibrary_Entity_Util_FileUpload $file,
	                                       ProjectLibrary_SDO_Core_Validator_File $validator = null,
	                                       $description = '', $destination = '/files', $accessType='public', $validators = null, $arrayVersions = array(), $destinationVersion = '/multimedia' ){

		// 1. Validate
		$ban = false;
		if( empty($validators)){
			$validator->validate();
			$ban = $validator->isValid();
		}else{
			
			foreach( $validators as $validator){
				$validator->validate();
				$ban = $validator->isValid();
				
				if($ban)
					break;
			}
		}
		if ( !$ban ){
			// Return errors
			throw new ProjectLibrary_SDO_Core_Validator_Exception( $validator );
		}
		else { 
			try {
				// 2. Save file in DB
				$dao = self::GetDAO();
				
				$fileSaver = new IO_FileSaver( $dao->getDB(), $file->getStructureName(), $destination, $destinationVersion  );
				
				$id = $fileSaver->save( $description, $accessType, $arrayVersions );
				
				// 3. Retrieve record from DB
				$file = self::LoadById( $id );
				
				if ( $file == new ProjectLibrary_Entity_Util_File() ){
					throw new Exception( "The File saved could not be retrieved." );      
				}
				// 4. Return recently saved object
				return $file;
			}
			catch( Exception $e ){
				throw new ProjectLibrary_SDO_Core_Application_Exception( 'Could not save the file. See the details for more information.', 101, $e );
			}
		}
	}
	
	public static function SaveFileUploadDifferentTypes( ProjectLibrary_Entity_Util_FileUpload  $file, 
	                                        $description = '', $destination = 'files/', $accessType='public', $validTypes=array(), $arrayVersions = array(),$destinationVersion = 'multimedia/' ){

	    $validators = array();                   	
		if(empty($validTypes)){
			$validators[] = new ProjectLibrary_SDO_Core_Validator_File_Word( $file );
		}else{
			foreach($validTypes as $type){
				switch($type){
					case 'word':
						$validators[] = new ProjectLibrary_SDO_Core_Validator_File_Word( $file );
						break;
					case 'pdf':
						$validators[] = new ProjectLibrary_SDO_Core_Validator_File_Pdf( $file );
						break;
					case 'excel':
						$validators[] = new ProjectLibrary_SDO_Core_Validator_File_Excel( $file );
						break;
					case 'mp3':
						$validators[] = new ProjectLibrary_SDO_Core_Validator_File_MP3( $file );
						break;
					case 'powerpoint':
						$validators[] = new ProjectLibrary_SDO_Core_Validator_File_PowerPoint( $file );
						break;
					case 'video':
						$validators[] = new ProjectLibrary_SDO_Core_Validator_File_Video( $file );
						break;
					case 'image':
						$validators[] = new ProjectLibrary_SDO_Core_Validator_File_Image( $file );
					default:
						break;
				}
			}
		}

	    if(empty($validators)){
			$validators[] = new ProjectLibrary_SDO_Core_Validator_File_Word( $file );
		}
		
		return self::SaveFileUpload( $file, null, $description, $destination, $accessType, $validators, $arrayVersions, $destinationVersion );
	}
	
	public static function SaveWordUpload( ProjectLibrary_Entity_Util_FileUpload $file, 
	                                        $description = '', $destination = 'files/' ){
	                                        	
		$validator = new ProjectLibrary_SDO_Core_Validator_File_Word( $file );
		return self::SaveFileUpload( $file, $validator, $description, $destination );
	}
	
	/**
	 * Saves an image from an uploaded file.
	 *
	 * @param ProjectLibrary_Entity_Util_FileUpload $file
	 * @return ProjectLibrary_Entity_Util_File
	 * @throws ProjectLibrary_SDO_Core_Validator_Exception, ProjectLibrary_SDO_Core_Application_Exception
	 */
	public static function SaveImageUpload( ProjectLibrary_Entity_Util_FileUpload $file, 
	                                        $description = '', $destination = 'files/' ){
		$validator = new ProjectLibrary_SDO_Core_Validator_File_Image( $file );
		return self::SaveFileUpload( $file, $validator, $description, $destination );
	}

	/**
	 * @return ProjectLibrary_SDO_Core_DAO_File
	 */
	protected static function GetDAO(){
		return new ProjectLibrary_SDO_Core_DAO_File();
	}
	

	public static function getFriendlyNameUrl($fiId,$gaId){
		$fi = ProjectLibrary_SDO_Core_Application_FileManagement::LoadById($fiId);
		$url = ProjectLibrary_SDO_Core_Application_Fotogaleria::getFriendlyNameUrl($gaId);
		//debug($url);
		$url .= "$fiId/{$fi->getFriendlyName()}/";
		return $url;
	}	
	
	public static function getFriendlylistUrl($p = 1){
		$url = ABS_HTTP_URL . "$p/Galeria-de-Imagenes/";
		return $url;
	}
	
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
			
			return $arrProfiles;
		}
		catch( SQLException $e ){
			throw new ProjectLibrary_SDO_Core_Application_Exception( 
				'Error al procesar la b&uacute;squeda',
				0, $e );
		}
	}
	
	protected function GetEntitySearchResultObject( $search ){
		return new ProjectLibrary_Entity_Search_Result_fileManagment( $search );
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
	
	protected function GetEntityUtilObjectArray(){
		return new ProjectLibrary_Entity_Util_fileManagmentArray();
	}
	
	public static function getFileByVersion($id,$version = 'small'){
		$dao = self::GetDAO();
		$entity = $dao->getFileByVersion($id,$version);
		$file = new ProjectLibrary_Entity_Util_File();   // Create new clean object to prevent old values being conserved
		return ProjectLibrary_SDO_Core_Util_EntityManager::ParseArrayToObject( $entity, $file );  // Convert array to to Object
	}
}
?>