<?php

class JO_Shell {
	
	private static $_instance;
	
	private $argv = array();
	
	
	/**
	 * @param array $options
	 * @return JO_Request
	 */
	public static function getInstance($options = array()) {
		if(self::$_instance == null) {
			self::$_instance = new self($options);
		}
		return self::$_instance;
	}
	
	public function __construct() {}
	
	public static function setArgv($argv) {
		self::getInstance();
		if(is_array($argv)) {
			self::$_instance->argv = self::$_instance->Args($argv);
		}
		return self::$_instance;
	}
	
	public static function getArgv($key = null) {
		self::getInstance();
		if($key === null) {
			return self::$_instance->argv;
		} else {
			return isset(self::$_instance->argv[$key]) ? self::$_instance->argv[$key] : '';
		}
	}

	public static function backgroundRun() { /// !NOTE first argument is command to use , seccond is (file if php else first arg of list ) and third is arguments list 
	    $microtime=str_replace(" ","",microtime());
	    
	    $arg_list = func_get_args();
	    $comto = array_shift($arg_list);
	    $file = array_shift($arg_list);
	    $arg_for_exec = @implode(' ', $arg_list);

	    if(file_exists($file)){
		$command=$comto.' '.$file.' '.$microtime.' '.$arg_for_exec.' > /dev/null & echo $!';
	    }else{
		$command=$comto.' '.$file.' '.$microtime.' '.$arg_for_exec.' > /dev/null & echo $!';
		//for php5 comto is "php5 -r"
	    }
	    
	    $res=array(
		    'micro'=>$microtime,
		    'comamnd'=>$command,
		    'pid'=>shell_exec($command)
	    );
	    return $res;
	}
	
	public static function backgroundCheck($res){
	    return shell_exec("ps xauw | grep {$res['microtime']}");
	}
	
	/* cmd part */
	public function Args($argv) {
	    array_shift($argv);
	    $out = array();
	    foreach ($argv as $arg){
	        if (substr($arg,0,2) == '--'){
	            $eqPos = strpos($arg,'=');
	            if ($eqPos === false){
	                $key = substr($arg,2);
	                $out[$key] = isset($out[$key]) ? $out[$key] : true;
	            } else {
	                $key = substr($arg,2,$eqPos-2);
	                $out[$key] = substr($arg,$eqPos+1);
	            }
	        } else if (substr($arg,0,1) == '-'){
	            if (substr($arg,2,1) == '='){
	                $key = substr($arg,1,1);
	                $out[$key] = substr($arg,3);
	            } else {
	                $chars = str_split(substr($arg,1));
	                foreach ($chars as $char){
	                    $key = $char;
	                    $out[$key] = isset($out[$key]) ? $out[$key] : true;
	                }
	            }
	        } else {
	            $out[] = $arg;
	        }
	    }
	    return $out;
	}

}

?>