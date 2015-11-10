<?php

class LandingController extends Helper_Controller_Default {

	public function indexAction() {        

		$request=$this->getRequest();

		if(JO_Session::get('user[user_id]')) {
			$this->redirect( WM_Router::create( $this->getRequest()->getBaseUrl() ) );
		}
		
		if(Helper_Config::get('enable_free_registration')) {
			$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=users&action=register' ) );
		}
		
		$this->view->login = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' );
		
		if(JO_Session::get('successfu_edite')) {
    		$this->view->successfu_edite = true;
    		JO_Session::clear('successfu_edite'); 
    	}
		
		if($request->isPost()) {
			
			$validate = new Helper_Validate();
			$validate->_set_rules($request->getPost('email'), $this->translate('Email'), 'not_empty;min_length[5];max_length[100];email');
			
			if($validate->_valid_form()) {
				$shared_contentObject = new Model_Users_Invate();
				$shared_content = $shared_contentObject->isInvatedByEmail($request->getPost('email'));
	    		if($shared_content == 1) {
	    			$this->view->error = $this->translate('This e-mail address is already registered');
	    		} else if($shared_content == 2) {
	    			$this->view->error = $this->translate('This e-mail address is already registered');
	    		} else {
	    			if(($key = Model_Users::addSharedContent($request->getPost('email'))) !== false) {
		    			JO_Session::set('successfu_edite', true);
		    			if(Helper_Config::get('not_ri')) {
    		    			Model_Email::send(
    				    	  	Helper_Config::get('report_mail'),
    				    	 	Helper_Config::get('noreply_mail'),
    				    	   	$this->translate('New invitation request'),
    				    	  	$this->translate('Hello, there is new invitation request in ').' '.Helper_Config::get('site_name')
    				    	 );
		    			}
						$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=landing' ) );
	    			} else {
	    				$this->view->error = $this->translate('There was an error. Please try again later!');
	    			}
	    		}
			} else {
				$this->view->error = $validate->_get_error_messages();
			}
			
		}
		
		$this->view->children = array(
				'header_part' 	=> 'layout/header_part',
				'footer_part' 	=> 'layout/footer_part'
		);
		
	}
	
}

?>