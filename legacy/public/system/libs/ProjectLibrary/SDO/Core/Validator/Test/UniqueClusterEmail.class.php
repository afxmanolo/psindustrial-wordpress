<?php
class ProjectLibrary_SDO_Core_Validator_Test_UniqueClusterEmail implements Validator_Test {

	/**
	 * @var ProjectLibrary_Entity_User_Profile
	 */
	protected $profile;
	
	/**
	 * @param ProjectLibrary_Entity_User_Profile $profile - The profile to be validated containing an unique email address
	 */
	public function __construct( ProjectLibrary_Entity_Cluster $profile ){
		$this->profile = $profile;
	}

	/**
	 * @param int $variable
	 * @see Validator_Test::isValid()
	 */
	public function isValid( $variable ){
		$tmpProfile = ProjectLibrary_SDO_Core_Application_Cluster::LoadByEmail( $variable );
		 
		$emailTmp = $tmpProfile->getEmail();

		if ( empty( $emailTmp ) || $tmpProfile->getClusterId() == $this->profile->getClusterId() ){
			// The email belongs to the same profile we are validating. No problem here 
			return true;
		}
		else {
			// The email is stored under another user profile. This email is not unique.
			return false;
		}
	}
	
}
?>