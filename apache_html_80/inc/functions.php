<?php
if (! defined ( "Z_ABSPATH" )) {
	header ( "HTTP/1.0 404 Not Found" );
	exit ();
}

function get_lang(){
	$lang = 'en';
	if(isset($_COOKIE['lang']) && is_safe_str($_COOKIE['lang'])){
		$lang = $_COOKIE['lang'];
	}elseif(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'zh') !== false){
		$lang = 'zh-chs';
	}
	if(!file_exists(Z_ABSPATH.'/lang/'.$lang.'.php')){
		$lang = 'en';
	}
	return $lang;
}

function lang($str){
	global $vsplate_lang;
	return isset($vsplate_lang[lang_key($str)])?esc_html($vsplate_lang[lang_key($str)]):esc_html($str);
}

function lang_key($key){
	return preg_replace("/[^a-zA-Z0-9]+/","", strtolower($key));
}

function is_safe_str($str) {
    if (preg_match('/^[_\.\-0-9a-zA-Z]+$/i', $str)) {
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

function get_repo($github_url){
	$github_url = trim($github_url);
	if(strpos($github_url, 'https://github.com/') !== 0){
		return false;
	}
	$repo = str_replace('https://github.com/', '', $github_url);
	$repo = trim($repo, '/');
	$arr = explode('/', $repo);
	if(count($arr) == 2)
		return $repo;
	return false;
}

function get_repo_detail($github_url){
	$repo = get_repo($github_url);
	if($repo == false)
		return false;
	$options = array(
            'http' => array(
                'method'=>"GET",
                'header'=>"Accept: */*\r\nUser-Agent: curl/7.47.0\r\n",
                'timeout' => 30
            )
        );
        $context = stream_context_create($options);
        $url = 'https://api.github.com/repos/'.$repo.'?client_id='.GITHUB_CLIENT_ID.'&client_secret='.GITHUB_SECRET;
        $result = @file_get_contents($url, false, $context);
        if($result == false){
        	return false;
        }
        if(is_json($result)){
        	return json_decode($result, 1);
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

/**
 * Is session started
 * @return bool
 */
function is_session_started() {
    if (php_sapi_name() !== 'cli') {
        if (version_compare(phpversion(), '5.4.0', '>=')) {
            return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
        } else {
            return session_id() === '' ? FALSE : TRUE;
        }
    }
    return FALSE;
}

function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
}

function deleteDir($dirPath) {
    if (! is_dir($dirPath)) {
        //throw new InvalidArgumentException("$dirPath must be a directory");
        return false;
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
    return $protocol . '://' . $host . $port . $_SERVER ['REQUEST_URI'];
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

/**
 * URL跳转
 *
 * @param unknown $url
 */
function gotourl($url) {
    header("Location: {$url}");
}

/**
 * 获取浏览用户信息，HTTP头，IP等
 */
function get_user_info() {
    return array(
        "IP" => get_ip(),
        "TIME" => get_time(),
        "HTTP_ACCEPT" => isset($_SERVER ["HTTP_ACCEPT"]) ? $_SERVER ["HTTP_ACCEPT"] : "",
        "HTTP_REFERER" => isset($_SERVER ["HTTP_REFERER"]) ? $_SERVER ["HTTP_REFERER"] : "",
        "HTTP_USER_AGENT" => isset($_SERVER ["HTTP_USER_AGENT"]) ? $_SERVER ["HTTP_USER_AGENT"] : ""
    );
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

/**
 * Check the required php version and the MySQL extension
 * 
 * @access private
 */
function z_check_php_mysql() {
    $required_php_version = "5.3.9";
    $php_version = phpversion();
    if (version_compare($required_php_version, $php_version) > 0) {
        header('Content-Type: text/plain; charset=utf-8');
        die("Your server is running PHP version {$php_version} but the application requires at least {$required_php_version}.");
    }

    if (!extension_loaded('PDO')) {
        header('Content-Type: text/plain; charset=utf-8');
        die("Your PHP installation appears to be missing the MySQL extension.");
    }
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

function z_validate_token() {
    if (!isset($_REQUEST ['token'])) {
        return FALSE;
    }
    $token = isset($_REQUEST ['token']) ? $_REQUEST ['token'] : "";
    if (md5($_SESSION ["token"]) == md5($token)) {
        return TRUE;
    }
    return FALSE;
}

function z_validate_captcha() {
    $ccaptcha = isset($_SESSION['user']['captcha']) ? $_SESSION['user']['captcha'] : '';
    if ($ccaptcha == '')
        return FALSE;
    unset($_SESSION['user']['captcha']);
    if (!isset($_REQUEST ['captcha'])) {
        return FALSE;
    }
    $captcha = isset($_REQUEST ['captcha']) ? $_REQUEST ['captcha'] : "";
    if(md5(strtolower($ccaptcha)) == md5(strtolower($captcha)))
        return TRUE;
    else
        return false;
}

function z_logout() {
    unset($_SESSION);
    session_unset();
    session_destroy();
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

function get_sign($str) {
    $arr = str_split($str);
    sort($arr, SORT_STRING);
    return md5(implode('', $arr));
}


function z_to_zhtime($time) {
    return date("Y年m月d日 H时i分s秒", strtotime($time));
}

function z_to_zhdate($time) {
    return date("m月d日", strtotime($time));
}

function z_to_zhdateY($time) {
    return date("Y年m月d日", strtotime($time));
}

function z_to_zhdatestr($time) {
    if (strtotime($time) >= date('Y-m-d'))
        return '今天';
    elseif (strtotime($time) >= date('Y-m-d', strtotime('-1 day')))
        return '昨天';
    elseif (strtotime($time) >= date('Y-m-d', strtotime('-2 day')))
        return '前天';
    else
        return date("m月d日", strtotime($time));
}

function z_islogin() {
    if (isset($_SESSION['user']) && @$_SESSION['user']['id'] > 0)
        return true;
    else
        return false;
}

function z_chkIdcard($idcard) {

    // 只能是18位  
    if (strlen($idcard) != 18) {
        return false;
    }

    // 取出本体码  
    $idcard_base = substr($idcard, 0, 17);

    // 取出校验码  
    $verify_code = substr($idcard, 17, 1);

    // 加权因子  
    $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);

    // 校验码对应值  
    $verify_code_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');

    // 根据前17位计算校验码  
    $total = 0;
    for ($i = 0; $i < 17; $i++) {
        $total += substr($idcard_base, $i, 1) * $factor[$i];
    }

    // 取模  
    $mod = $total % 11;

    // 比较校验码  
    if ($verify_code == $verify_code_list[$mod]) {
        return true;
    } else {
        return false;
    }
}

function z_getAgeByID($id) {

//过了这年的生日才算多了1周岁 
    if (empty($id))
        return '';
    $date = strtotime(substr($id, 6, 8));
//获得出生年月日的时间戳 
    $today = strtotime('today');
//获得今日的时间戳 
    $diff = floor(($today - $date) / 86400 / 365);
//得到两个日期相差的大体年数 
//strtotime加上这个年数后得到那日的时间戳后与今日的时间戳相比 
    $age = strtotime(substr($id, 6, 8) . ' +' . $diff . 'years') > $today ? ($diff + 1) : $diff;

    return $age;
}

function z_totext($str){
    $t = str_replace("\n", "<br>", esc_html($str));
    $t = str_replace(" ", "&nbsp;", $t);
    return $t;
}

function z_is_mobile() {
	if ( empty($_SERVER['HTTP_USER_AGENT']) ) {
		$is_mobile = false;
	} elseif ( strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false // many mobile devices (all iPhone, iPad, etc.)
		|| strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false
		|| strpos($_SERVER['HTTP_USER_AGENT'], 'Silk/') !== false
		|| strpos($_SERVER['HTTP_USER_AGENT'], 'Kindle') !== false
		|| strpos($_SERVER['HTTP_USER_AGENT'], 'BlackBerry') !== false
		|| strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== false
		|| strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mobi') !== false ) {
			$is_mobile = true;
	} else {
		$is_mobile = false;
	}
	return $is_mobile;
}

function z_gethash(){
    return md5(get_salt(16));
}

function merge_spaces($str){
	return preg_replace("/\s(?=\s)/","\\1",$str);
}
