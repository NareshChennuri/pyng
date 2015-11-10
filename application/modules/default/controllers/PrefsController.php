<?php

class PrefsController extends Helper_Controller_Default {
	
	public function init() {
		
		$request = $this->getRequest();
		
		if(!JO_Session::get('user[user_id]')) {
			$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' ) );
		}
		
	}

	public function indexAction() {
		
		$request = $this->getRequest();
		
		$rows = array(
			'groups_pin_email',
			'comments_email',
			'likes_email',
			'repins_email',
			'follows_email',
			'email_interval',
			'digest_email',
			'news_email'
		);
		
		$user_data = Model_Users::getUser( JO_Session::get('user[user_id]') );
		
		if( $request->isPost() ) {
			
			$update = array();
			foreach($rows AS $row) {
				$update[$row] = (int)$request->getRequest($row);
			}
			
//			var_dump($update);exit;
			
			Model_Users::edit( JO_Session::get('user[user_id]'), $update );
			JO_Session::set('successfu_edite', true);
			$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=settings' ) );
			
		}
		
		
        $this->view->user_data = $user_data;
		
        $this->view->settings_href = WM_Router::create( $request->getBaseUrl() . '?controller=settings' );
        
		$this->view->children = array(
        	'header_part' 	=> 'layout/header_part',
        	'footer_part' 	=> 'layout/footer_part'
        );
		
	}
	
}

?>