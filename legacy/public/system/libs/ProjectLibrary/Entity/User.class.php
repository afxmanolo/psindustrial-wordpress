<?php
class ProjectLibrary_Entity_User extends ProjectLibrary_Entity {
	
	/**
	 * @var int
	 */
	public $user_id;
	/**
	 * @var string
	 */
	public $first_name;
	/**
	 * @var string
	 */
	public $last_name;
	/**
	 * @var string
	 */
	public $email;
	/**
	 * @var string
	 */
	public $password;
	/**
	 * @var string
	 */
	public $role;
    public $archivo;
	
	public function __construct(){
		parent::__construct();
	}

	/**
	 * @return string
	 */
	public function getEmail(){
		return $this->email;
	}

	/**
	 * @param string $email
	 */
	public function setEmail( $email ){
		$this->email = $email;
	}

	/**
	 * @return string
	 */
	public function getFirstName(){
		return $this->first_name;
	}

	/**
	 * @param string $first_name
	 */
	public function setFirstName( $firstName ){
		$this->first_name = $firstName;
	}

	/**
	 * @return string
	 */
	public function getLastName(){
		return $this->last_name;
	}

	/**
	 * @param string $last_name
	 */
	public function setLastName( $lastName ){
		$this->last_name = $lastName;
	}

	/**
	 * @return string
	 */
	public function getPassword(){
		return $this->password;
	}

	/**
	 * @param string $password
	 */
	public function setPassword( $password ){
		$this->password = $password;
	}

	/**
	 * @return int
	 */
	public function getUserId(){
		return $this->user_id;
	}

	/**
	 * @param int $profile_id
	 */
	public function setUserId( $profileId ){
		$this->user_id = $profileId;
	}

	/**
	 * @return string
	 */
	public function getRole(){
		return $this->role;
	}

	/**
	 * @param string $role
	 */
	public function setRole( $role ){
		$this->role = $role;
	}
	
	public function getArchivo($position = null){
	    $image = !empty($this->archivo) ? explode(',', $this->archivo) : [];
	    return !is_numeric($position) ? $image : (!empty($image[$position]) ? $image[$position] : 0 );}
	    
	    public function getHTMLArchivo($width = null,$height= null, $nimage = 0, $target_blank = false, $urlEffect = false, $imgParam = 'border="0"'){
	        $imgParam2 = empty($width) ? '' : ' width="'.$width.'"';
	        $imgParam2 .= empty($height) ? '' : ' height="'.$height.'"';
	        $src = $this->getArchivo( $nimage );
	        
	        $effect = $urlEffect;
	        if(empty($src)){
	            $src = 'ima/fotomiembro.jpg';
	            $imgParam .= $imgParam2;
	            $urlImage .= $src;
	            $title = '';
	        }else{
	            $file = ProjectLibrary_SDO_Core_Application_FileManagement::LoadById($src);
	            $title = $file->getDescription();
	            $urlImage = BO_DIRECTORY . 'file.php?id=' . $src . '&type=image';
	            $src = BO_DIRECTORY . 'file.php?id=' . $src . '&type=image&img_size=predefined&width='.$width.'&height=' . $height;
	            
	        }
	        $img = $target_blank ? '<a '. ($effect ? 'rel="jquery-lightbox"' : '' ) .' href="'.$urlImage.'" id="ImagePreviewId" target="_blank">' : '';
	        $imgParam .= !$target_blank ? ' title="'.$title.'"' : '';
	        $img .= "<img src='".$src."' ".$imgParam.">";
	        $img .= ( $target_blank ? '</a>' : '');
	        return $img;
	    }
	    
	    public function setArchivo( $Archivo){
	        return $this->archivo = $Archivo;
	    }
	    
	   
	/**
	 * Utility methods
	 */
	
	public function __toString(){
		return $this->getFirstName() . ' ' . $this->getLastName();
	}
}
?>