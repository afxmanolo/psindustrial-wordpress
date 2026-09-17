<?php
class Validator_Test_Email extends Validator_Test_RegEx {
	/**
	 * This Regular Expression was taken from the following site:
	 * http://fightingforalostcause.net/misc/2006/compare-email-regex.php
	 * 
	 * It's the best I've found so far.
	 * 
	 * @var string
	 */
	protected $emailRegEx = '/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/';
	//	protected $emailRegEx = '/^(?!\.)[\.-_a-z0-9.\'+*!$^&%]++(?<!\.)@(?:(?![-.])[-a-z0-9.]+(?<![-.])\.[a-z]{2,6}|\d{1,3}(?:\.\d{1,3}){3})(?::\d+)?$/';
	
	public function __construct( ){
		parent::__construct( $this->emailRegEx );
	}
	
}
?>