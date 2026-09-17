<?php
abstract class ProjectLibrary_Entity extends Objeto {
	
	public function __construct(){
		parent::__construct();
	}
	
	public function __call($name, $arguments) {
		if(!method_exists($this,$name)){
			$arrWords = Util_String::splitCamelCase($name);
			$get_set = array_shift($arrWords);
			$atributo = NULL;
			foreach($arrWords as $word)$atributo .= strtolower($word) . "_";
			$atributo = substr($atributo,0,-1);
			switch ($get_set){
				case 'get':
					return $this->$atributo;
					break;
				case 'set':
					return $this->$atributo = $arguments[0];
					break;
			}
		}
	}
}
?>