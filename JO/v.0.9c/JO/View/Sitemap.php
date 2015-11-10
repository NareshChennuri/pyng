<?php

class JO_View_Sitemap extends JO_View_Abstract {
	
	private $data;
	
	public function __construct($view) {	
		
		$response = JO_Response::getInstance();
		$response->addHeader('Cache-Control: no-cache, must-revalidate');
    	$response->addHeader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    	$response->addHeader('Content-type: application/xml');
    	$response->setLevel(9);
		
		$this->data = $this->array_transform($view->getAll());
	}
	
	public function __toString() {
		$result = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n" . $this->data . "</urlset>";
		return $result;
	}
	
	public function array_transform($array){
		
		$array_text = '';
		if(isset($array['url'])) { 
			foreach($array['url'] as $values){
				$array_text .= "<url>\n";
				foreach($values AS $key => $value) {
					$array_text .=  "<$key>$value</$key>\n";
				}
				$array_text .= "</url>\n";
			}
		}
		return $array_text;
	}
	
}