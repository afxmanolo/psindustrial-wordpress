<?php
class ProjectLibrary_SDO_Core_Validator_File_Word extends ProjectLibrary_SDO_Core_Validator_File {
	
	protected function addTests( Validator_FileTester $tester ){
		$tester->addTest( new Validator_Test_FileUpload_Word(), "{$this->trans('the')} {$this->trans('file')} {$this->trans('not_valid_msg')} (Word)" );
	}
	
}
?>