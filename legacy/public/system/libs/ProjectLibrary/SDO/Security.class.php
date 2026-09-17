<?php
class ProjectLibrary_SDO_Security {
	
	/**
	 * SDO for Authentication for an user
	 * 
	 * @param string $username
	 * @param string $password
	 * @return int - Profile Id
	 */
	public static function Authenticate( $username, $password ){
		// TODO
		return ProjectLibrary_SDO_Core_Application_Security_Authentication::Auth( $username, $password );
	}
	

	/**
	 * SDO for Authentication for an user
	 * 
	 * @param string $username
	 * @param string $password
	 * @return int - Profile Id
	 */
	public static function AuthenticateMember( $username, $password ){
		// TODO
		return ProjectLibrary_SDO_Core_Application_Security_Authentication::AuthGenericUser( $username, $password, 'member' );
	}
	
}
?>