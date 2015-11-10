<?php

class Modules_Facebook_LoginController extends Helper_Controller_Default {
	
	public function indexAction() {

		$request = $this->getRequest();
		
		$settings = Model_Extensions::getSettingsPairs('facebook');
		if(!isset($settings['facebook_login_with_facebook']) || !$settings['facebook_login_with_facebook']) {
			$this->forward('error', 'error404');
		} elseif(!isset($settings['facebook_module_status_enable']) || !$settings['facebook_module_status_enable']) {
			$this->forward('error', 'error404');
		}
		
		$facebook = new Helper_Modules_Facebook();
		
		$user_data = $facebook->getUser(true);
		
		if($user_data) {
			
			$modelLogin = new Model_Facebook_Login($user_data['id']);
			$error = true;
			if($modelLogin->row) {
				$userObject = new Model_Users_User($modelLogin->row['user_id']);
				if($userObject->count()) {
					if(JO_Session::get('user[user_id]')) {
						if($modelLogin->row['user_id'] == JO_Session::get('user[user_id]')) {
							JO_Session::set('user', $userObject->toArray());
						}
					} else {
						JO_Session::set('user', $userObject->toArray());
					}
					new Model_Users_Edit($modelLogin->row['user_id'], array(
							'last_login' => new JO_Db_Expr('NOW()')
					));
					$modelLogin->facebook = $facebook;
					$modelLogin->update($user_data);
					if($request->getQuery('next')) {
						$this->redirect( ( urldecode($request->getQuery('next')) ) );
					} else {
						$this->redirect( WM_Router::create( $this->getRequest()->getBaseUrl() ) );
					}
				}
			} elseif(Helper_Config::get('enable_free_registration')) {
				$this->forward('modules_facebook_register');
			} elseif(JO_Session::get('user[user_id]')) {
				$this->redirect( $request->getBaseUrl() );
			}
			
			$this->setViewChange('no_account');
			$page_login_trouble = Model_Pages::getPage( Helper_Config::get('page_login_trouble') );
			if($page_login_trouble) {
				$this->view->page_login_trouble = array(
						'title' => $page_login_trouble['title'],
						'href' => WM_Router::create( $request->getBaseUrl() . '?controller=pages&action=read&page_id=' . $page_login_trouble['page_id'] )
				);
			}
			
		} else {
			//not session
			$this->setViewChange('error_login');
				
			$page_login_trouble = Model_Pages::getPage( Helper_Config::get('page_login_trouble') );
			if($page_login_trouble) {
				$this->view->page_login_trouble = array(
						'title' => $page_login_trouble['title'],
						'href' => WM_Router::create( $request->getBaseUrl() . '?controller=pages&action=read&page_id=' . $page_login_trouble['page_id'] )
				);
			}
		}
		
	}
	
	

}

?>