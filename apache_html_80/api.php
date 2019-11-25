<?php
require_once('./inc/init.php');

header("Content-type: application/json;charset='utf-8'");

if(!z_validate_token()){
	z_json_resp(0, 'Invalid Request');
	exit;
}

if(LOGIN_REQUIRED && !User::isLogin()){
    z_json_resp(0, 'Login required');
}

$mod = isset($_GET['mod'])?trim(strtolower($_GET['mod'])):'';
$action = isset($_GET['action'])?trim(strtolower($_GET['action'])):'';

if(!is_alphanum_str($mod) || !is_alphanum_str($action)){
	z_json_resp(0,'Error');
}

if(!in_array($mod, array('project', 'projects'))){
	z_json_resp(0,"Invalid mod");
}

if(!file_exists(Z_ABSPATH.'/api/'.$mod.'.php')){
	z_json_resp(0,"Invalid mod 2");
}

require_once(Z_ABSPATH.'/api/'.$mod.'.php');

$class = 'Api'.ucfirst($mod);

if(!class_exists($class)){
	z_json_resp(0,"Invalid mod 3");
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
