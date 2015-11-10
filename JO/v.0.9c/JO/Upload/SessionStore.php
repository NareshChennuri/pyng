<?php

class JO_Upload_SessionStore {

    private $the_file;
	private $the_temp_file;
	private $http_error;
	private $size;
	private $mime;

	private $extensions = array(".png", ".jpg", ".jpeg");
	private $error_message = array();
	protected $message;
	private $max_size = 0;
	private $tmp_name = 'session_upload';
	private $server_upload_max_filesize = 0;
	
	public function __construct($file = '') { 
		if(is_array($file)) {
			if($file['size'] == 0) {
				$file['error'] = 5;
			}
			$this->the_file = $file['name'];
			$this->http_error = $file['error'];
			$this->the_temp_file = $file['tmp_name'];
			$this->size = $file['size'];
			$this->mime = $file['type'];
		} else {
			$this->the_file = '';
			$this->http_error = '';
			$this->the_temp_file = '';
			$this->size = '';
			$this->mime = '';
		}
		
		$this->server_upload_max_filesize = $this->format_bytes($this->return_bytes(ini_get('upload_max_filesize')));
	}
	
	public function format_bytes($size) {
	    $units = array(' B', ' KB', ' MB', ' GB', ' TB');
	    for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
	    return round($size, 2).$units[$i];
	}
	
	public function return_bytes($val) {
	    $val = trim($val);
	    if(preg_match('/^(([\d]{1,})([\s]{0,})?([a-z]{1,}))+$/i', $val, $match)) { 
	    	$match[3] = mb_strtolower($match[4], 'utf-8');
	    	$match[2] = (int)$match[2];
		    if($match[3] == 't' || $match[3] == 'tb') {
		        $val = $match[2]*1024*1024*1024*1024;
		    } elseif($match[3] == 'g' || $match[3] == 'gb') {
		        $val = $match[2]*1024*1024*1024;
		    } elseif($match[3] == 'm' || $match[3] == 'mb') {
		        $val = $match[2]*1024*1024;
		    } elseif($match[3] == 'k' || $match[3] == 'kb') {
		        $val = $match[2]*1024;
		    } elseif($match[3] == 'b') {
		        $val = $match[2];
		    }
	    }
	        
	    return (int)$val;
	}
	
	public function setMaxSize($size) {
		$this->max_size = $this->return_bytes($size);
		return $this;
	}
	
	public function getError() {
		return $this->message;
	}
	
	public function setErrorMessage($key, $message) {
		$this->error_message[$key] = $message;
		return $this;
	}
	
	public function setName($name) {
		$this->tmp_name = $name;
		return $this;
	}
	
	/**
	 * @return JO_Upload_SessionStore
	 */
	public function resetExtensions() {
		$this->extensions = array();
		return $this;
	}
	
	/**
	 * @param string||array $extension
	 * @return JO_Upload_SessionStore
	 */
	public function setExtension($extension) {
		if(is_array($extension)) {
			foreach($extension AS $ex) {
				$this->extensions[] = $ex;
			}
		} else {
			$this->extensions[] = mb_strtolower($extension, 'utf-8');
		}
		return $this;
	}
	
	/**
	 * @return JO_Upload_SessionStore
	 */
	public function setAllExtension() {
		$this->extensions = false;
		return $this;
	}
	
	public function get_extension($from_file) {
		$ext = strtolower(strrchr($from_file,"."));
		return $ext;
	}
	
	public function validateExtension() {
		if($this->extensions === false) {
			return true;
		}
		$extension = $this->get_extension($this->the_file);
		$ext_array = $this->extensions;
		if (in_array($extension, $ext_array)) {
			// check mime type hier too against allowed/restricted mime types (boolean check mimetype)
			return true;
		} else {
			return false;
		}
	}
	
	public function getFileInfo($clear = false) {
		$info = JO_Session::get($this->tmp_name);
		if($clear) {
			JO_Session::set($this->tmp_name, false);
		}
		return $info;
	}
	
	public function upload($replace = false) {
		
		$info = $this->getFileInfo();
		if($info) {
			if(!$replace) {
				return true;
			}
		}
		if($this->the_file) { 
			if ($this->validateExtension()) { 
				
				if (is_uploaded_file($this->the_temp_file)) {
					
					if($this->max_size && $this->max_size < $this->size) {
						$this->message = $this->error_text(2);
						return false;
					} else {
						$file_data = @file_get_contents($this->the_temp_file);
						if($file_data) {
							JO_Session::set($this->tmp_name, array(
								'name' => $this->the_file,
								'type' => $this->mime,
								'data' => $file_data
							));
							return true;
						} else {
							$this->message = $this->error_text(5);
							return false;
						}
					}
					
				} else {
					$this->message = $this->error_text($this->http_error);
					return false;
				}
				
			} else {
				$this->message = sprintf($this->error_text(11), implode(',', $this->extensions));
				return false;
			}
		} else {
			$this->message = $this->error_text(12);
			return false;
		}
		
	}
	
	// some error (HTTP)reporting, change the messages or remove options if you like.
	public function error_text($err_num) {

		if(isset($this->error_message[$err_num])) {
			return $this->error_message[$err_num];
		}
		
		$translate = WM_Translate::getInstance();

		$error = array();
		$error[1] = $translate->translate('The uploaded file is larger than') . " " . $this->server_upload_max_filesize . ".";
		$error[2] = $translate->translate('The uploaded file is larger than') . " " . $this->format_bytes($this->max_size) . ".";
		$error[3] = $translate->translate('The file was partially uploaded');
		$error[4] = $translate->translate('The file has not been uploaded');
		$error[5] = $translate->translate('The file is empty');
		$error[6] = $translate->translate('Missing /tmp/ folder');
		$error[7] = $translate->translate('This file can not be saved');
		// end  http errors
		$error[11] = $translate->translate('Only files with the following extensions are allowed') . ": <b>%s</b>";
		$error[12] = $translate->translate('No file is specified for uploading');
			
		return (isset($error[$err_num]) ? $error[$err_num] : $err_num);
	}
	
}

?>