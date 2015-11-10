<?php

class JO_Loader {
 
    /**
     * @param string $class
     * @param string|null $dirs
     */
    public static function loadClass($class, $dirs = null) {
        if (class_exists($class, false) || interface_exists($class, false)) {
            return;
        }

        if ((null !== $dirs) && !is_string($dirs) && !is_array($dirs)) {
            throw new Exception('Directory argument must be a string or an array');
        }

        $className = ltrim($class, '\\');
        $file      = '';
        $namespace = '';
        $lastNsPos = strripos($className, '\\');
        if ($lastNsPos) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $file      = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $file .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        if (!empty($dirs)) {
            // use the autodiscovered path
            $dirPath = dirname($file);
            if (is_string($dirs)) {
                $dirs = explode(PATH_SEPARATOR, $dirs);
            }
            foreach ($dirs as $key => $dir) {
                if ($dir == '.') {
                    $dirs[$key] = $dirPath;
                } else {
                    $dir = rtrim($dir, '\\/');
                    $dirs[$key] = $dir . DIRECTORY_SEPARATOR . $dirPath;
                }
            } 
            $file = basename($file); 
            self::loadFile($file, $dirs, true);
        } else {
            self::loadFile($file, null, true);
        }

        if (!class_exists($class, false) && !interface_exists($class, false)) {
            throw new Exception("File \"$file\" does not exist or class \"$class\" was not found in the file"); 
        } 
    }

    /**
     * @param string $filename
     * @param string|null $dirs
     * @param bool $once
     * @return string
     */
    public static function loadFile($filename, $dirs = null, $once = false)
    { 
        self::_securityCheck($filename);

		if($dirs) {
		    $fileforcheck = $dirs . $filename;
		} else {
		    $fileforcheck = $filename;
		}

		if( ! self::isReadable($fileforcheck) ) {
	            throw new Exception('File ' . $filename . ' not exist');
		}

        $incPath = false;
        if (!empty($dirs) && (is_array($dirs) || is_string($dirs))) {
            if (is_array($dirs)) {
                $dirs = implode(PATH_SEPARATOR, $dirs);
            }
            $incPath = get_include_path();
            set_include_path($dirs . PATH_SEPARATOR . $incPath);
        }

        if ($once) {
            include_once $filename;
        } else {
            include $filename;
        }

        if ($incPath) {
            set_include_path($incPath);
        }

        return true;
    }

    /**
     * @param string $filename
     */
    protected static function _securityCheck($filename) {
        if (preg_match('/[^a-z0-9\\/\\\\_.:-]/i', $filename)) {
            throw new Exception('Security check: Illegal character in filename');
        }
    }

    /**
     * @param string $path
     * @return multitype:
     */
    public static function explodeIncludePath($path = null) {
        if (null === $path) {
            $path = get_include_path();
        }

        if (PATH_SEPARATOR == ':') {
            // On *nix systems, include_paths which include paths with a stream 
            // schema cannot be safely explode'd, so we have to be a bit more
            // intelligent in the approach.
            $paths = preg_split('#:(?!//)#', $path);
        } else {
            $paths = explode(PATH_SEPARATOR, $path);
        }
        return $paths;
    }

    /**
     * @param string $filename
     * @return string|string|string|string
     */
    public static function isReadable($filename) {
        if (is_readable($filename)) {
            // Return early if the filename is readable without needing the 
            // include_path
            return true;
        }

        foreach (self::explodeIncludePath() as $path) {
            if ($path == '.') {
                if (is_readable($filename)) {
                    return true;
                }
                continue;
            }
            $file = $path . '/' . $filename;
            if (is_readable($file)) {
                return true;
            }
        }
        return false;
    }
    
	/**
	 * @param array $paths
	 * @return string
	 */
	public static function setIncludePaths(array $paths ) {
		foreach( $paths AS $path ) { 
		    set_include_path(implode(PATH_SEPARATOR, array(
				realpath($path),
				get_include_path(),
		    )));
		}
		return true;
    }

}
 
