<?php
    
	class WM_Captcha {
		
		public $string='';
		public $img_path='';
		public $img_url='';
		public $width=150;
		public $height=30;
		public $font_path='';
		public $expiration=3600;
		public $quantity=100;
		public $attr='';
		public $alt='';
		public $symbols = 5;
		public $bg_color = array(255, 255, 255);
		public $border_color = array(153, 102, 102);
		public $border = 1;
		public $text_color = array(204, 153, 153);
		public $grid_color = array(255, 182, 182);
		public $shadow_color = array(255, 240, 240);
		public $fonts_type = array('arial', 'verdana', 'times');
		public $font_size_gd = 5;
		public $font_size_ttf = 12;
		
		private $pool = 'ABCDEFGHJKLMNPQRSTUV2345689';
		private $now;
		private $result=array();
		private $cache_folder;
		private $random_css_key;
		
		public function __construct($options = array()) {
			foreach($options AS $key => $value) {
				if($key) {
					$this->{$key} = $value;
				}
			}
			$this->font_path = dirname(__FILE__).'/fonts/';
			$this->cache_folder = realpath(BASE_PATH . '/cache');
		
			$this->random_css_key = rand(0000,9999);
			
			$this->GenerateString();
			$this->clear();
			$this->Generate();
			
		}
		
		public function registerCss() {
			$style = '<style type="text/css">
			#captcha_holder_' . $this->random_css_key . ' {
				padding: 5px;
				-moz-border-radius: 5px;
				-webkit-border-radius: 5px;
				border-radius: 5px;
				background: #7e0100;
			}
			#captcha_holder_' . $this->random_css_key . ' .image_' . $this->random_css_key . ' {
				width: ' . $this->width . 'px;
				height: ' . $this->height . 'px;
				margin-bottom: 5px;
			}
			#captcha_holder_' . $this->random_css_key . ' .input_' . $this->random_css_key . ' {
				padding: 2px 10px;
				-moz-border-radius: 5px;
				-webkit-border-radius: 5px;
				border-radius: 5px;
				background: #ffdc73;
				width: ' . ($this->width-20) . 'px;
			}
			#captcha_holder_' . $this->random_css_key . ' .input_' . $this->random_css_key . ' input {
				width: ' . ($this->width-25) . 'px;
				border: 1px solid #cca940;
				padding: 2px 0;
			}
			</style>';
			
			JO_Layout::getInstance()->placeholder('style',$style);
		}
		
		
		
		public function toHtml() {
			$this->registerCss();
			$result = $this->result();
			$html = '<div id="captcha_holder_' . $this->random_css_key . '">
						<div class="image_' . $this->random_css_key . '">
							' . $result['image'] . '
						</div>
						<div class="input_' . $this->random_css_key . '">
							<input type="text" name="captcha_code" value="" />
						</div>
					</div>';
			
			$result['html'] = $html;
			return $result;
		}
		
		public function GenerateString() {
			if ($this->string == '') {
				$str = '';
				for ($i = 0; $i < $this->symbols; $i++)
				{
					$str .= substr($this->pool, mt_rand(0, strlen($this->pool) -1), 1);
				}
				$this->string = $str;
		   }   
		}
		
		public function clear() {
			list($usec, $sec) = explode(" ", microtime());
			$this->now = ((float)$usec + (float)$sec);
					
			$current_dir = @opendir($this->cache_folder . '/captcha/');
			
			while($filename = @readdir($current_dir))
			{
				if ($filename != "." and $filename != ".." and $filename != "index.html")
				{
					$name = str_replace(".jpg", "", $filename);
				
					if (($name + $this->expiration) < $this->now)
					{
						@unlink($this->cache_folder . '/captcha/' . $filename);
					}
				}
			}
			
			@closedir($current_dir);
		}
		
		public function Generate() { 
			
			if ( ! @is_dir($this->cache_folder . '/captcha/') && is_writeable($this->cache_folder . '/captcha/'))
			  {
			   $this->result['error'] = 2;	
			   return FALSE;
			  }
			    
			  if ( ! extension_loaded('gd'))
			  {
			   $this->result['error'] = 3;				  	
			   return FALSE;
			  }					
    
			$length    = strlen($this->string);
			$angle    = ($length >= 6) ? rand(-($length-6), ($length-6)) : 0;
			$x_axis    = rand(6, (360/$length)-16);            
			$y_axis = ($angle >= 0 ) ? rand($this->height, $this->width) : rand(6, $this->height);
			
			// -----------------------------------
			// Create image
			// -----------------------------------
					
			// PHP.net recommends imagecreatetruecolor(), but it isn't always available
			if (function_exists('imagecreatetruecolor'))
			{
				$im = imagecreatetruecolor($this->width, $this->height);
			}
			else
			{
				$im = imagecreate($this->width, $this->height);
			}
					
			// -----------------------------------
			//  Assign colors
			// -----------------------------------
			
			$bg_color        = imagecolorallocate ($im, $this->bg_color[0],$this->bg_color[1], $this->bg_color[2]);
			$border_color    = imagecolorallocate ($im, $this->border_color[0],$this->border_color[1], $this->border_color[2]);
			$text_color        = imagecolorallocate ($im, $this->text_color[0],$this->text_color[1], $this->text_color[2]);
			$grid_color        = imagecolorallocate($im, $this->grid_color[0],$this->grid_color[1], $this->grid_color[2]);
			$shadow_color    = imagecolorallocate($im, $this->shadow_color[0],$this->shadow_color[1], $this->shadow_color[2]);

			// -----------------------------------
			//  Create the rectangle
			// -----------------------------------
			
			ImageFilledRectangle($im, 0, 0, $this->width, $this->height, $bg_color);
			
			// -----------------------------------
			//  Create the spiral pattern
			// -----------------------------------
			
			$theta        = 1;
			$thetac        = 7;
			$radius        = 16;
			$circles    = 20;
			$points        = 32;

			for ($i = 0; $i < ($circles * $points) - 1; $i++)
			{
				$theta = $theta + $thetac;
				$rad = $radius * ($i / $points );
				$x = ($rad * cos($theta)) + $x_axis;
				$y = ($rad * sin($theta)) + $y_axis;
				$theta = $theta + $thetac;
				$rad1 = $radius * (($i + 1) / $points);
				$x1 = ($rad1 * cos($theta)) + $x_axis;
				$y1 = ($rad1 * sin($theta )) + $y_axis;
				imageline($im, $x, $y, $x1, $y1, $grid_color);
				$theta = $theta - $thetac;
			}

			// -----------------------------------
			//  Write the text
			// -----------------------------------
			
			$use_font = ($this->font_path != '' AND file_exists($this->font_path) AND function_exists('imagettftext')) ? TRUE : FALSE;
				
			if ($use_font == FALSE)
			{
				$font_size = $this->font_size_gd;
				$x = rand(0, $this->width/($length/3));
				$y = 0;
			}
			else
			{
				$font_size    = $this->font_size_ttf;
				$x = rand(0, $this->width/($length/1.5));
				$y = $font_size+2;
			}

			for ($i = 0; $i < strlen($this->string); $i++)
			{
				if ($use_font == FALSE)
				{
					$y = rand(0 , $this->height/2);
					imagestring($im, $font_size, $x, $y, substr($this->string, $i, 1), $text_color);
					$x += ($font_size*2);
				}
				else
				{        
					$rand_keys = array_rand($this->fonts_type, 1);
					$y = rand($font_size+4, $this->height-5);
					imagettftext($im, $font_size, $angle, $x, $y, $text_color, $this->font_path . $this->fonts_type[$rand_keys].'.ttf', substr($this->string, $i, 1));
					$x += $font_size;
				}
			}
			

			// -----------------------------------
			//  Create the border
			// -----------------------------------

			$border_begin = $this->border ? 0 : -1;
			
			imagerectangle($im, $border_begin, $border_begin, $this->width-$this->border, $this->height-$this->border, $border_color);        

			// -----------------------------------
			//  Generate the image
			// -----------------------------------
			
			$img_name = $this->now.'.jpg';

			ImageJPEG($im, $this->cache_folder . '/captcha/' . $img_name, $this->quantity);
			
			$img = "<img src=\"" . JO_Request::getInstance()->getBaseUrl() . "cache/captcha/{$img_name}\" width=\"{$this->width}\" height=\"{$this->height}\" style=\"border:0px;\" alt=\"{$this->alt}\" {$this->attr} />";
			
			ImageDestroy($im);
				
			$this->result = array('word' => $this->string, 'time' => $this->now, 'image' => $img, 'error' => 0);
			
			 
		}
		
		public function result() {
			return $this->result;
		}
		
	}
	
?>