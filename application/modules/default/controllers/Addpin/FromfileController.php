<?php

class Addpin_FromfileController extends Helper_Controller_Default {

	public $SORT = 2;

	public function indexAction() {
		
		$request = $this->getRequest();
		
		$this->view->pin_add_url = WM_Router::create( $request->getBaseUrl() . '?controller=addpin_fromfile&action=stepone' );
		
	}
	
	public function steponeAction($error = null) {
		
		$this->noLayout(true);
		
		$request = $this->getRequest();
		$this->view->form_action = WM_Router::create( $request->getBaseUrl() . '?controller=addpin_fromfile&action=upload_images' );
			
		$this->view->upload_action = WM_Router::create( $request->getBaseUrl() . '?controller=addpin_fromfile&action=steptwo' );
			
		if( JO_Session::get('user[user_id]') ) {
			
			if($error) {
				$this->view->error_upload_image = $error;
			}
		} else {
			$this->view->redirect = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' );
		}
		
	}
	
	public function steptwoAction() {
		
		$this->noLayout(true);
		
		$request = $this->getRequest();
		
		if( JO_Session::get('user[user_id]') ) {
			if(JO_Session::get('upload_from_file') && file_exists(BASE_PATH . JO_Session::get('upload_from_file'))) {
				
				///////////////// Extension on create //////////////////
				$this->view->form_extensions = array();
				$extensions = Model_Extensions::getByMethod('pin_oncreateform');
				if($extensions) {
					$front = JO_Front::getInstance();
					foreach($extensions AS $id => $ext) {
						$this->view->form_extensions[] = array(
							'html' => $this->view->callChildren('modules_' . $ext . '_oncreateform'),
							'view' => $this->view->callChildrenView('modules_' . $ext . '_oncreateform'),
							'key' => $ext
						);
					}
				}
				
				$this->view->createBoard = WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=createboardwithoutcategory' );
				
				$temporary = '/cache/review/';
				$upload_folder = BASE_PATH . $temporary;
				
				$boards = new Model_Boards_BoardsWithShared(array(
					'filter_user_id' => JO_Session::get('user[user_id]')		
				));
				$this->view->boards = array();
				if($boards->count()) {
					foreach($boards AS $board) {
						$this->view->boards[] = array(
								'board_id' => $board['board_board_id'],
								'title' => $board['board_title']
						);
					}
				}
				
				
				$this->view->form_action = WM_Router::create( $request->getBaseUrl() . '?controller=addpin_fromfile&action=upload_images' );
				
				$this->view->upload_action = WM_Router::create( $request->getBaseUrl() . '?controller=addpin_fromfile&action=steptwo' );
			
				
				$this->view->from_url = WM_Router::create( $request->getBaseUrl() . '?controller=pin&action=createpin' );
			
				$this->view->file = JO_Session::get('upload_from_file_name');
				$this->view->full_path = $request->getBaseUrl() . JO_Session::get('upload_from_file');
				
			} else {
				$this->forward('addpin_fromfile', 'stepone', $this->translate('We couldn\'t find any images'));
			}
		} else {
			$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' ) );
// 			$this->view->redirect = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' );
		}
		
		
	}
	
	
	
	public function upload_imagesAction() {
		
		$request = $this->getRequest();
		
		if( JO_Session::get('user[user_id]') ) {
		
			if(JO_Session::get('upload_from_file')) {
				@unlink( BASE_PATH . JO_Session::get('upload_from_file') );
				JO_Session::clear('upload_from_file');
				JO_Session::clear('upload_from_file_name');
			}
			
			$image = $request->getFile('file');
			if(!$image) {
				 $this->view->error = $this->translate('There is no file selected');
			} else {
	
				$temporary = '/cache/review/';
				$upload_folder = BASE_PATH . $temporary;
				$upload = new Helper_Upload;
				
				$files_delete = glob($upload_folder . '*.*');
				
				if($files_delete) {
					for($i=0; $i<min(count($files_delete),250); $i++) {
						if((filemtime($files_delete[$i]) + 86400) < time()) {
							@unlink($files_delete[$i]);
						}
					}
				}
				
				$upload->setFile($image)
					->setExtension(array('.jpg','.jpeg','.png','.gif'))
					->setUploadDir($upload_folder);
					$new_name = md5(time() . serialize($image)); 
					if($upload->upload($new_name)) {
						$info = $upload->getFileInfo();
						if($info) {
							
							$config_image_minimum_size = (int)Helper_Config::get('config_image_minimum_size');
							if(!$config_image_minimum_size) { $config_image_minimum_size = 80; }
							
							if(isset($info['image_dimension']['x']) && $info['image_dimension']['x'] >= $config_image_minimum_size && $info['image_dimension']['y'] >= $config_image_minimum_size) {
							
								$this->view->from_url = WM_Router::create( $request->getBaseUrl() . '?controller=addpin_fromfile&action=stepone' );
		
								$this->view->success = 1;
								JO_Session::set('upload_from_file', $temporary . $info['name']);
								JO_Session::set('upload_from_file_name', $image['name']);
							
							} else {
								@unlink($upload_folder . $info['name']);
								$this->view->error = sprintf($this->translate('Photo size must be larger width and height of %s px'), $config_image_minimum_size);
							}
							
						} else {
							$this->view->error = $this->translate('An unknown error');
						}
					} else {
						$this->view->error = $upload->getError();
					}
			}
		} else {
			$this->view->location = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' );
		}
		
		echo $this->renderScript('json');
	}
	
}

?>