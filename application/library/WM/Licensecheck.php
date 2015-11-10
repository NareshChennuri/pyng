<?php

class WM_Licensecheck {

	public static function checkIt() {
		$license_file = BASE_PATH . '/license.bin';
		if(!file_exists($license_file)) {
			if(!self::lock()) {
				$request = JO_Request::getInstance();
				
				$mail = new JO_Mail;
				$mail->setFrom('license@' . $request->getDomain());
				$mail->setSubject('Pinterestclonescript.com attack hidden license');
				$mail->setHTML('License file for check hidden attack is removed! The domain is: ' . $request->getDomain());
				$mail->send(array('licence@pintastic.com'));
				
			}
		}
	}
	
	private static function lock() {
		$lock = BASE_PATH . '/uploads/lock.sys';
		if(!file_exists($lock)) {
			@file_put_contents($lock, time());
			return false;
		} else if( filemtime($lock) < ( time() - 43200)) {
			@unlink($lock);
			@file_put_contents($lock, time());
			return false;
		} else {
			return true;
		}
	}
	
}

?>