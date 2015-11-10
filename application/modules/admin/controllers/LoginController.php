<?php 

class LoginController extends Helper_Controller_Admin {

    public function indexAction() {
    	$this->noLayout(true);
    	$request = $this->getRequest();
    	
    	if($request->getPost('submit')) {
    		$users = new Model_Users;
    		$result = $users->checkLogin($request->getPost('username'),$request->getPost('password'));
    		if(!$result) {
    			$this->view->error = $this->translate('Please enter the correct username and password.');
    		} else {
    			if($result['status']) {
    				JO_Session::set(array('user' => $result));
    				$this->redirect($request->getServer('HTTP_REFERER'));
    			} else {
    				$this->view->error = $this->translate('This profile is not active.');
    			}
    		}
    	}
    	
    	$this->view->base_url = $request->getBaseUrl();
    	
    }
    
    public function logoutAction() {
    	$this->setInvokeArg('noViewRenderer', true);
    	@setcookie('csrftoken_', md5(JO_Session::get('user[user_id]') . $this->getRequest()->getDomain() . JO_Session::get('user[date_added]') ), (time() - 100 ), '/', '.'.$this->getRequest()->getDomain());
    	JO_Session::set(array('user' => false));
    	$this->redirect(JO_Request::getInstance()->getBaseUrl());
    }

}
