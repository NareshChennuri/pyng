<?php

class TranslateController extends Helper_Controller_Admin {
	
	public static function config() {
		return array(
			'name' => self::translate('Translation management'),
			'has_permision' => true,
			'menu' => self::translate('Systems'),
			'children' => self::translate('Localisation'),
			'in_menu' => true,
			'permision_key' => 'translate',
			'sort_order' => 80501
		);
	}
	
	/////////////////// end config
	
	private $session;
	
	public function init() {
		$this->session = JO_Session::getInstance();
	}
	
	public function indexAction() {
		
		$request = $this->getRequest();
		
		$this->view->modules = self::getModulesList();
		
		if($request->getQuery('mod') && in_array($request->getQuery('mod'), $this->view->modules)) {
			$this->view->mod = $request->getQuery('mod');
		} else {
			$this->view->mod = 'admin';
			$request->setParams('mod', 'admin');
		}
		
		if($this->session->get('successfu_edite')) {
    		$this->view->successfu_edite = true;
    		$this->session->clear('successfu_edite'); 
    	}	
    	if($this->session->get('error_permision')) {
    		$this->view->error_permision = $this->session->get('error_permision');
    		$this->session->clear('error_permision'); 
    	} 
		
		if($request->isPost()) {
			
			if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
				$this->session->set('error_permision', $this->translate('You do not have permission to this action'));
				$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/translate/');
			}
			
			Model_Translate::setTranslate($request->getParams());
			$this->session->set('successfu_edite', true);
			
			if($request->getPost('hidden_mod') != $this->view->mod) {
				$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/translate/?mod=' . $request->getPost('hidden_mod'));
			} else {
    			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/translate/?mod=' . $this->view->mod);
			}
		}
		
    	$this->view->translated = Model_Translate::getTranslate($this->view->mod);
    	if($this->view->translated) {
    		foreach($this->view->translated AS $k=>$v) {
    			$this->view->translated[$k]['keyword'] = htmlspecialchars($v['keyword'], ENT_QUOTES, 'utf-8');
    		}
    	}
    	
	}
	
	public function translateApiAction() {
		$word = $this->getRequest()->getPost('word');
		$from = $this->getRequest()->getPost('from');
		$to = $this->getRequest()->getPost('to');
		
		if(!Helper_Config::get('google_translate_key')) {
			$this->view->error = 'No Google Translate API key';
		} else {
			$url = 'https://www.googleapis.com/language/translate/v2?key='.Helper_Config::get('google_translate_key').'&q='.urlencode($word).'&source=' . $from . '&target=' . $to;
			
//			if (ini_get('allow_url_fopen')) {
//				$response = json_decode(@file_get_contents($url), true);
//			} else {
//				$response = json_decode($this->file_get_contents_curl($url), true);
//			}
			
			$response = json_decode($this->file_get_contents_curl($url), true);
			
			if(isset($response['data']['translations'][0]['translatedText'])) {
		    	$this->view->text = $response['data']['translations'][0]['translatedText'];
		    } elseif($response['error']['message']) {
		    	$this->view->error = $response['error']['message'];
		    } else {
		    	$this->view->error = 'Error translate';
		    }
		}
		
	    echo $this->renderScript('json');
	}
	
	
	private function file_get_contents_curl($url) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, false);
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
	
	private function getModulesList() {
		$modules = glob(APPLICATION_PATH . '/modules/*');
		if($modules) {
			$temp = array();
			foreach($modules AS $mod) {
				$name = basename($mod);
				if( !preg_match('/^(install|update)/', $name) ) {
					$temp[] = $name;
				}
			}
			return $temp;
		}
		return array();
	}
	
}

?>