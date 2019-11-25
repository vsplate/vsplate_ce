<?php
if (! defined ( "Z_ABSPATH" )) {
	header ( "HTTP/1.0 404 Not Found" );
	exit ();
}

use Symfony\Component\Yaml\Yaml;

class Project extends Request{
	public $id = NULL;
	private $dbh = NULL;
	public $api = NULL;
	private $dapi = NULL;
	private $portObj = NULL;
	public $dcompose = NULL;
	
	public function __construct() {
		$this->dbh = $GLOBALS ['z_dbh'];
		$this->dapi = new DockerAPI();
		$this->portObj = new Port();
		$this->dcompose = new PHPDockerCompose;
		$this->api = DOWNLOAD_API_API;
        	$this->key = DOWNLOAD_API_KEY;
	}
	
	// 添加下载GITHUB项目任务
	public function downloadGitHubRepos($dir, $github_url, $docker_compose, $github_updated_at){
		$url = $this->api."/download/github";
		$data = array(
		    "dir" => $dir,
		    "github_url" => $github_url,
		    "docker_compose" => $docker_compose,
		    "github_updated_at" => $github_updated_at
		);
		$resp =  $this->httpPostJson($url, $data);
		if(is_json($resp))
		    return json_decode ($resp, 1);
		else
		    return false;
	}
	
	// 添加下载压缩包任务
	public function downloadArchive($dir, $archive_url, $docker_compose){
		$url = $this->api."/download/archive";
		$data = array(
		    "dir" => $dir,
		    "archive_url" => $archive_url,
		    "docker_compose" => $docker_compose
		);
		$resp =  $this->httpPostJson($url, $data);
		if(is_json($resp))
		    return json_decode ($resp, 1);
		else
		    return false;
	}
	
	// 获取运行中的项目
	public function getRunningProject($sid){
		$list = $this->getUserList($sid);
		$running_list = array();
		foreach($list as $key => $item){
			if(strtolower($this->getProjectStatus($item['id'])) == 'exited'){
				continue;
			}
			$running_list[] = $item;
		}
		return $running_list;
	}
	
	//获取项目状态
	public function getProjectStatus($id){
		$project = $this->getDetail($id, true);
		if($project == false){
			return false;
		}
		$dir = $project['dir'];
		if($dir == false || USR_FILES_DIR == false || !is_safe_str($dir)){
			return false;
		}
		$absdir = USR_FILES_DIR.'/'.$dir;
		//存在creating.lock文件说明项目文件还在下载中
		$lock = $absdir.'/creating.lock';
		if(file_exists($lock)){
			$timestamp = file_get_contents($lock);
			//判断下载是否已超时
			if(time() - $timestamp > 300){
				return "timeout";
			}
			return "creating";
		}
		if($project['start_time'] == false || $project['start_time'] == '0000-00-00 00:00:00'){
			return "exited";
		}
		
		//如果超过20秒docker机上仍然未存在项目，说明执行失败或者任务分配出现问题
		$isexists = $this->dapi->composeIsExists($project['compose_name']);
		
		if(isset($isexists['status']) && $isexists['status'] == 0){
	        if(time() - strtotime($project['start_time']) >= 10){
	            return 'timeout';
	        }
	    }
	    
		$isupdone = $this->dapi->composeIsUpDone($project['compose_name']);
		
	    //判读是否启动完毕
	    if(isset($isupdone['status']) && $isupdone['status'] == 0){
	        return 'pending';
	    }

		$ps = $this->dapi->composePs($project['compose_name']);
		if(is_array($ps) && isset($ps['data']) && isset($ps['data'][2])){
			//如果容器还没全部启动说明部分容器启动失败
			$services = $this->inspect($id);
			foreach($services as $service){
				if(strtolower($service['config']['status']) != 'running'){
					return "error";
				}
				$domain = $service['domain'].'.'.SERVICE_DOMAIN;
				$ip = $service['config']['ip'];
			}
			return "running";
		}
		
		return "error";
	}
	
	// 重启项目
	public function restart($id){
		$this->stop($id);
		$this->start($id);
		return true;
	}
	
	// 获取并更新项目服务/容器详情
	public function inspect($id){
		$project = $this->getDetail($id);
		if(!isset($project['services']) || !is_json($project['services'])){
			return false;
		}
		$services = json_decode($project['services'], 1);
		foreach($services as $key => $service){
			$container_name = isset($service['container_name'])?$service['container_name']:'';
			if($container_name ==  false){
				continue;
			}
			$srv_config = DockerAPI::getInspecInfo($this->dapi->inspect($container_name));
			$services[$key]['config'] = $srv_config;
		}
		
		$this->updateServices($id, json_encode($services));
		return $services;
	}
	
	// 获取项目文件下载链接
	public function getDownloadURL($id){
		global $table_prefix;
		$key = md5($id.DOCKER_API_KEY);
		$this->updateDownloadKey($id, $key);
		$project = $this->getDetail($id);
		$dir = $project['dir'];
		if($dir == false || USR_FILES_DIR == false || !is_safe_str($dir)){
			throw new Exception('Invalid Dir.');
			return false;
		}
		
		$absdir = USR_FILES_DIR.'/'.$dir;
		if(!is_dir($absdir)){
			throw new Exception('Masssing project dir.');
			return false;
		}
		
		$download_key = $project['download_key'];
		if($download_key == false){
			throw new Exception('Massing project download key.');
			return false;
		}
		$url = "http://".Z_HOST."/download.php?uuid=".$project['uuid']."&key=".$download_key."&type=.zip";
		//return "http://".Z_HOST."/download.php?uuid=".$project['uuid']."&key=".$download_key."&type=.zip";
		return $url;
	}
	
	// 开始项目
	public function start($id){
		$this->portObj->deleteProject($id);
		$project = $this->getDetail($id);
		if($project == false){
			throw new Exception('Invalid Project.');
			return false;
		}
		if(!self::validFrom($project['from'])){
			throw new Exception('Invalid Project from.');
			return false;
		}
		$from = json_decode($project['from'], 1);
		$dir = $project['dir'];
		if($dir == false || USR_FILES_DIR == false || !is_safe_str($dir)){
			throw new Exception('Invalid Project dir.');
			return false;
		}
		$absdir = USR_FILES_DIR.'/'.$dir;
		$ymlfile = $absdir.'/docker-compose.yml';
		if(!is_dir($absdir)){
			throw new Exception('Invalid Project dir.');
			return false;
		}
		if(!file_exists($ymlfile)){
			$ymlfile = Z_ABSPATH.'/default.yml';
			//throw new Exception('Missing docker-compose.yml.');
			//return false;
		}
		
		// 对docker-compose.yml进行过滤
		$docker_compose_orig = file_get_contents($ymlfile);
		$this->dcompose->setContent($docker_compose_orig, $project['compose_name']);
		$docker_compose = $this->dcompose->final_arr;
		if($docker_compose == false || !isset($docker_compose['services']) || $docker_compose['services'] == false){
			throw new Exception('Missing services in docker-compose.yml.');
			return false;
		}
		//检查要启动的服务是否过多
		if(count($docker_compose['services']) > 5){
			throw new Exception('Too much services to run in docker-compose.yml.');
			return false;
		}
		//检查需要占用的端口是否过多
		foreach($docker_compose['services'] as $service){
			if(isset($service['ports']) && count($service['ports']) > 5){
				throw new Exception('Too much ports in docker-compose.yml.');
				return false;
			}
		}
		//添加新的端口映射
		foreach($docker_compose['services'] as $key => $service){
			if(!isset($service['ports']) || !is_array($service['ports'])){
				continue;
			}
			$new_ports = array();
			foreach($service['ports'] as $pkey => $pval){
				list($target, $orig) = explode(':', $pval);
				if(!$this->portObj->add($id, $service['container_name'], $orig)){
					continue;
				}
				$port_id = $this->portObj->id;
				$port_detail = $this->portObj->getDetail($port_id);
				if($port_detail == false){
					continue;
				}
				$new_target = $port_detail['target'];
				$new_ports[] = intval($new_target).":".intval($orig);
			}
			$docker_compose['services'][$key]['ports'] = $new_ports;
		}
		$archive_url = $this->getDownloadURL($project['id']);
		$this->dcompose->final_content = Yaml::dump($docker_compose, 10);
		//var_dump($this->dcompose->final_content);exit;
		$this->updateDockerCompose($project['id'], $this->dcompose->final_content);
		$this->dapi->composeArchiveUp($project['compose_name'], base64_encode($this->dcompose->final_content), $archive_url);
		
		//设置启动时间/IP
		$start_time = get_time();
		$this->updateStartTime($project['id'], $start_time);
		return true;
	}
	
	// 停止项目
	public function stop($id){
		$this->portObj->deleteProject($id);
		$project = $this->getDetail($id);
		if($project == false)
			return false;
		$this->dapi->composeRm($project['compose_name']);
		$this->updateStartTime($project['id'], null);
		$this->updateServices($project['id'], '');
		return true;
	}
	
	/**
	* 添加项目
	* @sid 用户SID
	* @from 来源
	*  - type: 类型(upload/github/docker-compose-url)
	*  - addr: 上传文件地址/github地址/docker-compose地址
	* @dir 项目文件保存地址
	* @timeout 该项目运行时间 
	**/
	public function add($sid, $from, $dir, $timeout = 0){
		global $table_prefix;
		if($dir == false || USR_FILES_DIR == false || !is_safe_str($dir)){
			return false;
		}
		$absdir = USR_FILES_DIR.'/'.$dir;
		if(!is_dir($absdir)){
			if(!mkdir($absdir)){
				return false;
			}
		}
		if(!is_array($from)){
			return false;
		}
		$name = $from['addr'];
		$from = json_encode($from);
		if(!self::validFrom($from)){
			return false;
		}
		
		//sid_md5(from)_timestamp_randstr
		$compose_name = $sid."_".md5($from)."_".time()."_".strtolower(get_salt(8));
		$compose_name_hash = md5($compose_name);
		$services = '';
		$create_time = get_time();
		if($timeout == false){
			$timeout = GUEST_TIMEOUT;
		}
		if(User::isLogin()){
			$timeout = $_SESSION['user']['timeout'];
		}
		try {
			$this->id = NULL;
			$sth = $this->dbh->prepare ( "INSERT INTO {$table_prefix}projects(`sid`,`uuid`,`name`,`compose_name`,`compose_name_hash`,`from`,`dir`,`services`,`create_time`,`timeout`) VALUES(:sid,UUID(), :name, :compose_name, :compose_name_hash, :from, :dir, :services, :create_time, :timeout)" );
			$sth->bindParam ( ':sid', $sid );
			$sth->bindParam ( ':name', $name );
			$sth->bindParam ( ':compose_name', $compose_name );
			$sth->bindParam ( ':compose_name_hash', $compose_name_hash );
			$sth->bindParam ( ':from', $from );
			$sth->bindParam ( ':dir', $dir );
			$sth->bindParam ( ':services', $services );
			$sth->bindParam ( ':create_time', $create_time );
			$sth->bindParam ( ':timeout', $timeout );
			$sth->execute ();
			if (! ($sth->rowCount () > 0)) {
				return FALSE;
			}
			$this->id = $this->dbh->lastInsertId ();
			return true;
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
	
	// 更新服务字段
	public function updateServices($id, $services){
		global $table_prefix;
		try {
			$sth = $this->dbh->prepare ( "UPDATE {$table_prefix}projects SET `services`= :services  WHERE `id` = :id" );
			$sth->bindParam ( ':services', $services );
			$sth->bindParam ( ':id', $id );
			$sth->execute ();
			if (! ($sth->rowCount () > 0))
				return FALSE;
			else
				return TRUE;
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
	
	// 更新下载key
	public function updateDownloadKey($id, $key){
		global $table_prefix;
		try {
			$sth = $this->dbh->prepare ( "UPDATE {$table_prefix}projects SET `download_Key`= :download_Key  WHERE `id` = :id" );
			$sth->bindParam ( ':download_Key', $key );
			$sth->bindParam ( ':id', $id );
			$sth->execute ();
			if (! ($sth->rowCount () > 0))
				return FALSE;
			else
				return TRUE;
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
	
	// 更新启动时间
	public function updateStartTime($id, $start_time){
		global $table_prefix;
		try {
			$sth = $this->dbh->prepare ( "UPDATE {$table_prefix}projects SET `start_time`= :start_time  WHERE `id` = :id" );
			$sth->bindParam ( ':start_time', $start_time );
			$sth->bindParam ( ':id', $id );
			$sth->execute ();
			if (! ($sth->rowCount () > 0))
				return FALSE;
			else
				return TRUE;
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
	
	// 更新项目
	public function update($id, $sid, $from, $docker_compose, $timeout){
		global $table_prefix;
		if(is_array($from)){
			$from = json_encode($from);
		}
		if(!self::validFrom($from)){
			return false;
		}
		try {
			$sth = $this->dbh->prepare ( "UPDATE {$table_prefix}projects SET `sid`= :sid, `from` = :from, `docker_compose` = :docker_compose, `timeout` = :timeout  WHERE `id` = :id" );
			$sth->bindParam ( ':sid', $sid );
			$sth->bindParam ( ':from', $from );
			$sth->bindParam ( ':docker_compose', $docker_compose );
			$sth->bindParam ( ':timeout', $timeout );
			$sth->bindParam ( ':id', $id );
			$sth->execute ();
			if (! ($sth->rowCount () > 0))
				return FALSE;
			else
				return TRUE;
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
	
	// 完全移除项目
	public function remove($id){
		if(!$this->stop($id)){
			return false;
		}
		if(!$this->delete($id)){
			return false;
		}
		return true;
	}
	
	// 从数据库删除项目信息，同时删除项目文件
	private function delete($id, $del_dir_only = false){
		global $table_prefix;
		$id = intval($id);
		$project = $this->getDetail($id);
		if($project == false){
			throw new Exception('Invalid Project.');
			return false;
		}
		$dir = $project['dir'];
		if($dir != false && USR_FILES_DIR != false){
			$absdir = USR_FILES_DIR.'/'.$dir;
			if(file_exists($absdir)){
				deleteDir($absdir);
			}
		}
		if($del_dir_only == true){
		    return TRUE;
		}
		try {
			$sth = $this->dbh->prepare ( "DELETE FROM {$table_prefix}projects  WHERE `id` = :id" );
			$sth->bindParam ( ':id', $id );
			$sth->execute ();
			if (! ($sth->rowCount () > 0))
				return FALSE;
			else
				return TRUE;
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
	
	// 获取项目详情
	public function getDetail($id, $nocache = false){
		global $table_prefix;
		try {
			$sth = $this->dbh->prepare ( "SELECT * FROM {$table_prefix}projects WHERE `id` = :id " );
			$sth->bindParam ( ':id', $id );
			$sth->execute ();
			$result = $sth->fetch ( PDO::FETCH_ASSOC );
			return $result;
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
	
	// 获取某用户的项目数量
	public function getProjectsNums($sid, $nocache = false){
		global $table_prefix;
		try {
			$sth = $this->dbh->prepare ( "SELECT count(*)'count' FROM {$table_prefix}projects WHERE `sid` = :sid " );
			$sth->bindParam ( ':sid', $sid );
			$sth->execute ();
			$result = $sth->fetch ( PDO::FETCH_ASSOC );
			if($result == false || !isset($result['count']))
				return false;
			return intval($result['count']);
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
	
	// 通过UUID获取项目详情
	public function getDetailUUID($uuid, $nocache = false){
		global $table_prefix;
		try {
			$sth = $this->dbh->prepare ( "SELECT * FROM {$table_prefix}projects WHERE `uuid` = :uuid " );
			$sth->bindParam ( ':uuid', $uuid );
			$sth->execute ();
			$result = $sth->fetch ( PDO::FETCH_ASSOC );
			
			return $result;
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
	
	// 通过compose_name_hash获取项目详情
	public function getDetailComposeNameHash($hash){
		global $table_prefix;
		try {
			$sth = $this->dbh->prepare ( "SELECT * FROM {$table_prefix}projects WHERE `compose_name_hash` = :compose_name_hash " );
			$sth->bindParam ( ':compose_name_hash', $hash );
			$sth->execute ();
			$result = $sth->fetch ( PDO::FETCH_ASSOC );
			return $result;
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
	
	// 通过dir获取项目详情
	public function getDetailDir($dir){
		global $table_prefix;
		try {
			$sth = $this->dbh->prepare ( "SELECT * FROM {$table_prefix}projects WHERE `dir` = :dir " );
			$sth->bindParam ( ':dir', $dir );
			$sth->execute ();
			$result = $sth->fetch ( PDO::FETCH_ASSOC );
			return $result;
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
	
	// 获取某用户的所有项目
	public function getUserList($sid, $nocache = false){
		global $table_prefix;
		try {
			$sth = $this->dbh->prepare ( "SELECT * FROM {$table_prefix}projects WHERE `sid` = :sid " );
			$sth->bindParam ( ':sid', $sid );
			$sth->execute ();
			$result = $sth->fetchAll ( PDO::FETCH_ASSOC );
			
			return $result;
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
	
	// 获取所有项目
	public function getListAll(){
		global $table_prefix;
		try {
			$sth = $this->dbh->prepare ( "SELECT * FROM {$table_prefix}projects " );
			$sth->execute ();
			$result = $sth->fetchAll ( PDO::FETCH_ASSOC );
			return $result;
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
	
	// 更新项目的所有者
	public function updateSid($old_sid, $new_sid){
		global $table_prefix;
		try {
			$sth = $this->dbh->prepare ( "UPDATE {$table_prefix}projects SET `sid`= :new_sid  WHERE `sid` = :old_sid" );
			$sth->bindParam ( ':new_sid', $new_sid );
			$sth->bindParam ( ':old_sid', $old_sid );
			$sth->execute ();
			if (! ($sth->rowCount () > 0))
				return FALSE;
			else
				return TRUE;
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
	
	// 更新docker-compose
	public function updateDockerCompose($id, $docker_compose){
		global $table_prefix;
		$docker_compose = trim($docker_compose);
		try {
			$sth = $this->dbh->prepare ( "UPDATE {$table_prefix}projects SET `docker_compose`= :docker_compose  WHERE `id` = :id" );
			$sth->bindParam ( ':docker_compose', $docker_compose );
			$sth->bindParam ( ':id', $id );
			$sth->execute ();
			if (! ($sth->rowCount () > 0)){
				return FALSE;
			}else{
				$this->updateServicesByDockerCompose($id, $docker_compose);
				return TRUE;
			}
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
	
	// 更新name
	public function updateName($id, $name){
		global $table_prefix;
		$name = trim($name);
		try {
			$sth = $this->dbh->prepare ( "UPDATE {$table_prefix}projects SET `name`= :name  WHERE `id` = :id" );
			$sth->bindParam ( ':name', $name );
			$sth->bindParam ( ':id', $id );
			$sth->execute ();
			if (! ($sth->rowCount () > 0)){
				return FALSE;
			}else{
				return TRUE;
			}
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
	
	//根据docker-compose内容更新services
	public function updateServicesByDockerCompose($id, $docker_compose){
		$dcompose = new PHPDockerCompose($docker_compose);
		if(!$this->isValidDockerCompose($docker_compose)){
			return false;
		}
		$services = array();
		foreach($dcompose->orig_arr['services'] as $key => $service){
			$services[] = array(
				"name"	=> trim($key),
				"container_name" => trim($service['container_name']),
				"domain" => md5($service['container_name'])
			);
		}
		$json = json_encode($services);
		return $this->updateServices($id, $json);
	}
	
	// 更新某用户的项目超时时间
	public function updateSidTimeout($sid, $timeout){
		global $table_prefix;
		try {
			$sth = $this->dbh->prepare ( "UPDATE {$table_prefix}projects SET `timeout`= :timeout  WHERE `sid` = :sid" );
			$sth->bindParam ( ':timeout', $timeout );
			$sth->bindParam ( ':sid', $sid );
			$sth->execute ();
			if (! ($sth->rowCount () > 0))
				return FALSE;
			else
				return TRUE;
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
	
	//判读是否基本的docker-compose, 注意：该函数不做安全检查
	public function isValidDockerCompose($docker_compose){
		$dcompose = new PHPDockerCompose($docker_compose);
		if($dcompose->orig_arr == false){
			return false;
		}
		if(!isset($dcompose->orig_arr['services']) || !is_array($dcompose->orig_arr['services'])){
			return false;
		}
		foreach($dcompose->orig_arr['services'] as $key => $service){
			if(!isset($service['container_name'])){
				return false;
			}
			if(!isset($service['image'])){
				return false;
			}
		}
		return true;
	}
	
	//判断from内容是否符合格式
	public static function validFrom($from){
		if(!is_json($from)){
			return false;
		}
		$from = json_decode($from, 1);
		if(!isset($from['type']) || $from['type'] == false || !isset($from['addr']) || $from['addr'] == false){
			return false;
		}
		return true;
	}
	
	// 重启服务
	public function restartService($container_name){
		if($container_name == false)
			return false;
		$this->dapi->restart($container_name);
		return true;
	}
	
	// 获取最近的10个项目
	public function getRecentlyProject(){
		global $table_prefix;
		try {
			$sth = $this->dbh->prepare ( "SELECT `from` from (SELECT * FROM `{$table_prefix}projects` order by id desc limit 100) a group by `from`" );
			$sth->execute ();
			$result = $sth->fetchAll ( PDO::FETCH_ASSOC );
			if($result ==  false || !is_array($result)){
				return false;
			}
			$list = array();
			foreach($result as $item){
				$from = isset($item['from'])?$item['from']:'';
				if(is_json($from)){
					$from = json_decode($from, 1);
				}
				if(($from['type'] == 'github' || $from['type'] == 'docker-compose-url') && !in_array($from['addr'], $list)){
					$list[] = $from['addr'];
				}
			}
			return $list;
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
}
