<?php

class Helper_Externallinks {

	public function fixExternallinks($text) {
		
		static $request = null, $external_urls = null;
		if($request === null) { $request = JO_Request::getInstance(); }
		if($external_urls === null) { $external_urls = JO_Registry::get('config_fix_external_urls'); }
		
		if(!$external_urls) {
			return $text;
		} 
		
		$dom = new JO_Html_Dom();
		$dom->load($text);
		$tags = $dom->find('a[href!^='.$request->getDomain().']');
		
		foreach($tags AS $tag) {
			if( stripos(trim($tag->href), 'http') === 0) {
				$tag->rel = 'nofollow';
				if($tag->target) {
					unset($tag->target);
				}
				$tag->onclick = ($tag->onclick ? $tag->onclick . ';' : '') . "target='_blank';";
			}
		}
		
		return (string)$dom;
	}
	
}

?>