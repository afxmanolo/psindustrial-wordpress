<?php
class ProjectLibrary_FrontEnd_BO_User extends ProjectLibrary_FrontEnd_BO {

	/**
	 * @var ProjectLibrary_DAO_Event
	 */
	protected $search;

	public function __construct(){
		parent::__construct();
		
	}

	public function execute(){
		$this->checkPermission();
		
		$cmd = $this->getCommand();
		switch( $cmd ){
			case 'saveAddress':
				$this->_saveAddress();
				break;
			case 'editAddress':
				$this->_editAddress();
				break;
			case 'delete':
				$this->_delete();
				break;
			case 'add':
			case 'edit':
				$this->_edit();
				break;
			case 'save':
				$this->_save();
				break;
			case 'list':
			default:
				$this->_list();
				break;
				
			case 'listBySucursalId':
				$this->_listBySucursalId();
				break;
			case 'updateSucursalId':
				$this->_updateSucursalId();
				break;
		}
	}

	protected function _updateSucursalId(){
		$userId = $this->getParameter();
		$sucursalId = $this->getParameter("value");
		$in = $this->getParameter("in");
		if ( !empty( $userId ) ){
			$user = ProjectLibrary_SDO_User::LoadUser( $userId );
	  		$userIdTmp = $user->getUserId();

	  		if( !empty($userIdTmp) && $user->getRole() == ProjectLibrary_SDO_Core_Application_User::COLABORADOR_NUTRISSA){
	  			$user->setSucursalId( $sucursalId );
		  		// Save user
				try{
					$user = ProjectLibrary_SDO_User::SaverUserProfile( $user );
					$this->tpl->assign( 'strSuccess', $this->tpl->trans('the_employee_has_been_updated') );
				}
				catch( ProjectLibrary_SDO_Core_Validator_Exception $e){
					//$this->tpl->assign( 'errors', new Validator_ErrorHandler( "There are some errors", $e->getErrors() ) );
					//$oldCmd = $this->getParameter( 'old_cmd', false, null );
					//$this->edit( $user,  $oldCmd );
				}
	  			
	  		}
		}
		$this->_listBySucursalId($sucursalId, $in);
	}
	
	protected function _listBySucursalId( $sucursalId = null,$in=null ){

		$this->search = new ProjectLibrary_Entity_Search(
			$this->getParameter( 'q', false, NULL ),
			$this->getParameter( 'o', false, 'first_name' ),
			$this->getParameter( 'p', true, 1 ),
			$this->getParameter( 'k', true, 15 )
		);
		$role = ProjectLibrary_SDO_Core_Application_User::COLABORADOR_NUTRISSA;
		
		$extraConditions = array();
		$extraConditions[] = array( "columName"=>"role","value"=>$role,"isInteger"=>false);
		
		
		$in = empty($in)|| !is_numeric($in) ? $this->getParameter('in') : $in;
		$sucursalId = empty($sucursalId)|| !is_numeric($sucursalId) ? $this->getParameter('id',true,0) : $sucursalId; 
		if($in){
			$extraConditions[] = array( "columName"=>"sucursal_id","value"=>$sucursalId,"isInteger"=>true);
		}else{
			$extraConditions[] = array( "SQL"=>"sucursal_id <>" . $sucursalId);
		}
		
		$result = ProjectLibrary_SDO_User::Search( $this->search,$extraConditions );
		
		$exportToExcel = $this->getParameter( 'toExcel' );
		if ( $exportToExcel ){
		
			$users = $result->getResults();
			$data = array();
			foreach($users as $user){
				$data[] = get_object_vars( $user );
			}
			$this->exportToExcel( $data );

		}
		else {
			$this->tpl->assign( 'options', $result );
			$this->tpl->assign("arrHiddenFields", array('cmd'=>'listBySucursalId','sucursal_id'=>$sucursalId,"in"=>$in) );
			$this->tpl->assign("in", $in );
			$this->tpl->assign("sucursal_id", $sucursalId );
			
			$this->tpl->display(  "user/listAgregacionColaborador.php" );
		}
	}
	
	
	protected function _list(){
	    
		$this->search = new ProjectLibrary_Entity_Search(
			$this->getParameter( 'q', false, NULL ),
			$this->getParameter( 'o', false, 'first_name' ),
			$this->getParameter( 'p', true, 1 ),
			$this->getParameter( 'k', true, -1 )
		);
		
		$result = ProjectLibrary_SDO_User::SearchUsers( $this->search );
		
		$exportToExcel = $this->getParameter( 'toExcel' );
		if ( $exportToExcel ){
		
			$users = $result->getResults();
			$data = array();
			foreach($users as $user){
				$data[] = get_object_vars( $user );
			}
			$this->exportToExcel( $data );

		}
		else {
			$this->tpl->assign( 'options', $result );
			
			$this->tpl->display(  "user/list.php" );
		}
	}
		
	protected function _edit(){
		$id = $this->getParameter();

		$entity = ProjectLibrary_SDO_User::LoadUser( $id );
		
		$userProfile = ProjectLibrary_FrontEnd_Util_UserSession::GetIdentity();
		if( $entity->getUserId() == 1 && $userProfile->getUserId()!=1 ){
			$entity->setPassword('');
		}
		$this->tpl->assign( 'errors', new Validator_ErrorHandler() );
		
		$this->edit( $entity, $this->getCommand() );

	}

	/*public function edit( $entity, $cmd ){

		$this->tpl->assign( 'user',     $entity );

		$this->tpl->assign( 'strSubtitles' , ( $cmd =='add' ? $this->tpl->trans('add') : $this->tpl->trans('edit') ) . ' ' . $this->tpl->trans('user') );
		$this->tpl->assign( 'strCmd'       , $this->getCommand() );
		
		
		$ArrRoles = ProjectLibrary_SDO_User::GetRoles();
		
		$this->tpl->assign( 'ArrRoles', $ArrRoles );

		$this->tpl->display( 'user/edit.php' );
	}*/
	public function edit( $entity, $cmd ){
		$ArrRoles = ProjectLibrary_SDO_User::GetRoles();
		$this->tpl->assign( 'ArrRoles', $ArrRoles );
		$ArrRoles = array_combine( $ArrRoles, $ArrRoles );
		/**************************************************/
		$viewConfig         = array();
		$form               = array();
		$form['name']       = 'frmUser';
		$form['elements']   = array();
		$form['elements'][] = array( 'title' => 'usuarioId:', 'type'=>'hidden', 'options'=>array(), 'name'=>'entity[id]', 'id'=>'id', 'value'=> $entity->getUserId(),'class'=>'col-xs-12 col-md-6','params'=>'required');
		$form['elements'][] = array( 'title' => 'Usuario:', 'type'=>'username', 'options'=>array(), 'name'=>'entity[email]', 'id'=>'email', 'value'=> $entity->getEmail(),'class'=>'col-xs-12 col-md-6','params'=>'required');
		$form['elements'][] = array( 'title' => 'Password:', 'type'=>'password', 'options'=>array(), 'name'=>'entity[password]', 'id'=>'password', 'value'=> $entity->getPassword(),'class'=>'col-xs-12 col-md-6','params'=>'required');
		$form['elements'][] = array( 'title' => 'Role:', 'type'=>'select', 'options'=>$ArrRoles, 'name'=>'entity[role]', 'id'=>'role', 'value'=> $entity->getRole(),'class'=>'col-xs-12 col-md-6','params'=>'required');
		//$form['elements'][] = array( 'title' => 'Sucursal:', 'type'=>'select', 'options'=>ProjectLibrary_SDO_Core_Application_Sucursales::GetAllInArray(), 'name'=>'entity[sucursalId]', 'id'=>'sucursalId', 'value'=> $entity->getSucursalId(),'class'=>'col-xs-12 col-md-6');		
		$form['elements'][] = array( 'title' => 'Informaci&oacute;n Personal', 'type'=>'fieldset', 'value'=>"");
		$form['elements'][] = array( 'title' => 'Nombre:', 'type'=>'text', 'options'=>array(), 'name'=>'entity[first_name]', 'id'=>'first_name', 'value'=> $entity->getFirstName(),'class'=>'col-xs-12 col-md-6','params'=>'required');
		$form['elements'][] = array( 'title' => 'Apellidos:', 'type'=>'text', 'options'=>array(), 'name'=>'entity[last_name]', 'id'=>'last_name', 'value'=> $entity->getLastName(),'class'=>'col-xs-12 col-md-6','params'=>'required');
		
		$form['elements'][] = array( 'title' => 'Archivo', 'type'=>'hidden', 'options'=>array(), 'name'=>'entity[archivo]', 'id'=>'archivo', 'value'=> $entity->getArchivo() );
		
		$viewConfig['form'] = $form;
		$this->tpl->assign( 'form', $form );
		$this->tpl->assign( 'viewConfig', $viewConfig );
		/**************************************************/

		$this->tpl->assign( 'user',     $entity );
		$this->tpl->assign( 'strSubtitles' , ( $cmd =='add' ? 'Agregar' :'Editar' ) . ' Usuarios'  );
		$this->tpl->assign( 'strCmd'       , $this->getCommand() );
		$this->tpl->display( 'user/edit.php' );
	}

	protected function exportToExcel( $data ){

		$xls = new ExcelWriter( 'Events_' . date('Y-m-d'), 'Products' );
		$xls->writeHeadersAndDataFromArray( $data );


	}

	/*protected function _save(){
		
		if ( !empty( $_REQUEST ) && isset( $_REQUEST['entity'] ) ){
	  		
			foreach ($_REQUEST['entity'] as $key => $value)
				$entity[$key] = strip_tags($value);
  			
			$user = new ProjectLibrary_Entity_User();
  			$user->setUserId( $entity['id'] );
  			$user->setEmail( $entity['email'] );
  			$user->setFirstName( $entity['first_name'] );
  			$user->setLastName( $entity['last_name'] );
  			$user->setPassword( $entity['password'] );
  			$user->setRole( $entity['role'] );
			// Save user
			try{
				ProjectLibrary_SDO_User::SaverUserProfile( $user );
				$this->tpl->assign( 'strSuccess', $this->tpl->trans('the').' '.$this->tpl->trans('user').' "' . $user->getEmail() . '" '.$this->tpl->trans('has_been').' '.strtolower($this->tpl->trans('saved')));
				$this->_list();
			}
			catch( ProjectLibrary_SDO_Core_Validator_Exception $e){
				$this->tpl->assign( 'errors', new Validator_ErrorHandler( $this->tpl->trans("there_are_errors"), $e->getErrors() ) );
				$oldCmd = $this->getParameter( 'old_cmd', false, null );
				$this->edit( $user,  $oldCmd );
			}
		}
		else{
			throw new Exception($this->tpl->trans('not_enough_information').'. '.$this->tpl->trans('try_again'));
		}
	}*/

	protected function _save(){
		
		if ( !empty( $_REQUEST ) && isset( $_REQUEST['entity'] ) ){
	  		
			foreach ($_REQUEST['entity'] as $key => $value)
				$entity[$key] = strip_tags($value);
  			
			$user = new ProjectLibrary_Entity_User();

			if(!empty($entity['id'])){
  				$user->setUserId( $entity['id'] );
			}

  			$user->setEmail( $entity['email'] );
  			$user->setFirstName( $entity['first_name'] );
  			$user->setLastName( $entity['last_name'] );
  			$user->setPassword( $entity['password'] );
  			$user->setRole( $entity['role'] );
  			
			// Save user
			/////////////////////////////////////////////////////////////////////////////////////////
  			$ProductosTmp = ProjectLibrary_SDO_User::LoadUser( $user->getUserId() );
  			$imageHandler = ProjectLibrary_SDO_ImageHandler::GetImageHanlderObject('archivo');
  			$imageHandler->setArrImages( $ProductosTmp->getArchivo() );  			
  			$imageHandler->setUploadFileDirectory('files/images/');  			
  			$imageHandler->setUploadFileDescription( $this->getParameter( $imageHandler->GetDescriptionField(),false,'Imagen de Productos' .$user->getUserId() ) );  			
  			$imageHandler->setValidTypes( array('image') );  			
  			$imageHandler->setAccessType( ProjectLibrary_SDO_ImageHandler::GetValidAccessType('public') );  			
  			$arrVersions = array();
  			$arrVersions[] = array('version'=>'small',	'path'=>'imagenes/productos/',	'width'=>150, 'height'=>108);
  			$arrVersions[] = array('version'=>'medium',	'path'=>'imagenes/productos/',	'width'=>403, 'height'=>403);
  			$arrVersions[] = array('version'=>'large',	'path'=>'imagenes/productos/',	'width'=>700, 'height'=>600);
  			$imageHandler->setArrVersions($arrVersions);  			
  			$imageHandler->setMaximum( 3 );
  			$arrImages = $imageHandler->proccessImages();
  			$user->setArchivo( $arrImages );
  			//$user->setArrayImages( $arrImages );
  			/////////////////////////////////////////////////////////////////////////////////////////
			try{
				ProjectLibrary_SDO_User::SaverUserProfile( $user );
				//$this->tpl->assign( 'strSuccess', $this->tpl->trans('the').' '.$this->tpl->trans('user').' "' . $user->getEmail() . '" '.$this->tpl->trans('has_been').' '.strtolower($this->tpl->trans('saved')));
				$this->tpl->assign( 'strSuccess', 'El usuario '.$user->getEmail().' ha sido guardado');
				$this->_list();
			}
			catch( ProjectLibrary_SDO_Core_Validator_Exception $e){
				$this->tpl->assign( 'errors', new Validator_ErrorHandler( $this->tpl->trans("there_are_errors"), $e->getErrors() ) );
				$oldCmd = $this->getParameter( 'old_cmd', false, null );
				$this->edit( $user,  $oldCmd );
			}
		}
		else{
			throw new Exception($this->tpl->trans('not_enough_information').'. '.$this->tpl->trans('try_again'));
		}
	}

	protected function _delete(){
	    /*if($userProfile->getRole() != ProjectLibrary_FrontEnd_BO_Authentification::SUPER_ROLE){
	        header("Location: ".ABS_HTTP_URL."system/backoffice/index.php");
	        die;
	    }*/
		$id = $this->getParameter();
		if ( !empty( $id ) && $id!=1) {
			try{
				$user = ProjectLibrary_SDO_Core_Application_User::Delete($id );
				$this->tpl->assign( 'strSuccess', $this->tpl->trans('the').' '.$this->tpl->trans('user').' "' . $user->getEmail() . '" '.$this->tpl->trans('has_been').' '.strtolower($this->tpl->trans('deleted')));
			}
			catch(ProjectLibrary_SDO_Core_Application_Exception $e) {
				//debug($e);
				$this->tpl->assign( 'strError', $e->getMessage() );
			}
		}
		else {
			if( $id != 1){
				$this->tpl->assign( 'strError', 'El usuario por borrar no fue encontrado.' );
			}
			else{
				$this->tpl->assign( 'strError', 'It\'s not possible to delete the user 1, because is the Principal Administrator Account' );
			}
		}
		
		$this->_list();
	}

	protected function _editAddress(){
		$profileId = $this->getParameter();
		$address = ProjectLibrary_SDO_User::LoadUserAddress( $profileId );
		
		$this->tpl->assign( 'errors', new Validator_ErrorHandler( ) );
		$this->editAddress( $address );
	}
	
	public function editAddress( ProjectLibrary_Entity_User_Address $address ){
		
		$profile = ProjectLibrary_SDO_User::LoadUserProfile( $address->getProfileId() );
		
		$this->tpl->assign( 'profile', $profile ); 
		$this->tpl->assign( 'address', $address );
		$this->tpl->display( 'user/address.php' );
	}
	
	public function _saveAddress(){
		$entity = $this->getParameter( 'entity', false, array() );
		
		$address = new ProjectLibrary_Entity_User_Address();
		$address->setProfileId( $entity['profile_id'] );
		$address->setStreet( $entity['street'] );
		$address->setStreet2( $entity['street2'] );
		$address->setStreet3( $entity['street3'] );
		$address->setStreet4( $entity['street4'] );
		$address->setCity( $entity['city'] );
		$address->setState( $entity['state'] );
		$address->setCountry( $entity['country'] );
		$address->setZipCode( $entity['zip_code'] );
		try {
			ProjectLibrary_SDO_User::SaveUserAddress( $address );
			$this->tpl->assign( 'strSuccess', $this->tpl->trans('the').' '.$this->tpl->trans('address').' '.$this->tpl->trans('of_the').' '.$this->tpl->trans('user').' "' . $category->getName() . '" '.$this->tpl->trans('has_been').' '.strtolower($this->tpl->trans('saved')));
			$this->_list();
		}
		catch( ProjectLibrary_SDO_Core_Validator_Exception $e ) {
			$this->tpl->assign( 'errors', new Validator_ErrorHandler( $this->tpl->trans("there_are_errors"), $e->getErrors() ) );
			$this->editAddress( $address );
		}
	}
}
?>