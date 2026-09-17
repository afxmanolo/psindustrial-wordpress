<?php

class Util_Email extends Objeto {
	
	/*
	 */
	
	
	/**
	 * @param boolean $toAdmin
	 * @param boolean $toUser
	 * @param eCommerce_Entity_User_Profile $user
	 * @param string $subject
	 * @param string $main_message
	 * @param array $styles = array(
								    "containerWidth" => "500px",		(required)
								    "fontSize1" => "12px",					(required)
								    "fontColor1" => "#666",					(required)
								    "fontColor2" => "#666", 
								    "headerImage" => array(
								       "src" => "http://www.domain.com/image.png",	(required)
								       "width" => "Npx",						(required)
								       "height" => "Npx",						(required)
										),
								    "divisionLineColor" => "#F00"		(required)
									)
	 * @param array $from = array("name" => "User Name", "email" => "user@domain.com")
	 * @param string $user_message
	 * @param string $admin_message
	 * @param array $admin_to = array("user1@domain.com", "user2@domain.com", "user3@domain.com")
	 */
	public function sendHTMLMail($toAdmin = true, $toUser = true, $user, $subject, $main_message, $styles, $from = array(), $user_message = '', $admin_message = '', $admin_to = array() ){
		$config = Config::getGlobalConfiguration();
		
		$mail = $config['project_email'];
		
		$from["name"] = empty($from["name"]) ? $config["project_name"] : $from["name"];
		$from["email"] = empty($from["email"]) ? $config["project_email"] : $from["email"];
		
		$admin_to = empty($admin_to) ? $config['project_name']." <".$config['project_email'].">" : implode(',', $admin_to);
		//<p align="left">'.$this->trans('to_enter_our_site_click_link_msg').': <a href="'.ABS_HTTP_URL.'">'.ABS_HTTP_URL.'</a></p>
		$body =
'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title></title>
</head>
<body><div align="center"><div style="width:'.$styles["containerWidth"].';font-size:'.$styles["fontSize1"].';font-family:Arial,Helvetica,sans-serif;color:'.$styles["fontColor1"].'">
	<div style="width:100%" align="left"><img src="'.$styles["headerImage"]["src"].'" width="775" height="130" /></div>
	<div style="width:100%; border-top:3px solid '.$styles["divisionLineColor"].'">&nbsp;</div>
	<div style="padding:0 25px" align="left">
		{CUSTOM_MESSAGE}
		<div style="padding:0 25px" align="left">
			<p align="center">'.$main_message.'</p>
			
			<p>'.$this->trans('any_comment_contact_us_msg').': <a href="mailto:'.$mail.'">'.$mail.'</a></p>
			<p style="line-height:150%;margin-top:24px;font-weight:bold;color'.(empty($styles["fontColor2"]) ? $styles["fontColor1"] : $styles["fontColor2"]).'">'.$this->trans('sincerely').'<br />'.$from['project_name'].'</p>
		</div>
	</div>
	<div style="width:100%; border-bottom:3px solid '.$styles["divisionLineColor"].'">&nbsp;</div>
</div></div></body></html>';
		
		$header =  'MIME-Version: 1.0' . "\r\n";
		$header .= "Content-type: text/html; charset=iso-8859-1\r\n";
		$header .= "From: ".$from['name']." <".$from['email'].">\r\n";
		$header .= 'X-Sender:'.$from['name'].'<'.$from['email'].'>'."\r\n"; 
		$header .= 'X-Mailer: PHP/'.phpversion()."\r\n";
		$header .= 'X-Priority: 1 (Higuest)'."\r\n";

// echo str_replace('{CUSTOM_MESSAGE}', $user_message, $body);
// echo str_replace('{CUSTOM_MESSAGE}', $admin_message, $body);
// die();

		if($toAdmin) {
			@mail($admin_to, $subject, str_replace('{CUSTOM_MESSAGE}', $admin_message, $body), $header);
		}
		
		if($toUser) {
			$body    = utf8_decode($body);
			$subject = utf8_decode($subject);
			@mail(utf8_decode($user->getFirstName()).' '.utf8_decode($user->getLastName()).' <'.$user->getEmail().'>', $subject, str_replace('{CUSTOM_MESSAGE}', $user_message, $body), $header);
		}
	}
	
}

?>
