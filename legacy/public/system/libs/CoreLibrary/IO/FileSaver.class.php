<?php
class IO_FileSaver extends Objeto {
	
	private $path = "files/";
	private $pathVersion = "multimedia/";
	private $name;
	private $db;
	
	public function __construct( DBPEAR $db, $structureName, $path = 'files/', $pathVersion = 'multimedia/'){
		parent::__construct();
		$this->path = $path;
		$this->pathVersion = $pathVersion;
		$this->name = $structureName;
		$this->db = $db;
	}
	
	public function save( $description = '',$accessType = 'public', $arrayVersions = array() ){
		$dao = new DB_DAO_FileDAO( $this->db );
		
		$file = &$_FILES[ $this->name ];
		
		$obj = array();
		
		// save the hard copy
		if ( is_file( $file['tmp_name'] ) ){
		$obj['path'] 		= $this->path;
		$obj['filename'] 	= sha1_file( $file[ 'tmp_name' ] );
		$obj['name']		= $file['name']; 
  		$obj['type'] 		= $this->getType( $file );
  		$obj['size']		= $file['size'];
  		$obj['date']		= date( 'Y-m-d H:i:s' );
  		$obj['description'] = $description;
		$obj['access_type'] = $accessType;
		$obj['version']		= 'original';
		$obj['original_id'] = NULL;
		
		$obj_small = $obj_medium = $obj;
		
		//$obj['access_type'] = 'public';
		
		if( $this->saveHardCopy( $file['tmp_name'], $obj['path'] . $obj['filename'] ) ){
			// Hard copy was successfull, proceed to DB copying
  			$id = $dao->saveNew( $obj );
  			
  			unset($obj['id']);
  			
  			foreach($arrayVersions as $version){
  				$obj_version 	= $obj;
  				if(!$this->saveVersionCopy($obj_version,$version,$id))throw new Exception( "Could not save file into destination.", 0 );
  				$id_version 	= $dao->saveNew( $obj_version );
  			}
  			
  			return $id;
  		}
  		else {
  			throw new Exception( "Could not save file into destination.", 0 );
  		}
		}
		else {
			return 0;		// Nothing to save
		}
		
	}
	/*public function save( $description = '',$accessType = 'public' ){
		$file = &$_FILES[ $this->name ];
		$obj = array();
		// save the hard copy
		if ( is_file( $file['tmp_name'] ) ){
		$obj['path'] 		= $this->path;
		$obj['filename'] 	= sha1_file( $file[ 'tmp_name' ] );
		$obj['name']		= $file['name']; 
  		$obj['type'] 		= $this->getType( $file );
  		$obj['size']		= $file['size'];
  		$obj['date']		= date( 'Y-m-d H:i:s' );
  		$obj['description'] = $description;
		$obj['access_type'] = $accessType;
		
  		$dao = new DB_DAO_FileDAO( $this->db );
  		$id = $dao->fileExists( $obj['filename' ] );
  		
  		//accept duplicate images : FALSE
  		//the image will to use only one file for many registers in the table
  		if ( FALSE && $id != 0 ){
  			$fileTmp = $dao->loadById($id);
  			foreach( $fileTmp as $key => $value){
  				$obj[$key] = $value;
  			}
  			$obj['date'] = date( 'Y-m-d H:i:s' );
  			$obj['access_type'] = $accessType;
  			$obj['description'] = $description;
			$id = $obj['id'];
  			$dao->update( $obj );
  			return $id;
  		}
  		elseif( $this->saveHardCopy( $file['tmp_name'], $obj['path'] . $obj['filename'] ) ){
				// Hard copy was successfull, proceed to DB copying
  			$id = $dao->saveNew( $obj );
  			return $id;
  		}
  		else {
  			throw new Exception( "Could not save file into destination.", 0 );
  		}
		}
		else {
			return 0;		// Nothing to save
		}
		
	}*/
	
	public function saveHardCopy( $src, $dest, $uploaded = true, $inFO = false ){
		$dest = ($inFO ? "" : "../") . $dest;
		
		if($uploaded){
			$x = move_uploaded_file( $src, $dest );	
		}else{
			$x = copy( $src, $dest );
			//unlink($src);
		}
		
		if ( !$x ){
			return false;
		}
		else {
			return true;
		}
	}
	/*public function saveHardCopy( $src, $dest ){
		$dest = "../" . $dest;
		if ( !move_uploaded_file( $src, $dest ) ){
			return false;
		}
		else {
			return true;
		}
	}*/
	
	public function saveVersionCopy(& $obj,$version,$original_id,$import = false){
		$original_filename 	= $obj['path'].$obj['filename']; 
		$obj['filename'] 	= sha1("{$obj['filename']}{$version['version']}");
		$obj['version'] 	= $version['version'];
		$obj['path']		= $version['path'];
		$obj['access_type'] = $version['access_type'] ?? 'public';
		$obj['original_id'] = $original_id;
		
		//VALIDAR SI ES IMAGEN
		$validaType 	= preg_match('/^image\/.*$/',$obj['type']);
		$validaExten	= preg_match('/\.(' . join( '|', array( 'jpe?g', 'gif', 'png', 'bmp', 'png', 'tiff' ) ) . ')$/i',$obj['name']);
		
		
		if($validaType && $validaExten){
			$filename_version = $this->resizeImg($obj,$original_filename,$version['width'],$version['height'],$import);
		}else{
			$filename_version = $this->copyTo($obj,$original_filename);
		}//end if
		
		if(!$filename_version)return false;
		
		$obj['filename'] = $filename_version;
		return true;
	}
	
	protected function getType( $file){
		return $file['type'];
	}
	
	public function setStoragePath( $path = 'files/' ){
		$this->path = $path;
	}
	
	public function setStructureName( $name ){
		$this->name = $name;
		return $this;
	}
	
	private function copyTo($file,$original_filemane){
		$extension = 	pathinfo($file['name']);$extension = $extension['extension'];
		$filename_dest		= "../../{$this->pathVersion}$file[path]$file[filename].$extension";
		$original_filemane 	= '../'.$original_filemane;
		$x = copy($original_filemane,$filename_dest);
		return "$file[filename].$extension";
	}
	
	private function resizeImg( $file, $original_filename, $new_w, $new_h, $import = false ){
		set_time_limit( 0 );
		$pathDB = $file['path'];
		$path = '../' . ($import?$this->pathVersion:'../'.$this->pathVersion) . $file['path'];
		$original_filename = '../' . ($import?'bo/':'') . $original_filename;
		
		$src_img = $this->open_image( $original_filename );
		$old_w=imageSX($src_img);
		$old_h=imageSY($src_img);
		
		$thumb_w = $old_w;
		$thumb_h = $old_h;
		
		$new_w = (empty($new_w) || !is_numeric($new_w)) ? $thumb_w : $new_w;
		$new_h = (empty($new_h) || !is_numeric($new_h)) ? $thumb_h : $new_h;
		
		if( $thumb_w > $new_w) {
			$thumb_h = round(($thumb_h*$new_w)/$thumb_w);
			$thumb_w = $new_w;
		}
		
		if( $thumb_h > $new_h ){
			$thumb_w = round(($thumb_w*$new_h)/$thumb_h);
			$thumb_h = $new_h;
		}
		
		
		
//		if ( $old_w <= $new_w && $old_h <= $new_h ){
//			// If the original image is smaller than the requested size, don't enlarge it, just present the original
//			$this->_view( $file );
//			return;
//		}
		//
		
		$dst_img=ImageCreateTrueColor($thumb_w,$thumb_h);
		imagecopyresampled($dst_img,$src_img,0,0,0,0,$thumb_w, $thumb_h,$old_w,$old_h);

		$info_imagen = getimagesize($original_filename);
        $tipo_imagen = $info_imagen[2];
		
        $new_filename 	= $path . $file['filename'];
        $new_fileDB		= $file['filename'];
        
		switch ($tipo_imagen) {
            case 1: //si es gif …
            		$new_filename .= '.gif';
            		$new_fileDB .= '.gif';
                if (!imagegif($dst_img,$new_filename)) return false;
            break;
            
            case 2: //si es jpeg …
            		$new_filename .= '.jpg';
            		$new_fileDB .= '.jpg';
                if (!imagejpeg($dst_img,$new_filename)) return false;
            break;
 
            case 3: //si es png …
            		$new_filename .= '.png';
            		$new_fileDB .= '.png';
                if (!imagepng($dst_img,$new_filename)) return false;
            break;
        }
		
		//header('Content-type: image/jpeg');
		//imagejpeg($dst_img);
		//imagedestroy( $dst_img );
		
		return $new_fileDB;
	}
	
	private function open_image($file) {
	//		ini_set( "memory_limit", "32M" );
		
		# JPG:
		$im = @imagecreatefromjpeg($file);
		if ($im !== false) { return $im; }
		# GIF:
		$im = @imagecreatefromgif($file);
		if ($im !== false) { return $im; }
		# PNG:
		$im = @imagecreatefrompng($file);
		if ($im !== false) { return $im; }

		# GD File:
		$im = @imagecreatefromgd($file);
		if ($im !== false) { return $im; }

		# GD2 File:
		$im = @imagecreatefromgd2($file);
		if ($im !== false) { return $im; }

		# WBMP:
		$im = @imagecreatefromwbmp($file);
		if ($im !== false) { return $im; }

		# XBM:
		$im = @imagecreatefromxbm($file);
		if ($im !== false) { return $im; }
		
		if(!SERVER_DEVELOPMENT && 1 == 2){
			# XPM:
			$im = @imagecreatefromxpm($file);
			//if ($im !== false) { return $im; }
		}

		# Try and load from string:
		$im = @imagecreatefromstring(file_get_contents($file));
		if ($im !== false) { return $im; }

		return false;
	}

}
?>