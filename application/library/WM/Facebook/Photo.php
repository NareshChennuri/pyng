<?php
/*
 *
 * require 'curl.php';
 *
 * $photo="https://graph.facebook.com/me/picture?access_token=".$session['access_token'];
 *
 * $sample = new sfFacebookPhoto;
 *
 * $thephotoURL=$sample->getRealUrl($photo);
 *
 * echo $thephotoURL;
 *  
 */
class WM_Facebook_Photo {

	private $useragent = 'WM Framework FacebookPhoto PHP5 (curl)';
    private $curl = null;
    private $response_meta_info = array();
    private $header = array(
            "Accept-Encoding: gzip,deflate",
            "Accept-Charset: utf-8;q=0.7,*;q=0.7",
            "Connection: close"
        );

    public function __construct() {
    	if(function_exists('curl_init')) {
	        $this->curl = curl_init();
	        register_shutdown_function(array($this, 'shutdown'));
    	}
    }

    /**
     * Get the real url for picture to use after
     */
    public function getRealUrl($photoLink) {        
    	if(!$this->curl) {
    		return false;
    	}    
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($this->curl, CURLOPT_HEADER, false);
        curl_setopt($this->curl, CURLOPT_USERAGENT, $this->useragent);
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 15);
        curl_setopt($this->curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($this->curl, CURLOPT_URL, $photoLink);
        //this assumes your code is into a class method, and uses $this->readHeader as the callback //function
        curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, array(&$this,'readHeader'));
        $response = curl_exec($this->curl);
        if(!curl_errno($this->curl)) {
            $info = curl_getinfo($this->curl);
             if($info["http_code"] == 302) {
                 $headers = $this->getHeaders();
                 if(isset($headers['fileUrl'])) {
                     return $headers['fileUrl'];
                 } elseif(isset($headers['Location'])) {
                 	return $headers['Location'];
                 }
             }
        }
        return false;
    }

    /**
	 * CURL callback function for reading and processing headers
	 * Override this for your needs
	 *
	 * @param object $ch
	 * @param string $header
	 * @return integer
	 */
	private function readHeader($ch, $header) {
	        //extracting example data: filename from header field Content-Disposition
	        $filename = $this->extractCustomHeader('Location: ', '\n', $header);
	        if ($filename) {
	            $this->response_meta_info['fileUrl'] = trim($filename);
	        }
	        return strlen($header);
	}
	
	private function extractCustomHeader($start,$end,$header) {
	    $pattern = '/'. $start .'(.*?)'. $end .'/';
	    if (preg_match($pattern, $header, $result)) {
	        return $result[1];
	    } else {
	        return false;
	    }
	}

    public function getHeaders() {
        return $this->response_meta_info;
	}

    /**
     * Cleanup resources
     */
    public function shutdown() {
        if($this->curl) {
            curl_close($this->curl);
        }
    }
	
}

?>