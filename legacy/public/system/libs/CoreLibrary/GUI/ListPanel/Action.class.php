<?php

class GUI_ListPanel_Action extends Objeto {
	
	private $imageUrl;
	private $imageAlt = '';
	private $imageTitle = '';
	private $onClickEvent = '';
	private $target = '';
	private $link;
	private $type;
	private $name;
	private $value;
	private $params;
	
	public function __construct( $link, $imageUrl, $target = '',  $type = '', $name = '', $value = '' ,$params = ''){
		parent::__construct();
		$this->setLink( $link );
		$this->setImageUrl( $imageUrl );
		$this->setTarget( $target );
		$this->setType( $type );
		$this->setName( $name );
		$this->setValue( $value );
		$this->setParams($params);
	}
	
	function setParams($p){$this->params = $p;}
	function getParams(){ return $this->params;}
	
	function setImageUrl( $imageUrl ) { 
		$this->imageUrl = $imageUrl; 
	}
	
	function setValue( $Value ) { 
		$this->value = $Value; 
	}
	function getValue() { 
		return $this->value; 
	}
	
	function setName( $Name ) { 
		$this->name = $Name; 
	}
	function getName() { 
		return $this->name; 
	}
	
	function setType( $Type ) { 
		$this->type = $Type; 
	}
	function getType() { 
		return $this->type ; 
	}
	
	function getImageUrl() { 
		return $this->imageUrl; 
	}
	
	function getImageAlt() { 
		return $this->imageAlt; 
	}
	
	function setImageAlt( $imageAlt ) { 
		$this->imageAlt = $imageAlt; 
	}
	
	function getImageTitle() { 
		return $this->imageTitle; 
	}
	
	function setImageTitle( $imageTitle ) { 
		$this->imageTitle = $imageTitle; 
	}
	
	function getLink() { 
		return $this->link; 
	}
	
	function setLink( $link ) { 
		$this->link = $link; 
	}
	
	function setOnClickEvent( $strJavaScript ){
		$this->onClickEvent = $strJavaScript;
	}
	
	function getOnClickEvent(){
		return $this->onClickEvent;
	}

	function setTarget( $target ){
		$this->target = $target;
	}
	
	function getTarget(){
		return $this->target;
	}
}
?>