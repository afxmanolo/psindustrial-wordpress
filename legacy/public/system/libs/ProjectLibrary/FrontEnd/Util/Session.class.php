<?php
class ProjectLibrary_FrontEnd_Util_Session extends Objeto {
	
	protected static function &GetStorage(){
		return $_SESSION;
	}
	
	public static function Set( $key, $value ){
		$storage = &self::GetStorage();		
		$storage[ $key ] = $value;
	}
	
	/**
	 * Retrieves the current value stored in the Session or NULL if it doesn't exist
	 *
	 * @param string $key
	 * @return mixed
	 */
	public static function Get( $key ){
		$storage = &self::GetStorage();
		if ( isset( $storage[ $key ] ) ){
			return $storage[ $key ];
		}
		else {
			return null;
		}
	}
	
	public static function Delete( $key ){
		$storage = &self::GetStorage();
		if ( isset( $storage[ $key ] ) ) {
			unset( $storage[ $key ] );
		}
	}
	
}
?>