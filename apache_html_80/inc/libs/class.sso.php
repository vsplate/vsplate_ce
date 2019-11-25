<?php
if (! defined ( "Z_ABSPATH" )) {
	header ( "HTTP/1.0 404 Not Found" );
	exit ();
}

class SSO extends OAuth2 {
	//SSO认证地址
	public $url = 'https://sso.vsplate.com/auth/authorize.php';
	//获取资源地址
	public $res_url = 'https://sso.vsplate.com/auth/resource.php';
	public $type = 'vsplate';
	
	public $client_id = AUTH_CLIENT_ID;
	public $redirect_uri = AUTH_REDIRECT_URI;
	//敏感参数
	private $client_secret = AUTH_SECRET;
	
	//跳转到用户授权链接
	function auth($type){
		$this->type = $type;
		$auth_url = $this->genURL();
		$goto = isset($_SERVER['HTTP_REFERER'])?trim($_SERVER['HTTP_REFERER']):'';
		if(is_valid_url($goto)){
			$_SESSION[$this->pre.'goto'] = $goto;
		}
		if($auth_url == false)
			die('ERROR');
		header('location: '.$auth_url);
		exit;
	}
	
	//回调处理
	public function callback(){
		if($this->chkState() !== true){
			die('1.Invalid Request');
		}
		$sessionid = isset($_GET['sessionid'])?trim($_GET['sessionid']):'';
		if($sessionid ==  false){
			die('2.Invalid Request');
		}
		$resp = $this->accessResource($sessionid);
		if($resp == false || $resp['status'] != 1){
			die('Fetch Resouorce Failed');
		}
		$resource = $resp['data'];
		if(!isset($resource['sid']) || $resource['sid'] == false){
			die('SSO Resp Error');
		}
		$type = "vsplate";
		$username = isset($resource['username'])?strtolower(trim($resource['username'])):'';
		$unique_id = isset($resource['sid'])?strtolower(trim($resource['sid'])):'';
		$email = isset($resource['email'])?strtolower(trim($resource['email'])):'';
		$sid = User::genSid($type, $unique_id);
		
		$usrObj = new User;
		if(!$usrObj->isExists($sid)){
			$usrObj->register($sid, $unique_id, $type, $username, $email);
		}
		$user = $usrObj->getDetail($sid);
		if($user == false){
			die('Login Failed');
		}
		$usrObj->update($user['sid'], $unique_id, $type, $username, $email);
		$usrObj->login($user['sid'], $sessionid);
		$goto = '/';
		if(isset($_SESSION[$this->pre.'goto']) && $_SESSION[$this->pre.'goto'] != false){
			$goto = $_SESSION[$this->pre.'goto'];
		}
		header('Location: '.$goto);
		exit;
	}
	
	
	function genURL(){
		$state = parent::genState();
		$url = parent::getURL($this->url, array(
			"client_id"	=> $this->client_id,
			"redirect_uri"	=> $this->redirect_uri,
			"state"		=> $state,
			"type"		=> $this->type
		));
		return $url;
	}
	
	function chkState(){
		if(parent::chkState() !== true){
			return false;
		}
		//$referer = isset($_SERVER['HTTP_REFERER'])?trim($_SERVER['HTTP_REFERER']):'';
		//if($referer == false || strpos($referer, 'https://github.com/') !== 0){
		//	return false;
		//}
		return true;
	}
	
	function accessResource($sessionid){
		$resp = $this->httpPost($this->res_url, array(
			"client_id"	=> $this->client_id,
			"secret"	=> $this->client_secret
		), "Cookie: PHPSESSID=".urlencode($sessionid).";\r\nContent-Type: application/x-www-form-urlencoded\r\n");
		if($resp == false || !is_json($resp)){
			return false;
		}
		return json_decode($resp, 1);
	}
}
