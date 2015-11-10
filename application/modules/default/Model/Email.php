<?php

class Model_Email extends JO_Model {
	
	public static $error;

	public static function send($to, $from, $title, $body = '', $attachmendImagesBody = false) {
		
		$mail = new JO_Mailer_Base();
// 		$mail->attachmendImagesBody = $attachmendImagesBody;
		$mail->attachmendImagesBody = false;
		if(Helper_Config::get('mail_smtp')) {
			$mail->SMTPAuth = true;
			$mail->IsSMTP();
			$mail->Host = Helper_Config::get('mail_smtp_host');
			$mail->Port = Helper_Config::get('mail_smtp_port');
			$mail->Username = Helper_Config::get('mail_smtp_user');
			$mail->Password = Helper_Config::get('mail_smtp_password');
		}
		
		$mail->SetFrom($from, '');
		$mail->AddReplyTo($from,"");
		$mail->Subject    = $title;
		
		$mail->AltBody    = self::translate("To view the message, please use an HTML compatible email viewer!"); // optional, comment out and test
		
		$mail->MsgHTML($body, BASE_PATH);
		$mail->AddAddress($to, "");
		
    	$result = $mail->Send();
    	if($result) {
    		return true;
    	} else { 
    		self::$error = $mail->ErrorInfo;
    		return false;
    	}
		
	}
	
}

?>