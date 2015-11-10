<?php

class JO_Api_Facebook {

	/**
     *
     * @var JO_Api_Facebook_Connect 
     */
    protected $_facebook;
    /**
     *
     * @var string
     */
    protected $Application_Id;
    /**
     *
     * @var string
     */
    protected $Application_Secret;
    /**
     *
     * @var string
     */
    protected $Permissions;
    /**
     *
     * @var string
     */
    protected $CallBack;

    /**
     * setup the facebook login functionality!
     * 
     * @param string $Application_Id
     * @param string $Application_Secret
     * @param string $Permissions
     * @param string $callback 
     */
    public function __construct($Application_Id, $Application_Secret, $Permissions = '', $CallBack = '') {
        $this->Application_Id = $Application_Id;
        $this->Application_Secret = $Application_Secret;
        $this->Permissions = $Permissions;
        $this->CallBack = $CallBack;
        $this->_facebook = new JO_Api_Facebook_Connect(array(
                    'appId' => $this->Application_Id,
                    'secret' => $this->Application_Secret,
                    'cookie' => true
                ));
    }

    /**
     * checks for a session
     * @return string html
     */
    public function connection() {
        $session = $this->_facebook->getSession();
        $me = null;
        if ($session) {
            try {
                $uid = $this->_facebook->getUser();
                $me = $this->_facebook->api('/me');
            } catch (JO_Api_Facebook_Exception $e) {
               throw new JO_Exception('connection error facebook',0,$e);
            }
        }



        return "
          <div id=\"fb-root\"></div>
          <script>
            window.fbAsyncInit = function()
            {
                FB.init
                ({
                    appId   : '" . $this->_facebook->getAppId() . "',
                    session : " . json_encode($session) . ",
                    status  : true, // check login status
                    cookie  : true, // enable cookies to allow the server to access the session
                    xfbml   : true // parse XFBML
                });
                FB.Event.subscribe('auth.login', function()
                {
                    window.location.reload();
                });
            };

          (function()
          {
            var e = document.createElement('script');
            e.src = document.location.protocol + '//connect.facebook.net/en_US/all.js';
            e.async = true;
            document.getElementById('fb-root').appendChild(e);
            }());
            </script>

            <fb:login-button perms=\"" . $this->Permissions . "\" onlogin='" . $this->CallBack . "'>Connect</fb:login-button>
          ";
    }

    /**
     *
     * @return array/string
     */
    public function InformationInfo() { 
        if ( !isset($_REQUEST["fbs_" . $this->Application_Id]) || $_REQUEST["fbs_" . $this->Application_Id] == "")
            return false;
        $PermissionCheck = split(",", $this->Permissions);

        $a = html_entity_decode($_REQUEST["fbs_" . $this->Application_Id]);
        
        $a = str_ireplace(array("\\",'"'), "", $a);
        if (!$a) {
            return false;
        }
        
        
    	if (ini_get('allow_url_fopen')) {
			$response = @file_get_contents('https://graph.facebook.com/me?' . $a);
		} elseif(function_exists('curl_init')) {
			$response = $this->file_get_contents_curl('https://graph.facebook.com/me?' . $a);
		}
		
		if(!$response) {
			return false;
		}

        $user = json_decode($response, true);
        if(isset($user->error->type)){
            throw new JO_Api_Facebook_Exception(array('error_msg'=>$user->error->message));
        }
        
        $user['avatar'] = "https://graph.facebook.com/" . $user['id'] . "/picture?type=large";
        return $user;
        
    }

    /**
     *
     * @return mixed
     */
    function FBLogin() {
        $session = $this->_facebook->getSession();
        $me = null;
        if ($session) {
            try {
                $uid = $this->_facebook->getUser();
                $me = $this->_facebook->api('/me');
            } catch (JO_Api_Facebook_Exception $e) {
               // throw new Zend_Exception('FB login error',0,$e);
                $this->FBlogout();
            }
        }
        if ($me) {
            return $this->InformationInfo();
        } else {
            return $this->connection();
        }
    }

    public function getAccessToken(){
        return $this->_facebook->getAccessToken();
    }

    public function api($params){
        return $this->_facebook->api($params);
    }

    public function stream_publish($uid,$msg,$actionLink){
         $param = array(
                'method' => 'stream.publish',
                'uid' => $uid,
                'message' => $msg,
                'access_token' => $this->getAccessToken(),
                'action_links' => json_encode($actionLink)
            );
         return $this->api($param);
    }

    public function FBlogout(){
        setcookie ('fbs_' . $this->Application_Id, "", time() - 3600);
    }

    public function getLogoutUrl($next){
        $params = array('next'=>$next);
        return $this->_facebook->getLogoutUrl($params);
    }
    
	private function file_get_contents_curl($url) {
		if(function_exists('curl_init')) {
			return false;
		}
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		if(!ini_get('safe_mode') && !ini_get('open_basedir')) {
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		}
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);	
		curl_setopt($ch, CURLOPT_USERAGENT, "Facebook connect api JO framework");
		curl_setopt($ch, CURLOPT_HTTPGET, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
		curl_setopt($ch, CURLOPT_MAXCONNECTS, 5);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		$Rec_Data = curl_exec($ch);
		curl_close($ch);
		return $Rec_Data;
	}
	
}

?>