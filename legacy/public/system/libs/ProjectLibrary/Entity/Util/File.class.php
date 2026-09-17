<?php

class ProjectLibrary_Entity_Util_File extends ProjectLibrary_Entity {
	
	/**
	 * File ID
	 *
	 * @var int
	 */
	public $id;
	
	/**
	 * Relative path to the physical file
	 *
	 * @var string
	 */
	public $path;
	
	/**
	 * Filename of the physical file (named after it's MD5 File Hash code)
	 *
	 * @var string
	 */
	public $filename;
	
	/**
	 * User friendly name of the file
	 *
	 * @var unknown_type
	 */
	public $name;
	
	/**
	 * File MIME Type
	 *
	 * @var string
	 */
	public $type;
	
	/**
	 * File size in Bytes
	 *
	 * @var float
	 */
	public $size;
	
	/**
	 * Creation date and  time of the file in SQL format (YYY-MM-DD HH:MM:SS)
	 *
	 * @var string
	 */
	public $date;
	
	/**
	 * File description
	 *
	 * @var string
	 */
	public $description;
	
	/**
	 * access type
	 *
	 * @var string
	 */
	public $access_type;
	
	public $version;
	
	public $original_id;
	
	public function __construct(){
		parent::__construct();
	}

	/**
	 * @return string
	 */
	public function getDate(){
		return $this->date;
	}

	/**
	 * @param string $date
	 */
	public function setDate( $date ){
		$this->date = $date;
	}

	/**
	 * @return string
	 */
	public function getDescription(){
		return $this->description;
	}

	/**
	 * @param string $description
	 */
	public function setDescription( $description ){
		$this->description = $description;
	}

	/**
	 * @return int
	 */
	public function getId(){
		return $this->id;
	}

	/**
	 * @param int $file_id
	 */
	public function setId( $id ){
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function getFilename(){
		return $this->filename;
	}

	/**
	 * @param string $filename
	 */
	public function setFilename( $filename ){
		$this->filename = $filename;
	}

	/**
	 * @return unknown_type
	 */
	public function getName(){
		return $this->name;
	}

	/**
	 * @param unknown_type $name
	 */
	public function setName( $name ){
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getPath(){
		return $this->path;
	}

	/**
	 * @param string $path
	 */
	public function setPath( $path ){
		$this->path = $path;
	}

	/**
	 * @return float
	 */
	public function getSize(){
		return $this->size;
	}

	/**
	 * @param float $size
	 */
	public function setSize( $size ){
		$this->size = $size;
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

	public function setAccessType( $access_type ){
		$this->access_type = $access_type; 
	}
	
	public function getAccessType(){
		return $this->access_type; 
	}
	
	public function setVersion($version){
		return $this->version = $version;
	}
	
	public function getVersion(){
		return $this->version;
	}
	
	public function setOriginalId($original_id){
		return $this->original_id = $original_id;
	}
	
	public function getOriginalId(){
		return $this->original_id;
	}
	
public function getFriendlyName(){return Util_String::validStringForUrl( Util_String::getWords($this->description,50,'') ) ;}
public function getFriendlyNameUrl($gaId){return ProjectLibrary_SDO_Core_Application_FileManagement::getFriendlyNameUrl($this->id,$gaId);}
public function getFriendlyNameUrlNoticia($gaId){return ProjectLibrary_SDO_Core_Application_FileManagement::getFriendlyNameUrl($this->id,$gaId);}
}
?>