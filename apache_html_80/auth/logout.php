<?php
require_once('../inc/init.php');

if(!z_validate_token()){
	die('Invalid Request');
}

if(!User::isLogin()){
	header('Location: /');
	exit;
}
$goto = '/';
if(isset($_SERVER['HTTP_REFERER']) && is_valid_url($_SERVER['HTTP_REFERER']) 
	&& strpos($_SERVER['HTTP_REFERER'], 'logout') === false
	&& strpos($_SERVER['HTTP_REFERER'], 'url') === false
	&& strpos($_SERVER['HTTP_REFERER'], 'goto') === false){
	$goto = $_SERVER['HTTP_REFERER'];
}
$usrObj = new User;
$usrObj->logout();
header('Location: '.$goto);
exit;
