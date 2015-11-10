<?php
/*
 +--------------------------------------------------------------------------------------------+
|   DISCLAIMER - LEGAL NOTICE -                                                              |
+--------------------------------------------------------------------------------------------+
|                                                                                            |
|  This program is free for non comercial use, see the license terms available at            |
|  http://www.francodacosta.com/licencing/ for more information                              |
|                                                                                            |
|  This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; |
|  without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. |
|                                                                                            |
|  USE IT AT YOUR OWN RISK                                                                   |
|                                                                                            |
|                                                                                            |
+--------------------------------------------------------------------------------------------+

*/
/**
 * phMagick - Image manipulation with Image Magick
*
* @version    0.4.1
* @author     Nuno Costa - sven@francodacosta.com
* @copyright  Copyright (c) 2007
* @license    LGPL
* @link       http://www.francodacosta.com/phmagick
* @since      2008-03-13
*/
class JO_Phpthumb_Factory_Imagick_Phmagick {
	private $availableMethods = array();
	private $loadedPlugins = array();
	
	private $escapeChars = null ;
	
	private $history = array();
	private $originalFile = '';
	private $source = '';
	private $destination = '';
	private $imageMagickPath = '';
	private $imageQuality = 80 ;
	
	public $debug = false;
	private $log = array();
	
	function __construct($sourceFile='', $destinationFile=''){
		$this->originalFile = $sourceFile;
	
		$this->source = $sourceFile ;
		$this->destination = $destinationFile;
	
		if(is_null($this->escapeChars) ){
			$this->escapeChars = !( strtolower ( substr( php_uname('s'), 0, 3))  == "win" ) ;
		}
	
		$this->loadPlugins();
	}
	
	
	public function getLog(){
		return $this->log;
	}
	public function getBinary($binName){
		return $this->getImageMagickPath()  . $binName ;
	}
	
	//-----------------
	function setSource ($path){
		$this->source = str_replace(' ','\ ',$path) ;
		return $this ;
	}
	
	function getSource (){
		return $this->source ;
	}
	
	//-----------------
	function setDestination ($path){
		$path = str_replace(' ','\ ',$path) ;
		$this->destination = $path ;
		return $this;
	}
	
	function getDestination (){
		if( ($this->destination == '')){
			$source = $this->getSource() ;
			$ext = end (explode('.', $source)) ;
			$this->destinationFile = dirname($source) . '/' . md5(microtime()) . '.' . $ext;
		}
		return $this->destination ;
	}
	
	//-----------------
	
	function setImageMagickPath ($path){
		if($path != '')
			if ( strpos($path, '/') < strlen($path))
			$path .= '/';
		$this->imageMagickPath = str_replace(' ','\ ',$path) ;
	}
	
	function getImageMagickPath (){
		return $this->imageMagickPath;
	}
	//-----------------
	function setImageQuality($value){
		$this->imageQuality = intval($value);
		return $this;
	}
	
	function getImageQuality(){
		return $this->imageQuality;
	}
	
	//-----------------
	
	function getHistory( $type = Null ){
		switch ($type){
	
			case JO_Phpthumb_Factory_Imagick_Phmagick_History::returnCsv:
				return explode(',', array_unique($this->history));
				break;
	
			default:
			case JO_Phpthumb_Factory_Imagick_Phmagick_History::returnArray :
				return array_unique($this->history) ;
				break;
	
		}
	}
	
	public function setHistory($path){
		$this->history[] = $path ;
		return $this;
	}
	
	public function clearHistory(){
		unset ($this->history);
		$this->history = array();
	}
	
	
	public function requirePlugin($name, $version=null){
	
		if(key_exists($name, $this->loadedPlugins)) {
			if(! is_null($version)) {
				if( property_exists($this->loadedPlugins[$name], 'version') ){
					if($this->loadedPlugins[$name]->version > $version)
						return true;
	
					if($this->debug) throw new JO_Phpthumb_Factory_Imagick_Phmagick_Exception ('Plugin "'.$name.'" version ='.$this->loadedPlugins[$name]->version . ' required >= ' . $version);
				}
			}
			return true ;
		}
	
		if($this->debug) throw new JO_Phpthumb_Factory_Imagick_Phmagick_Exception ('Plugin "'.$name.'" not found!');
		return false;
	}
	
	//-----------------
	
	private function loadPlugins(){
		$base = dirname(__FILE__) . '/Phmagick/Plugins';
		$plugins = glob($base . '/*.php');
		foreach($plugins as $plugin){
			include_once $plugin ;
			$name = basename($plugin, '.php');
			$className = 'JO_Phpthumb_Factory_Imagick_Phmagick_Plugins_'.$name ;
			$obj = new $className();
			$this->loadedPlugins[$name] = $obj ;
			foreach (get_class_methods($obj) as $method )
				$this->availableMethods[$method] = $name ;
		} 
	}
	
	
	public function execute($cmd){
	
		$ret = null ;
		$out = array();
	
		if($this->escapeChars) {
			$cmd= str_replace    ('(','\(',$cmd);
			$cmd= str_replace    (')','\)',$cmd);
		}
		
		if(function_exists('exec')) {
			exec( $cmd .' 2>&1', $out, $ret);
		} else {
			throw new JO_Phpthumb_Factory_Imagick_Phmagick_Exception ('exec() has been disabled' );
		}
		
		if($ret != 0)
			if($this->debug) trigger_error (new JO_Phpthumb_Factory_Imagick_Phmagick_Exception ('Error executing "'. $cmd.'" <br>return code: '. $ret .' <br>command output :"'. implode("<br>", $out).'"' ), E_USER_NOTICE );
	
		$this->log[] = array(
				'cmd' => $cmd
				,'return' => $ret
				,'output' => $out
		);
	
		return $ret ;
	}
	
	public function __call($method, $args){
		if(! key_exists($method, $this->availableMethods))
			throw new Exception ('Call to undefined method : ' . $method);
	
		array_unshift($args, $this);
		$ret = call_user_func_array(array($this->loadedPlugins[$this->availableMethods[$method]], $method), $args);
	
		if($ret === false)
			throw new Exception ('Error executing method "' . $method ."'");
	
		return $ret ;
	}
}
	
?>