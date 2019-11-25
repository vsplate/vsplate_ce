<?php
if (! defined ( "Z_ABSPATH" )) {
	header ( "HTTP/1.0 404 Not Found" );
	exit ();
}

abstract class OAuth2 {
	public $pre = 'vsoauth_';
	public $timeout = 10;
	
	function getURL($url, $param = array(), $force = false){
		if(strpos($url, 'https://') !== 0 && strpos($url, 'http://') !== 0){
			return false;
		}
		if(!filter_var($url, FILTER_VALIDATE_URL)){
			return false;
		}
		if($force == false){
			if(!isset($param['client_id'])){
				return false;
			}
			if(!isset($param['state'])){
				return false;
			}
			if(!isset($param['redirect_uri'])){
				return false;
			}
		}
		$query = http_build_query($param);
		$url = $url.'?'.$query;
		return $url;
	}
	
	function chkState(){
		if(!isset($_SESSION[$this->pre.'state']) || $_SESSION[$this->pre.'state'] == false){
			return false;
		}
		if(!isset($_POST['state']) && !isset($_GET['state'])){
			return false;
		}
		if(md5($_SESSION[$this->pre.'state']) === md5(@$_POST['state']) || md5($_SESSION[$this->pre.'state']) === md5(@$_GET['state'])){
			return true;
		}
		return false;
	}
	
	function genState(){
		$_SESSION[$this->pre.'state'] = md5(get_salt(32));
		return $_SESSION[$this->pre.'state'];
	}
	
	function httpGet($url, $header = '') {
		$this->message = '';
		$this->response_header = null;
		$options = array(
		    'http' => array(
		   	'method' => 'GET',
	       		'header' => $header,
		        'timeout' => $this->timeout
		    )
		);
		$context = stream_context_create($options);
		$result = file_get_contents($url, false, $context);
		$this->response_header = $http_response_header;
		return $result;
    	}
    

    function httpPost($url, $data = array(), $header = '') {
	$this->message = '';
	$this->response_header = null;
	$data = $data;
	$options = array(
	    'http' => array(
	        'method' => 'POST',
	        'header' => "Accept: application/json\r\n".$header,
	        'content' => http_build_query($data),
	        'timeout' => $this->timeout
	    )
	);
	$context = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
	$this->response_header = isset($http_response_header)?$http_response_header:'';
	return $result;
    }
    
    function httpPostJson($url, $data = array()) {
	$this->message = '';
	$this->response_header = null;
	$data = $data;
	$options = array(
	    'http' => array(
	        'method' => 'POST',
	        'header' => "Content-type: application/json\r\n",
	        'content' => json_encode($data),
	        'timeout' => $this->timeout
	    )
	);
	$context = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
	$this->response_header = $http_response_header;
	return $result;
    }
}
