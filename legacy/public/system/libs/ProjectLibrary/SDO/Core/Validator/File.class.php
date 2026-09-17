<?php
class ProjectLibrary_SDO_Core_Validator_File extends ProjectLibrary_SDO_Core_Validator {
	
	/**
	 * @var ProjectLibrary_Entity_Util_FileUpload
	 */
	protected $entity;

	public function __construct( ProjectLibrary_Entity_Util_FileUpload $file ){ 
		parent::__construct( $file );
	}
	
	/**
	 * @see ProjectLibrary_SDO_Core_Validator::setup()
	 */
	protected function setup(){
		$tester = new Validator_FileTester( $this->entity->getStructureName(), true );
		$this->addTests( $tester );
		$this->validator->addFileTester( $this->entity->getStructureName(), $tester );
	}
	
	protected function getTests( Validator_FileTester $tester ){
		$tester->addTest( new Validator_Test_FileUpload('.*'), "{$this->trans('the')} {$this->trans('file')} {$this->trans('not_valid_msg')}" );
	}
}
?>