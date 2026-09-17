<?php
abstract class DB_DAO_Timestampable extends DB_DAO_AdvancedDAO {
	
	public function saveNew( &$arrEvent ){
		$arrEvent['creation_date'] = date( 'Y-m-d H:i:s' );
		return parent::saveNew( $arrEvent );
	}
	
	public function update( &$arrEvent ){
		$arrEvent['modification_date'] = date( 'Y-m-d H:i:s' );
		return parent::update( $arrEvent );
	}
	
}
?>