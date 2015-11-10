<?php
/*
 *
 * $upload = JO_Upload; 
 * $upload->setFile($_FILES['file'])
 * 		  ->setUploadDir(path to upload file)
 *		  ->setExtension(array(".pdf", ".srt", ".jpg") OR ".jpg"); 
 * 
 * $new_name = md5(time()); 
 * 
 * if($upload->upload($new_name)) {
 * 		$info = $upload->getFileInfo();
 * 		if($info) {
 *  		//do something
 *  	}
 * }
 *  
 */

class JO_Upload {

    private $the_file;
	private $the_temp_file;
	private $http_error;
	private $size;
	private $mime;
	
    private $upload_dir;
	private $replace = 'n';
	private $do_filename_check = 'n';
	private $max_length_filename = 100;
	private $rename_file = true; // if this var is true the file copy get a new name
    private $extensions = false; //array(".png", ".zip", ".pdf", ".srt", ".jpg")
	private $error_message = array();
	private $return_rename_text = false;
	private $create_directory = true;
	private $file_copy; // the new name
	
	protected $ext_string;
	protected $message = array();
	
	
	/**
	 * @param array $file
	 * @return JO_Upload
	 */
	public function setFile(array $file) {
		$this->the_file = $file['name'];
		$this->http_error = $file['error'];
		$this->the_temp_file = $file['tmp_name'];
		$this->size = $file['size'];
		$this->mime = $file['type'];
		return $this;
	}
	
	/**
	 * @return new file name
	 */
	public function getNewFileName() {
		return $this->file_copy;
	}
	
	/**
	 * @param bool $create
	 * @return JO_Upload
	 */
	public function setCreateDirectory($create = true) {
		$this->create_directory = $create;
		return $this;
	}
	
	/**
	 * @param bool $return
	 * @return JO_Upload
	 */
	public function setReturnRenameText($return = false) {
		$this->return_rename_text = $return;
		return $this;
	}
	
	/**
	 * @param int $code
	 * @param string $message
	 * @return JO_Upload
	 */
	public function setErrorMessage($code, $message) {
		$this->error_message[$code] = $message;
		return $this;
	}
	
	/**
	 * @return JO_Upload
	 */
	public function resetExtensions() {
		$this->extensions = array();
		return $this;
	}
	
	/**
	 * @param string||array $extension
	 * @return JO_Upload
	 */
	public function setExtension($extension) {
		if(is_array($extension)) {
			foreach($extension AS $ex) {
				$this->extensions[] = $ex;
			}
		} else {
			$this->extensions[] = $extension;
		}
		return $this;
	}
	
	/**
	 * @param string $dir
	 * @return JO_Upload
	 */
	public function setUploadDir($dir) {
		$this->upload_dir = $dir;
		return $this;
	}
	
	/**
	 * @param bool $replace
	 * @return JO_Upload
	 */
	public function setReplace($replace = false) {
		$this->replace = $replace === true ? 'y' : 'n';
		return $this;
	}
	
	/**
	 * @param bool $check
	 * @return JO_Upload
	 */
	public function setFileNameCheck($check = false) {
		$this->do_filename_check = $check === true ? 'y' : 'n';
		return $this;
	}
	
	/**
	 * @param int $length
	 * @return JO_Upload
	 */
	public function setMaxLengthFileName($length = 100) {
		$this->max_length_filename = $length;
		return $this;
	}
	
	/**
	 * @param bool $rename
	 * @return JO_Upload
	 */
	public function setRenameFile($rename = false) {
		$this->rename_file = $rename;
		return $this;
	}
	

	public function __construct() {}
	
	/**
	 * @return string
	 */
	public function getError() {
        $msg_string = "";
        foreach ($this->getErrorArray() as $value) {
            $msg_string .= $value."; ";
        }
        return $msg_string;
    }

    private function remuve_empty($in) {
        $mas = array();
        if(is_array($in)) {
            foreach($in AS $k=>$v) {
                if(!empty($v)) {
                    $mas[$k] = is_array($v) ? $this->remuve_empty($v) : $v;
                }
            }
        }
        return $mas;
    }

    /**
     * @return array
     */
    public function getErrorArray() {
        return $this->remuve_empty($this->message);
    }
    
	private function set_file_name($new_name = "") { // this "conversion" is used for unique/new filenames
		if ($this->rename_file) {
			if ($this->the_file == "") return;
			$name = ($new_name == "") ? strtotime("now") : $new_name;
			$name = $name.$this->get_extension($this->the_file);
		} else {
			$name = $this->the_file;
		}
		return $name;
	}
	
	/**
	 * @param string $to_name
	 * @return bool
	 */
	public function upload($to_name = "") {
	    
		$new_name = $this->set_file_name($to_name);
		if ($this->check_file_name($new_name)) {
		    
			if ($this->validateExtension()) {
			    
				if (is_uploaded_file($this->the_temp_file)) {
				    
					$this->file_copy = $new_name;
					
					if ($this->move_upload($this->the_temp_file, $this->file_copy)) {
					    
						$this->message[] = $this->error_text($this->http_error);
						if ($this->rename_file && $this->return_rename_text) $this->message[] = $this->error_text(16);
						return true;
					}
				} else {
					$this->message[] = $this->error_text($this->http_error);
					
					return false;
				}
			} else {
				$this->show_extensions();
				$this->message[] = $this->error_text(11);
				
				return false;
			}
		} else {
		   
			return false;
		}
	}
	
	private function check_file_name($the_name) {
		if ($the_name != "") {
			if (strlen($the_name) > $this->max_length_filename) {
				$this->message[] = $this->error_text(13);
				return false;
			} else {
				if ($this->do_filename_check == "y") {
					if (preg_match("/^[a-z0-9_]*\.(.){1,5}$/i", $the_name)) {
						return true;
					} else {
						$this->message[] = $this->error_text(12);
						return false;
					}
				} else {
					return true;
				}
			}
		} else {
			$this->message[] = $this->error_text(10);
			return false;
		}
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
	
	// this method is only used for detailed error reporting
	public function show_extensions() {
		$this->ext_string = implode(" ", $this->extensions);
	}
	
	public function move_upload($tmp_file, $new_file) {
		umask(0);
		
		if ($this->existing_file($new_file)) {
		    
			$newfile = $this->upload_dir.$new_file;
			
			if ($this->check_dir($this->upload_dir)) {
			    
				if (move_uploaded_file($tmp_file, $newfile)) {
					if ($this->replace == "y") {
						//system("chmod 0777 $newfile"); // maybe you need to use the system command in some cases...
						chmod($newfile , 0777);
					} else {
						// system("chmod 0755 $newfile");
						chmod($newfile , 0755);
					}
					return true;
				} else {
					return false;
				}
			} else {
				$this->message[] = $this->error_text(14);
				return false;
			}
		} else {
			$this->message[] = $this->error_text(15);
			return false;
		}
	}
	
	public function check_dir($directory) { 
		if (!@is_dir($directory)) {
			if ($this->create_directory) {
			    
				umask(0);
				if(@mkdir($directory, 0777, true)) {
					return true;	
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			return true;
		}
	}

	public function existing_file($file_name) {
		if ($this->replace == "y") {
			return true;
		} else {
			if (file_exists($this->upload_dir.$file_name)) {
				return false;
			} else {
				return true;
			}
		}
	}
	
	/**
	 * @param string $name
	 * @return array
	 */
	public function getFileInfo() {
		$name = $this->upload_dir . $this->getNewFileName();
		
		if(!file_exists($name) || !is_file($name)) {
			return false;
		}
		
        $str = array();
		$str['name'] = basename($name);
		$str['size'] = filesize($name);
		$str['mime'] = $this->mime;
		$str['image_dimension'] = array('x' => 0, 'y' => 0);
		if (function_exists("mime_content_type")) {
			$str['mime'] = mime_content_type($name);
		}
		if ($img_dim = getimagesize($name)) {
			$str['image_dimension'] = array('x' => $img_dim[0], 'y' => $img_dim[1]);
		}
		return $str;
	}
	
	// this method was first located inside the foto_upload extension
	public function del_temp_file($file) {
		$delete = @unlink($file);
		clearstatcache();
		if (@file_exists($file)) {
			$filesys = preg_replace("/\//","\\",$file);
			$delete = @system("del $filesys");
			clearstatcache();
			if (@file_exists($file)) {
				$delete = @chmod ($file, 0775);
				$delete = @unlink($file);
				$delete = @system("del $filesys");
			}
		}
	}
	
	// some error (HTTP)reporting, change the messages or remove options if you like.
	public function error_text($err_num) {

		if(isset($this->error_message[$err_num])) {
			return $this->replaceKeys($this->error_message[$err_num]);
		}
		
		$error = array();
		//$error[0] = $this->the_file." is uploaded!";
		$error[1] = "The uploaded file is larger than the allowed maximum size for uploading to the server settings.";
		$error[2] = "The uploaded file is larger than the allowed maximum size for upload in your site.";
		$error[3] = "File was partially uploaded";
		$error[4] = "File was not successfully uploaded";
		// end  http errors
		$error[10] = "Please select file to upload";
		$error[11] = "Only files with the following extensions are allowed: {ext_string}";
		$error[12] = "Sorry, the file name contains illegal characters. Use only alphanumeric characters and underscore without spaces. Correct the file name ends with a point and then the extension.";
		$error[13] = "The name of the file exceeds maximum length of {max_length_filename} characters.";
		$error[14] = "Sorry, the directory file upload does not exist!";
		$error[15] = "Error uploading files: {the_file}. File already exists!";
		$error[16] = "The uploaded file is renamed to {file_copy}.";
			
		return (isset($error[$err_num]) ? $this->replaceKeys($error[$err_num]) : $err_num);
	}
	
	public function replaceKeys($text) {
		return str_replace(array(
				'{ext_string}',
				'{max_length_filename}',
				'{the_file}',
				'{file_copy'
			), array(
				$this->ext_string,
				$this->max_length_filename,
				$this->the_file,
				$this->file_copy
			),$text);
	}
}