<?php

class MinifyController extends Helper_Controller_Default {

	public function init() {
		error_reporting(0);
	}
	
	/*public function indexAction() {
		
		$request = $this->getRequest();
		$response = $this->getResponse();
		
		
		if($request->getQuery('file_image') && file_exists(BASE_PATH . '/'. $request->getQuery('file_image'))) {
			$this->noViewRenderer(true);
			$type = JO_File_Ext::getMimeFromFile($request->getQuery('file_image'));
// 			var_dump($_SERVER['HTTP_IF_MODIFIED_SINCE']); exit;
			header("Cache-Control: public, max-age=10800, pre-check=10800");
			header("Pragma: public");
			header("Expires: " . date(DATE_RFC822,strtotime(" 2 day")));
			header("Content-type: " . $type);
			$etag = md5_file(BASE_PATH . '/'. $request->getQuery('file_image'));
			header("Etag: $etag");
			header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime(BASE_PATH . '/'. $request->getQuery('file_image'))).' GMT', true, 304);
			
			if( $request->getServer('HTTP_IF_MODIFIED_SINCE') ) {
				if( strtotime($request->getServer('HTTP_IF_MODIFIED_SINCE')) == filemtime(BASE_PATH . '/'. $request->getQuery('file_image'))) {
					header("HTTP/1.1 304 Not Modified");
					exit;
				}
			}
			
			ob_start();
			readfile(BASE_PATH . '/'. $request->getQuery('file_image'));
			$ImageData = ob_get_contents();
			$ImageDataLength = ob_get_length();
			//ob_end_clean();
			header("Content-Length: ".$ImageDataLength);
			
			exit;
			
		} else {
			$this->forward('error', 'error404');
		}
		
	}*/
	
	public function indexAction() {
		
		$request = $this->getRequest();
		$response = $this->getResponse();
		
		$file_path = $request->getQuery('file_path');
		
		if($file_path && file_exists(BASE_PATH . '/'. $file_path)) {
			$this->noViewRenderer(true);

			$type = JO_File_Ext::getMimeFromFile($file_path);
			
			$live = 86400*365;
			
			$response->addHeader("Cache-Control: private, max-age=$live, pre-check=$live");
			$response->addHeader("Pragma: private");
			$response->addHeader("Expires: " . date(DATE_RFC822, ( time()+$live ) ));
			$response->addHeader("Content-type: " . $type);
			$etag = md5_file(BASE_PATH . '/'. $file_path);
			
			$lastmodified = max(0, filemtime(BASE_PATH . '/'. $file_path));
			$hash = $lastmodified . '-' . md5_file(BASE_PATH . '/'. $file_path);
			
			$response->addHeader('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime(BASE_PATH . '/'. $file_path)).' GMT', true, 304);
			
			if( $request->getServer('HTTP_IF_MODIFIED_SINCE') && strtotime($request->getServer('HTTP_IF_MODIFIED_SINCE')) == filemtime(BASE_PATH . '/'. $file_path) ) {
					$response->addHeader("HTTP/1.1 304 Not Modified");
					$response->appendBody('');
					exit;
			} else {
				$response->addHeader("Etag: $hash");
			}
			
			
			$fileData = @file_get_contents(BASE_PATH . '/'. $file_path);
		
			$response->addHeader("Content-Length: ".strlen($fileData));
			$response->setLevel(9);
			$response->appendBody($fileData);
			exit;
			
		} else {
			$this->forward('error', 'error404');
		}
		
	}
	
	public function jscssAction() {
		
		$request = $this->getRequest();
		$response = $this->getResponse();
		
		$file_path = $request->getQuery('file_path');
		
		if($file_path && file_exists(BASE_PATH . '/'. $file_path)) {
			$this->noViewRenderer(true);

			$type = JO_File_Ext::getMimeFromFile($file_path);
			
			$live = 86400*365;
			
			$response->addHeader("Cache-Control: private, max-age=$live, pre-check=$live");
			$response->addHeader("Pragma: private");
			$response->addHeader("Expires: " . date(DATE_RFC822, ( time()+$live ) ));
			$response->addHeader("Content-type: " . $type);
			$etag = md5_file(BASE_PATH . '/'. $file_path);
			
			$lastmodified = max(0, filemtime(BASE_PATH . '/'. $file_path));
			$hash = $lastmodified . '-' . md5_file(BASE_PATH . '/'. $file_path);
			
			$response->addHeader('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime(BASE_PATH . '/'. $file_path)).' GMT', true, 304);
			
			if( $request->getServer('HTTP_IF_MODIFIED_SINCE') && strtotime($request->getServer('HTTP_IF_MODIFIED_SINCE')) == filemtime(BASE_PATH . '/'. $file_path) ) {
					$response->addHeader("HTTP/1.1 304 Not Modified");
					$response->appendBody('');
					exit;
			} else {
				$response->addHeader("Etag: $hash");
			}
			
			
			$fileData = @file_get_contents(BASE_PATH . '/'. $file_path);
		
			$response->addHeader("Content-Length: ".strlen($fileData));
			$response->setLevel(9);
			$response->appendBody($fileData);
			exit;
			
		} else {
			$this->forward('error', 'error404');
		}
		
	}

	
}

?>