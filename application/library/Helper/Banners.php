<?php

class Helper_Banners {
	
	public static function returnHtml($banners) {
		if(count($banners)) { 
			static $view = null, $request = null;
			if($view === null) { $view = JO_View::getInstance(); }
			if($request === null) { $request = JO_Request::getInstance(); }
		
			foreach($banners AS $banner) {
				$banner['html'] = html_entity_decode($banner['html']);
				$view->banner = $banner;
				return $view->render('box', 'banners');
			}
			
		}
	}

}

?>