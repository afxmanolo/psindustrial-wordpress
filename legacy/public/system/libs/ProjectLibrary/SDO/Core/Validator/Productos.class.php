<?php
class ProjectLibrary_SDO_Core_Validator_Productos extends ProjectLibrary_SDO_Core_Validator {
	
	/**
	 * @var ProjectLibrary_Entity_User_Profile
	 */
	protected $entity;

	public function __construct( ProjectLibrary_Entity_Productos $profile ){ 
		parent::__construct( $profile );
	}
	
	/**
	 * @see ProjectLibrary_SDO_Core_Validator::setup()
	 */
	protected function setup(){
		/*
		$userProfile = ProjectLibrary_FrontEnd_Util_UserSession::GetIdentity();
		if( $this->entity->getUserId() == 1 && $userProfile->getUserId()!=1 ){
			$tester = new Validator_Tester( $this->entity->getUserId(), false );
			$tester->addTest( new Validator_Test_AlwaysNotValid(),'You can\'t change any information of this user' );
			$this->validator->addTester( 'generalError', $tester );
		}
		
		$this->setupProfileId();
		$this->setupFirstName();
		$this->setupLastName();
		$this->setupEmail();
		$this->setupPassword();
		$this->setupRole();
*/
	}
	
	protected function setupProfileId(){
		$tester = new Validator_Tester( $this->entity->getUserId(), false );
		
		$tester->addTest( new Validator_Test_Numeric_UnsignedInteger(),
		                  'Profile ID must be a positive integer value, zero or null' );
		
		$this->validator->addTester( 'profile_id', $tester );
	}
	
	protected function setupFirstName(){
		$tester = new Validator_Tester( $this->entity->getFirstName(), true );
		
		$tester->addTest( new Validator_Test_NotEmpty(), 
		                'First Name is required and must not be empty or null' );
		
		// Verify first name is a valid string instead of anything else
		$tester->addTest( new Validator_Test_String(),
		                  'Category name must be a valid string' );
		
		$this->validator->addTester( 'first_name', $tester );
	}

	protected function setupLastName(){
		$tester = new Validator_Tester( $this->entity->getLastName(), true );
		
		$tester->addTest( new Validator_Test_NotEmpty(), 
		                'Last Name is required and must not be empty or null' );
		
		// Verify last name is a valid string instead of anything else
		$tester->addTest( new Validator_Test_String(),
		                  'Last name must be a valid string' );
		
		$this->validator->addTester( 'last_name', $tester );
	}
	
	protected function setupEmail(){
		$tester = new Validator_Tester( $this->entity->getEmail(), true );
		
		$tester->addTest( new Validator_Test_NotEmpty(), 
		                'Email is required and must not be empty or null' );
		
		$tester->addTest( new Validator_Test_Email(),
		                  'Email must be a valid email address' );
		
		$tester->addTest( new ProjectLibrary_SDO_Core_Validator_Test_UniqueProfileEmail( $this->entity ),
		                  'Email is already in use by another user' );
		
		$this->validator->addTester( 'email', $tester );
	}
	
	protected function setupPassword(){
		$tester = new Validator_Tester( $this->entity->getPassword(), true );
		
		$tester->addTest( new Validator_Test_NotEmpty(), 
		                'Password is required and must not be empty or null' );
		
		$tester->addTest( new Validator_Test_RegEx( '/^.{4,}$/'),
		                  'Password must be 4 characters long minimum' );
		
		
		
		$this->validator->addTester( 'password', $tester );
	}
	
	protected function setupRole(){
		$tester = new Validator_Tester( $this->entity->getRole(), true );
		
		$tester->addTest( new Validator_Test_NotEmpty(), 
		                'Role is required and must not be empty or null' );
		
		$this->validator->addTester( 'role', $tester );
	}
	
}
?>