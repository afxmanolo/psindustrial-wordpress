<?php
class ProjectLibrary_SDO_Core_Validator_User extends ProjectLibrary_SDO_Core_Validator {
	
	/**
	 * @var ProjectLibrary_Entity_User_Profile
	 */
	protected $entity;

	public function __construct( ProjectLibrary_Entity_User $profile ){ 
		parent::__construct( $profile );
	}
	
	/**
	 * @see ProjectLibrary_SDO_Core_Validator::setup()
	 */
	protected function setup(){
		
		/*$userProfile = ProjectLibrary_FrontEnd_Util_UserSession::GetIdentity();
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
		$this->setupRole();*/
	}
	
	protected function setupProfileId(){
		$tester = new Validator_Tester( $this->entity->getUserId(), false );
		
		$tester->addTest( new Validator_Test_Numeric_UnsignedInteger(),
		                  $this->trans('profile_id').' '.str_replace('{n}', '0', $this->trans('integer_number_equal_greater_n_msg' )) );
		
		$this->validator->addTester( 'profile_id', $tester );
	}
	
	protected function setupFirstName(){
		$tester = new Validator_Tester( $this->entity->getFirstName(), true );
		
		$tester->addTest( new Validator_Test_NotEmpty(), 
		                $this->trans('first_name').' '.$this->trans('is_required_msg') );
		
		// Verify first name is a valid string instead of anything else
		$tester->addTest( new Validator_Test_String_NoHTML(),
		                  $this->trans('first_name').' '.$this->trans('no_html_tags_msg') );
		
		$this->validator->addTester( 'first_name', $tester );
	}

	protected function setupLastName(){
		$tester = new Validator_Tester( $this->entity->getLastName(), true );
		
		$tester->addTest( new Validator_Test_NotEmpty(), 
		                $this->trans('last_name').' '.$this->trans('is_required_msg') );
		
		// Verify last name is a valid string instead of anything else
		$tester->addTest( new Validator_Test_String_NoHTML(),
		                  $this->trans('last_name').' '.$this->trans('no_html_tags_msg') );
		
		$this->validator->addTester( 'last_name', $tester );
	}
	
	protected function setupEmail(){
		$tester = new Validator_Tester( $this->entity->getEmail(), true );
		
		$tester->addTest( new Validator_Test_NotEmpty(), 
		                'Email '.$this->trans('is_required_msg') );
		
		$tester->addTest( new Validator_Test_Email(),
		                  'Email '.$this->trans('not_valid_msg') );
		
		$tester->addTest( new ProjectLibrary_SDO_Core_Validator_Test_UniqueProfileEmail( $this->entity ),
		                  $this->trans('email_already_chosen_msg') );
		
		$this->validator->addTester( 'email', $tester );
	}
	
	protected function setupPassword(){
		$tester = new Validator_Tester( $this->entity->getPassword(), true );
		
		$tester->addTest( new Validator_Test_NotEmpty(), 
		                $this->trans('password').' '.$this->trans('is_required_msg(f)') );
		
		$tester->addTest( new Validator_Test_RegEx( '/^.{4,}$/'),
		                  $this->trans('password').' '.str_replace('{n}', '4', $this->trans('n_characters_minimum_msg')) );
		
		
		
		$this->validator->addTester( 'password', $tester );
	}
	
	protected function setupRole(){
		$tester = new Validator_Tester( $this->entity->getRole(), true );
		
		$tester->addTest( new Validator_Test_NotEmpty(), 
		                $this->trans('role').' '.$this->trans('is_required_msg') );
		
		$this->validator->addTester( 'role', $tester );
	}
	
}
?>