<?php
class IO_FileViewer extends Objeto {
	
	protected $dao;
	
	public function __construct( DBPEAR $db ){
		parent::__construct();
		$this->setDAO( new DB_DAO_FileDAO( $db ) );
	}
	
	public function setDAO( DB_DAO_FileDAO $dao ){
		$this->dao = $dao;
	}
	
	public function view( $id ){
		$file = $this->dao->loadById( $id );
		if ( !empty( $file ) && file_exists( $file['path'] . $file['filename' ] ) ){
			$this->_view( $file );
		}
		else {
			echo "File does not exist in DB and/or in file system.";
		}
	}
	
	protected function _view( $file, $contentDisposition = 'inline', $checkSum = false ){
		if ( $checkSum && ( $file['filename'] != sha1_file( $file['path'] . $file['filename'] ) ) ){
			echo "File is corrupt and could be dangerous.";
			exit;
		}
		
  	header( 'Pragma: public' );
  	header( 'Expires: 0' );
  	header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
  	header( 'Cache-Control: private', false );
  	header( 'Content-Type: ' . $file['type'] );
    header( 'Content-Disposition: ' . $contentDisposition . '; filename="' . $file['name'] .'"' );
		header( 'Last-Modified: ' . str_replace( '+0000', 'GMT', gmdate( 'r', strtotime( $file['date'] ) ) ) );
  	header( 'Content-Length: ' . filesize( $file['path'] . $file['filename'] ) );

  	@set_time_limit( 0 );
  	readfile( $file['path'] . $file['filename'] );
  	
  	exit;
	}
	
	public function download( $id ){
		$file = $this->dao->loadById( $id );
		if ( !empty( $file ) && file_exists( $file['path'] . $file['filename' ] ) ){
			$this->_download( $file );
		}
		else {
			echo "File does not exist in DB and/or in file system.";
		}
		
	}
	
	protected function _download( $file ){
		
		$this->_view( $file, 'attachment' );
	}
	
}
?>