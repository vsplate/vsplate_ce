<?php
require_once('./inc/init.php');

header("Content-type: application/json;charset='utf-8'");

if(Z_DATADIR == false || Z_ALLOWIP == false || Z_AUTHKEY == false || !is_dir(Z_DATADIR)){
    z_json_resp(0,'Invalid config');
}

$allow_ip = explode(',', Z_ALLOWIP);

if($allow_ip == false || !in_array($_SERVER['REMOTE_ADDR'], $allow_ip)){
    z_json_resp(0,'1.Access Forbidden');
}

$data = (file_get_contents('php://input')==false)?json_encode($_POST):file_get_contents('php://input');
if(!isset($_SERVER['HTTP_AUTHKEY']) || $_SERVER['HTTP_AUTHKEY'] != md5(get_url().$data.Z_AUTHKEY)){
    z_json_resp(0,'2.Access Forbidden');
}

$mod = isset($_GET['mod'])?trim(strtolower($_GET['mod'])):'';
$action = isset($_GET['action'])?trim(strtolower($_GET['action'])):'';
$route = isset($_GET['r'])?trim(strtolower($_GET['r'])):'';

if($route != false){
    $route = trim($route, '/\\');
    $arr = explode('/', $route);
    if(count($arr) != 2){
        z_json_resp(0,'Error');
    }
    $mod = route2clsfunc($arr[0]);
    $action = route2clsfunc($arr[1]);
}


if(!is_alphanum_str($mod) || !is_alphanum_str($action)){
	z_json_resp(0,'Invalid route');
}

if(strpos($action, '_') === 0){
    z_json_resp(0,"Invalid action");
}

if(!file_exists(Z_ABSPATH.'/api/'.strtolower($mod).'.php')){
	z_json_resp(0,"Invalid mod", '/api/'.strtolower($mod));
}

require_once(Z_ABSPATH.'/api/'.strtolower($mod).'.php');

$class = 'Api'.ucfirst($mod);

if(!class_exists($class)){
	z_json_resp(0,"Invalid mod 3", $class);
}

$obj = new $class();

if(!method_exists($obj, $action)){
	z_json_resp(0,"Invalid action");
}

try{
	$obj->$action();
} catch (Exception $e) {
	z_json_resp(0,$e->getMessage ());
}
