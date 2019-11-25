<?php
require_once('./inc/init.php');

$current_uuid = isset($_REQUEST['uuid'])?trim($_REQUEST['uuid']):0;
$key = isset($_REQUEST['key'])?trim($_REQUEST['key']):0;

$projObj = new Project();

$detail = $projObj->getDetailUUID($current_uuid);

function resp_404(){
	header('HTTP/1.1 404 Not Found');
	header("status: 404 Not Found");
}

if($detail == false){
	resp_404();
	die('Invalid project uuid.');
}

if($detail['download_key'] == false || !hash_equals(trim($detail['download_key']), trim($key))){
	resp_404();
	die('Invalid project download key.');
}

$dir = $detail['dir'];
if($dir == false || !is_safe_str($dir)){
	resp_404();
	die("Unknow Error");
}
$absdir = USR_FILES_DIR.'/'.$dir;

//转换为真实路径
$current_path = realpath($absdir);
//判断是否还在用户项目的目录下
if($current_path == false || strpos($current_path, USR_FILES_DIR) !== 0 || !file_exists($current_path)){
	resp_404();
	die("Invalid Path");
}

//禁止直接操作项目目录
if(realpath($current_path) == realpath(USR_FILES_DIR)){
	resp_404();
	die("Invalid Path 2");
}

$name = basename($detail['name']).'_'.get_time();
$name = preg_replace("/[^a-zA-Z0-9_.-]+/","", $name);
$zip_file = '/tmp/'.$name.'.zip';
exec('cd '.$absdir.' && zip -r '.$zip_file.' .');
if(!file_exists($zip_file)){
    die("ZIP Failed");
}


header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename='.basename($zip_file));
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($zip_file));
readfile($zip_file);
unlink($zip_file);
