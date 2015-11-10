<?php

class Helper_ObjectCall extends ArrayObject {
	
	public function __construct($children, $param = '') {
		$object = $method = '';
		if(is_array($children)) {
			$object = $children[0];
			$method = isset($children[1]) ? $children[1] : 'index';
		} elseif(preg_match('/^([a-z0-9_]{1,})(->|::|\/)?([a-z0-9_]{1,})?$/i', $children, $match)) {
			$object = $match[1];
			$method = (isset($match[3]) && $match[3] ? $match[3] : false);
		}
		
		if($object) {
			$object = $this->_formatName($object);
			if(!class_exists($object, false)) {
				JO_Loader::loadClass($object);
			}
			$class = new $object($param);
			if($method) {
				parent::__construct($class->$method($param));
			} else {
				return $class;
			}
		}
	}
	
	/**
	 * @param string $unformatted
	 * @param bool $isAction
	 * @return string
	 */
	protected function _formatName($unformatted)
    {
        $segments = explode('_', $unformatted);

        foreach ($segments as $key => $segment) {
            $segment        = str_replace(array('-', '.'), ' ', strtolower($segment));
            $segment        = preg_replace('/[^a-z0-9 ]/', '', $segment);
            $segments[$key] = str_replace(' ', '', ucwords($segment));
        }

        return implode('_', $segments);
    }

}

?>