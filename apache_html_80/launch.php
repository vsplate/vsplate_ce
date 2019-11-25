<?php
require_once('./inc/init.php');

$upway = isset($_REQUEST['upway'])?trim($_REQUEST['upway']):'';
$isajax = isset($_REQUEST['isajax'])?intval(boolval($_REQUEST['isajax'])):0;
$docker_compose = isset($_REQUEST['docker-compose'])?trim($_REQUEST['docker-compose']):'';

function launch_resp_info($status, $msg, $data = ''){
	global $isajax;
	if($isajax == true){
		z_json_resp($status, $msg, $data);
		exit;
	}
	if($status == false){
		die(esc_html($msg));
	}else{
		die(esc_html($msg));
	}
}

if(!z_validate_token()){
	z_json_resp(0, 'Invalid Request');
	exit;
}

if(!in_array($upway, array('text', 'upload'))){
	launch_resp_info(0, "Invalid upway");
}

$projObj = new Project();
$sid = User::currentSid();

$proj_num = $projObj->getProjectsNums($sid);

//游客的最大项目数量
if(!User::isLogin() && $proj_num >= GUEST_MAX_NUM){
	launch_resp_info(0, "You have reached guest's maximum number of projects.");
}

if($upway == 'text'){
	$upway_text = "docker-compose";
	$address = isset($_REQUEST['address'])?trim($_REQUEST['address']):'';
	$github_updated_at = isset($_REQUEST['github_updated_at'])?trim($_REQUEST['github_updated_at']):'';
	if(strpos($github_updated_at, 'T') === false && strpos($github_updated_at, 'Z') === false){
		$github_updated_at = '';
	}
	//无远程URL，自定义docker-compose
	if ($address == false && $docker_compose != false) {
		$upway_text = "custom";
		$from = array(
			"type" => "docker-compose-custom",
			"addr" => 'custom_'.get_salt(8)
			);
		$dir = $sid.'_'.md5(json_encode($from))."_".time()."_".strtolower(get_salt(8));
		$absdir = USR_FILES_DIR.'/'.$dir;
		$ymlfile = $absdir.'/docker-compose.yml';
		if(!$projObj->add($sid, $from, $dir)){
			deleteDir($absdir);
			launch_resp_info(0, "Error: Create project failed.");
		}
		
		if($docker_compose == false){
			$projObj->remove($projObj->id);
			launch_resp_info(0, "Error: Fail to fetch the remote content.");
		}
		$dc = new PHPDockerCompose($docker_compose);
		if($dc->filtered_arr == false){
			$projObj->remove($projObj->id);
			launch_resp_info(0, "Error: Invalid docker-compose content.");
		}
		if(!is_dir($absdir)){
			$projObj->remove($projObj->id);
			launch_resp_info(0, "Error 2: Create project failed.");
		}
		if(!file_put_contents($ymlfile, $docker_compose)){
			$projObj->remove($projObj->id);
			launch_resp_info(0, "Error 3: Create project failed.");
		}
		
		$uuid = 0;
	    if($projObj->id > 0){
		    $detail = $projObj->getDetail($projObj->id);
		    if(isset($detail['uuid'])){
		        $data = array("uuid"=>trim($detail['uuid']));
		        $uuid = $detail['uuid'];
		    }
	    }
	    if($isajax){
		    launch_resp_info(1, "Success", $data);
	    }else{
		    $tourl = "./labs.php?autostart=1&uuid=".$uuid;
		    header("Location: ".$tourl);
		    exit;
	    }
	//判断是否github项目
	}elseif (preg_match('/^https:\/\/github.com\/\w+/', strtolower($address)) && !preg_match('/.yml$/', strtolower($address))) {
		$upway_text = "github-repos";
		if($github_updated_at == false){
			$repos_detail = get_repo_detail($address);
			if($repos_detail == false || !isset($repos_detail['updated_at'])){
				launch_resp_info(0, "Failed to fetch GitHub repository information.");
			}
			$github_updated_at = $repos_detail['updated_at'];
		}
		$from = array(
			"type" => "github",
			"addr" => $address
			);
		$dir = $sid.'_'.md5(json_encode($from))."_".time()."_".strtolower(get_salt(8));
		$absdir = USR_FILES_DIR.'/'.$dir;
		//创建下载目录，并创建下载超时/状态记录文件
		mkdir($absdir);
		file_put_contents($absdir.'/creating.lock',time());
		
		if(!$projObj->add($sid, $from, $dir)){
			launch_resp_info(0, "Error: Create project failed.");
		}
		//添加到下载任务
		if(!$projObj->downloadGitHubRepos($dir, $address, $docker_compose, $github_updated_at)){
			deleteDir($absdir);
			$projObj->remove($projObj->id);
			launch_resp_info(0, "Error: Download project failed.");
		}
		$uuid = 0;
	    if($projObj->id > 0){
		    $detail = $projObj->getDetail($projObj->id);
		    if(isset($detail['uuid'])){
		        $data = array("uuid"=>trim($detail['uuid']));
		        $uuid = $detail['uuid'];
		    }
	    }
	    if($isajax){
		    launch_resp_info(1, "Success", $data);
	    }else{
		    $tourl = "./labs.php?autostart=1&uuid=".$uuid;
		    header("Location: ".$tourl);
		    exit;
	    }
	//检查URL地址是否合法
	}elseif (preg_match('/^(http|https):\/\/\w+/', strtolower($address))){
		if(strpos(strtolower($address), 'https://github.com/') === 0 && strpos(strtolower($address), '/blob/') !== false){
			$address = str_replace('/blob/', '/raw/', $address);
		}
		$upway_text = "docker-compose";
		$from = array(
			"type" => "docker-compose-url",
			"addr" => $address
			);
		$dir = $sid.'_'.md5(json_encode($from))."_".time()."_".strtolower(get_salt(8));
		$absdir = USR_FILES_DIR.'/'.$dir;
		$ymlfile = $absdir.'/docker-compose.yml';
		// 直接获取远程docker-compose内容
		// 如果存在用户自定义docker-compose则直接使用用户自定义的docker-compose
		if(trim($docker_compose) == false){
			$docker_compose = get_url_contents($address);
		}
		
		if($docker_compose == false){
			launch_resp_info(0, "Error: Fail to fetch the remote content.");
		}
		$dc = new PHPDockerCompose($docker_compose);
		if($dc->filtered_arr == false){
			launch_resp_info(0, "Error: Invalid docker-compose file.");
		}
		if(!is_dir($absdir)){
			mkdir($absdir);
		}
		if(!file_put_contents($ymlfile, $docker_compose)){
			deleteDir($absdir);
			launch_resp_info(0, "Error 3: Create project failed.");
		}
		if(!$projObj->add($sid, $from, $dir)){
			deleteDir($absdir);
			launch_resp_info(0, "Error: Create project failed.");
		}
		$uuid = 0;
	    if($projObj->id > 0){
		    $detail = $projObj->getDetail($projObj->id);
		    if(isset($detail['uuid'])){
		        $data = array("uuid"=>trim($detail['uuid']));
		        $uuid = $detail['uuid'];
		    }
	    }
	    if($isajax){
		    launch_resp_info(1, "Success", $data);
	    }else{
		    $tourl = "./labs.php?autostart=1&uuid=".$uuid;
		    header("Location: ".$tourl);
		    exit;
	    }
	}else{
		launch_resp_info(0, "Error: Empty URL and docker-compose.yml.");
	}
}elseif($upway == 'upload'){
	if(!isset($_FILES['file']) || !isset($_FILES['file']['name'])){
		launch_resp_info(0, 'Empty file');
	}
	$ext = get_file_ext($_FILES['file']['name']);
	if($ext == false || !in_array($ext, array('.zip'))){
		launch_resp_info(0, 'Invalid ZIP file');
	}
	if (!is_uploaded_file($_FILES['file']['tmp_name'])) {
		launch_resp_info(0, 'Error 1: Unknow Error');
	}
	
	$zipfile = $_FILES['file']['tmp_name'];
	
	$za = new ZipArchive();
	$res = $za->open($zipfile);
	if($res !== true){
		launch_resp_info(0, 'Error: Cannot open the zip file.');
	}
	//检查文件名
	for( $i = 0; $i < $za->numFiles; $i++ ){ 
		$stat = $za->statIndex($i);
		if(strpos($stat['name'], '../') !== false || strpos($stat['name'], '..\\') !== false) {
			launch_resp_info(0, 'Error 2: Cannot open the zip file.');
		}
	}
	
	$from = array(
		"type" => "docker-compose-custom",
		"addr" => 'upload_'.$_FILES['file']['name'].'_'.get_salt(8)
		);
	$dir = $sid.'_'.md5(json_encode($from))."_".time()."_".strtolower(get_salt(8));
	$absdir = USR_FILES_DIR.'/'.$dir;
	$ymlfile = $absdir.'/docker-compose.yml';
	if(!$projObj->add($sid, $from, $dir)){
		deleteDir($absdir);
		launch_resp_info(0, "Error: Create project failed.");
	}
	
	if(!is_dir($absdir)){
		launch_resp_info(0, "Error 2: Create project failed.");
	}
	//解压到项目目录
	try {
		$za->extractTo($absdir);
	}catch (Exception $e){
		$projObj->remove($projObj->id);
		if(Z_DEBUG){
			launch_resp_info(0, "Error 3: Cannot open the zip file. ".$e->getMessage());
		}
		launch_resp_info(0, "Error 3: Cannot open the zip file.");
	}finally{
		$za->close();
	}
        
	//写入/覆盖docker-compose.yml
	if($docker_compose != false){
		if(!file_put_contents($ymlfile, $docker_compose)){
			launch_resp_info(0, "Error 3: Create project failed.");
		}
	}
	$uuid = 0;
	if($projObj->id > 0){
		$detail = $projObj->getDetail($projObj->id);
		if(isset($detail['uuid'])){
		    $data = array("uuid"=>trim($detail['uuid']));
		    $uuid = $detail['uuid'];
		}
	}
	if($isajax){
		launch_resp_info(1, "Success", $data);
	}else{
		$tourl = "./labs.php?autostart=1&uuid=".$uuid;
		header("Location: ".$tourl);
		exit;
	}
}else{
	launch_resp_info(0, "Unknow Operation");
}
