<?php

class Addpin_GalleryController extends Helper_Controller_Default {

	public $SORT = 3;
	public $thumb_sizes = array();
	
	public function init() {
		if(!Helper_Config::get('config_gallery_pin')) {
			$this->noViewRenderer(true);
		}
	}

	public function indexAction() {
		
		$request = $this->getRequest();
		
		$this->view->pin_add_url = WM_Router::create( $request->getBaseUrl() . '?controller=addpin_gallery&action=stepone' );
		
	}
	
	public function steponeAction($error = null) {
		
		$this->noLayout(true);
		
		$request = $this->getRequest();
		
		$this->view->form_action = WM_Router::create( $request->getBaseUrl() . '?controller=addpin_gallery&action=steptwo' );
		$this->view->upload_action = WM_Router::create( $request->getBaseUrl() . '?controller=addpin_gallery&action=upload_images' );
		$this->view->js_i18n = WM_Router::create( $request->getBaseUrl() . '?controller=addpin_gallery&action=i18n' );
		$this->view->steptwo = WM_Router::create( $request->getBaseUrl() . '?controller=addpin_gallery&action=steptwo' );
		
		$this->view->files_limit = (int)Helper_Config::get('config_gallery_limit');
		
		if( JO_Session::get('user[user_id]') ) {
			
			$files = JO_Session::get('gallery_upload_images');
			if($files) {
				$imageObject = new Helper_Images();
				foreach($files AS $file) {
					$imageObject->deleteImages('/../' . $file['path'] . $file['tmp_name']);
				}
				JO_Session::clear('gallery_upload_images');
			}
			
			if($error) {
				$this->view->error_upload_image = $error;
			} 
			
		} else {
			$this->view->redirect = WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' );
		}
		
	}

	public function steptwoAction() {
		
		$request = $this->getRequest();
		
		$this->noLayout(true);
		
		if( JO_Session::get('user[user_id]') ) {
			$files = JO_Session::get('gallery_upload_images');
			if($files) {
				
				$this->view->from_url = WM_Router::create( $request->getBaseUrl() . '?controller=pin&action=createpin' );
				
				$this->view->createBoard = WM_Router::create( $request->getBaseUrl() . '?controller=boards&action=createboardwithoutcategory' );
				
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
				
				$imageObject = new Helper_Images();
				
				$this->view->images = array();
				foreach($files AS $file) {
					$this->view->images[] = array(
						'src' => $request->getBaseUrl() . $file['path'] . $file['tmp_name'],
						'thumb' => $imageObject->resize('/../' . $file['path'] . $file['tmp_name'], 75, 75, true)
					);
				}
				
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
				
			} else {
				$this->forward('addpin_gallery', 'stepone', true);
			}
		} else {
			$this->redirect( WM_Router::create( $request->getBaseUrl() . '?controller=users&action=login' ) );
		}
	}
	
	///////
	
	public function upload_imagesAction() {
		
		$request = $this->getRequest();
		
		if( JO_Session::get('user[user_id]') ) {
			
			$image = $request->getFile('file');
			if(!$image) {
				 $this->view->error = $this->translate('There is no file selected');
			} else {
	
				$temporary = '/cache/review/';
				$upload_folder = BASE_PATH . $temporary;
				$upload = new Helper_Upload;
				
				$files_delete = glob($upload_folder . '*.*');
				
				$imageObject = new Helper_Images();
				
				if($files_delete) {
					for($i=0; $i<min(count($files_delete),250); $i++) {
						if((filemtime($files_delete[$i]) + 86400) < time()) {
							$imageObject->deleteImages('/../' . $temporary . basename($files_delete[$i]));
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
							
							$files = JO_Session::get('gallery_upload_images');
							if(!$files) {
								$files = array();
							}
							
							$config_image_minimum_size = (int)Helper_Config::get('config_image_minimum_size');
							if(!$config_image_minimum_size) { $config_image_minimum_size = 80; }
							
							if(isset($info['image_dimension']['x']) && $info['image_dimension']['x'] >= $config_image_minimum_size && $info['image_dimension']['y'] >= $config_image_minimum_size) {
								$imageObject->resize('/../' . $temporary . $info['name'], 75, 75, true);
								
								$files[] = array(
									'name' => $image['name'],
									'tmp_name' => $info['name'],
									'path' => $temporary
								);
								
								JO_Session::set('gallery_upload_images', $files);
								
								$this->view->success = 1;
							} else {
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
	
	public function i18nAction() {
		$response = $this->getResponse();
		$response->addHeader('Cache-Control: no-cache, must-revalidate');
		$response->addHeader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		$response->addHeader('Content-type: application/javascript; charset=utf-8');
		
		$this->noLayout(true);
		
	}
	
}

?>