<?php
class ProjectLibrary_SDO_Core_Util_EntityManager extends Objeto {
	
	public static function ParseArrayToObject( array $entity = null, ProjectLibrary_Entity $object = null ){
	    
		if ( $entity == null ){
			$entity = array();
		}
		
		foreach( $entity as $key => $value ){
			$function = 'set' . str_replace( ' ', '' , ucwords( str_replace( '_', ' ', $key ) ) );
			
			if ( method_exists( $object, $function ) ){
				$object->{$function}( $value);
			}
			else {
				throw new Exception( get_class( $object ) . " does not has a setter method for handling '$key': $function " );
			}
		}
		
		return $object;
	}
	
	public function GetResultsInArray($key, $attribute, $results){
		$getKey = 'get' . str_replace( ' ', '' , ucwords( str_replace( '_', ' ', $key ) ) );
		$getAttribute = 'get' . str_replace( ' ', '' , ucwords( str_replace( '_', ' ', $attribute ) ) );
		$arrayRet = array();		
		foreach($results as $object){			
			if ( method_exists( $object, $getAttribute ) && method_exists( $object, $getKey ) ){
				$key = $object->{$getKey}();
				$attribute = $object->{$getAttribute}();			
				$arrayRet[ $key ] = $attribute;
			}
			else {
				throw new Exception( get_class( $object ) . " does not has a getter method for handling '$key': $function " );
			}						
		}
		return $arrayRet;
	} 		
}
?>