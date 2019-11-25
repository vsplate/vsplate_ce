<?php
require_once('../inc/init.php');

if(User::isLogin()){
	header('Location: /');
	exit;
}

$type = isset($_GET['type'])?strtolower(trim($_GET['type'])):'';

if(!in_array($type, array('vsplate'))){
	die('Invalid Auth Type');
}

$auth = new SSO();
$auth->callback();
