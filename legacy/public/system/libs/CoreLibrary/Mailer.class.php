<?php

class Mailer extends Objeto {

	/**
	 * PHPMailer Object
	 *
	 * @var PHPMailer
	 */
	protected $mailer;
	
	private $lang;

	const MAIL_DEST_TO  = 1;
	const MAIL_DEST_CC  = 2;
	const MAIL_DEST_BCC = 3;
	 
	const MAIL_PRIORITY_HIGH   = 1;
	const MAIL_PRIORITY_NORMAL = 3;
	const MAIL_PRIORITY_LOW    = 5;
	
	const MAIL_ENCODING_8BIT      = '8bit';
	const MAIL_ENCODING_7BIT      = '7bit';
	const MAIL_ENCODING_BINARY    = 'binary';
	const MAIL_ENCODING_BASE64    = 'base64';
	const MAIL_ENCODING_PRINTABLE = 'quoted-printable';
	
	/**
	 * Creates a new instance of the class.
	 * 
	 * @param string $fromAddress - A valid email address for the sender
	 * @param string $fromName    - A friendly name for the sender
	 */
	public function __construct( $fromAddress, $fromName = '' ){
		// By default, PHPMailer uses the mail() function
		$this->mailer = new PHPMailer();
		$this->mailer->From = $fromAddress;
		$this->mailer->FromName = $fromName;
		$this->setPhpMail();
	}
	
	/**
	 * Tells the mailer to send the email using SMTP
	 *
	 * @param string $username      - The SMTP username 
	 * @param string $password      - The SMTP password
	 * @param boolean $requiresAuth - Requires Auth before sending?
	 * @param string $host          - A valid hostname
	 * @param integer $port         - Host port number
	 * @param boolean $debug        - Debug answer?
	 */
	public function setSmtp( $username, $password, $requiresAuth = true, 
	                         $host = 'localhost', $port = 25, $debug = false, $tipoEnvio = 'mail' ){
		switch ($tipoEnvio){
	    	case 'mail':
	    		$this->mailer->IsMail();
	    		break;
	    	case 'sendmail':
	    		$this->mailer->IsSendmail();
	    		break;
	    	case 'smtp':
	    	default:
	    		$this->mailer->IsSMTP();
	    		break;
	    }
		$this->mailer->Username = $username;
		$this->mailer->Password = $password;
		$this->mailer->SMTPAuth = $requiresAuth;
		$this->mailer->Host = $host;
		$this->mailer->Port = $port;
		$this->mailer->SMTPDebug = $debug;
	}
	
	/**
	 * Set the mail to be send using the PHP mail() function.
	 *
	 * @return void
	 */
	public function setPhpMail(){
		$this->mailer->IsMail();
	}
	
	/**
	 * This function sends the email using PhpMailer::send() function.
	 * If the email wasn't sent, an exception is thrown.
	 *
	 * @return void
	 */
	public function send(){
		$sent = $this->mailer->Send();
		if ( !$sent ){
			throw new MailException( $this->mailer->ErrorInfo );
		}
	}
	
	/**
	 * Adds an email address as To, CC or BCC.
	 *
	 * @param string $email - A valid email address (ie. agent.smith@thematrix.com)
	 * @param string $name  - A friendly name for the address (ie. Agent Smith )
	 * @param int $field    - The destinatary qualification (see Mailer::MAIL_DEST_*)
	 * 
	 * @todo throw exception
	 */
	public function addAddress( $email, $name = '', $field = Mailer::MAIL_DEST_TO ){
		
		// TODO ARL: Validate email address
		
		switch( $field ){
			case Mailer::MAIL_DEST_BCC:
				$this->mailer->AddBCC( $email, $name );
				break;
			case Mailer::MAIL_DEST_CC:
				$this->mailer->AddCC( $email, $name );
				break;
			case Mailer::MAIL_DEST_TO:
			default:
				$this->mailer->AddAddress( $email, $name );
				break;
		}
	}
	
	/**
   * Adds an attachment from a path on the filesystem.
   * 
   * @param string $path     - Path to the attachment file. 
   * @param string $name     - Friendly name for the attached file.
   * @param string $encoding - File encoding.
   * @param string $type     - File extension (MIME) type.
   * 
   * @see Mailer::MAIL_ENCODING_* and Mailer::getMimeTypeFromFileExtension()
   * 
   * @return void
   * 
   * @throws Exception when the file is not found
   */
  public function AddFileAttachment( $path, $name = '', $encoding = Mailer::MAIL_ENCODING_BASE64, $type = 'application/octet-stream') {
  	$attached = $this->mailer->AddAttachment( $path, $name, $encoding, $type );
  	if ( !$attached ){
  		throw new Exception( 'File "$path" could not be found.' );
  	}
  }

  /**
   * Adds a string or binary attachment (non-filesystem) to the list.
   * This method can be used to attach ascii or binary data, such as a BLOB 
   * record from a database.
   * 
   * @param string $string   - String attachment data.
   * @param string $filename - Name of the attachment.
   * @param string $encoding - File encoding.
   * @param string $type     - File extension (MIME) type.
   * 
   * @see Mailer::MAIL_ENCODING_* and Mailer::getMimeTypeFromFileExtension()
   * 
   * @return void
   */
  public function AddStringAttachment( $string, $filename, $encoding = Mailer::MAIL_ENCODING_BASE64, $type = 'application/octet-stream') {
  	$this->mailer->AddStringAttachment( $string, $filename, $encoding, $type );
  }

  /**
   * Sets the priority of the email to be send.
   *
   * @param int $priority - See Mailer::MAIL_PRIORITY_*
   */
  public function setPriority( $priority = Mailer::MAIL_PRIORITY_HIGH ){
  	if( $priority != Mailer::MAIL_PRIORITY_HIGH &&
  	    $priority != Mailer::MAIL_PRIORITY_LOW  && 
  	    $priority != Mailer::MAIL_PRIORITY_NORMAL ){
  		$priority = Mailer::MAIL_PRIORITY_NORMAL;     	
  	}
  	$this->mailer->Priority = $priority;
  }

  /**
   * Sets the email subject field
   *
   * @param string $subject
   */
	public function setSubject( $subject ){
		$this->mailer->Subject = $subject;
	}
	
	/**
	 * Sets the email body. If the email is HTML, embedded files (images, css, etc)
	 * will be attached as well.
	 *
	 * @param string $body    - The email body string
	 * @param boolean $isHtml - Is the body in HTML format?
	 * 
	 * @return void
	 */
	public function setBody( $body, $isHtml = false ){
		
		$this->mailer->IsHTML( $isHtml );
		if ( $isHtml ){
			$this->mailer->MsgHTML( $body );
		}
		else {
			$this->mailer->Body = $body;
		}
	}
	
	/**
	 * Sets the alternative body for non-HTML email readers 
	 *
	 * @param string $body
	 * @return void
	 */
	public function setAltBody( $body ){
		$this->mailer->AltBody = $body;
	}

	/**
	 * Retrieves the MIME Type from the file extension.
	 * Ie. 'doc' = 'application/msword'
	 * 
	 * @param string $ext - The extension to get it's MIME Type (3 chars)
	 * @return string
	 * @see PhpMailer::_mime_types()
	 */
	public static function getMimeTypeFromFileExtension( $ext ){
		return PHPMailer::_mime_types( $ext );
	}

}

class MailException extends Exception {}
?>
