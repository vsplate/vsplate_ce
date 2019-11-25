<?php
if (! defined ( "Z_ABSPATH" )) {
	header ( "HTTP/1.0 404 Not Found" );
	exit ();
}

abstract class Request {

    public $api;
    public $key = '';
    private $timeout = 30;
    public $response_header = null;
    public $message = '';

    function httpGet($url) {
        $this->message = '';
        $this->response_header = null;
        $auth = $this->key;
        $options = array(
            'http' => array(
                'method'=>"GET",
                'header'=>"Accept-language: en\r\nAUTH-KEY: {$auth}\r\n",
                'timeout' => $this->timeout
            )
        );
        $context = stream_context_create($options);
        @$result = file_get_contents($url, false, $context);
        $this->response_header = isset($http_response_header)?$http_response_header:'';
        return $result;
    }
    
    function httpDelete($url) {
        $this->message = '';
        $this->response_header = null;
        $auth = $this->key;
        $options = array(
            'http' => array(
                'method'=>"DELETE",
                'header'=>"Accept-language: en\r\nAUTH-KEY: {$auth}\r\n",
                'timeout' => $this->timeout
            )
        );
        $context = stream_context_create($options);
        @$result = file_get_contents($url, false, $context);
        $this->response_header = isset($http_response_header)?$http_response_header:'';
        return $result;
    }

    function httpPost($url, $data = array()) {
        $this->message = '';
        $this->response_header = null;
        $data = $data;
        $auth = $this->key;
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => "AUTH-KEY: {$auth}\r\n",
                'content' => http_build_query($data),
                'timeout' => $this->timeout
            )
        );
        $context = stream_context_create($options);
        @$result = file_get_contents($url, false, $context);
        $this->response_header = isset($http_response_header)?$http_response_header:'';
        return $result;
    }
    
    function httpPostJson($url, $data = array()) {
        $this->message = '';
        $this->response_header = null;
        $data = $data;
        $auth = $this->key;
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-type: application/json\r\nAUTH-KEY: {$auth}\r\n",
                'content' => json_encode($data),
                'timeout' => $this->timeout
            )
        );
        $context = stream_context_create($options);
        @$result = file_get_contents($url, false, $context);
        $this->response_header = isset($http_response_header)?$http_response_header:'';
        return $result;
    }

}
