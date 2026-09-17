<?php
class IO_FileApplication extends Application {
	
	protected $db;
	protected $dao;
	
	protected $viewer;
	protected $saver;
	
	public function __construct( DBPEAR $db, $name = '' ){
		parent::__construct();
		$this->db = $db;
		$this->dao = new DB_DAO_FileDAO( $db );
		
		$this->setFileViewer( new IO_FileViewer( $db ) );
		$this->setFileSaver( new IO_FileSaver( $db, $name ) );
	}
	
	public function execute(){
		$cmd = parent::getCommand();
		$id = $this->getParameter();
		switch ( $cmd ){
			case 'download':
				$this->download( $id );
				break;
			case 'view':
			default:
				$this->view( $id );
				break;
		}
		
	}
	
	public function setFileViewer( IO_FileViewer $viewer ){
		$this->viewer = $viewer;
	}
	
	public function setFileSaver( IO_FileSaver $saver ){
		$this->saver = $saver;
	}
	
	public function save( $pathToSaveFile = 'files/', $fileDescription ){
		$this->saver->setStoragePath( $pathToSaveFile );
		$this->saver->save( $fileDescription );
	}
	
	public function view( $fileId ){
		$this->viewer->view( $fileId );
	}
	
	public function download( $fileId ){
		$this->viewer->download( $fileId );
	}

	public function delete(){
		
	}
	
}
?>