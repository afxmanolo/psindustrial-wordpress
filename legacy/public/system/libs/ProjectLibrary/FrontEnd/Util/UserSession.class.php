<?php
class ProjectLibrary_FrontEnd_Util_UserSession {
	
	const NAMESPACES = 'ProjectLibrary_user_session';
	
	public static function HasIdentity(){
		$tmp = ProjectLibrary_FrontEnd_Util_Session::Get( self::NAMESPACES );
		if ( !empty( $tmp ) && $tmp instanceof ProjectLibrary_Entity_User ){
		     	return true; 
		}
		else {
			return false;
		}
	}
	
	/**
	 * @return ProjectLibrary_Entity_User_Profile
	 */
	public static function GetIdentity(){
		if ( self::HasIdentity() ){
			return ProjectLibrary_FrontEnd_Util_Session::Get( self::NAMESPACES );
		}
		else {
			return null;
		}
	}
	
	public static function DestroyIdentity(){
		if ( self::HasIdentity() ){
			ProjectLibrary_FrontEnd_Util_Session::Set( self::NAMESPACES, null );
		}
	}
	
	/**
	 * @param ProjectLibrary_Entity_User_Profile $profile
	 */
	public static function SetIdentity( ProjectLibrary_Entity_User $profile ){
		ProjectLibrary_FrontEnd_Util_Session::Set( self::NAMESPACES, $profile );
	}
	
}
?>