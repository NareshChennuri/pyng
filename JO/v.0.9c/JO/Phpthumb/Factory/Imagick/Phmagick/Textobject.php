<?php

class JO_Phpthumb_Factory_Imagick_Phmagick_Textobject {
	protected $fontSize;
	protected $font;
	
	protected $color;
	protected $background;
	
	protected $pGravity; //ignored in fromString()
	protected $pText = '';
	
	public function __construct(){
		$this->fontSize   = JO_Phpthumb_Factory_Imagick_Phmagick_Textobjectdefaults::$fontSize;
		$this->font       = JO_Phpthumb_Factory_Imagick_Phmagick_Textobjectdefaults::$font;
		$this->color      = JO_Phpthumb_Factory_Imagick_Phmagick_Textobjectdefaults::$color ;
		$this->background = JO_Phpthumb_Factory_Imagick_Phmagick_Textobjectdefaults::$background;
		$this->pGravity   = JO_Phpthumb_Factory_Imagick_Phmagick_Textobjectdefaults::$gravity;
	}
	
	function defaultFontSize($value){
		JO_Phpthumb_Factory_Imagick_Phmagick_Textobjectdefaults::$fontSize = $value;
	}
	
	function defaultFont($value){
		JO_Phpthumb_Factory_Imagick_Phmagick_Textobjectdefaults::$font = $value;
	}
	
	function defaultColor($value){
		JO_Phpthumb_Factory_Imagick_Phmagick_Textobjectdefaults::$color = $value;
	}
	
	function defaultBackground($value){
		JO_Phpthumb_Factory_Imagick_Phmagick_Textobjectdefaults::$background = $value;
	}
	
	function defaultGravity($value){
		JO_Phpthumb_Factory_Imagick_Phmagick_Textobjectdefaults::$gravity = $value;
	}
	
	
	
	function fontSize($i){
		$this->fontSize = $i ;
		return $this;
	}
	
	function font($i){
		$this->font = $i ;
		return $this;
	}
	
	function color($i){
		$this->color = $i ;
		return $this;
	}
	
	function background($i){
		$this->background = $i ;
		return $this;
	}
	
	function __get($var){
		return $this->$var ;
	}
	
	function gravity( $gravity){
		$this->pGravity = $gravity;
		return $this ;
	}
	
	function text( $text){
		$this->pText = $text;
		return $this ;
	}
}

?>