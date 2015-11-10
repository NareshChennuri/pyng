<?php

class PagesController extends Helper_Controller_Admin  {
	
	public static function config() {
		return array(
			'name' => self::translate('Information & pages'),
			'has_permision' => true,
			'menu' => self::translate('Catalog'),
			'in_menu' => true,
			'permision_key' => 'pages',
			'sort_order' => 80500
		);
	}
	
	/////////////////// end config
	
	private $session;
	
	public function init() {
		$this->session = JO_Session::getInstance();
	}
	
	public function indexAction() {
		$request = $this->getRequest();
		
		$pages_module = new Model_Pages();
		
		if($this->session->get('successfu_edite')) {
    		$this->view->successfu_edite = true;
    		$this->session->clear('successfu_edite'); 
    	} 
    	if($this->session->get('error_permision')) {
    		$this->view->error_permision = $this->session->get('error_permision');
    		$this->session->clear('error_permision'); 
    	} 
    	
    	$this->session->set('come_from_index', true);
    	
    	$parent_id = (int)$request->getQuery('parent_id');
    	$page_info = Model_Pages::getPage($parent_id);
    	if($page_info) {
    		$this->view->parent_title = $page_info['title'];
    		$this->view->back_url = $request->getModule() . '/pages/' . ($page_info['parent_id'] ? '?parent_id=' . $page_info['parent_id'] : '');
    	} else {
    		$parent_id = 0;
    	}
    	
    	$this->view->new_record_url = $request->getModule() . '/pages/create/' . ($parent_id ? '?parent_id=' . $parent_id : '');
		$this->view->about_url = $request->getModule() . '/pages/about/';
		
    	$page_num = $this->getRequest()->getRequest('page', 1);
    	$data = array(
			'start' => ($page_num * Helper_Config::get('config_admin_limit')) - Helper_Config::get('config_admin_limit'),
			'limit' => Helper_Config::get('config_admin_limit'),
    		'parent_id' => $parent_id
		);
    	
		$this->view->pages = array();
		$pages = $pages_module->getPages($data);

		if($pages) {
		    foreach($pages AS $page) {
			  $page['href'] = WM_Router::create(JO_Request::getInstance()->getBaseUrl() . '?page_id=' . $page['page_id']);
			  $page['edit'] = $request->getModule() . '/pages/edit/?id=' . $page['page_id'] . ($parent_id ? '&parent_id=' . $parent_id : '');
			  $page['childrens'] = $request->getModule() . '/pages/?parent_id=' . $page['page_id'];
			  $this->view->pages[] = $page;
		    }
		}
		
		$total_records = $pages_module->getTotalPages($data);
		$total_pages = ceil($total_records / Helper_Config::get('config_admin_limit'));
		
		$pagination = new Model_Pagination;
		$pagination->setLimit(Helper_Config::get('config_admin_limit'));
		$pagination->setPage($page_num);
		$pagination->setTotal($total_records);
		$pagination->setUrl($this->getRequest()->getModule() . '/pages/?page={page}' . ($parent_id ? '&parent_id=' . $parent_id : ''));
		$this->view->pagination = $pagination->render();
		$this->view->pagination_text = str_replace(
			array('{$page}', '{$total_pages}', '{$total_records}'), 
			array($page_num, $total_pages, $total_records), 
			$this->translate('Page {$page} from {$total_pages} ({$total_records} records)'));
		
		
	}
	
	public function aboutAction() {

		$request = $this->getRequest();
		
		if($request->isPost()) {
			Model_Pages::updatePagesMenu($request->getPost('about_menu'));
			$this->session->set('successfu_edite', true);
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/pages/');
		}
		
		$this->view->cancel_url = $request->getModule() . '/pages/';
		
		$pages = Model_Pages::getPagesFromParent(0);
		
		$this->view->pages_about = array();
		$this->view->pages_about[] = array(
				'title' => $this->translate('SEPARATOR MENU'),
				'page_id' => -1
		);
		if($pages) {
			foreach($pages AS $p) {
				$this->view->pages_about[] = array(
						'title' => $p['title'],
						'page_id' => $p['page_id']
				);
			}
		}
		
		$this->view->about_menu = Model_Pages::getPagesMenu();
		
	}
	
	public function createAction() {
		if( !WM_Users::allow('create',  $this->getRequest()->getController()) ) {
			$this->forward('error', 'noPermission');
		}
		
		$this->setViewChange('form_pages');
		if($this->getRequest()->isPost()) {
    		Model_Pages::createPage($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/pages/' . ( $this->getRequest()->getQuery('parent_id') ? '?parent_id=' . $this->getRequest()->getQuery('parent_id') : '' ));
    	}
    	
    	if(JO_Session::get('come_from_index') === true) {
    		$temporary_images = JO_Session::get('temporary_images');
			if($temporary_images) {
				foreach($temporary_images AS $key => $image) {
					$mi = new Helper_Images();
					$mi->deleteImages($image['image']);
				}
			}
			JO_Session::clear('come_from_index');
			JO_Session::clear('temporary_images');
    	}
    	
		$this->getPageForm();
	}
	
	public function editAction() {
		if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			$this->session->set('error_permision', $this->translate('You do not have permission to this action'));
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/pages/');
		}
		$this->setViewChange('form_pages');
		if($this->getRequest()->isPost()) {
    		Model_Pages::editePage($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/pages/' . ( $this->getRequest()->getQuery('parent_id') ? '?parent_id=' . $this->getRequest()->getQuery('parent_id') : '' ));
    	}
		$this->getPageForm();
	}
	
	public function changeStatusAction() {
		$this->noViewRenderer(true);
		if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
			Model_Pages::changeStatus($this->getRequest()->getPost('id'));
			echo 'ok';
		}
	}
	
	public function changeStatusMultiAction() {
		if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			$this->session->set('error_permision', $this->translate('You do not have permission to this action'));
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/pages/' . ( $this->getRequest()->getQuery('parent_id') ? '?parent_id=' . $this->getRequest()->getQuery('parent_id') : '' ));
		}
		$this->noViewRenderer(true);
		$action_check = $this->getRequest()->getPost('action_check');
		if($action_check && is_array($action_check)) {
			foreach($action_check AS $record_id) {
				Model_Pages::changeStatus($record_id);
			}
		}
	}
	
	public function deleteAction() {
		$this->noViewRenderer(true);
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
			Model_Pages::deletePage($this->getRequest()->getPost('id'));
			echo 'ok';
		}
	}
	
	public function deleteMultiAction() {
		$this->noViewRenderer(true);
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			$this->session->set('error_permision', $this->translate('You do not have permission to this action'));
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/pages/' . ( $this->getRequest()->getQuery('parent_id') ? '?parent_id=' . $this->getRequest()->getQuery('parent_id') : '' ));
		}
		$action_check = $this->getRequest()->getPost('action_check');
		if($action_check && is_array($action_check)) {
			foreach($action_check AS $record_id) {
				Model_Pages::deletePage($record_id);
			}
		}
	}
	
	public function sort_orderAction() {
		$this->noViewRenderer(true);
		if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
			$sort_order_data = $this->getRequest()->getPost('sort_order');
			foreach($sort_order_data AS $sort_order => $post_id) {
				if($post_id) {
					Model_Pages::changeSortOrder($post_id, $sort_order);
				}
			}
			echo 'ok';
		}
	}
	
	
	
	/***************************************** IMAGES FUNCTIONS ********************************************/
	
	
	public function editeImageInfoAction() {
		if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			$this->forward('error', 'noPermission');
		}
		$this->noLayout(true);
		
		$image_id = $this->getRequest()->getQuery('id');
		
		$model_gallery = new Model_Gallery;
		
		if($this->getRequest()->isPost()) {
			if($this->getRequest()->getPost('session_edit')) {
				$temporary_images = JO_Session::get('temporary_images');
				if(isset($temporary_images[$image_id])) {
					$temporary_images[$image_id]['title'] = $this->getRequest()->getPost('title');
					$temporary_images[$image_id]['description'] = $this->getRequest()->getPost('description');
				}
				JO_Session::set('temporary_images', $temporary_images);
				exit('ok');
			} else {
				$model_gallery->updateImageInfo($image_id, $this->getRequest()->getParams());
			}
			exit('ok');
		}
		
		$this->view->image_id = $image_id;
		
		$image_info = $model_gallery->getImage($image_id);
		
		if(!$image_info) {
			$image_info = JO_Session::get('temporary_images['.$image_id.']');
			$this->view->session_edit = 'true';
		}
		
		if(!$image_info && !$this->view->error) {
			$this->view->error = $this->translate('Picture not found');
		} else {
			$model_image = new Helper_Images;
			$this->view->image = $image_info['image'];
			$this->view->preview = $model_image->resize($image_info['image'], 100, 100);
		}
		
		if($image_info) {
			$this->view->title = $image_info['title'];
		} else {
			$this->view->title = '';
		}
		
		if($image_info) {
			$this->view->description = $image_info['description'];
		} else {
			$this->view->description = '';
		}
		
	}
	
	public function rotateImageAction() {
		if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			$this->forward('error', 'noPermission');
		}
		$this->setInvokeArg('noViewRenderer',true);
		
		$deg = $this->getRequest()->getPost('deg');
		$file = $this->getRequest()->getPost('file');
		
		if(!$file || !$deg) {
			exit('error');
		}
		
		$model_images = new Helper_Images;
		$upload_folder  = realpath(BASE_PATH . '/uploads');
		
		if(!file_exists($upload_folder . $file) || !is_file($upload_folder . $file)) {
			exit('error');
		}
		
		if($deg == 'left') {
			$model_images->deleteImages($file, false);
			$image_object = new JO_GDThumb($upload_folder . $file);
			$image_object->rotate(90);
			$image_object->save($upload_folder . $file);
			echo $model_images->resize($file, 100, 100) . '?time=' . time();
		} elseif($deg == 'right') {
			$model_images->deleteImages($file, false);
			$image_object = new JO_GDThumb($upload_folder . $file);
			$image_object->rotate(-90);
			$image_object->save($upload_folder . $file);
			echo $model_images->resize($file, 100, 100) . '?time=' . time();
		} else {
			echo 'error';
		}
		
	}
	
	public function uploadImagesAction() {
		if( !WM_Users::allow('create',  $this->getRequest()->getController()) ) {
			$this->forward('error', 'noPermission');
		}
		
		$gallery_id = (int)$this->getRequest()->getRequest('id');
		
		$page_info = Model_Pages::getPage($gallery_id);

		$image = $this->getRequest()->getFile('Filedata');
		if(!$image && $this->view->error) {
			 $this->view->error = $this->translate('Invalid file');
		}
		
		if($page_info) {
			$gallery_path = '/gallery/' . date("Y/m/", strtotime($page_info['date_added']));
		} else {
			$gallery_path = '/temp/gallery/';
		}
		
		$upload_folder  = realpath(BASE_PATH . '/uploads');
		$upload_folder .= $gallery_path;
		
		$upload = new JO_Upload;
		$upload->setFile($image)
				->setExtension(array('.jpg','.jpeg','.png','.gif'))
				->setUploadDir($upload_folder);
		
		$new_name = md5(time() . serialize($image)); 
		if($upload->upload($new_name)) {
			$info = $upload->getFileInfo();
			if($info) {
				$file_path = $gallery_path . $info['name'];
				
				$data = array(
					'gallery_id' => $gallery_id,
					'image' => $file_path,
					'controller' => 'pages'
				);
				
				if($page_info) {
				
					$insert_id = Model_Gallery::createImage($data);
					
					if($insert_id) {
						$model_images = new Helper_Images();
						$this->view->id = $insert_id;
						$this->view->thumb = $model_images->resize($file_path, 100, 100);
						$this->view->image = $this->getRequest()->getBaseUrl() . 'uploads' . $file_path;
					} else {
						$this->view->error = $this->translate('There was an error record. Try Again ');
						@unlink($upload_folder . $info['name']);
					}
				
				} else {
					$temporary_images = JO_Session::get('temporary_images');
					if(!is_array($temporary_images)) {
						$temporary_images = array();
					}
					$temporary_images[] = $data;
					JO_Session::set('temporary_images', $temporary_images);
					$model_images = new Helper_Images;
					$this->view->id = (count($temporary_images)-1);
					$model_images = new Helper_Images();
					$this->view->thumb = $model_images->resize($file_path, 100, 100);
					$this->view->image = $this->getRequest()->getBaseUrl() . 'uploads' . $file_path;
				}
				
			} else {
				$this->view->error = $this->translate('An unknown error');
			}
		} else {
			$this->view->error = $upload->getError();
		}
		
		
		$response = $this->getResponse();
		$response->addHeader('Cache-Control: no-cache, must-revalidate');
    	$response->addHeader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    	$response->addHeader('Content-type: application/json');
    	
    	echo $this->renderScript('json');
	}
	
	public function sortImageAction() {
		if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			$this->forward('error', 'noPermission');
		}
		
		$gallery_id = (int)$this->getRequest()->getRequest('id');
		
		$this->setInvokeArg('noViewRenderer',true);
		
		$ids = $this->getRequest()->getPost('ids');
		
		$temporary_images = JO_Session::get('temporary_images');
		
		$temp = array();
		
		if(!$ids) {
			echo $this->translate('An unknown error');
		} else {
			foreach($ids AS $sort_order => $image_id) {
				if($gallery_id) {
					Model_Gallery::sortOrderImages($image_id, ($sort_order+1));
				} else {
					if(isset($temporary_images[$image_id])) {
						$temp[$sort_order] = $temporary_images[$image_id];
					}
				}
			}
			if(!$gallery_id) {
				JO_Session::set('temporary_images', $temp);
			}
			echo $this->translate('Sorting is successful');
		}
	}
	
	public function deleteImageAction() {
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			$this->forward('error', 'noPermission');
		}
		
		$gallery_id = (int)$this->getRequest()->getRequest('id');
		
		$this->setInvokeArg('noViewRenderer',true);
		$image_id = $this->getRequest()->getPost('id');
		
		if($gallery_id) {
			echo Model_Gallery::deleteImage($image_id);
		} else {
			$temporary_images = JO_Session::get('temporary_images');
			$temp = array();
			foreach($temporary_images AS $key => $image) {
				if($key != $image_id) {
					$temp[] = $image;
				} else {
					$mi = new Helper_Images();
					$mi->deleteImages($image['image']);
				}
			}
			echo 'ok';
			JO_Session::set('temporary_images', $temp);
		}
		
	}
	
	public function swfConfigAction() {
		$this->noLayout(true);
		$response = $this->getResponse();
		$response->addHeader('content-type: application/x-javascript; charset=utf-8');
	}
	
	/***************************************** HELP FUNCTIONS ********************************************/
	
	private function getPageForm() {
		$request = $this->getRequest();
		
		$page_id = $request->getQuery('id');
		
		$pages_module = new Model_Pages();
		
		if($page_id) {
			$page_info = $pages_module->getPage($page_id);
		}
		
		$parent_id = (int)$request->getQuery('parent_id');
    	$parent_info = Model_Pages::getPage($parent_id);
    	if($parent_info) {
    		$this->view->parent_title = $parent_info['title'];
    	}
    	
    	$this->view->page_id = $page_id;
    	
    	$this->view->cancel_url = $request->getModule() . '/pages/' . ($parent_id ? '?parent_id=' . $parent_id : '');
	
		if($request->getPost('in_footer')) {
    		$this->view->in_footer = $request->getPost('in_footer');
    	} elseif(isset($page_info)) {
    		$this->view->in_footer = $page_info['in_footer'];
    	} else {
    		$this->view->in_footer = 0;
    	}
    	
		if($request->getPost('status')) {
    		$this->view->status = $request->getPost('status');
    	} elseif(isset($page_info)) {
    		$this->view->status = $page_info['status'];
    	} else {
    		$this->view->status = 1;
    	}
    	
		if($request->getPost('title')) {
    		$this->view->title = $request->getPost('title');
    	} elseif(isset($page_info)) {
    		$this->view->title = $page_info['title'];
    	}
    	
		if($request->getPost('description')) {
    		$this->view->description = $request->getPost('description');
    	} elseif(isset($page_info)) {
    		$this->view->description = $page_info['description'];
    	}
    	
		if($request->getPost('meta_title')) {
    		$this->view->meta_title = $request->getPost('meta_title');
    	} elseif(isset($page_info)) {
    		$this->view->meta_title = $page_info['meta_title'];
    	}
    	
		if($request->getPost('meta_description')) {
    		$this->view->meta_description = $request->getPost('meta_description');
    	} elseif(isset($page_info)) {
    		$this->view->meta_description = $page_info['meta_description'];
    	}
    	
		if($request->getPost('meta_keywords')) {
    		$this->view->meta_keywords = $request->getPost('meta_keywords');
    	} elseif(isset($page_info)) {
    		$this->view->meta_keywords = $page_info['meta_keywords'];
    	}
    	
		if($request->getRequest('keyword')) {
    		$this->view->keyword = $request->getRequest('keyword');
    	} elseif(isset($page_info)) {
    		$this->view->keyword = $page_info['keyword'];
    	}
	
		if(isset($page_info)) {
			$images = Model_Gallery::getGalleryImages($page_id, 'pages');
			
			if($images) {
				$model_images = new Helper_Images;
				$this->view->images = array();
	 			foreach($images AS $image) {
					$this->view->images[] = array(
						'image_id' => $image['image_id'],
						'image' => 'uploads' . $image['image'],
						'thumb' => $model_images->resize($image['image'], 100, 100),
						'title' => $image['title']
					);
				}
			}
		} else {
			$temporary_images = JO_Session::get('temporary_images'); 
			if($temporary_images) {
				$model_images = new Helper_Images;
				$this->view->images = array();
	 			foreach($temporary_images AS $key => $image) {
					$this->view->images[] = array(
						'image_id' => $key,
						'image' => 'uploads' . $image['image'],
						'thumb' => $model_images->resize($image['image'], 100, 100),
						'title' => isset($image[Helper_Config::get('config_language_id')]['title']) ?$image[Helper_Config::get('config_language_id')]['title'] : ''
					);
				}
			}
		}
		
	}
	
	
  
}