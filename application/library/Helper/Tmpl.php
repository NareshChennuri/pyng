<?php

class Helper_Tmpl {
	
	public $template;
	public $templates = array();
	public $data = array();
	
	public $cache_enable = true;
	
	public $cache_live = 600;
	
	public $template_time = array();
	
	public $tags = array(
			'tmpl' => array(
					'open' => ''
			),
			'wrap' => array(
					'open' => '',
			),
			'each' => array(
					'_default' => array('$2' => '$index => $value'),
					'open' => '\'; if(isset($1a)) { foreach($1a AS $2) { $result .= \'',
					'close' => '\'; }} $result .= \''
			),
			'if' => array(
					'open' => '\'; if(($notnull_1) && $1a){ $result .= \'',
					'close' => '\'; } $result .= \''
			),
			'else' => array(
					'_default' => array('$1' => 'true'),
					'open' => '\'; } else if(($notnull_1) && $1a){ $result .= \''
			),
			'html' => array(
					'open' => '\' . (isset($1a) ? html_entity_decode($1a) : \'\') . \''
			),
			'=' => array(
					'_default' => array('$1' => '$data'),
					'open' => '\'. (isset($1a) ? $1a : \'\') .\''
			),
			'!' => array(
					'open' => ''
			)
	);
	
	public function getData($template) {
		$tpl = Helper_Config::get('template');
		
		if(file_exists(BASE_PATH . '/data/templates/' . $tpl . '/' . $template . '.html')) {
			$this->template_time[$template] = filemtime(BASE_PATH . '/data/templates/' . $tpl . '/' . $template . '.html');
			return @file_get_contents(BASE_PATH . '/data/templates/' . $tpl . '/' . $template . '.html');
		}
		return '';
	}
	
	public function __construct($template, $data) {
		if(isset($this->templates[$template])) {
			$this->template = $this->templates[$template];
		} else {
			$template_data = $this->getData($template);
			$this->template = $template_data;
			$this->templates[$template] = $template_data;
		}
		$this->data = $data; 
	}
	
	public function funct($a) {
		$all = $a[0];
		$slash = isset($a[1])?$a[1]:false;
		$type = isset($a[2])?$a[2]:false;
		$fnargs = isset($a[3])?$a[3]:false;
		$target = isset($a[4])?$a[4]:false;
		$parens = isset($a[5])?$a[5]:false;
		$args = isset($a[6])?$a[6]:false;
	
		$expr = $exprAutoFnDetect = '';
	
		if(!isset($this->tags[$type])) {
			throw new Exception('Unknown template tag: ' . $type);
		}
	
		$tag = $this->tags[$type];
		$def = isset($tag['_default'])&&$tag['_default']?$tag['_default']:'';
	
		$return = '';
		$return .= isset($tag[ !$slash ? 'open' : 'close' ])?$tag[ !$slash ? 'open' : 'close' ]:'';
	
		$def = isset($tag['_default'])&&$tag['_default']?$tag['_default']:'';
	
		if($parens && !preg_match('/\w$/', $target)) {
			$target .= $parens;
			$parens = '';
		}
	
		$target = trim($target);
	
		if(strpos($target,'.')) {
	
			if($target) {
	
				if(substr($target,0,1) == '$') {
					$ex = preg_split('/[.\[\]]/',$target);
					$ex = $this->removeEmpty($ex);
					$f = array_shift($ex);
					$ret = $f;
					if($ex) {
						$ret .= '["' . implode('"]["',$ex) . '"]';
					}
					/*$ex = explode('.',$target);
					$f = array_shift($ex);
					$ret = $f;
					if($ex) { var_dump($ex);
						if(preg_match_all('/\[([^\]]*)\]/i','['.implode('][',$ex).']', $m)) {
							$tmp = explode('[',implode('][',$ex));
							//$ret .= '["'.trim(array_shift($tmp),'[]').'"]';
							$ret .= '["' . implode('"]["',$m[1]) . '"]';
							 exit;
						} else {
							$ret .= '["' . implode('"]["',$ex) . '"]';
						}
					}*/ 
					$return = implode( $ret , explode('$1a', $return));
					$return = implode( 'isset('.$ret.')' , explode('$notnull_1', $return));	
				} else {
					$return = implode( '$this_data["'.implode('"]["',explode('.',$target)).'"]' , explode('$1a', $return));
					$return = implode( 'isset($this_data["'.implode('"]["',explode('.',$target)).'"])' , explode('$notnull_1', $return));
				}
	
				if($parens) {
					$return = implode( $target . $parens , explode('$1', $return));
				} else {
					$return = implode( $target , explode('$1', $return));
				}
	
			} else if(isset($def['$1'])) {
				$return = implode( $def['$1'] , explode('$notnull_1', $return));
				$return = implode( $def['$1'] , explode('$1a', $return));
			} else {
				$return = implode( 'NULL' , explode('$notnull_1', $return));
				$return = implode( 'NULL' , explode('$1a', $return));
			}
		} else {
	
			if($target) {
					
				if(substr($target,0,1) == '!') {
					$target = substr($target, 1);
					$return = implode( '(!isset($this_data["' . $target . '"]) OR !$this_data["' . $target . '"])' , explode('$1a', $return));
					$return = implode( '1' , explode('$notnull_1', $return));
					
				} elseif(substr($target,0,1) == '$') {
					/*$ex = explode('.',$target);
					$f = array_shift($ex);
					$ret = $f;
					if($ex) {
						$ret .= '["' . implode('"]["',$ex) . '"]';
					}*/
					$ex = preg_split('/[.\[\]]/',$target);
					$ex = $this->removeEmpty($ex);
					$f = array_shift($ex);
					$ret = $f;
					if($ex) {
						$ret .= '["' . implode('"]["',$ex) . '"]';
					}
					$return = implode( $ret , explode('$1a', $return));
					$return = implode( '('.$ret.')' , explode('$notnull_1', $return));
					
				} elseif(strpos($target,'%')!==false) {
					$ex = explode('%',$target);
					$f = array_shift($ex);
					$ret = $f;
					//if($ex) {
					//	$ret .= '["' . implode('"]["',$ex) . '"]';
					//}
					//$return = implode( $ret , explode('$1a', $return));
					//$return = implode( '('.$ret.')' , explode('$notnull_1', $return));
					
					$return = implode( 'isset($this_data["' . $ret . '"])' , explode('$notnull_1', $return));
					$return = implode( '$this_data["' . $ret . '"]%'.implode('%',$ex) , explode('$1a', $return));
					if($parens) {
						$return = implode( $target , explode('$1', $return));
					}
					
				} elseif(strpos($target, ' ')) {
					$parts = explode(' ', $target);
					$tmp = array();
					foreach($parts AS $part) {
						$part = trim($part);
						if( preg_match('/[a-z0-9_]/i', $part) ) {
							$tmp[] = ('(isset($this_data["' . $part . '"]) && $this_data["' . $part . '"])');
						} else {
							$tmp[] = $part;
						}
					}
					$return = implode( 'true' , explode('$notnull_1', $return));
					$return = implode( implode(' ', $tmp) , explode('$1a', $return));
					if($parens) {
						$return = implode( implode(' ', $tmp) , explode('$1', $return));
					}
				} else {
	
					$return = implode( 'isset($this_data["' . $target . '"])' , explode('$notnull_1', $return));
					$return = implode( '$this_data["' . $target . '"]' , explode('$1a', $return));
					if($parens) {
						$return = implode( $target , explode('$1', $return));
					}
				}
			} else if(isset($def['$1'])) {
				$return = implode( $def['$1'] , explode('$notnull_1', $return));
				$return = implode( $def['$1'] , explode('$1a', $return));
			} else {
				$return = implode( 'NULL' , explode('$notnull_1', $return));
				$return = implode( 'NULL' , explode('$1a', $return));
			}
				
		}
	
		$return = implode( $fnargs ? $fnargs : (isset($def['$2'])?$def['$2']:'') , explode('$2', $return));
	
		return $return;
	
	}
	
	private function removeEmpty($array) {
		$t = array();
		foreach($array AS $k => $a) {
			if($a !== '') {
				$t[$k] = $a;
			}
		}
		return $t;
	}
	
	public function render($name = null) {
		$this->template = preg_replace('/\$\{([^\}]*)\}/', '{{= $1}}', $this->template);
		$this->template = str_replace("'", "\'", $this->template);
		
		$this->template = preg_replace_callback('/\{\{(\/?)(\w+|.)(?:\(((?:[^\}]|\}(?!\}))*?)?\))?(?:\s+(.*?)?)?(\(((?:[^\}]|\}(?!\}))*?)\))?\s*\}\}/i', array($this,'funct'), $this->template);
		
		$this->template = '$result = \''.$this->template.'\'; return $result;';
		
		if($this->cache_enable) {
			
			$tpl = Helper_Config::get('template');
			
			$patch = BASE_PATH . '/cache/templates/';
			if(!file_exists($patch)) {
				mkdir($patch, 0777, true);
			}
			
			$fnc = 'response_' . $name;
			
			$orig_name = $name;
			$name = $name . '.cache';
			
			$this->template = 'function ' . $fnc . '($this_data) { ' . $this->template . ' }';
			if(file_exists($patch . $name)) { 
				if(isset($this->template_time[$orig_name]) && $this->template_time[$orig_name] && $this->template_time[$orig_name] > filemtime($patch . $name)) {
					file_put_contents($patch . $name, '<?php ' . $this->template . ' ?>');
					include_once $patch . $name;
					return $fnc($this->data);
				} elseif(filemtime( $patch . $name ) + $this->cache_live < time()) {
					file_put_contents($patch . $name, '<?php ' . $this->template . ' ?>');
					include_once $patch . $name;
					return $fnc($this->data);
				} else {
					include_once $patch . $name;
					return $fnc($this->data);
				}
			} else {
				//create cache
				file_put_contents($patch . $name, '<?php ' . $this->template . ' ?>');
				include_once $patch . $name;
				return $fnc($this->data);
			}
			//echo $this->template;
		} else {
		
			try {
				$call = create_function('$this_data', $this->template);
			} catch(Exception $e) {
				throw new Exception($e);
			}
			return $call($this->data);
		
		}
	}
	
}

?>