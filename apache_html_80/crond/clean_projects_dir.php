<?php
if (php_sapi_name() != "cli") {
	die('Access Denied');
}
//清理僵尸项目文件目录
require_once(__DIR__.'/../inc/init.php');

if(USR_FILES_DIR == false){
	exit;
}

$files = scandir(USR_FILES_DIR);

if($files == false || !is_array($files)){
	exit;
}

$projObj = new Project();

foreach($files as $dir){
	if(in_array($dir, array('.','..'))){
		continue;
	}
	$detail = $projObj->getDetailDir($dir);
	if($detail == false){
		echo "$dir\n";
		deleteDir(USR_FILES_DIR.'/'.$dir);
	}else{
	    //删除5分钟内未使用的Guest项目
		$create_time = $detail['create_time'];
		$start_time = $detail['start_time'];
		if($start_time == false && strtotime($create_time)+300-time() < 0 && strpos($detail['sid'], 'guest_') === 0){
			$projObj->remove($detail['id']);
		}
	}
}
