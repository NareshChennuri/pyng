<?php

class FilemanagerController extends Helper_Controller_Admin {
	
	public static function config() {
		return array(
			'name' => self::translate('Filemanager'),
			'has_permision' => false,
			'menu' => self::translate('Filemanager'),
			'in_menu' => false,
			'permision_key' => 'filemanager'
		);
	}
	
	/////////////////// end config

	private $upload_config;
	
	private $upload_folder;
	
	private $httpImages;
	
	public function init() {
		
		if(DIRECTORY_SEPARATOR != '/') {
			defined('DS') || define('DS', '/');
		} else {
			defined('DS') || define('DS', '/');
		}
		
		if(!defined('BASE_PATH')) {
			throw new JO_Exception('BASE_PATH not set!');
		}
		
		$this->upload_folder = realpath(BASE_PATH . DS . 'uploads');
		
		if(!$this->upload_folder || !file_exists($this->upload_folder) || !is_dir($this->upload_folder)) {
			throw new JO_Exception($this->translate('Upload folder not exist!'));
		}
		
		$this->httpImages = 'uploads';
	}
	
	public function indexAction() {
		$this->noLayout(true);
		
		$request = JO_Request::getInstance();
		
		$this->view->field = $request->getRequest('field','image');
		
		if($request->getQuery('CKEditorFuncNum')) {
			$this->view->fckeditor = $request->getQuery('CKEditorFuncNum');
		} else {
			$this->view->fckeditor = false;
		}
		
		$this->view->directory = rtrim($this->httpImages, '/');
		
	}
	
	public function imageAction() {
		$this->setInvokeArg('noViewRenderer',true);
		$image = $this->getRequest()->getRequest('image');
		
		if ($image !== null) {
			$models_images = new Helper_Images;
			echo $models_images->resize($image, 100, 100);
		}
	}
	
	public function directoryAction() {	
		$json = array();
		
		$directoryp = $this->getRequest()->getRequest('directory');
		
		if ($directoryp !== null) {
			$directories = glob(rtrim($this->upload_folder . str_replace('../', '', $directoryp), '/') . '/*', GLOB_ONLYDIR); 
			
			if ($directories) {
				$i = 0;
			
				foreach ($directories as $directory) {
					$name = basename($directory);
					if( in_array($name, array('cache', 'temp') ) ) continue;
					
					$json[$i]['data'] = basename($directory);
					$json[$i]['attributes']['directory'] = substr($directory, strlen($this->upload_folder));
					
					$children = glob(rtrim($directory, '/') . '/*', GLOB_ONLYDIR);
					
					if ($children)  {
						$json[$i]['children'] = ' ';
					}
					
					$i++;
				}
			}		
		}
		
		$response = $this->getResponse();
		$response->addHeader('Cache-Control: no-cache, must-revalidate');
    	$response->addHeader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    	$response->addHeader('Content-type: application/json');
		$this->setInvokeArg('noViewRenderer',true);
		
		echo JO_Json::encode($json);	
		
	}
	
	public function filesAction() {
		$json = array();
		
		$models_images = new Helper_Images;
		
		$directoryp = $this->getRequest()->getRequest('directory');
		
		if ($directoryp !== null) {
			$directory = $this->upload_folder . str_replace('../', '', $directoryp);
		} else {
			$directory = $this->upload_folder;
		}
		
		$allowed_images = array(
			'.jpg',
			'.jpeg',
			'.png',
			'.gif'
		);
		
		$allowed_files = array(
			'.doc',
			'.docx',
			'.rtf',
			'.txt',
			'.pdf',
			'.flv',
			'.mp4'
		);
		
		$files = glob(rtrim($directory, '/') . '/*');
		
		$files = is_array($files) ? $files : array();
		
		foreach ($files as $file) {
			if (is_file($file)) {
				$ext = strrchr($file, '.');
			} else {
				$ext = '';
			}	
			
			if (in_array(strtolower($ext), $allowed_images)) {
				$size = filesize($file);
	
				$file_size = $this->format_bytes($size);
					
				$json[] = array(
					'file'     => substr($file, strlen($this->upload_folder)),
					'filename' => basename($file),
					'size'     => $file_size,
					'thumb'    => $models_images->resize(substr($file, strlen($this->upload_folder)), 100, 100)
				);
			} elseif (in_array(strtolower($ext), $allowed_files)) {
				$size = filesize($file);
	
				$file_size = $this->format_bytes($size);
					
				$json[] = array(
					'file'     => substr($file, strlen($this->upload_folder)),
					'filename' => basename($file),
					'size'     => $file_size,
					'thumb'    => $models_images->resize('/files/file_'.trim(strtolower($ext),'.').'.png', 100, 100)
				);
			}
		}
		
		$response = $this->getResponse();
		$response->addHeader('Cache-Control: no-cache, must-revalidate');
    	$response->addHeader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    	$response->addHeader('Content-type: application/json');
		$this->setInvokeArg('noViewRenderer',true);
		
		echo JO_Json::encode($json);			
	}
	
	public function createAction() {
				
		$json = array();
		
		$directoryp = $this->getRequest()->getRequest('directory');
		$namep = $this->getRequest()->getRequest('name');
		
		if ($directoryp !== null) {
			if ($namep !== null) {
				$directory = rtrim($this->upload_folder . str_replace('../', '', $directoryp), '/');							   
				
				if (!is_dir($directory)) {
					$json['error'] = $directoryp . ' ' . $this->translate('is not a folder');
				}
				
				if (file_exists($directory . '/' . str_replace('../', '', $namep)) && is_dir($directory . '/' . str_replace('../', '', $namep))) {
					$json['error'] = $namep . ' ' . $this->translate('Already exists');
				}
			} else {
				$json['error'] = $this->translate('You did not enter a name');
			}
		} else {
			$json['error'] = $this->translate('You have not selected folder');
		}
		
		if (!isset($json['error'])) {	
			if(mkdir($directory . '/' . str_replace('../', '', $namep), 0777, true)) {
				$json['success'] = $namep . ' ' . $this->translate('has successfully created');
			} else {
				$json['error'] = $this->translate('The creation of') . ' ' . $namep . ' ' . $this->translate('Failed!');
			}
		}	
		
		$response = $this->getResponse();
		$response->addHeader('Cache-Control: no-cache, must-revalidate');
    	$response->addHeader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    	$response->addHeader('Content-type: application/json');
		$this->setInvokeArg('noViewRenderer',true);
		
		echo JO_Json::encode($json);		
	}
	
	public function deleteAction() {
		
		$json = array();
		
		$pathp = $this->getRequest()->getPost('path');
		
		if ($pathp !== null) {
			$path = rtrim($this->upload_folder . str_replace('../', '', $pathp), '/');
			 
			if (!file_exists($path)) {
				$json['error'] = $this->translate('Path not found');
			}
			
			if (rtrim($path, '/') == rtrim($this->upload_folder, '/')) {
				$json['error'] = $this->translate('You can not delete the base path');
			}
		} else {
			$json['error'] = $this->translate('No path is selected');
		}
		
		
		if (!isset($json['error'])) {
			if (is_file($path)) {
				$images = new Helper_Images;
				$images->deleteImages($pathp);
			} elseif (is_dir($path)) {
				$this->recursiveDelete($path);
				$cache_folder = $this->upload_folder . DS . 'cache' . $pathp;
				$this->recursiveDelete($cache_folder);
			}
			
			$json['success'] = $this->translate('Deletion is successful');
		}				
		
		$response = $this->getResponse();
		$response->addHeader('Cache-Control: no-cache, must-revalidate');
    	$response->addHeader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    	$response->addHeader('Content-type: application/json');
		$this->setInvokeArg('noViewRenderer',true);
		
		echo JO_Json::encode($json);
	}
	
	public function moveAction() {
		
		$json = array();
		
		$fromp = $this->getRequest()->getPost('from');
		$top = $this->getRequest()->getPost('to');
		
		if ($fromp !== null && $top !== null) {
			$from = rtrim($this->upload_folder . str_replace('../', '', $fromp), '/');
			
			if (!file_exists($from)) {
				$json['error'] = $this->translate('Path not found');
			}
			
			if (rtrim($from, '/') == rtrim($this->upload_folder, '/')) {
				$json['error'] = $this->translate('Unable to move the base folder');
			}
			
			$to = rtrim($this->upload_folder . str_replace('../', '', $top), '/');

			if (!file_exists($to)) {
				$json['error'] = $this->translate('Final path was not found');
			}	
			
			if (file_exists($to . '/' . basename($from))) {
				$json['error'] = $this->translate('File/folder already exists');
			}
		} else {
			$json['error'] = $this->translate('Error selecting folders/files');
		}
		
		
		if (!isset($json['error'])) {
			rename($from, $to . '/' . basename($from));
			
			$json['success'] = $this->translate('Move was successful');
		}
		
		$response = $this->getResponse();
		$response->addHeader('Cache-Control: no-cache, must-revalidate');
    	$response->addHeader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    	$response->addHeader('Content-type: application/json');
		$this->setInvokeArg('noViewRenderer',true);
		
		echo JO_Json::encode($json);
	}
	
	public function copyAction() {
		
		$json = array();
		
		$pathp = $this->getRequest()->getPost('path');
		$namep = $this->getRequest()->getRequest('name');
		
		if ($pathp !== null && $namep !== null) {
			if ((strlen(utf8_decode($namep)) < 3) || (strlen(utf8_decode($namep)) > 64)) {
				$json['error'] = $this->translate('The name must be from 3 to 64 characters');
			}
				
			$old_name = rtrim($this->upload_folder . str_replace('../', '', $pathp), '/');
			
			if (!file_exists($old_name) || rtrim($old_name,'/') == rtrim($this->upload_folder, '/')) {
				$json['error'] = $this->translate('Error copying');
			}
			
			if (is_file($old_name)) {
				$ext = strrchr($old_name, '.');
			} else {
				$ext = '';
			}		
			
			$new_name = dirname($old_name) . '/' . str_replace('../', '', $namep . $ext);
																			   
			if (file_exists($new_name)) {
				$json['error'] = $this->translate('Folder/file exists');
			}			
		} else {
			$json['error'] = $this->translate('Error selecting folders/files');
		}
		
		
		if (!isset($json['error'])) {
			if (is_file($old_name)) {
				copy($old_name, $new_name);
			} else {
				$this->recursiveCopy($old_name, $new_name);
			}
			
			$json['success'] = $this->translate('Copying was successful');
		}
		
		$response = $this->getResponse();
		$response->addHeader('Cache-Control: no-cache, must-revalidate');
    	$response->addHeader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    	$response->addHeader('Content-type: application/json');
		$this->setInvokeArg('noViewRenderer',true);
		
		echo JO_Json::encode($json);		
	}
	
	public function foldersAction() {
		$this->setInvokeArg('noViewRenderer',true);
		echo $this->recursiveFolders($this->upload_folder);
	}
	
	public function renameAction() {
		
		$json = array();
		
		$pathp = $this->getRequest()->getPost('path');
		$namep = $this->getRequest()->getRequest('name');
		
		if ($pathp !== null && $namep !== null) {
			if ((strlen(utf8_decode($pathp)) < 3) || (strlen(utf8_decode($pathp)) > 64)) {
				$json['error'] = $this->translate('The name must be from 3 to 64 characters');
			}
				
			$old_name = rtrim($this->upload_folder . str_replace('../', '', $pathp), '/');
			
			if (!file_exists($old_name) || rtrim($old_name,'/') == rtrim($this->upload_folder,'/')) {
				$json['error'] = $this->translate('You can not rename the base folder');
			}
			
			if (is_file($old_name)) {
				$ext = strrchr($old_name, '.');
			} else {
				$ext = '';
			}		
			
			$new_name = dirname($old_name) . '/' . str_replace('../', '', $namep . $ext);
																			   
			if (file_exists($new_name) && is_dir($new_name)) {
				$json['error'] = $this->translate('Folder exists');
			} elseif (file_exists($new_name) && is_file($new_name)) {
				$json['error'] = $this->translate('File exists');
			}						
		}
		
		if (!isset($json['error'])) {
			rename($old_name, $new_name);
			
			$json['success'] = $this->translate('The renaming was successful');
		}
		
		$response = $this->getResponse();
		$response->addHeader('Cache-Control: no-cache, must-revalidate');
    	$response->addHeader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    	$response->addHeader('Content-type: application/json');
		$this->setInvokeArg('noViewRenderer',true);
		
		echo JO_Json::encode($json);
	}
	
	public function uploadAction() {
		
		$json = array();
		
		$directoryp = $this->getRequest()->getPost('directory');
		
		if ($directoryp !== null) {
			$file = $this->getRequest()->getFile('image');
			if ($file !== null && $file['tmp_name']) {
				if ((strlen(utf8_decode($file['name'])) < 3) || (strlen(utf8_decode($file['name'])) > 255)) {
					$json['error'] = $this->translate('The name must be from 3 to 255 characters');
				}
					
				$directory = rtrim($this->upload_folder . str_replace('../', '', $directoryp), '/');
				
				if (!is_dir($directory)) {
					$json['error'] = $this->translate('Directory not found');
				}
				
				if ($file['size'] > 2097152000000) {
					$json['error'] = $this->translate('The size must be less than') . ' ' . $this->format_bytes(2097152000000);
				}
				
//				$allowed = array(
//					'image/jpeg',
//					'image/pjpeg',
//					'image/png',
//					'image/x-png',
//					'image/gif'
//				);
//						
//				if (!in_array($file['type'], $allowed)) {
//					$json['error'] = 'Файлът не е валидна снимка';
//				}
				
				$allowed = array(
					'.jpg',
					'.jpeg',
					'.gif',
					'.png',
					'.doc',
					'.docx',
					'.rtf',
					'.txt',
					'.pdf',
					'.flv',
					'.mp4'
				);
						
				if (!in_array(strtolower(strrchr($file['name'], '.')), $allowed)) {
					$json['error'] = $this->translate('Extensions are permitted:') . ' ' . implode($allowed);
				}

				
				if ($file['error'] != UPLOAD_ERR_OK) {
					$json['error'] = 'error_upload_' . $file['error'];
				}			
			} else {
				$json['error'] = $this->translate('Not selected a file to upload');
			}
		} else {
			$json['error'] = $this->translate('Not selected folder');
		}
		
		
		$file_name = self::rename_if_exists($directory .'/', basename($file['name']));
		
		if (@move_uploaded_file($file['tmp_name'], $directory . '/' . $file_name)) {		
			$json['success'] = $this->translate('Upload is successful');
			if($file_name != basename($file['name'])) {
				$json['success'] .= "\n" . $this->translate('The file name was changed to:') . " " . $file_name;
			}
		} else {
			$json['error'] = $this->translate('Upload failed');
		}
		
		$response = $this->getResponse();
		$response->addHeader('Cache-Control: no-cache, must-revalidate');
    	$response->addHeader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
//    	$response->addHeader('Content-type: application/json');
		$this->setInvokeArg('noViewRenderer',true);
		
		echo JO_Json::encode($json);
	}
	
	public function multiuploadAction() {
		$this->setInvokeArg('noViewRenderer',true);
		
		$json = array();
		
		$directoryp = $this->getRequest()->getPost('directory');
		if(!$directoryp) {
			$directoryp = '/';
		}
//		var_dump($this->getRequest()->getFile('Filedata')); exit;
		if ($directoryp !== null) {
			$files = $this->getRequest()->getFile('Filedata');
			if($files && $files['name']) {
				
				foreach($files['name'] AS $row => $file) {
					
					if(!isset($files['name'][$row]) || !$files['name'][$row]) continue;
					
					if ((strlen(utf8_decode($file)) < 3) || (strlen(utf8_decode($file)) > 255)) {
						$json['error'] = $this->translate('The name must be from 3 to 255 characters');
					}
						
					$directory = rtrim($this->upload_folder . str_replace('../', '', $directoryp), '/');
					
					if (!is_dir($directory)) {
						$json['error'] = $this->translate('Directory not found');
					}
					
					if ($files['size'][$row] > 2097152000000) {
						$json['error'] = $this->translate('The size must be less than') . ' ' . $this->format_bytes(2097152000000);
					}
					
	//				$allowed = array(
	//					'image/jpeg',
	//					'image/pjpeg',
	//					'image/png',
	//					'image/x-png',
	//					'image/gif'
	//				);
	//						
	//				if (!in_array($file['type'], $allowed)) {
	//					$json['error'] = 'Файлът не е валидна снимка';
	//				}
					
					$allowed = array(
						'.jpg',
						'.jpeg',
						'.gif',
						'.png',
						'.doc',
						'.docx',
						'.rtf',
						'.txt',
						'.pdf',
						'.flv',
						'.mp4'
					);
							
					if (!in_array(strtolower(strrchr($file, '.')), $allowed)) {
						$json['error'] = $this->translate('Extensions are permitted:') . ' ' . implode($allowed);
					}
	
					
					if ($file['error'] != UPLOAD_ERR_OK) {
						$json['error'] = 'error_upload_' . $files['error'][$row];
					}	

					$file_name = self::rename_if_exists($directory .'/', basename($file));
			
					if (@move_uploaded_file($files['tmp_name'][$row], $directory . '/' . $file_name)) {		
						$json['success'] = $this->translate('Upload is successful');
						if($file_name != basename($file)) {
							$json['success'] .= "\n" . $this->translate('The file name was changed to:') . " " . $file_name;
						}
					} else {
						$json['error'] = $this->translate('Upload failed');
					}
				
				}
				
			} else {
				$json['error'] = $this->translate('Not selected a file to upload');
			}
		} else {
			$json['error'] = $this->translate('Not selected folder');
		}
		
		$response = $this->getResponse();
		$response->addHeader('Cache-Control: no-cache, must-revalidate');
    	$response->addHeader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
//    	$response->addHeader('Content-type: application/json');
		$this->setInvokeArg('noViewRenderer',true);
		
		echo JO_Json::encode($json);
		
	}
	
	/******************************** HELP FUNCTIONS *****************************************/
	
	private function rename_if_exists($dir, $filename) {
	    $ext = strrchr($filename, '.');
	    $prefix = substr($filename, 0, -strlen($ext));
	    $i = 0;
	    while(file_exists($dir . $filename)) { // If file exists, add a number to it.
	        $filename = $prefix . '[' .++$i . ']' . $ext;
	    }
	    return $filename;
	}
	
	private function format_bytes($size) {
	    $units = array(' B', ' KB', ' MB', ' GB', ' TB');
	    for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
	    return round($size, 2).$units[$i];
	}
	
	protected function recursiveDelete($directory) {
		if (is_dir($directory) && file_exists($directory)) {
			$handle = opendir($directory);
		} else {
			return FALSE;
		}
		
		while (false !== ($file = readdir($handle))) {
			if ($file != '.' && $file != '..') {
				if (!is_dir($directory . '/' . $file)) {
					unlink($directory . '/' . $file);
				} else {
					$this->recursiveDelete($directory . '/' . $file);
				}
			}
		}
		
		closedir($handle);
		
		rmdir($directory);
		
		return TRUE;
	}
	
	private function recursiveCopy($source, $destination) { 
		$directory = opendir($source); 
		
		@mkdir($destination, 0777, true); 
		
		while (false !== ($file = readdir($directory))) {
			if (($file != '.') && ($file != '..')) { 
				if (is_dir($source . '/' . $file)) { 
					$this->recursiveCopy($source . '/' . $file, $destination . '/' . $file); 
				} else { 
					copy($source . '/' . $file, $destination . '/' . $file); 
				} 
			} 
		} 
		
		closedir($directory); 
	} 
	
	protected function recursiveFolders($directory) {
		$output = '';
		
		$output .= '<option value="' . substr($directory, strlen($this->upload_folder)) . '">' . substr($directory, strlen($this->upload_folder)) . '</option>';
		
		$directories = glob(rtrim(str_replace('../', '', $directory), '/') . '/*', GLOB_ONLYDIR);
		
		$directories = is_array($directories) ? $directories : array();
		
		foreach ($directories  as $directory) {
			$output .= $this->recursiveFolders($directory);
		}
		
		return $output;
	}
	
	
}

?>