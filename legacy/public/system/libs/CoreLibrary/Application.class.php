<?php

class Application extends Objeto {

	protected $redirectStrategies = array();
	protected $permissionStrategy;
	
	public function __construct(){
		parent::__construct();
		if ( session_id() == "" ){		// Note: cannot use empty() function
			session_start();
		}
	}
	
	public function hasPermission( $cmd = '' ){
		$this->permissionStrategy->setCommand( $cmd );
		return $this->permissionStrategy->isGranted();
	}

	/**
	 * Returns the application command from the Request defined by the variable 'cmd'
	 * @example http://.../app.php?cmd=edit
	 *
	 * @return string
	 */
	public function getCommand(){
		if ( isset( $_REQUEST[ 'cmd' ] ) ){
			return $_REQUEST[ 'cmd' ];
		}
		else return null;
	}

	/**
	 * Returns the application command from the Request defined by the variable 'cmd'
	 * @example http://.../app.php?cmd=edit&action=save
	 *
	 * @return string
	 */
	public function getAction(){
		if ( isset( $_REQUEST[ 'action' ] ) ){
			return $_REQUEST[ 'action' ];
		}
		else return null;
	}
	
	/**
	 * This function retrieves any variable from the Request defined by the variable $name 
	 *
	 * @param string $name       - Name of the variable to retrieve 
	 * @param boolean $isNumeric - Validate the variable to be numeric
	 * @param mixed $default     - The default value in case of failure to retrieve the variable
	 * @return mixed
	 */
	public function getParameter( $name = 'id', $isNumeric = true , $default = 0 ){
		if ( isset( $_REQUEST[ $name ] ) && !empty( $_REQUEST[ $name ] ) ){
			if ( $isNumeric ) {
				if( is_numeric( $_REQUEST[ $name ] ) ){
					return $_REQUEST[ $name ];
				}
				else {
					return $default;
				}
			}
			else {
				return $_REQUEST[ $name ]; 
			}
		}
		else {
			return $default;
		}
	}

	/**
	 * This function redirects to another file by sending browser headers or
	 * by "requiring" the file
	 *
	 * @param string $url
	 * @param boolean $include
	 */
	public static function redirect( $url, $include = true ){
		if( $include ){
			require ( $url );
		}
		else {
			header( 'Location: ' . $url );
		}
	}
	
	public function setPermissionStrategy( PermissionStrategy $strategy ){
		$this->permissionStrategy = $strategy;
	}
	
}
?>