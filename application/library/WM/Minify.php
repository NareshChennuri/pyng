<?php

class WM_Minify  {
	
	/**
	 * Minify a CSS-file
	 *
	 * @param string $file The file to be minified.
	 * @return string
	 */
	public static function minifyCSS($file)
	{
		// create unique filename
		$fileName = md5($file) . '_'.basename($file);
		$finalURL = 'cache/css/' . $fileName;
		$finalPath = BASE_PATH . '/cache/css/' . $fileName;

		if(!file_exists(BASE_PATH . '/cache/css/')) {
			mkdir(BASE_PATH . '/cache/css/', 0777, true);
		}
		
		
		// check that file does not yet exist or has been updated already
		if(!file_exists($finalPath) || filemtime(BASE_PATH . '/' . $file) > filemtime($finalPath))
		{
			// minify the file
			$css = new JO_Minify_Css(BASE_PATH . '/' . $file);
			$css->setFileBase( JO_Request::getInstance()->getBaseUrl() . dirname($file) );
			$css->setFile($file);
			$css->minify($finalPath);
		}

		return $finalURL;
	}

	/**
	 * Minify a JS-file
	 *
	 * @param string $file The file to be minified.
	 * @return string
	 */
	public static function minifyJs($file)
	{
		// create unique filename
		$fileName = md5($file) . '_'.basename($file);
		$finalURL = 'cache/js/' . $fileName;
		$finalPath = BASE_PATH . '/cache/js/' . $fileName;

		if(!file_exists(BASE_PATH . '/cache/js/')) {
			mkdir(BASE_PATH . '/cache/js/', 0777, true);
		}
		
		// check that file does not yet exist or has been updated already
		if(!file_exists($finalPath) || filemtime(BASE_PATH . '/' . $file) > filemtime($finalPath))
		{
			// minify the file
			$css = new JO_Minify_Js(BASE_PATH . '/' . $file);
			$css->minify($finalPath);
		}

		return $finalURL;
	}
	
}

?>