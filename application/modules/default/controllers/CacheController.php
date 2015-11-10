<?php

class CacheController extends Helper_Controller_Default {
	
	public function indexAction() {
		$this->forward('error', 'error404');
	}
	
	public function imagesAction() {
        $file = $this->getRequest()->getParam('file');
        if(!$file) {
        	$file = $this->getRequest()->getParam('setFile');
        }
        if(!$file || strpos($file, '..') ==! false) {
        	$this->forward('error', 'error404');
        }
        $this->view->renderImage('images/' . $file ,'extensions_' . $this->getRequest()->getParam('extension'));
    }
    
    public function jsAction() {
        $file = $this->getRequest()->getParam('file'); 
        if(!$file) {
        	$file = $this->getRequest()->getParam('setFile');
        }
        if(!$file ||  strpos($file, '..') ==! false) {
        	$this->forward('error', 'error404');
        }
        $this->view->renderJs('js/' . $file ,'extensions_' . $this->getRequest()->getParam('extension'));
    }
    
    public function cssAction() {
        $file = $this->getRequest()->getParam('file');
        if(!$file) {
        	$file = $this->getRequest()->getParam('setFile');
        }
        if(!$file ||  strpos($file, '..') ==! false) {
        	$this->forward('error', 'error404');
        }
        $this->view->renderCss('css/' . $file ,'extensions_' . $this->getRequest()->getParam('extension'));
		
    }
    
    public function cache_imageAction() {
    	$request = $this->getRequest();

    	$width = (int)$request->getParam('width');
    	$height = (int)$request->getParam('height');
    	$cached_file = $request->getParam('cached_file');
    	$file = $request->getParam('file') . $request->getParam('extension');
    	$extension = $request->getParam('extension');
    	$gray = $request->getParam('gray') == 'gray';
    	$crop = $request->getParam('crop') == 'crop';
    	$watermark = $request->getParam('watermark') == 'watermark' ? 'watermark.png' : false;
    	
    	$model_images = new Helper_Images();

    	$image = false;
    	if($width && $height) {
    		$image = $model_images->resize('/'.$file, $width, $height, $crop, $watermark, $gray);
    	} else if($width) {
    		$image = $model_images->resizeWidth('/'.$file, $width, $watermark, $gray);
    	} else if($height) {
    		$image = $model_images->resizeHeight('/'.$file, $height, $watermark, $gray);
    	} else {
    		$this->forward('error', 'error404');
    	}
    	
    	if($image) {
    		
    		$response = $this->getResponse();
    		$response->addHeader('Content-type:' . JO_File_Ext::getMimeFromFile($image));
    		$response->appendBody(file_get_contents( $image ));
    		exit;
    	} else {
    		$this->forward('error', 'error404');
    	}
    }
	
}

?>