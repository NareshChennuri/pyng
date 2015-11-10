<?php

/**
 * Minify abstract class
 *
 * This source file can be used to write minifiers for multiple file types.
 *
 * The class is documented in the file itself. If you find any bugs help me out and report them. Reporting can be done by sending an email to minify@mullie.eu.
 * If you report a bug, make sure you give me enough information (include your code).
 *
 * License
 * Copyright (c) 2012, Matthias Mullie. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products derived from this software without specific prior written permission.
 *
 * This software is provided by the author "as is" and any express or implied warranties, including, but not limited to, the implied warranties of merchantability and fitness for a particular purpose are disclaimed. In no event shall the author be liable for any direct, indirect, incidental, special, exemplary, or consequential damages (including, but not limited to, procurement of substitute goods or services; loss of use, data, or profits; or business interruption) however caused and on any theory of liability, whether in contract, strict liability, or tort (including negligence or otherwise) arising in any way out of the use of this software, even if advised of the possibility of such damage.
 *
 * @author Matthias Mullie <minify@mullie.eu>
 * @version 1.0.0
 *
 * @copyright Copyright (c) 2012, Matthias Mullie. All rights reserved.
 * @license BSD License
 */
abstract class JO_Minify
{
	/**
	 * The data to be minified
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Init the minify class - optionally, css may be passed along already.
	 *
	 * @param string[optional] $css
	 */
	public function __construct()
	{
		// it's possible to add the css through the constructor as well ;)
		$arguments = func_get_args();
		if(func_num_args()) call_user_func_array(array($this, 'add'), $arguments);
	}

	/**
	 * Add a file or straight-up code to be minified.
	 *
	 * @param string $data
	 */
	public function add($data)
	{
		// this method can be overloaded
		foreach(func_get_args() as $data)
		{
			// redefine var
			$data = (string) $data;

			// load data
			$value = $this->load($data);
			$key = ($data != $value) ? $data : 0;

			// initialize key
			if(!array_key_exists($key, $this->data)) $this->data[$key] = '';

			// store data
			$this->data[$key] .= $value;
		}
	}

	/**
	 * Load data.
	 *
	 * @param string $data Either a path to a file or the content itself.
	 * @return string
	 */
	protected function load($data)
	{
		// check if the data is a file
		if(@file_exists($data) && is_file($data))
		{
			// grab content
			return @file_get_contents($data);
		}

		// no file, just return the data itself
		else return $data;
	}

	/**
	 * Save to file
	 *
	 * @param string $content The minified data.
	 * @param string $path The path to save the minified data to.
	 */
	public function save($content, $path)
	{ 
		
		if( @file_put_contents($path, $content) === false ) throw new JO_Minify_Exception('The file "' . $path . '" could not be opened. Check if PHP has enough permissions.');
		
		try {
			$gzdata = gzencode($content, JO_Response::getInstance()->getLevel());
			file_put_contents($path . '.gz', $gzdata);
		} catch (JO_Exception $e) {
		}
	}
}


?>