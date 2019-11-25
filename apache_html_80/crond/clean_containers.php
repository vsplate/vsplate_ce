<?php
if (php_sapi_name() != "cli") {
	die('Access Denied');
}
//清理容器
require_once(__DIR__.'/../inc/init.php');

$dapi = new DockerAPI();
$projObj = new Project();

$tmp = DockerAPI::getContainerList($dapi->getAll());

if($tmp == false || !is_array($tmp)){
	exit;
}

//过滤出符合vsplate格式的容器 md5(xxx)_name
$all = array();
foreach($tmp as $c){
	if (preg_match('/^(\w){32}_(.+)/', strtolower($c['name']))) {
		$all[] = $c;
	}
}
foreach($all as $container){
	list($project_hash, $name) = explode('_', $container['name'], 2);
	$detail = $projObj->getDetailComposeNameHash($project_hash);
	//删除僵尸容器
	if($detail == false || $detail['start_time'] == false){
		echo $container['name']."\n";
		$dapi->composeRm($project_hash, false);
		if($container['status'] == 'running'){
			$dapi->stop($container['name']);
		}
		$dapi->remove($container['name']);
	}else{
	//删除超时容器
		$start_time = $detail['start_time'];
		$timeout = $detail['timeout'];
		//判断是否超时
		if(strtotime($start_time)+$timeout-time() < 0){
		    //停止容器
			$projObj->stop($detail['id']);
		}
	}
}
