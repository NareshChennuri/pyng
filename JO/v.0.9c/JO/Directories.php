<?php

class JO_Directories extends DirectoryIterator /*implements RecursiveIterator*/ {

	/*public static function test() {
		    $directory = dirname(__FILE__);
		    $filenames = array();
		    $iterator = new JO_Directories($directory);
		    foreach ($iterator as $fileinfo) {
		        if ($fileinfo->isFile()) {
		            $filenames[$fileinfo->getMTime()] = $fileinfo->getType();
		        }
		    }
		    ksort($filenames);
		    print_r($filenames);
	}*/
	
}

?>