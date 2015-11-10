<?php

class JO_Registry extends ArrayObject
{

    private static $_registryClassName = 'JO_Registry';

    private static $_registry = null;

    /**
     * @return JO_Registry
     */
    public static function getInstance()
    {
        if (self::$_registry === null) {
            self::init();
        }

        return self::$_registry;
    }

    public static function setInstance(JO_Registry $registry)
    {
        if (self::$_registry !== null) {
            throw new Exception('Registry is already initialized');
        }

        self::setClassName(get_class($registry));
        self::$_registry = $registry;
    }

    protected static function init()
    {
        self::setInstance(new self::$_registryClassName());
    }

    public static function setClassName($registryClassName = 'JO_Registry')
    {
        if (self::$_registry !== null) {
            throw new Exception('Registry is already initialized');
        }

        if (!is_string($registryClassName)) {
            throw new Exception("Argument is not a class name");
        }

        if (!class_exists($registryClassName)) {
            require_once 'JO_Loader.php';
            JO_Loader::loadClass($registryClassName);
        }

        self::$_registryClassName = $registryClassName;
    }

    public static function _unsetInstance()
    {
        self::$_registry = null;
    }

    /**
     * @param string $index
     * @return mixed
     */
    public static function get($index)
    {
        $instance = self::getInstance();

        if ($instance->offsetExists($index)) 
        return $instance->offsetGet($index);
    }
    
    /**
     * @param string $index
     * @return mixed|NULL
     */
    public static function forceGet($index) {
    	$instance = self::getInstance();
    	if($instance->offsetExists($index)) {
    		return $instance->get($index);
    	}
    	return null;
    }
    
    /**
     * @param string $key
     * @return NULL|NULL|Ambigous <mixed, NULL>
     */
    public static function getArray($key) {
    	$instance = self::getInstance();
    	
   	 	$array_keys = array();
		if(preg_match('/^([^\[]{1,})\[(.*)\]+$/', $key, $match)) {
			$array_keys[] = $match[1];
			$ns = explode('[', '['.$match[2].']');
			foreach($ns AS $nss) {
				if($nss) {
					$array_keys[] = trim($nss, '][');
				}
			}

			if(!$array_keys) {
				return null;
			}
			
			$buf = $instance;

			foreach($array_keys AS $k) {
				if(isset($buf[$k])) {
					$buf = $buf[$k];
				} else {
					$buf = null;
				}
			}
			return $buf;
		} else {
			return self::forceGet($key);
		}
    }

    /**
     * @param string $index
     * @param mixed $value
     */
    public static function set($index, $value)
    {
        $instance = self::getInstance();
        $instance->offsetSet($index, $value);
    }

    /**
     * @param string $index
     * @return string
     */
    public static function isRegistered($index)
    {
        if (self::$_registry === null) {
            return false;
        }
        return self::$_registry->offsetExists($index);
    }

    public function __construct($array = array(), $flags = parent::ARRAY_AS_PROPS)
    {
        parent::__construct($array, $flags);
    }


    public function offsetExists($index)
    {
        return array_key_exists($index, $this);
    }

}
 
