<?php

class Helper_Images {

	private $dirImages;
	
	private $httpImages;
	
	/**
	 * @var JO_Request
	 */
	private $request;
	
	private $temp_sizes = array(
		'width' => 0,
		'height' => 0,
		'date_added' => 0
	);
	
	public function __construct() {
				
		$this->dirImages = realpath(BASE_PATH . '/' . 'uploads') . '/';
		
		if(!$this->dirImages || !file_exists($this->dirImages) || !is_dir($this->dirImages)) {
			throw new JO_Exception('Upload folder not exist!');
		}

		$this->request = JO_Request::getInstance();
		
		$this->httpImages = 'uploads/';
		
	}
	
	public function resize2($filename, $width, $height, $auto = false) {
		if(!$filename) { $filename = JO_Registry::get('no_image'); }
		
		$info = pathinfo($filename);
		
		if(!isset($info['extension'])) { $filename = JO_Registry::get('no_image'); }
		
		$info = pathinfo($filename);
		
		$extension = $info['extension'];
		$gray_name = '';

		if($auto == 'width') {
			$whe = $width . 'x';
		} elseif($auto == 'height') {
			$whe = 'x' . $height;
		} else {
			$whe = $width . 'x' . $height;
		}
		
		$old_image = $filename;
		
		$tmp = substr($filename, 0, strrpos($filename, '.'));
		$filename = substr($filename, 0, strrpos($filename, '.'));
//		$filename = substr($tmp, 0, strrpos($tmp, '/')) . '/' . md5(basename($tmp)) . '-' . md5($filename);

		$new_image = 'cache' . $filename . '-' . $whe . $gray_name . '.' . $extension;
		$new_image = str_replace('/../','/',$new_image);
		
		return $this->request->getBaseUrl() . $this->httpImages . $new_image;
	}
	
	public function getSizes($key) {
		return isset($this->temp_sizes[$key]) ? $this->temp_sizes[$key] : '';
	}
	
	public function resize($filename, $width, $height, $crop = false, $watermark = false, $gray = false, $auto = false) {
		if(!$width && !$height) { $width = 1;$height = 1; }
		if (!file_exists($this->dirImages . $filename) || !is_file($this->dirImages . $filename)) {
			$filename = JO_Registry::forceGet('no_image');
			if (!$filename || !file_exists($this->dirImages . $filename) || !is_file($this->dirImages . $filename)) {
				$filename = 'no_image.jpg';
				if (!file_exists($this->dirImages . $filename) || !is_file($this->dirImages . $filename)) {
					return;	
				}
			}
		} 

		$info = pathinfo($filename);
		$extension = $info['extension'];
		$gray_name = '';
		if($gray) {
			$gray_name = '-gray';	
		}
		if($crop) {
			$gray_name .= '-crop';	
		}
		if($watermark) {
			$gray_name .= '-watermark';	
		}
		
		if($auto == 'width') {
			$whe = $width . 'x';
		} elseif($auto == 'height') {
			$whe = 'x' . $height;
		} else {
			$whe = $width . 'x' . $height;
		}
		
		$old_image = $filename;
		
		$tmp = substr($filename, 0, strrpos($filename, '.'));
		$filename = substr($filename, 0, strrpos($filename, '.'));
//		$filename = substr($tmp, 0, strrpos($tmp, '/')) . '/' . md5(basename($tmp)) . '-' . md5($filename);

		$new_image = 'cache' . $filename . '-' . $whe . $gray_name . '.' . $extension;
		$new_image = str_replace('/../','/',$new_image);
		
		$this->temp_sizes = array(
				'width' => $width,
				'height' => $height
		);
		
		if (!file_exists($this->dirImages . $new_image) || (filemtime($this->dirImages . $old_image) > filemtime($this->dirImages . $new_image))) {
			$path = '';
			$directories = explode('/', dirname(str_replace('../', '', $new_image)));
			
			foreach ($directories as $directory) {
				$path = $path . '/' . $directory;
				
				if (!file_exists($this->dirImages . $path)) {
					@mkdir($this->dirImages . $path, 0777, true);
				}		
			}
			
			
			$image = new JO_Thumb($this->dirImages . $old_image);
			
			if(function_exists('exif_read_data')) {
				$exif = exif_read_data($this->dirImages . $old_image);
				if(isset($exif['Orientation'])) {
					switch($exif['Orientation']) {
						case 8:
							$image->rotate(-90);
							break;
						case 3:
							$image->rotate(180);
							break;
						case 6:
							$image->rotate(90);
							break;
					}
				}
			}
			
			if($crop === false) {
				$image->resize($width, $height);
			} else {
				$image->resize_crop($width, $height);
			} 
			
			if($watermark && JO_Registry::get($watermark) && file_exists(BASE_PATH . '/uploads/' . JO_Registry::get($watermark))) {
				$image->watermark(BASE_PATH . '/uploads/' . JO_Registry::get($watermark), false);
			}
			
			$image->save($this->dirImages . $new_image, $gray);
		}
		
		if(file_exists($this->dirImages . $new_image)) {
			$this->temp_sizes['date_added'] = filemtime($this->dirImages . $new_image);
		}
		
		return $this->request->getBaseUrl() . $this->httpImages . $new_image;
	}
	
	public function original($filename) {
		if (!file_exists($this->dirImages . $filename) || !is_file($this->dirImages . $filename)) {
			$filename = JO_Registry::forceGet('no_image');
			if (!file_exists($this->dirImages . $filename) || !is_file($this->dirImages . $filename)) {
				$filename = 'no_image.jpg';
				if (!file_exists($this->dirImages . $filename) || !is_file($this->dirImages . $filename)) {
					return;	
				}
			}
		}
		
		return $this->request->getBaseUrl() . $this->httpImages . $filename;
	}
	
	public function resizeWidth($filename, $width, $watermark = false, $gray = false) {
		if(!$width) { $width = 1; }
		if (!file_exists($this->dirImages . $filename) || !is_file($this->dirImages . $filename)) {
			$filename = JO_Registry::forceGet('no_image');
			if (!file_exists($this->dirImages . $filename) || !is_file($this->dirImages . $filename)) {
				$filename = 'no_image.jpg';
				if (!file_exists($this->dirImages . $filename) || !is_file($this->dirImages . $filename)) {
					return;	
				}
			}
		} 
		
		$imag_info = getimagesize($this->dirImages . $filename);
		
		if(!$imag_info) {
			return;
		}
		
		if($imag_info[0]/$width < 1) {
			$this->temp_sizes = array(
				'width' => $imag_info[0],
				'height' => $imag_info[1]
			);
			return $this->resize($filename, $imag_info[0], $imag_info[1], false, $watermark, $gray, 'width');
		}
		
		$new_height = round($imag_info[1] / ($imag_info[0]/$width));
		
		$this->temp_sizes = array(
			'width' => $width,
			'height' => $new_height
		);
		
		return $this->resize($filename, $width, $new_height, false, $watermark, $gray, 'width');
		
	}
	
	public function resizeHeight($filename, $height, $watermark = false, $gray = false) {
		if(!$height) { $height = 1; }
		if (!file_exists($this->dirImages . $filename) || !is_file($this->dirImages . $filename)) {
			$filename = JO_Registry::forceGet('no_image');
			if (!file_exists($this->dirImages . $filename) || !is_file($this->dirImages . $filename)) {
				$filename = 'no_image.jpg';
				if (!file_exists($this->dirImages . $filename) || !is_file($this->dirImages . $filename)) {
					return;	
				}
			}
		} 
		
		$imag_info = getimagesize($this->dirImages . $filename);
		
		if(!$imag_info) {
			return;
		}
		
		if($imag_info[1]/$height < 1) {
			$this->temp_sizes = array(
				'width' => $imag_info[0],
				'height' => $imag_info[1]
			);
			return $this->resize($filename, $imag_info[0], $imag_info[1], false, $watermark, $gray, 'height');
		}
		
		$new_width = round($imag_info[0] / ($imag_info[1]/$height));
		
		$this->temp_sizes = array(
			'width' => $new_width,
			'height' => $height
		);
		
		return $this->resize($filename, $new_width, $height, false, $watermark, $gray, 'height');
		
	}
	
	public function deleteImages($file, $delete_real = true) { 
		
		if(file_exists($this->dirImages . $file) && is_file($this->dirImages . $file)) { 
			$ext = explode('.',$file);
			$ext = '.' . end($ext);
			$filem = str_replace($ext, '', $file);
			
			$files = glob($this->dirImages . 'cache' . '/' . $filem . '*' . $ext);
			if(is_array($files)) {
				foreach($files AS $file_delete) {
					if(is_file($file_delete)) {
						@unlink($file_delete);
					}
				}
			}
			
			$tmp = substr($file, 0, strrpos($file, '.'));
			$filename = substr($tmp, 0, strrpos($tmp, '/')) . '/' . md5(basename($tmp)) . '-' . md5($file);
		
			$files = glob($this->dirImages . 'cache' . '/' . $filename . '*' . $ext);
			if(is_array($files)) {
				foreach($files AS $file_delete) {
					if(is_file($file_delete)) {
						@unlink($file_delete);
					}
				}
			}
			
			if($delete_real) {
				@unlink($this->dirImages . $file);
			}
		}
	}
	
	
	public function fixEditorText($text) {
		
		if(!JO_Registry::get('config_editor_external_image_cache')) {
		//	return $text;
		}
		
		$dom = new JO_Html_Dom();
		$dom->load($text);
//		$tags = $dom->find('img[src^=uploads/]');

		$tags = $dom->find('img');
		$orig = $repl = array();
		foreach($tags AS $tag) {
			
			$src = $tag->src;
			if(stripos($tag->src, 'http://') !== false && @getimagesize($tag->src)) {
				$comp = parse_url($tag->src);
				if(isset($comp['path']) && $comp['path']) {
					$comp['path'] = '/upload_from_url/' . basename($comp['path']);
					if(file_exists(BASE_PATH . '/uploads' . $comp['path'])) {
						$src = '/uploads' . $comp['path'];
					} else { 
						$dir = dirname($comp['path']);
						if(!file_exists($this->dirImages . $dir) || !is_dir($this->dirImages . $dir)) {
							@mkdir($this->dirImages . $dir, 0777, true);
						}
						if(file_exists($this->dirImages . $dir) && is_dir($this->dirImages . $dir)) {
							if(@copy($tag->src, $this->dirImages . $comp['path'])) {
								$src = $comp['path'];
							}
						}
					}
				}
			} 
			
			$width = $tag->width;
			$height = $tag->height;
			$style = $tag->style;
			if(!$width && preg_match('/width:\s?([\d]{1,})/i',$style, $css)) {
				$width = $css[1];
			}
			if(!$height && preg_match('/height:\s?([\d]{1,})/i',$style, $css)) {
				$height = $css[1];
			}
			
			if($width || $height) {
				$generate = self::resizeForEditor($src, $width, $height);
				if($generate) {
					$tag->src = $generate;
				}
			}	
		}
		
		return (string)$dom;
	}
	
	private function resizeForEditor($filename, $width = false, $height = false) {
		$filename = preg_replace('/uploads\//i','/',$filename);
		if (!file_exists($this->dirImages . $filename) || !is_file($this->dirImages . $filename)) {
			return false;
		} 
		
		$imag_info = @getimagesize($this->dirImages . $filename);
		if(!$imag_info) {
			return;
		}
		
		$info = pathinfo($filename);
		$extension = $info['extension'];
		
		if($height && !$width) {
			$width = round($imag_info[0] / ($imag_info[1]/$height));
		} elseif($width && !$height) {
			$height = round($imag_info[1] / ($imag_info[0]/$width));
		}
		if(!$width || !$height) {
			return false;
		}
		
		$old_image = $filename;
		
		$tmp = substr($filename, 0, strrpos($filename, '.'));
		$filename = substr($tmp, 0, strrpos($tmp, '/')) . '/' . md5(basename($tmp)) . '-' . md5($filename);

		$new_image = 'cache' . $filename . '-' . $width . 'x' . $height . '.' . $extension;
		$new_image = str_replace('/../','/',$new_image);
		//$new_image = 'cache' . substr($filename, 0, strrpos($filename, '.')) . '-' . $width . 'x' . $height . '.' . $extension;
		
		if (!file_exists($this->dirImages . $new_image) || (filemtime($this->dirImages . $old_image) > filemtime($this->dirImages . $new_image))) {
			$path = '';
			$directories = explode('/', dirname(str_replace('../', '', $new_image)));
			
			foreach ($directories as $directory) {
				$path = $path . '/' . $directory;
				
				if (!file_exists($this->dirImages . $path)) {
					@mkdir($this->dirImages . $path, 0777, true);
				}		
			}
			
			$image = new JO_Thumb($this->dirImages . $old_image);
			$image->resize($width, $height, false);
			$image->save($this->dirImages . $new_image);
		}
		
		return $this->httpImages . $new_image;
		
	}
	
	private static function _preg_quote($str, $delimiter) {
		$text = preg_quote($str);
		$text = str_replace($delimiter, '\\' . $delimiter, $text); 
		return $text;
	}	
	
	public static function copyFromUrl($url, $target) {
		if(!@copy($url, $target)) {
			$http = new JO_Http();
			$http->useCurl(true);
			if( ($host = JO_Validate::validateHost($url))!==false ) {
				$http->setReferrer('http://' . $host);
			}
			$http->execute($url);
			
			if($http->error) {
				return false;
			} else {
				$im = @ImageCreateFromString($http->result);
				if (!$im) {
					return false;
				}
				return @file_put_contents($target, $im);
			}
		} else {
			return true;
		}
	}
	
}

?>