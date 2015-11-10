<?php

class JO_Phpthumb_Factory_Imagick_Phmagick_Textobjectdefaults {
	public static $fontSize ='12';
	public static $font = false;
	
	public static $color = '#000';
	public static $background = false;
	
	public static $gravity = JO_Phpthumb_Factory_Imagick_Phmagick_Gravity::Center; //ignored in fromString()
	public $Text = '';
	
	private function __construct(){
	}
}

?>