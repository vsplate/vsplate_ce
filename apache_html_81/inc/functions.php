<?php
if (! defined ( "Z_ABSPATH" )) {
	header ( "HTTP/1.0 404 Not Found" );
	exit ();
}

function route2clsfunc($str){
    $arr = explode('-', $str);
    $r = '';
    foreach($arr as $k => $v){
        if($k == 0){
            $r = $v;
        }else{
            $r .= ucfirst($v);
        }
    }
    return $r;
}

function is_safe_str($str) {
    if (trim($str) != '.' && trim($str) != '..' && preg_match('/^[_\.\-0-9a-zA-Z]+$/i', $str)) {
        return true;
    } else {
        return false;
    }
}

function is_alphanum_str($str) {
    if (preg_match('/^[0-9a-zA-Z]+$/i', $str)) {
        return true;
    } else {
        return false;
    }
}

function is_valid_url($url){
	if($url == 'http://'.Z_HOST || $url == 'https://'.Z_HOST)
		return true;
	if(strpos($url, 'http://'.Z_HOST.'/') === 0 || strpos($url, 'https://'.Z_HOST.'/') === 0){
		return true;
	}
	return false;
}

function get_url_contents($url){
	$url = trim($url);
	if (!preg_match('/^(http|https):\/\/\w+/', strtolower($url))) {
		return false;
	}
	$options = array(
            'http' => array(
                'method'=>"GET",
                'header'=>"Accept: */*\r\nUser-Agent: curl/7.47.0\r\n",
                'timeout' => 20
            )
        );
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        if($result == false){
        	return false;
        }
        return $result;
}

function get_ip(){
	$ip = $_SERVER['REMOTE_ADDR'];
	if(isset($_SERVER['X-Forwarded-For']) && filter_var($_SERVER['X-Forwarded-For'], FILTER_VALIDATE_IP)){
		$ip = $_SERVER['X-Forwarded-For'];
	}elseif(isset($_SERVER['X-Real-IP']) && filter_var($_SERVER['X-Real-IP'], FILTER_VALIDATE_IP)){
		$ip = $_SERVER['X-Real-IP'];
	}
	return $ip;
}

function second_to_time($seconds){
	$hours = floor($seconds / 3600);
	$mins = floor($seconds / 60 % 60);
	$secs = floor($seconds % 60);
	$timeFormat = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
	return $timeFormat;
}

function deleteDir($dirPath) {
    if (! is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            deleteDir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}

function z_json_resp($status, $info, $data = ''){
    echo json_encode(array('status'=>$status, 'msg'=>$info, 'data'=>$data));
    exit;
}

function is_in_project(){
    return (isset($_GET['pid']) && intval($_GET['pid']) != 0);
}

function z_rm_dir($files){
    $tmp = array();
    foreach ($files as $file){
        if(!is_file($file))
            continue;
        $tmp[] = $file;
    }
    return $tmp;
}

/**
 * Get current time
 *
 * @return String
 */
function get_time() {
    return date('Y-m-d H:i:s');
}

/**
 * Get date
 *
 * @return String
 */
function get_date() {
    return date('Y-m-d');
}

/**
 * Is str in formats like json
 *
 * @param String $string
 * @return bool
 */
function is_json($string) {
    $string = trim($string);
    json_decode($string, true);
    return (json_last_error() == JSON_ERROR_NONE);
}

/**
 * Htmlspecialchars
 *
 * @param String $string
 * @return array or string
 */
function esc_html($string) {
    if (is_array($string)) {
        foreach ($string as $key => $val) {
            $string [$key] = esc_html($val);
        }
    } else {
        //var_dump($string);
        $string = htmlspecialchars($string);
    }
    return $string;
}

function z_addslashes($string) {
    if (is_array($string)) {
        foreach ($string as $key => $val) {
            $string [$key] = z_addslashes($val);
        }
    } else {
        //var_dump($string);
        $string = addslashes($string);
    }
    return $string;
}

/**
 * Get current page URL
 *
 * @return string
 */
function get_url() {
    $ssl = (!empty($_SERVER ['HTTPS']) && $_SERVER ['HTTPS'] == 'on') ? true : false;
    $sp = strtolower($_SERVER ['SERVER_PROTOCOL']);
    $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
    $port = $_SERVER ['SERVER_PORT'];
    $port = ((!$ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':' . $port;
    $host = isset($_SERVER ['HTTP_X_FORWARDED_HOST']) ? $_SERVER ['HTTP_X_FORWARDED_HOST'] : isset($_SERVER ['HTTP_HOST']) ? $_SERVER ['HTTP_HOST'] : $_SERVER ['SERVER_NAME'];
    return $protocol . '://' . $host . $_SERVER ['REQUEST_URI'];
}

/**
 * 获取域名
 *
 * @param unknown $referer
 * @return unknown
 */
function get_url_domain($referer) {
    preg_match("/^(http:\/\/)?([^\/]+)/i", $referer, $matches);
    $domain = isset($matches [2]) ? $matches [2] : "unknow";
    return $domain;
}


function get_salt($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $salt = '';
    for ($i = 0; $i < $length; $i ++) {
        $salt .= $chars [mt_rand(0, strlen($chars) - 1)];
    }
    return $salt;
}

function getTimestamp() {
    return time();
}

function get_file_ext($name){
	return strrchr(basename($name), '.');
}

/**
 * Set PHP error reporting based on debug settings.
 *
 * @access private
 */
function z_debug_mode() {
    if (Z_DEBUG) {
        error_reporting(E_ALL);
    } else {
        error_reporting(0);
    }
}

function json_resp($status, $msg, $data = array()) {
    header('Content-Type: text/json; charset=utf-8');
    $resp = array(
        "status" => $status,
        "msg" => $msg,
        "data" => $data
    );
    echo json_encode($resp);
    exit;
}

function cutstr_html($string) {
    $string = strip_tags($string);
    $string = trim($string);
    $string = str_replace("\t", "", $string);
    $string = str_replace("\r\n", "", $string);
    $string = str_replace("\r", "", $string);
    $string = str_replace("\n", "", $string);
    $string = str_replace(" ", "", $string);
    $string = str_replace("&nbsp;", "", $string);
    return trim($string);
}

function text_html($string) {
    $string = strip_tags($string);
    $string = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;", $string);
    $string = str_replace("\r\n", "<br>", $string);
    $string = str_replace("\r", "", $string);
    $string = str_replace("\n", "<br>", $string);
    $string = str_replace(" ", "&nbsp;", $string);
    return trim($string);
}

function merge_spaces($str){
	return preg_replace("/\s(?=\s)/","\\1",$str);
}
