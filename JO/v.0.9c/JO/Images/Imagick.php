<?php

class JO_Images_Imagick {
	

	private $file;
    /**
     * @var Imagick
     */
    private $image;
    private $info;
    private $isAnimate = false;
		
	public function __construct($file) {
		if ($info = @getimagesize($file)) {
			$this->file = $file;
			
			$info1 = pathinfo($file);
        	$extension = strtolower($info1['extension']);

			$this->info = array(
            	'width'  	=> $info[0],
            	'height' 	=> $info[1],
            	'bits'   	=> $info['bits'],
            	'mime'   	=> $info['mime'],
				'extension' => $extension
        	);
        	
        	$this->image = new Imagick($file);
        	$this->isAnimate = $this->image->getnumberimages() > 1;

    	} else {
      		throw new JO_Exception('Error: Could not load image ' . $file . '!');
    	}
	}
	
	public function __destruct() {
		$this->image->clear();
		$this->image->destroy(); 
	}
	
	public function gray() {
		foreach($this->image AS $image) {
			$image->setImageColorSpace(Imagick::COLORSPACE_GRAY);
			$image->modulateImage(100,0,100);  
		}
	}	
	
    public function save($file, $gray = false, $quality = 100) {

        if($gray) {
        	$this->gray();
        }
      
	    $res = $this->image->writeImages($file, true);
	    $this->image->clear();
		$this->image->destroy(); 
	    return $res;
    }   
	
    public function resize($width = 0, $height = 0, $scaled = true, $color = 'FFFFFF') {
    	if (!$this->info['width'] || !$this->info['height']) {
			return;
		}
    	
    	$rgb = $this->html2rgb($color);

		$xpos = 0;
		$ypos = 0;

		$scale = min($width / $this->info['width'], $height / $this->info['height']);
		
		if ($scale == 1) {
			return;
		}
		
		if($scaled) {
			$new_width = round($this->info['width'] * $scale);
			$new_height = round($this->info['height'] * $scale);			
	    	$xpos = round(($width - $new_width) / 2);
	   		$ypos = round(($height - $new_height) / 2);
		} else {
			$new_width = $width;
			$new_height = $height;
		}
		
		$canvas = new Imagick();
		$canvas->newImage($width, $height, new ImagickPixel('rgba('.$rgb[0].','.$rgb[1].','.$rgb[2].',0)'));
		list($extension) = explode('?', $this->info['extension']);
		$canvas->setImageFormat($extension);
		
    	for($r=0; $r<count($this->image); $r++){
    		$this->image->scaleImage($new_width, $new_height);
    		$this->image->nextImage();
            $canvas->compositeImage($this->image, $this->image->getImageCompose(), $xpos, $ypos);
		}
		
		$this->image = $canvas;
		
		//$canvas->clear();
		//$canvas->destroy(); 

        $this->info['width']  = $width;
        $this->info['height'] = $height;
    }
    
    public function watermark($file, $position = 'bottomright') {
    	
        $watermark = new self($file);
        
        if(!$watermark) return;
        
        $watermark_width = $watermark->info['width'];
        $watermark_height = $watermark->info['height'];
        
        switch($position) {
            case 'topleft':
                $watermark_pos_x = 0;
                $watermark_pos_y = 0;
        		for($r=0; $r<count($this->image); $r++){
	    			$this->image->nextImage();
            		$this->image->compositeImage($watermark->image, $watermark->image->getImageCompose(), $watermark_pos_x, $watermark_pos_y);
            	}
                break;
            case 'topright':
                $watermark_pos_x = $this->info['width'] - $watermark_width;
                $watermark_pos_y = 0;
        		for($r=0; $r<count($this->image); $r++){
	    			$this->image->nextImage();
            		$this->image->compositeImage($watermark->image, $watermark->image->getImageCompose(), $watermark_pos_x, $watermark_pos_y);
            	}
                break;
            case 'bottomleft':
                $watermark_pos_x = 0;
                $watermark_pos_y = $this->info['height'] - $watermark_height;
        		for($r=0; $r<count($this->image); $r++){
	    			$this->image->nextImage();
            		$this->image->compositeImage($watermark->image, $watermark->image->getImageCompose(), $watermark_pos_x, $watermark_pos_y);
            	}
                break;
            case 'bottomright':
                $watermark_pos_x = $this->info['width'] - $watermark_width;
                $watermark_pos_y = $this->info['height'] - $watermark_height;
       			for($r=0; $r<count($this->image); $r++){
	    			$this->image->nextImage();
            		$this->image->compositeImage($watermark->image, $watermark->image->getImageCompose(), $watermark_pos_x, $watermark_pos_y);
            	}
                break;
            default:
	            $padX = 40;
	            $padY = 30;
	            $wrep = ceil($this->info['width'] / $watermark_width);
	            $hrep = ceil($this->info['height'] / $watermark_height);
	            $positions = array();
	            for ($i = 0; $i <= $wrep; $i ++) {
	                for ($j = 0; $j <= $hrep; $j ++) {
	                    $positions[(($watermark_width + $padX) * $i) . '_' . (($watermark_height + $padY) * $j)] = array( (($watermark_width + $padX) * $i), (($watermark_height + $padY) * $j) );
	                }
	            }
	            foreach ($positions as $key => $data) {
	            	for($r=0; $r<count($this->image); $r++){
		    			$this->image->nextImage();
	            		$this->image->compositeImage($watermark->image, $watermark->image->getImageCompose(), $data[0], $data[1]);
	            	}
	            }
                break;
        }
        
        $watermark->image->clear();
		$watermark->image->destroy();
		unset($watermark);
    }
    
    public function crop($top_x, $top_y, $bottom_x, $bottom_y) {
    	
	    //Crop every other image
		for($r=0; $r<count($this->image); $r++){
		    $this->image->nextImage();
		    $this->image->cropImage(($bottom_x - $top_x), ($bottom_y - $top_y), $top_x, $top_y);
		}
        
        $this->info['width'] = $bottom_x - $top_x;
        $this->info['height'] = $bottom_y - $top_y;
    }
    
    public function resize_crop($width, $height) {
        if (!$this->info['width'] || !$this->info['height']) {
            return;
        }
        
    	//Crop every other image
		for($r=0; $r<count($this->image); $r++){
		    $this->image->nextImage();
		    $this->image->cropThumbnailImage($width, $height);
		}
        
        $this->info['width'] = $width;
        $this->info['height'] = $height;
        
    }
    
    public function rotate($degree, $color = 'FFFFFF') {
    	
    	$rgb = $this->html2rgb($color);
		
    	for($r=0; $r<count($this->image); $r++){
    		$this->image->nextImage();
            $this->image->rotateImage(new ImagickPixel('rgba('.$rgb[0].','.$rgb[1].','.$rgb[2].',0)'), $degree);
		}
    	
        $this->info['width'] = $this->image->getImageWidth();
		$this->info['height'] = $this->image->getImageHeight();
        
    }
	    
    private function filter() { }
    

            
    private function text($text, $x = 0, $y = 0, $size = 5, $color = '000000') {
//		$rgb = $this->html2rgb($color);
//        
//		imagestring($this->image, $size, $x, $y, $text, imagecolorallocate($this->image, $rgb[0], $rgb[1], $rgb[2]));
    }
    
    private function merge($file, $x = 0, $y = 0, $opacity = 100) {
//    	$merge = new self($file);
//		        
//        imagecopymerge($this->image, $merge->image, $x, $y, 0, 0, $merge->info['width'], $merge->info['height'], $opacity);
    }
			
	private function html2rgb($color) {
		if ($color[0] == '#') {
			$color = substr($color, 1);
		}
		
		if (strlen($color) == 6) {
			list($r, $g, $b) = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);   
		} elseif (strlen($color) == 3) {
			list($r, $g, $b) = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);    
		} else {
			return FALSE;
		}
		
		$r = hexdec($r); 
		$g = hexdec($g); 
		$b = hexdec($b);    
		
		return array($r, $g, $b);
	}	
	
	
}

?>