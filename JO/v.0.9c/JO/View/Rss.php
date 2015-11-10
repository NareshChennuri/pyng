<?php

class JO_View_Rss extends JO_View_Abstract {
	
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
		$site_logo = false;
		if(JO_Registry::get('site_logo') && file_exists(BASE_PATH . '/' . JO_Registry::get('site_logo'))) {
		    $site_logo = JO_Registry::get('site_logo'); 
		}
		
		$result = '<?xml version="1.0" encoding="utf-8" ?>
					<rss version="2.0">
					  <channel>
					    <title>'.JO_Registry::get('meta_title').'</title>
					    <link>'.JO_Request::getInstance()->getBaseUrl().'</link>
					    <description><![CDATA['.JO_Registry::get('meta_description').']]></description>
					    '.($site_logo ? '<image>
					        <url>'.JO_Request::getInstance()->getBaseUrl() . $site_logo.'</url>
					        <link>'.JO_Request::getInstance()->getBaseUrl().'</link>
					    </image>' : '') .'
						'.$this->data.'
					</channel>
					</rss> ';
		return $result;
	}
	
	public function array_transform($array){
		$array_text = '';
		if(isset($array['item'])) { 
			foreach($array['item'] as $values){
				$array_text .= "<item>\n";
				foreach($values AS $key => $value) {
					if($key == 'description') {
						$array_text .=  "<$key><![CDATA[$value]]></$key>\n";
					} elseif ($key == 'enclosure') {
						if($value) {
//							$image_info = @getimagesize($value);
//							if($image_info && isset($image_info['mime'])) {
//								$array_text .=  "<$key url=\"".$value."\" type=\"".$image_info['mime']."\" />\n";
//							} else {
								$ext = explode('?',pathinfo($value, PATHINFO_EXTENSION));
								$array_text .=  "<$key url=\"".$value."\" type=\"".JO_File_Ext::getMimeFromExt($ext[0])."\" />\n";
//							}
						}
					} else {
						$array_text .=  "<$key>$value</$key>\n";
					}
				}
				$array_text .= "</item>\n";
			}
		}
		return $array_text;
	}
	
}