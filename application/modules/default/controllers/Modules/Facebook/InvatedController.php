<?php

class Modules_Facebook_InvatedController extends Helper_Controller_Default {
	
	/**
	 * @var WM_Facebook
	 */
	protected $facebook;
	
	public function init() {
		$this->facebook = JO_Registry::get('facebookapi');
	}
	
	public function indexAction() {
		
		$request = $this->getRequest();
		
		$facebookCheck = new Model_Facebook_Login();
		$invate = $facebookCheck->checkInvateFacebook($request->getQuery('code'));
		
		if( !$invate ) {
			
			$this->setViewChange('../login/no_account');
					
			$page_login_trouble = Model_Pages::getPage( JO_Registry::get('page_login_trouble') );
			if($page_login_trouble) {
				$this->view->page_login_trouble = array(
					'title' => $page_login_trouble['title'],
					'href' => WM_Router::create( $request->getBaseUrl() . '?controller=pages&action=read&page_id=' . $page_login_trouble['page_id'] )
				);
			}
			
		} else {
			
			$facebookObject = new Helper_Modules_Facebook();
			$this->view->facebook_login_url = $facebookObject->getLoginUrl(null,'modules_facebook_register');
			
		}
		
		$this->view->children = array(
        	'header_part' 	=> 'layout/header_part',
        	'footer_part' 	=> 'layout/footer_part'
        );
		
	}
	
}

?>