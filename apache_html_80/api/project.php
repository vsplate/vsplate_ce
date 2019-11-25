<?php
if (! defined ( "Z_ABSPATH" )) {
	header ( "HTTP/1.0 404 Not Found" );
	exit ();
}

class ApiProject{
	public $uuid;
	public $id;
	public $projObj;
	public $detail;
	public $async;
	public $async_time = 100; //阻塞超时时间，单位秒
	
	public function __construct(){
		$this->uuid = isset($_REQUEST['uuid'])?trim($_REQUEST['uuid']):'';
		$this->async = isset($_REQUEST['async'])?boolval($_REQUEST['async']):true;
		if($this->uuid == false){
			z_json_resp(0, 'Invalid uuid');
		}
		$this->projObj = new Project();
		$this->detail = $this->projObj->getDetailUUID($this->uuid);
		if($this->detail == false){
			z_json_resp(0, 'Invalid uuid');
		}
		if($this->detail['sid'] == false || $this->detail['sid'] != User::currentSid()){
			die('Invalid project uuid.');
		}
		$this->id = $this->detail['id'];
	}
	
	public function status(){
		header('Cache-Control: private, max-age=5');
		$result = $this->projObj->getProjectStatus($this->id);
		$time_left = strtotime($this->detail['start_time'])+$this->detail['timeout']-time();
		if($time_left < 0){
			$time_left = 0;
			$result = 'Exited';
			$this->projObj->stop($this->detail['id']);
		}
		$data = array(
			"status" => $result,
			"start_time" => $this->detail['start_time'],
			"time_left" => $time_left
		);
		if($data != false){
			z_json_resp(1, 'Success', $data);
		}else{
			z_json_resp(0, 'Failed');
		}
	}
	
	public function saveTitle(){
		$title = isset($_REQUEST['title'])?trim($_REQUEST['title']):'';
		if($title == false){
			z_json_resp(0, 'Empty title');
		}
		if($this->projObj->updateName($this->id, $title)){
			z_json_resp(1, 'Success');
		}else{
			z_json_resp(0, 'Failed');
		}
	}
	
	public function start(){
		//判断当前用户最大运行数量
		$sid = User::currentSid();
		$running_proj_num = count($this->projObj->getRunningProject($sid));
		if($running_proj_num >= MAX_RUNNING){
			z_json_resp(0, "You have reached the maximum number of running projects (Limits ".intval(MAX_RUNNING).").");
		}
		//判断当前项目的状态
		$status = $this->projObj->getProjectStatus($this->id);
		if(strtolower($status) != 'exited' && strtolower($status) != 'error' && strtolower($status) != 'timeout'){
			z_json_resp(0, 'Fail to start the project. This project is in operating');
		}
		if($this->projObj->start($this->id)){
		    //阻塞执行
		    if($this->async == false){
		        $start_time = time();
		        while(1){
		            //判断执行是否超时
		            if(time() - $start_time > $this->async_time){
		                break;
		            }
		            sleep(3);
		            $status = strtolower($this->projObj->getProjectStatus($this->id));
		            if($status == 'exited' || $status == 'timeout' || $status == 'error' || $status == 'running'){
		                break;
		            }
		        }
		    }
			z_json_resp(1, 'Success');
		}else{
			z_json_resp(0, 'Failed');
		}
	}
	
	public function stop(){
		if($this->projObj->stop($this->id)){
			z_json_resp(1, 'Success');
		}else{
			z_json_resp(0, 'Failed');
		}
	}
	
	public function restart(){
		if($this->projObj->restart($this->id)){
			z_json_resp(1, 'Success');
		}else{
			z_json_resp(0, 'Failed');
		}
	}
	
	public function delete(){
		if($this->projObj->remove($this->id)){
			z_json_resp(1, 'Success');
		}else{
			z_json_resp(0, 'Failed');
		}
	}
	
	public function serviceList(){
		if(strtolower($this->projObj->getProjectStatus($this->id)) != 'running'){
			z_json_resp(0, '1 Failed');
		}
		$services = $this->projObj->inspect($this->id);
		$srvs_tmp = array();
		if(is_array($services)){
			foreach($services as $srv){
				$item = array(
					"name" => trim($srv['name']),
					"domain" => trim($srv['domain']),
					"config" => array(
						"status" => trim($srv['config']['status']),
						"images" => trim($srv['config']['images'])
					)
				);
				$srvs_tmp[] = $item;
			}
		}
		if($services){
			z_json_resp(1, 'Success', $srvs_tmp);
		}else{
			z_json_resp(0, '2 Failed');
		}
	}
	
	public function restartService(){
		$service = isset($_REQUEST['service'])?trim($_REQUEST['service']):'';
		if($service == false){
			z_json_resp(0, 'Empty service');
		}
		$srvs = is_json($this->detail['services'])?json_decode($this->detail['services'], 1):false;
		$container_name = '';
		if($srvs == false){
			z_json_resp(0, 'Error');
		}
		foreach($srvs as $srv){
			if($srv['name'] == $service){
				$container_name = $srv['container_name'];
				break;
			}
		}
		if($container_name ==  false){
			z_json_resp(0, 'Invalid service');
		}
		if($this->projObj->restartService($container_name)){
			z_json_resp(1, 'Success');
		}else{
			z_json_resp(0, 'Failed');
		}
	}
	
	public function serviceTerminalStatus(){
		$service = isset($_REQUEST['service'])?trim($_REQUEST['service']):'';
		if($service == false){
			z_json_resp(0, 'Empty service');
		}
		$srvs = is_json($this->detail['services'])?json_decode($this->detail['services'], 1):false;
		if($srvs == false){
			z_json_resp(0, 'Error');
		}
		$container = false;
		foreach($srvs as $srv){
			if($srv['name'] == $service){
				$container = $srv;
				break;
			}
		}
		if($container ==  false){
			z_json_resp(0, 'Invalid service');
		}
		$terminal_url = isset($container['domain'])?'http://terminal.'.$container['domain'].'.'.SERVICE_DOMAIN.'/':'#';
		if($terminal_url == false || $terminal_url == '#'){
			z_json_resp(0, 'Invalid Terminal URL');
			exit;
		}
		$opts = array(   
			'http'=>array(   
				'method'=>"GET",   
				'timeout'=>10,
			)
		);
		$context = stream_context_create($opts);
		$result = @file_get_contents($terminal_url, false, $context);
		if(isset($http_response_header) && isset($http_response_header[0]) && (strpos($http_response_header[0], "200") || strpos($http_response_header[0], "401"))){
			z_json_resp(1, 'Started');
			exit;
		}else{
			z_json_resp(0, 'Stoped');
			exit; 
		}
	}
	
	public function startTerminalStatus(){
		$service = isset($_REQUEST['service'])?trim($_REQUEST['service']):'';
		if($service == false){
			z_json_resp(0, 'Empty service');
		}
		$srvs = is_json($this->detail['services'])?json_decode($this->detail['services'], 1):false;
		if($srvs == false){
			z_json_resp(0, 'Error');
		}
		$container = false;
		foreach($srvs as $srv){
			if($srv['name'] == $service){
				$container = $srv;
				break;
			}
		}
		if($container ==  false){
			z_json_resp(0, 'Invalid service');
		}
		$data = $this->projObj->startTerminal($container['container_name']);
		if($data == false){
			z_json_resp(0, 'Error');
		}
		sleep(1);
		z_json_resp(1, 'Executed', $data);
		exit;
	}
	
	public function stopTerminalStatus(){
		$service = isset($_REQUEST['service'])?trim($_REQUEST['service']):'';
		if($service == false){
			z_json_resp(0, 'Empty service');
		}
		$srvs = is_json($this->detail['services'])?json_decode($this->detail['services'], 1):false;
		if($srvs == false){
			z_json_resp(0, 'Error');
		}
		$container = false;
		foreach($srvs as $srv){
			if($srv['name'] == $service){
				$container = $srv;
				break;
			}
		}
		if($container ==  false){
			z_json_resp(0, 'Invalid service');
		}
		$data = $this->projObj->stopTerminal($container['container_name']);
		if($data == false){
			z_json_resp(0, 'Error');
		}
		sleep(1);
		z_json_resp(1, 'Executed', $data);
		exit;
	}
}
