<?php

class JO_Thumb {
	
    /**
     * @var JO_Images_Gd
     */
    private $image = null;
		
	public function __construct($file) {
		$file = preg_replace('#([/]{2,})#','/',$file);
		if ($info = @getimagesize($file)) {
			
			if(!$info) { 
				$this->image = null;
				return $this;
				throw new JO_Exception('Error: Could not load image ' . $file . '!');
			}
			
			if ($info['mime'] == 'image/gif') {
				$this->image = new JO_Images_Gd($file);
			} else {
				if(class_exists('Imagick', false)) { 
					$this->image = new JO_Images_Imagick($file);
				} else { 
					$this->image = new JO_Images_Gd($file);
				}
			}

    	} else {
			$this->image = null;
			return $this;
      		throw new JO_Exception('Error: Could not load image ' . $file . '!');
    	}
	}
	
	public function gray() {
		if(!$this->image) { return; }
		$this->image->gray();
	}
	
    public function save($file, $gray = false, $quality = 100) {
		if(!$this->image) { return; }
    	return $this->image->save($file, $gray, $quality);
    }	    
	
    public function resize($width = 0, $height = 0, $scaled = true) {
		if(!$this->image) { return; }
    	$this->image->resize($width, $height, $scaled);
    }
    
    public function watermark($file, $position = 'bottomright') {
		if(!$this->image) { return; }
    	$this->image->watermark($file, $position);
    }
    
    public function crop($top_x, $top_y, $bottom_x, $bottom_y) {
		if(!$this->image) { return; }
    	$this->image->crop($top_x, $top_y, $bottom_x, $bottom_y);
    }
    
    public function resize_crop($width, $height) {
		if(!$this->image) { return; }
    	$this->image->resize_crop($width, $height);
    }
    
    public function rotate($degree, $color = 'FFFFFF') {
		if(!$this->image) { return; }
    	$this->image->rotate($degree, $color);
    }
	    
    public function filter($filter) {
		if(!$this->image) { return; }
    	$this->image->filter($filter);
    }
            
    public function text($text, $x = 0, $y = 0, $size = 5, $color = '000000') {
		if(!$this->image) { return; }
    	$this->image->text($text, $x, $y, $size, $color);
    }
    
    public function merge($file, $x = 0, $y = 0, $opacity = 100) {
		if(!$this->image) { return; }
    	$this->image->merge($file, $x, $y, $opacity);
    }
	

}

?>