<?php

class MassmailController extends Helper_Controller_Admin {
    
    public static function config() {
		
		return array(
			'name' => self::translate('Mass Email'),
			'has_permision' => true,
			'menu' => self::translate('Users'),
			'in_menu' => true,
			'permision_key' => 'massmail',
			'sort_order' => 921000
		);
	}
	
	/////////////////// end config
	
	private $session;
	
	public function indexAction() {
		if( !WM_Users::allow('create',  $this->getRequest()->getController()) ) {
			$this->forward('error', 'noPermission');
		}
		$request = $this->getRequest();
		$this->view->user = $request->getPost('user');
		$this->view->title = $request->getPost('title');
		$this->view->description = $request->getPost('description');
		$this->view->users = Model_Users::getUsers(array(
			'filter_email' => '@'		
		));
		if(JO_Session::get('successfu_edite')) {
    		$this->view->successfu_edite = true;
    		JO_Session::clear('successfu_edite'); 
    	}	
		
    	$this->view->errors = array();
    	
		if($request->isPost()) {
			
			if($this->getRequest()->issetPost('user')) {
				$result = '';
				if($this->getRequest()->getPost('user')=='all') {
				    $email_list = Model_Users::getUsers(array(
						'filter_email' => '@'		
					));
				    foreach($email_list as $email) {
					    if(JO_Validate::validateEmail($email["email"])) {
					       $result = Model_Email::send(
		        	        	$email["email"],
		        	        	Helper_Config::get('noreply_mail'),
		        	        	$this->getRequest()->getPost('title'),
		        	        	html_entity_decode($this->getRequest()->getPost('description'), ENT_QUOTES, 'utf-8')
		        	        );
					    }
				    }
				    
				} elseif(JO_Validate::validateEmail($this->getRequest()->getPost('user'))) { 
				    $result = Model_Email::send(
	    	        	$this->getRequest()->getPost('user'),
	    	        	Helper_Config::get('noreply_mail'),
	    	        	$this->getRequest()->getPost('title'),
	    	        	html_entity_decode($this->getRequest()->getPost('description'), ENT_QUOTES, 'utf-8')
	    	        );
				}
				if($result) {
					JO_Session::set('successfu_edite', true);
    				$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/massmail/');
				} else {
					$this->view->errors['no_emails'] = $this->translate('There was an error with sending the mail!');
				}
			}
			
		}
		
	}
	
}

?>