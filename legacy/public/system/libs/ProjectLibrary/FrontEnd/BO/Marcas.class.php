<?php
class ProjectLibrary_FrontEnd_BO_Marcas extends ProjectLibrary_FrontEnd_BO {


public function getSDOSearch( $search, $extraConditions ){
	return ProjectLibrary_SDO_Marcas::SearchMarcas( $search, $extraConditions );
}	


/********************************************************************************************/
/********************************************************************************************/
/********************************************************************************************/
	
	/**
	 * @var ProjectLibrary_DAO_Event
	 */
	protected $search;
	protected $idFields;
	
	public function __construct(){
		parent::__construct();
		$this->idFields = 'marcas_id';
	}

	public function execute(){				
		$cmd = $this->getCommand();
		switch( $cmd ){
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
		}
	}

	protected function _list(){

		$this->search = new ProjectLibrary_Entity_Search(
			$this->getParameter( 'q', false, NULL ),
			$this->getParameter( 'o', false, 'marcas_id ASC' ), //fecha DESC
			$this->getParameter( 'p', true, 1 ),
			$this->getParameter( 'k', true, -1 )
		);
		$extraConditions = array();
		//$extraConditions[] = array( "columName"=>"type","value"=>'',"isInteger"=>false);
		$result = $this->getSDOSearch( $this->search, $extraConditions );
		
		
		$exportToExcel = $this->getParameter( 'toExcel' );
		if ( $exportToExcel ){
		
			$regiters = $result->getResults();
			$data = array();
			foreach($regiters as $regiter){
				$data[] = get_object_vars( $regiter );
			}
			$this->exportToExcel( $data );

		}
		else {
			$viewConfig = array();
			$viewConfig['name'] = 'Marcas';
			$viewConfig['title'] = 'Marcas';
			$viewConfig['id'] = $this->idFields;
			
			//---------------------------
			if(strpos($viewConfig['id'],",") > -1){
				$ids = explode(",",$viewConfig['id']);
				$id = $ids[0];
				unset($ids[0]);
				$viewConfig['id'] = '{$'.$id.'}';
				foreach($ids as $id){
					$viewConfig['id'] .= ',{$'.$id.'}';
				}
			}else{
				$viewConfig['id'] = '{$'.$viewConfig['id'].'}';
			}
			//---------------------------
			
			$viewConfig["hiddenColums"]= array('marcas_id','imagen');
			
			$this->tpl->assign( 'viewConfig', $viewConfig );
			
			$this->tpl->assign( 'options', $result );
			
			$this->tpl->display(  "Marcas/list.php" );
		}
	}
	
	protected function _add(){
		try{
			$Marcas = new ProjectLibrary_Entity_Marcas();
			$Marcas = ProjectLibrary_SDO_Marcas::SaverMarcas( $Marcas );
			$this->_edit( $Marcas->getMarcasId() );
		}catch( ProjectLibrary_SDO_Core_Validator_Exception $e){
			$this->tpl->assign( 'strError', 'Intente Nuevamente' );
			$this->_list();
		}
	}
	
	protected function _edit($id = null){
		//$id = $this->getParameter();
		$id = empty($id ) ? $this->getParameter('id',false,0) : $id;
		//---------------------------
		if( strpos($id,",") > -1 ){
			$idF = explode(",",$this->idFields);
			$ArrId = explode(",",$id);
			$id = array();
			for($i=0; $i< count($idF); $i++){
				$id[ $idF[$i] ] = (empty($ArrId[$i])) ? 0 : $ArrId[$i];
			}
		}
		//---------------------------
		
		$entity = ProjectLibrary_SDO_Marcas::LoadMarcas( $id );
		
		
		$this->tpl->assign( 'errors', new Validator_ErrorHandler() );
		
		$this->edit( $entity, $this->getCommand() );

	}

	public function edit( $entity, $cmd ){

		$this->tpl->assign( 'object',     $entity );
		
		$viewConfig = array();
		$form = array();

		
		$form['name'] = 'MarcasForm';
		$form['elements'] = array();
		
		
		/***********************************************************************************************************/
		
		$form['elements'][] = array( 'title' => 'MarcasId', 'type'=>'hidden', 'options'=>array(), 'name'=>'entity[marcas_id]', 'id'=>'marcas_id', 'value'=> $entity->getMarcasId(),'class'=>'col-xs-12 col-md-6','params'=>'required'  );
		$form['elements'][] = array( 'title' => 'Nombre', 'type'=>'text', 'options'=>array(), 'name'=>'entity[nombre]', 'id'=>'nombre', 'value'=> $entity->getNombre(),'class'=>'col-xs-12 col-md-6','params'=>'required'  );
		$form['elements'][] = array( 'title' => 'Imagen', 'type'=>'hidden', 'options'=>array(), 'name'=>'entity[imagen]', 'id'=>'imagen', 'value'=> $entity->getImagen(),'class'=>'col-xs-12 col-md-6'  );
		/***********************************************************************************************************/
		
		//$viewConfig['id'] = 'noticia_evento_id';
		
		$viewConfig['form'] = $form;
		$viewConfig['title'] = ( $cmd =='add' ? 'Agregar' : 'Editar' ) . ' Marcas';
		
		$this->tpl->assign( 'form', $form );	
		$this->tpl->assign( 'viewConfig', $viewConfig );
		
		//$this->tpl->assign( 'ArrayImages', $entity->getArrayImages() );
		$this->tpl->assign( 'Marcas',     $entity );
		$this->tpl->assign( 'cancelParams', $cmd == 'add' ? '?cmd=delete&id=' . $entity->getMarcasId() . '' : '' );
		$this->tpl->assign( 'cancelConfirm', $cmd == 'add' ? 'if(confirm(\'Desea eliminar el registro recientemente creado?\'))'  : '' );
		
			$this->tpl->display( 'Marcas/edit.php' );
	}

	protected function exportToExcel( $data, $fileName = '' ){

		

		$rowO = $data[0];

		

		$contentHtml .= '<table border="1">';

		$contentHtml .= '<tr>

		<td height="72" width="255" bgcolor="#cccccc"><img src="'.ABS_HTTP_URL.'/ima/logo.gif"></td>

		<td colspan="'.(count($rowO) - 1).'"><h2>Order Report - '.date("M d Y").'</h2></td>

		</tr>';

		

		$contentHtml .= '<tr>';

		

		foreach($rowO as $key => $row){

			$key = str_replace("_",' ',$key);

			$key = ucwords($key);

			$contentHtml .= '<th align="center">'.$key.'</th>';

		}

		$contentHtml .= '</tr>';

		

		$i = 0;

		foreach($data as $row){

			

			$bgcolor = (++$i % 2 == 0) ? 'bgcolor="#cccccc"' : '';

			$contentHtml .= '<tr>';

			foreach($row as $rowElement){

				$contentHtml .= '<td '.$bgcolor.' align="right">'.$rowElement.'</td>';

			}

			$contentHtml .= '</tr>';

		}

		

		$contentHtml .= '</table>';

		

		

		$total_bytes = strlen($contentHtml);

		header("Content-type: application/vnd.ms-excel");

		header("Content-disposition: attachment; filename=".$fileName.".xls; size=" . $total_bytes);

		echo $contentHtml;

	}

	protected function _save(){
		
		if ( !empty( $_REQUEST ) && isset( $_REQUEST['entity'] ) ){
	  		
			$entity = $_REQUEST['entity'];
  			
			$Marcas = new ProjectLibrary_Entity_Marcas();
			

			// Save type
			try{
			
			
			/******************************************************/
			/******************************************************/
			
if(!empty($entity['marcas_id'])){
    $Marcas->setMarcasId( $entity['marcas_id']  );
}

$txt = (empty($entity['nombre']) ? '' : $entity['nombre']);$Marcas->setNombre( strip_tags($txt) );

/////////////////////////////////////////////////////////////////////////////////////////
$MarcasTmp = ProjectLibrary_SDO_Categorias::LoadCategorias( $Marcas->getMarcasId() );
$imageHandler = ProjectLibrary_SDO_ImageHandler::GetImageHanlderObject('imagen');
$imageHandler->setArrImages( $MarcasTmp->getImagen() );
$imageHandler->setUploadFileDirectory('files/images/marcas/');
$imageHandler->setUploadFileDescription( $this->getParameter( $imageHandler->GetDescriptionField(),false,'Imagen de Marcas' .$Marcas->getMarcasId() ) );
$imageHandler->setValidTypes( array('image') );
$imageHandler->setAccessType( ProjectLibrary_SDO_ImageHandler::GetValidAccessType('public') );
$arrVersions = array();
$arrVersions[] = array('version'=>'small',	'path'=>'imagenes/marcas/',	'width'=>250, 'height'=>120);
$arrVersions[] = array('version'=>'medium',	'path'=>'imagenes/marcas/',	'width'=>500, 'height'=>240);
$arrVersions[] = array('version'=>'large',	'path'=>'imagenes/marcas/',	'width'=>1000, 'height'=>480);
$imageHandler->setArrVersions($arrVersions);
$imageHandler->setMaximum( 1 );
$arrImages = $imageHandler->proccessImages();
$Marcas->setImagen( $arrImages );
/////////////////////////////////////////////////////////////////////////////////////////
				
				
				ProjectLibrary_SDO_Marcas::SaverMarcas( $Marcas );
				$this->tpl->assign( 'strSuccess', 'Registro Marcas ha sido guardado.' );
				$this->_list();
			}
			catch( ProjectLibrary_SDO_Core_Validator_Exception $e){
				$this->tpl->assign( 'errors', new Validator_ErrorHandler( "Existen Algunos Errores", $e->getErrors() ) );
				$oldCmd = $this->getParameter( 'old_cmd', false, null );
				$this->edit( $Marcas,  $oldCmd );
			}
		}
		else{
			throw new Exception('No hay suficiente informaci&oacute;n intente nuevamente');
		}
	}

	protected function _delete(){
		//$id = $this->getParameter();
		$id = $this->getParameter('id',false,0);
		$id2Str = $id;
		//---------------------------
		if( strpos($id,",") > -1 ){
			$idF = explode(",",$this->idFields);
			$ArrId = explode(",",$id);
			$id = array();
			$id2Str = "(";
			for($i=0; $i< count($idF); $i++){
				$id[ $idF[$i] ] = (empty($ArrId[$i])) ? 0 : $ArrId[$i];
				$id2Str .=($i>0) ? ',' : '';
				$id2Str .= $ArrId[$i] . "";
			}
			$id2Str .= ")";
		}
		//---------------------------
		
			try{
				$Marcas = ProjectLibrary_SDO_Marcas::DeleteMarcas($id );
				//$this->tpl->assign( 'strSuccess', 'Marcas "' . $Marcas->getMarcasId() . '" ha sido eliminado' );
				$this->tpl->assign( 'strSuccess', 'Registro Marcas "' . $id2Str . '" ha sido eliminado' );
				
			}
			catch(ProjectLibrary_SDO_Core_Application_Exception $e) {
				//debug($e);
				$this->tpl->assign( 'strError', $e->getMessage() );
			}
		
		$this->_list();
	}
	
	
	

}
?>