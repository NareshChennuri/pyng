<?php

class UsergroupsController extends Helper_Controller_Admin {
	
	public static function config() {

		return array(
			'name' => self::translate('Users Groups management'),
			'has_permision' => true,
			'menu' => self::translate('Users'),
			'in_menu' => true,
			'permision_key' => 'usergroups',
			'sort_order' => 22000
		);
	}
	
	/////////////////// end config
	
	private $session;
	
	public function init() {
		$this->session = JO_Session::getInstance();
	}
	
	public function indexAction() {
		if($this->session->get('successfu_edite')) {
    		$this->view->successfu_edite = true;
    		$this->session->clear('successfu_edite'); 
    	}
    	if($this->session->get('error_permision')) {
    		$this->view->error_permision = $this->session->get('error_permision');
    		$this->session->clear('error_permision'); 
    	} 
        
		$this->view->groups = array();
        $groups = Model_Usergroups::getGroups();
        if($groups) {
            
            foreach($groups AS $group) {
            	$group['description'] = html_entity_decode($group['description'], ENT_QUOTES, 'utf-8');
                $group['nodelete'] = array_key_exists($group['ug_id'], (array)unserialize(JO_Session::get('groups')));
            	
            	$this->view->groups[] = $group;
                
            }
        } 
	}
	
	public function createAction() {
	    if( !WM_Users::allow('create',  $this->getRequest()->getController()) ) {
			$this->forward('error', 'noPermission');
		}
		$this->setViewChange('form');
		
		if($this->getRequest()->isPost()) {
    		Model_Usergroups::createUserGroup($this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/usergroups/');
    	}
		
		$this->getForm();
	}
	
	public function editeAction() {
	    if( !WM_Users::allow('edit',  $this->getRequest()->getController()) ) {
			$this->session->set('error_permision', $this->translate('You do not have permission to this action'));
			$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/usergroups/');
		}
		$this->setViewChange('form');
		
		if($this->getRequest()->isPost()) {
    		Model_Usergroups::editeUserGroup($this->getRequest()->getQuery('id'), $this->getRequest()->getParams());
    		$this->session->set('successfu_edite', true);
    		$this->redirect($this->getRequest()->getBaseUrl() . $this->getRequest()->getModule() . '/usergroups/');
    	}
		
		$this->getForm();
	}
	
	public function deleteAction() {
		$this->setInvokeArg('noViewRenderer',true);
		if( !WM_Users::allow('delete',  $this->getRequest()->getController()) ) {
			echo $this->translate('You do not have permission to this action');
		} else {
		Model_Usergroups::deleteUserGroup($this->getRequest()->getPost('id'));
		}
	}
	
	private function getForm() {
		$request = $this->getRequest();
		
		$group_id = $request->getQuery('id');
		
		$modelGroup = new Model_Usergroups;
		
		if($group_id) {
			$group_info = $modelGroup->getUserGroup($group_id);
		}
		
		if($request->getPost('name')) {
			$this->view->name = $request->getPost('name');
		} elseif(isset($group_info)) {
			$this->view->name = $group_info['name'];
		}
		
		if($request->getPost('description')) {
			$this->view->description = $request->getPost('description');
		} elseif(isset($group_info)) {
			$this->view->description = $group_info['description'];
		}
		
		if($request->isPost()) {
			$this->view->access = (array)$request->getPost('access');
		} elseif(isset($group_info)) {
			$this->view->access = $group_info['access'];
		} else {
			$this->view->access = array();
		}
		
		//$access_modules = Helper_Config::get('temporary_for_permision');
		//$this->view->access_modules = array();
		/*foreach($access_modules AS $group => $models) {
			foreach($models AS $model) {
				if(isset($this->view->access_modules[$group])) {
					$this->view->access_modules[$group]['name'] = $this->view->access_modules[$group]['name'] . ', ' .$model['name'];
				} else {
					$this->view->access_modules[$group] = array(
						'key' => $model['key'],
						'name' => $model['name']
					);
				}
			}
		}*/
		
		$this->view->access_modules = array();
        
		$controllers = self::initPermision();
		
		if($controllers) {
			foreach($controllers AS $c) {
				$this->view->access_modules[$c['key']] = array(
					'title' => $c['name'],
					'table' => $c['key']
				);
                $sort_order[$c['key']] = trim(mb_strtolower($c['name'], 'utf-8'));
			}
		}

        array_multisort($sort_order, SORT_ASC, $this->view->access_modules);
		
		$this->view->permisions_types = array(
			'read' => $this->translate('Read'),
			'create' => $this->translate('Create'),
			'edit' => $this->translate('Edit'),
			'delete' => $this->translate('Delete')
		);
		
		foreach($this->view->permisions_types AS $type => $name) {
			
			if($request->isPost()) {
				if($request->issetPost('access['.$type.']')) {
					$this->view->access[$type] = $request->getPost('access['.$type.']');
				} else {
					$this->view->access[$type] = array();
				}
			} elseif(isset($group_info)) {
				if(isset($group_info['access'][$type]) && is_array($group_info['access'][$type])) {
					$this->view->access[$type] = $group_info['access'][$type];
				} else {
					$this->view->access[$type] = array();
				}
			} else {
				$this->view->access[$type] = array();
			}
			
		}
		
		
		
		
	} 
	
	public static function initPermision() {
	
		$request = JO_Request::getInstance();
		$temporary_for_permision = array();
		$files = glob(APPLICATION_PATH . '/modules/' . $request->getModule() . '/controllers/*.php');
		if($files) { 
			foreach($files AS $d => $file) {
				if( preg_match('/(.*)\/(.*)Controller.php/i', $file, $match) ) {
					$name = basename($file, '.php');
					if(!class_exists($name, false)) {
						JO_Loader::loadFile($file);
					}
					if(method_exists($name, 'config')) {
						$data = call_user_func(array($name, 'config'));
						if(isset($data['has_permision']) && $data['has_permision'] === true) {
							$temporary_for_permision[] = array(
								'name' => $data['name'],
								'key' => mb_strtolower($match[2])
							);
						}
					}
				}
			}
		} 
		return $temporary_for_permision;
	}

}

?>