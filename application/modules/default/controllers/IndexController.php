<?php

class IndexController extends Helper_Controller_Default {
	
	public function indexAction() {	
		
		$request = $this->getRequest();
		
		//first login after registration
		//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		
		if($request->getParam('direct_path') == 'true') {
			$this->sendWelcomeAction();
			$this->redirect( $request->getBaseUrl() );
		}
		
		
		if(!Helper_Config::get('config_disable_js')) {
			//if pin is in detail page
			$this->view->open_from_pin_detail_page = false;
			if($request->getParam('open_from_pin_detail_page')) {
				$pin_array = new Model_Pins_Pin($request->getParam('open_from_pin_detail_page'));
				if($pin_array->count()) {
					$pin_array = $pin_array->data;
					$this->getLayout()->placeholder('title', $pin_array['board_title'] . ' - ' . htmlspecialchars(strip_tags( html_entity_decode($pin_array['pin_description']) )));
					JO_Layout::getInstance()->placeholder('header_metas', $this->view->callChildren('pin/header_metas', $pin_array));
				}
				
				$this->view->open_from_pin_detail_page = WM_Router::pinAction($request->getParam('open_from_pin_detail_page'));
			}
		}
		
		///// get pins
		$page = (int)$request->getRequest('page');
		if($page < 1) {
			$page = 1;
		}
		
		$this->view->result_data = '';
		if(!Helper_Config::get('config_disable_js')) {
			//get pins data
			if($request->isXmlHttpRequest()) {
				$this->forward('index', 'getPins');
			}
		} else {
			if($page > 1 && $request->isXmlHttpRequest()) {
				$this->forward('index', 'getPins');
			}
			$pins = (array)$this->getPinsAction(true);
			foreach($pins AS $pin) {
				$template = new Helper_Tmpl($pin['template'], $pin);
				$this->view->result_data .= $template->render($pin['template']);
			}
		}
		
		//call header and footer childrens
		$this->view->children = array(
				'header_part' 	=> 'layout/header_part',
				'footer_part' 	=> 'layout/footer_part'
		);
		
	}
	
	public function sendWelcomeAction() {
		if(JO_Session::get('user[user_id]')) {
			new Model_Users_Edit(JO_Session::get('user[user_id]'), array(
					'first_login' => '0'
			));
			JO_Session::clear('category_id');
			$this->view->user_info = JO_Session::get('user');
			$this->view->user_info['first_login'] = 0;
			JO_Session::set('user', $this->view->user_info);
			
			$template = Model_Notification::getTemplate('welcome');

			if($template) {

				$template_data = array(

						'user_id' => JO_Session::get('user[user_id]'),

						'user_firstname' => JO_Session::get('user[firstname]'),

						'user_lastname' => JO_Session::get('user[lastname]'),

						'user_fullname' => JO_Session::get('user[fullname]'),

						'user_username' => JO_Session::get('user[username]'),

						'site_url' => $this->getRequest()->getBaseUrl(),

						'site_name' => Helper_Config::get('site_name'),

						'mail_footer' => html_entity_decode(Helper_Config::get('mail_footer'), ENT_QUOTES, 'utf-8')

				);

					

				if(!$template['title']) {

					$template['title'] = $this->translate('Welcome to ${site_name}!');

				}

			

				$title = Model_Notification::parseTemplate(html_entity_decode($template['title'], ENT_QUOTES, 'utf-8'), $template_data);

				$body = Model_Notification::parseTemplate(html_entity_decode($template['template'], ENT_QUOTES, 'utf-8'), $template_data);

					

				Model_Email::send(

						JO_Session::get('user[email]'),

						Helper_Config::get('noreply_mail'),

						$title,

						$body

				);
			}
			
		}
		$this->noViewRenderer(true);
	}
	
	public function getPinsAction($return_data = false) {
		
		$request = $this->getRequest();
		$response = $this->getResponse();
		
		$page = (int)$request->getRequest('page');
		if($page < 1) {
			$page = 1;
		}
		
		$pp = (int)Helper_Config::get('config_front_limit');
		if(!(int)$pp) {
			$pp = 50;
		}
		if((int)$request->getRequest('per_page') > 0 && (int)$request->getRequest('per_page') < 300) {
			$pp = (int)$request->getRequest('per_page');
		}
		
		$data = array(
				'start' => ( $pp * $page ) - $pp,
				'limit' => $pp
		);
		
		$return = array();
		
		//Recent Activity
		if($page == 1 && JO_Session::get('user[user_id]')) {
			$history_data = Model_History_Activity::getHistoryV2(array(
					'start' => 0,
					'limit' => 10,
					'sort' => 'DESC',
					'order' => 'history_id'
			));
			
			$history = array();
			
			foreach($history_data AS $row) {
				
				$user_avatars = Helper_Uploadimages::userAvatars(array(
						'avatar' => $row['user_avatar'],
						'store' => $row['user_store'],
						'user_id' => $row['user_user_id']
				));
				
				$haystack_pins = array(
					Model_History_Abstract::REPIN,
					Model_History_Abstract::ADDPIN,
					Model_History_Abstract::COMMENTPIN,
					Model_History_Abstract::LIKEPIN
				);
				
				$text = $row['history_text_type'];
				
				$href_pin = false;
				/*if( in_array($row['history_history_action'], $haystack_pins) ) {
// 				if($row['history_history_action'] == Model_History_Abstract::REPIN) {
					$href = WM_Router::create($request->getBaseUrl() . '?controller=pin&pin_id=' . $row['history_pin_id']);
				} else {*/
					$href = WM_Router::create($request->getBaseUrl() . '?controller=users&action=profile&user_id=' . $row['history_from_user_id']);
					
					if( Model_History_Abstract::REPIN == $row['history_history_action'] ) {
						$text = array($this->translate('repinned your'), $this->translate('pin'),1);
						$href_pin = WM_Router::pinAction( $row['history_pin_id'] );
					} elseif( Model_History_Abstract::LIKEPIN == $row['history_history_action'] ) {
						$text = array($this->translate('like your'), $this->translate('pin'),1);
						$href_pin = WM_Router::pinAction( $row['history_pin_id'] );
					} elseif( Model_History_Abstract::UNLIKEPIN == $row['history_history_action'] ) {
						$text = array($this->translate('unlike your'), $this->translate('pin'),1);
						$href_pin = WM_Router::pinAction( $row['history_pin_id'] );
					} elseif( Model_History_Abstract::COMMENTPIN == $row['history_history_action'] ) {
						$text = array($this->translate('comment your'), $this->translate('pin'),1);
						$href_pin = WM_Router::pinAction( $row['history_pin_id'] );
					} elseif( Model_History_Abstract::FOLLOW == $row['history_history_action'] ) {
						$board_info = new Model_Boards_Board($row['history_board_id']);
						if($board_info->count()) {
							$text = array($this->translate('is now following your'), /*$this->translate('pins')*/$board_info['board_title']);
							$href_pin = WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=view&user_id=' . $board_info['board_user_id'] . '&board_id=' . $board_info['board_board_id'] );
						}
					} elseif( Model_History_Abstract::UNFOLLOW == $row['history_history_action'] ) {
						$board_info = new Model_Boards_Board($row['history_board_id']);
						if($board_info->count()) {
							$text = array($this->translate('has unfollow your'), /*$this->translate('pins')*/$board_info['board_title']);
							$href_pin = WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=view&user_id=' . $board_info['board_user_id'] . '&board_id=' . $board_info['board_board_id'] );
						}
					}
					
				//}
				
				$history[] = array(
					'history_id' => $row['history_history_id'],
					'date_added' => $row['history_date_added'],
					'profile' => array(
						'fullname' => $row['user_fullname'],
						'avatars' => $user_avatars
					),
					//urls
					'href' => $href,
					'href_pin' => $href_pin,
					//texts
					'text_type' => $text,
					'text_date_dif' => sprintf($this->translate('%d %s ago'), $row['history_date_dif']['value'], $this->translate($row['history_date_dif']['key']))
				);
			}
			
			if($history) {
				$return[] = array(
					'template' => 'home_history',
					'history' => $history,
					//text
					'text_title_box' => $this->translate('Recent Activity')
				);
			}
		}
		
		// pins data
		$pins = new Model_Pins_HomePage($data);
		
		//format response data
		$formatObject = new Helper_Format();
		
		if( $pins->count() ) {
			$banners = Model_Banners::getBanners(
					new JO_Db_Expr("`controller` = '".$request->getController()."' AND position >= '".(int)$data['start']."' AND position <= '".(int)($data['start']+$pp)."'")
			);
			
			foreach($pins->data AS $row => $pin) {
				///banners
				$key = $row + (($pp*$page)-$pp);
				if(isset($banners[$key]) && $banners[$key]) {
					if( ($banners_result = $formatObject->fromatListBanners($banners[$key])) !== false) {
						$return[] = $banners_result;
					}
				}
				//pins
				$return[] = $formatObject->fromatList($pin);
			}
			
		} else {
			if($page == 1) {
				$message = $this->translate('No pyngs!');
			} else {
				$message = $this->translate('No more pyngs!');
			}
			$return[] = $formatObject->fromatListNoResults($message);
		}
		
		if($return_data) {
			return $return;
		}
		
		$formatObject->responseJsonCallback($return);
		$this->noViewRenderer(true);
		
	}
	
	
	
}

?>