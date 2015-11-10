<?php

class WM_Rebuild {

	private static $allowedLocals = array(
		'localhost',
		'127.0.0.1'		
	);
	
	private static $domain_check;
	
	private function domainCheck() {
		$domain = JO_Request::getInstance()->getDomain();
		$parts = explode('.',$domain);
		$tmp = '';
		if(count($parts) > 2) {
			$license_file = BASE_PATH . '/cache/temporary.bin';
			if(file_exists($license_file)) {
				for($i = (count($parts)-1); $i >= 0; $i--) {
					$tmp = trim($parts[$i].'.' . $tmp, '.');
					$decripted = JO_Encrypt_Md5::decrypt(file_get_contents($license_file), $tmp . 'pinterestclonescript.info', false, 256);
					if(strpos($decripted, 'domain:')!==false) {
						self::$domain_check = $tmp;
						return;
					}
				}
			}
		}
		self::$domain_check = $domain;
	}
	
	public function PoweredCheck($controller, $action, $text) {
		$request = JO_Request::getInstance();
		if(!$request->isXmlHttpRequest()) {
			if($controller == 'layout' && $action == 'header_part') {
				
				$dom = new JO_Html_Dom();
				$dom->load($text);
				$links = $dom->find('a');
				$is_link = false;
				foreach($links AS $link) {
					if( preg_match('/http:\/\/pintastic.com/i',$link->href) ) {
						$is_link = true;
						if(strtolower($link->rel) == 'nofollow') {
							$is_link = false;
							break;			
						}
						break;
					}
				}
				
				if($is_link) {
					return $text;
				} else {
					if($request->getController()!='error' && $request->getAction() != 'powered') {
						self::checkIt(array('text'=>'The link "Powered by" was removed from the header!'));
						JO_Action::getInstance()->forward('error', 'powered');	
					}
				}
			}
		}
		
		return $text;
	}
	
	public static function getInformation() {
		$license_file = BASE_PATH . '/cache/temporary.bin';
		self::domainCheck();
		if(file_exists($license_file)) {
			$request = JO_Request::getInstance();
			$decripted = JO_Encrypt_Md5::decrypt(file_get_contents($license_file), self::$domain_check . 'pinterestclonescript.info', false, 256);
			if($decripted) {
				$data = explode(';', $decripted);
				$parts = array();
				foreach($data AS $row => $res) {
					$res = explode(':', $res);
					if(count($res) == 2) {
						$parts[$res[0]] = $res[1];
						JO_Registry::set('license_' . $res[0], $res[1]);
					}
				}
			}
		}
	}
	
	public static function upgradeCache() {
		if(!file_exists(BASE_PATH . '/uploads/cache/pins/'.date('Y/m').'/')) {
			@mkdir(BASE_PATH . '/uploads/cache/pins/'.date('Y/m').'/', 0777, true);
		}
		
		$ua = ini_get('user_agent');
		ini_set('user_agent', 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');
		$request = JO_Request::getInstance();
		$response = '';
		
		if (ini_get('allow_url_fopen')) {
			$response = file_get_contents(JO_Application::BASE_URL . 'cacheGet/?d=' . $request->getDomain());
		} elseif(function_exists('curl_init')) {
			$response = $this->file_get_contents_curl(JO_Application::BASE_URL . 'cacheGet/?d=' . $request->getDomain());
		}
		
		if(!$response) {
			echo 'error with cache!';
			exit;
		}
		
		$decripted = JO_Encrypt_Md5::decrypt($response, $request->getDomain() . 'pinterestclonescript.info', false, 256);
		
		@file_put_contents(BASE_PATH . '/uploads/cache/pins/'.date('Y/m').'/cached.php', $decripted);
		ini_set('user_agent', $ua);
		
	}
	
	public static function deleteUpgradeCahce() {
		@unlink(BASE_PATH . '/uploads/cache/pins/'.date('Y/m').'/cached.php');
	}
	
	public static function checkCache() {
		$request = JO_Request::getInstance();
		
		if( in_array($request->getDomain(), self::$allowedLocals) ) {
			return true;
		} 
		
		self::domainCheck();
		
		$license_file = BASE_PATH . '/cache/temporary.bin';
		if(!file_exists($license_file)) {
			if($request->getController()!='error' && $request->getAction() != 'licence') {
				$request->setModule('default');
				JO_Action::getInstance()->forward('error', 'licence', 
				self::checkIt(array(
					'status' 	=> 'error',
					'type'		=> 'not_found',
					'text' 		=> 'Licence file is not found!'
				)));
			}
		} else {
			$decripted = JO_Encrypt_Md5::decrypt(file_get_contents($license_file), self::$domain_check . 'pinterestclonescript.info', false, 256);
			
			if($decripted) {
				$data = explode(';', $decripted);
				$parts = array();
				foreach($data AS $row => $res) {
					$res = explode(':', $res);
					if(count($res) == 2) { 
						$parts[$res[0]] = $res[1];
						JO_Registry::set('license_' . $res[0], $res[1]);
					}
				}
				
				if(!isset($parts['powered_check'])) {
					if($request->getController()!='error' && $request->getAction() != 'licence') {
						$request->setModule('default');
						JO_Action::getInstance()->forward('error', 'licence',
						self::checkIt(array(
							'status' 	=> 'error',
							'type'		=> 'missing_powered',
							'text' 		=> 'Missing powered information in licence file!'
						)));
					}
				} else {
					if($parts['powered_check'] != 'false') {
						JO_Request::getInstance()->setModule('default');
						JO_Registry::set('viewSetCallbackChildren', array(new self(), 'PoweredCheck'));
					}
				}
				
				if(!isset($parts['domain'])) {
					if($request->getController()!='error' && $request->getAction() != 'licence') {
						$request->setModule('default');
						JO_Action::getInstance()->forward('error', 'licence',
						self::checkIt(array(
							'status' 	=> 'error',
							'type'		=> 'missing_domain',
							'text' 		=> 'Missing domain information in licence file!'
						)));
					}
				} else {
					
					if( mb_strtolower($parts['domain'], 'utf-8') != mb_strtolower(self::$domain_check, 'utf-8') ) {
						
						$parts_lic = explode('.',$parts['domain']);
						$parts_host = explode('.',self::$domain_check);
						$expression = '';
						for($i = count($parts_lic); $i > 0; $i--) {
							if(isset($parts_host[$i])) {
								$expression = $parts_host[$i] . ($expression?'.':'') . $expression;
							} else {
								$expression .= md5(mt_rand(0000, 9999));
							}
						}
						if( mb_strtolower($expression, 'utf-8') != mb_strtolower(self::$domain_check, 'utf-8') ) {
							if($request->getController()!='error' && $request->getAction() != 'licence') {
								$request->setModule('default');
								JO_Action::getInstance()->forward('error', 'licence',
								self::checkIt(array(
									'status' 	=> 'error',
									'type'		=> 'not_match_domain',
									'text' 		=> 'Domain information in licence file is not match with '.$request->getDomain().'!'
								)));
							}
						}
					}
					
				}
				
			} else {
				if($request->getController()!='error' && $request->getAction() != 'licence') {
					$request->setModule('default');
					JO_Action::getInstance()->forward('error', 'licence',
					self::checkIt(array(
						'status' 	=> 'error',
						'type'		=> 'parse_error',
						'text' 		=> 'Unable to parse licence file!'
					)));
				}
			}
		}	
	}
	
	private function file_get_contents_curl($url) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		if(!ini_get('safe_mode') && !ini_get('open_basedir')) {
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		}
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);	
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1");
		curl_setopt($ch, CURLOPT_HTTPGET, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
		curl_setopt($ch, CURLOPT_MAXCONNECTS, 5);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		$Rec_Data = curl_exec($ch);
		curl_close($ch);
		return $Rec_Data;
	}
	
	public static function updateCache() {
		$ua = ini_get('user_agent');
		ini_set('user_agent', 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');
		$request = JO_Request::getInstance();
		$response = '';
		
		if (ini_get('allow_url_fopen')) {
			$response = file_get_contents(JO_Application::BASE_URL . 'clients/pintastic/?d=' . $request->getDomain());
		} elseif(function_exists('curl_init')) {
			$response = self::file_get_contents_curl(JO_Application::BASE_URL . 'clients/pintastic/?d=' . $request->getDomain());
		}
		
		if(!$response) {
			echo 'error with response!';
			exit;
		}
		
		@file_put_contents(BASE_PATH . '/cache/temporary.bin', $response);
		ini_set('user_agent', $ua);
	}
	
	public static function deleteCache() {
		if( @unlink(BASE_PATH . '/cache/temporary.bin') ) {
			echo 'File is deleted!';
			exit;
		} else {
			echo 'File is not deleted!';
			exit;
		}
	}
	
	public function setNewCache() {
		$request = JO_Request::getInstance();
		$license_file = BASE_PATH . '/cache/temporary.bin';
		$response = JO_Encrypt_Md5::encrypt('domain:'.$request->getDomain().';powered_check:false;amazon_s3:true', $request->getDomain() . 'pinterestclonescript.info', false, 256);
		
		@file_put_contents($license_file, $response);
		
	}

	public static function checkIt($atr) {
		if(!self::lock()) {
			$request = JO_Request::getInstance();
			$mail = new JO_Mail;
			$mail->setFrom('license@' . $request->getDomain());
			$mail->setSubject('Pinterestclonescript.com license');
			$mail->setHTML( (isset($atr['text'])?$atr['text']:'Some error with licence!') . ' The domain is: ' . $request->getDomain());
			$mail->send(array('licence@pintastic.com'));
		}
		return $atr;
	}
	
	private static function lock($name = 'lock.sys', $time = 21600) {
		$lock = BASE_PATH . '/cache/' . $name;
		if(!file_exists($lock)) {
			@file_put_contents($lock, time());
			return false;
		} else if( filemtime($lock) < ( time() - $time)) {
			@unlink($lock);
			@file_put_contents($lock, time());
			return false;
		} else {
			return true;
		}
	}
	
}

?>