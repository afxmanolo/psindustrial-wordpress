<?php
class ProjectLibrary_Entity_Util_FileUpload extends ProjectLibrary_Entity {
	
	/**
	 * The structure name under which this file is stored in the $_FILES array
	 *
	 * @var string
	 */
	public $structureName;
	/**
	 * The original name of the file uploaded
	 *
	 * @var string
	 */
	public $name;
	/**
	 * The MIME type of the file
	 *
	 * @var string
	 */
	public $type;
	/**
	 * The size of the file (in bytes)
	 *
	 * @var long
	 */
	public $size;
	/**
	 * The temporary location where the file was uploaded
	 *
	 * @var string
	 */
	public $tmp_name;
	/**
	 * The error code 
	 *
	 * @var int
	 * @see UPLOAD_ERR_* (http://mx.php.net/manual/en/features.file-upload.errors.php)
	 */
	public $error;
	
	public function __construct( $fileStructure ){
		parent::__construct();
		
		if ( !isset( $_FILES[ $fileStructure ] ) ){
			throw new Exception( "File structure '$fileStructure' not found inside $ "  . "_FILES array." );
		}
		$file = $_FILES[ $fileStructure ];
		$this->setStructureName( $fileStructure );
		$this->setName(    $file[ 'name' ] );
		$this->setType(    $file[ 'type' ] );
		$this->setSize(    $file[ 'size' ] );
		
		$this->setTmpName( $file[ 'tmp_name' ] );
		$this->setError(   $file[ 'error' ] );
	}

	/**
	 * @return string
	 */
	public function getStructureName(){
		return $this->structureName;
	}

	/**
	 * @param string $structureName
	 */
	public function setStructureName( $structureName ){
		$this->structureName = $structureName;
	}


	/**
	 * @return int
	 */
	public function getError(){
		return $this->error;
	}

	/**
	 * @param int $error
	 */
	public function setError( $error ){
		$this->error = $error;
	}

	/**
	 * @return string
	 */
	public function getName(){
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName( $name ){
		$this->name = $name;
	}

	/**
	 * @return long
	 */
	public function getSize(){
		return $this->size;
	}

	/**
	 * @param long $size
	 */
	public function setSize( $size ){
		$this->size = $size;
	}

	/**
	 * @return string
	 */
	public function getTmpName(){
		return $this->tmp_name;
	}

	/**
	 * @param string $tmp_name
	 */
	public function setTmpName( $tmp_name ){
		$this->tmp_name = $tmp_name;
	}

	/**
	 * @return string
	 */
	public function getType(){
		return $this->type;
	}

	/**
	 * @param string $type
	 */
	public function setType( $type ){
		$this->type = $type;
	}

	
}
?>