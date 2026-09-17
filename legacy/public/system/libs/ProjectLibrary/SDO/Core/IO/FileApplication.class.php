<?php
class ProjectLibrary_SDO_Core_IO_FileApplication extends ProjectLibrary_SDO_Core_Application{
	
	
	public function __construct(){
		parent::__construct();
		
	}
	
	public static function getIOFileApplication( $type ='all'){
		//we need to verify if we return the object or we redirect to other place
		$dao = self::GetDAO();
		$id = empty($_REQUEST['id']) || !is_numeric($_REQUEST['id']) ? 0 : $_REQUEST['id'];
		
		$file = $dao->loadById( $id );
		
		$result = self::CheckPermission($file);		
		if( ($result || empty($file["access_type"]) ||  $file["access_type"] =='public') 
		&& $file["access_type"] !='private' ){
		    
			switch($type)
			{
				case 'image':
					return new IO_ImageFileApplication( new ProjectLibrary_SDO_Core_DB() );
				break;
				case 'audio':
					return new IO_AudioFileApplication( new ProjectLibrary_SDO_Core_DB() );
				break;
				/*case 'video':
					return new IO_VideoFileApplication( new ProjectLibrary_SDO_Core_DB() );
				break;*/
				default:
					return new IO_FileApplication( new ProjectLibrary_SDO_Core_DB() );
				break;
			}
		}else{
			
			header("HTTP/1.0 404 Not Found");
			die();
		}
	}
	
	public static function GetAccessType( $type ){
		$validAccess = array('private','public','Gallery');
		return !in_array($type,$validAccess) ? $validAccess[0] : $type;
	}
	
	protected static function CheckPermission($file){
		$result = false;
		$user = ProjectLibrary_FrontEnd_Util_UserSession::GetIdentity();
		
		switch( $file["access_type"] ){
			case 'Entrevista':
				$search = new ProjectLibrary_Entity_Search('','fecha DESC',1,1);
				$extraConditions = array();
				$extraConditions[] = array( "SQL"=>$file["id"] . " IN (entrevista.array_images)");
				$result = ProjectLibrary_SDO_Core_Application_Entrevista::Search( $search, $extraConditions );
		
				$entrevistas = $result->getResults();
				
				
				$entrevistas = $entrevistas->getIterator();
				$entrevista = $entrevistas->current();
		
				if( !empty($entrevista) ){
						//and the user is login
						$result = (($entrevista->getMiembroId() == $user->getUserId()) && !empty($user));
				}
			break;
			default:
				//if the user has login correctly then 
				$result = !empty($user);
			break;
		}
		if( !empty($user)){
			$result = ($result==false) ? $user->getRole() == 'Admin' : $result;
		}
		return $result;
	}
	
	
	public static function GetDAO(){
		return new DB_DAO_FileDAO( new ProjectLibrary_SDO_Core_DB() );
	}
}


?>