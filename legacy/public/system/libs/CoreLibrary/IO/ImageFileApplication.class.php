<?php
class IO_ImageFileApplication extends IO_FileApplication{

	public function __construct( DBPEAR $db ){
		parent::__construct( $db );
		$this->setFileViewer( new IO_ImageFileViewer( $db ) );
	}
}


?>