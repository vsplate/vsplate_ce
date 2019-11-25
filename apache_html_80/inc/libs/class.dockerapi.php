<?php
if (! defined ( "Z_ABSPATH" )) {
	header ( "HTTP/1.0 404 Not Found" );
	exit ();
}


//Simple Docker Python

class DockerAPI extends Request{

    function __construct() {
        $this->api = DOCKER_API_API;
        $this->key = DOCKER_API_KEY;
    }

    public function composeArchiveUp($project_name, $docker_compose, $archive_url, $archive_hash = ''){
    	$project_name = md5($project_name);
        $url = $this->api."/docker-compose/archive-up";
        $data = array(
            "project" => $project_name,
            "docker_compose" => $docker_compose,
            "archive_url" => $archive_url,
            "archive_hash" => $archive_hash
        );
        $resp =  $this->httpPostJson($url, $data);
        if(is_json($resp))
            return json_decode ($resp, 1);
        else
            return false;
    }
    
    public function composeRm($project, $md5=true){
    	if($md5){
    		$project = md5($project);
    	}
        $url = $this->api."/docker-compose/rm";
        $data = array(
            "project" => $project
        );
        $resp =  $this->httpPostJson($url, $data);
        if(is_json($resp))
            return json_decode ($resp, 1);
        else
            return false;
    }
    
    public function composeIsExists($project, $md5=true){
    	if($md5){
    		$project = md5($project);
    	}
        $url = $this->api."/docker-compose/isexists";
        $data = array(
            "project" => $project
        );
        $resp =  $this->httpPostJson($url, $data);
        if(is_json($resp))
            return json_decode ($resp, 1);
        else
            return false;
    }
    
    public function composeIsUpDone($project, $md5=true){
    	if($md5){
    		$project = md5($project);
    	}
        $url = $this->api."/docker-compose/isupdone";
        $data = array(
            "project" => $project
        );
        $resp =  $this->httpPostJson($url, $data);
        if(is_json($resp))
            return json_decode ($resp, 1);
        else
            return false;
    }
    
    public function composeUpLogs($project, $md5=true){
    	if($md5){
    		$project = md5($project);
    	}
        $url = $this->api."/docker-compose/uplogs";
        $data = array(
            "project" => $project
        );
        $resp =  $this->httpPostJson($url, $data);
        if(is_json($resp))
            return json_decode ($resp, 1);
        else
            return false;
    }
    
    /*public function composeServices($project){
    	$project = md5($project);
        $url = $this->api."/docker-compose/services";
        $data = array(
            "project" => $project
        );
        $resp =  $this->httpPostJson($url, $data);
        if(is_json($resp))
            return json_decode ($resp, 1);
        else
            return false;
    }*/
    
    public function composePs($project, $md5=true){
        if($md5){
    		$project = md5($project);
    	}
        $url = $this->api."/docker-compose/ps";
        $data = array(
            "project" => $project
        );
        $resp =  $this->httpPostJson($url, $data);
        if(is_json($resp))
            return json_decode ($resp, 1);
        else
            return false;
    }
    
    /*public function composePs($project){
    	$project = md5($project);
        $url = $this->api."/docker-compose/ps";
        $data = array(
            "project" => $project
        );
        $resp =  $this->httpPostJson($url, $data);
        if(is_json($resp))
            return json_decode ($resp, 1);
        else
            return false;
    }*/
    
    public function getAll(){
        $url = $this->api."/docker/ps-a";
        $resp =  $this->httpPostJson($url);
        if(is_json($resp))
            return json_decode ($resp, 1);
        else
            return false;
    }
    
    public function getAllRunning(){
        $url = $this->api."/docker/ps";
        $resp =  $this->httpPostJson($url);
        if(is_json($resp))
            return json_decode ($resp, 1);
        else
            return false;
    }
    
    public function inspect($name){
    	$url = $this->api."/docker/inspect";
        $data = array(
            "name" => $name
        );
        $resp =  $this->httpPostJson($url, $data);
        if(is_json($resp))
            return json_decode ($resp, 1);
        else
            return false;
    }
    
    public function restart($name){
        $url = $this->api."/docker/restart";
        $data = array(
            "name" => $name
        );
        $resp =  $this->httpPostJson($url, $data);
        if(is_json($resp))
            return json_decode ($resp, 1);
        else
            return false;
    }
    
    public function remove($name){
        $url = $this->api."/docker/remove";
        $data = array(
            "name" => $name
        );
        $resp =  $this->httpPostJson($url, $data);
        if(is_json($resp))
            return json_decode ($resp, 1);
        else
            return false;
    }
    
    public function stop($name){
        $url = $this->api."/docker/stop";
        $data = array(
            "name" => $name
        );
        $resp =  $this->httpPostJson($url, $data);
        if(is_json($resp))
            return json_decode ($resp, 1);
        else
            return false;
    }
    
    public function start($name){
        $url = $this->api."/docker/start";
        $data = array(
            "name" => $name
        );
        $resp =  $this->httpPostJson($url, $data);
        if(is_json($resp))
            return json_decode ($resp, 1);
        else
            return false;
    }
    
    public function exec($name, $command){
        $url = $this->api."/docker/exec";
        $data = array(
            "name" => $name,
            "command" => $command
        );
        $resp =  $this->httpPostJson($url, $data);
        if(is_json($resp))
            return json_decode ($resp, 1);
        else
            return false;
    }
    
    public function execfile($name, $file){
        $url = $this->api."/docker/execfile";
        $data = array(
            "name" => $name,
            "file" => $file
        );
        $resp =  $this->httpPostJson($url, $data);
        if(is_json($resp))
            return json_decode ($resp, 1);
        else
            return false;
    }
    
    //从获取列表执行结果提取列表
    public static function getContainerList($resp){
    	if($resp['status'] != 1){
    		return false;
    	}else{
    		//var_dump($resp['data']['result']);
		$all = array();
		foreach($resp['data'] as $key => $line){
			if($key == 0){
				//第一行为列名，忽略第一行
				continue;
			}
			$line = trim($line);
			//echo $line."\n";
			preg_match('/(\w+)[\s]{2,}([a-zA-Z0-9:_\.\/-]+)[\s]{2,}"(.*)"[\s]{2,}([\w]+ [\w]+ [\w]+ [\w]*)[\s]{2,}(.+)[\s]{2,}(.+)[\s]{2,}(\w+)/', $line, $matches);
			//var_dump($matches);
			if(!isset($matches[1]) or $matches[1] == false){
				//ID不存在跳过
				continue;
			}
			$item['cid'] = $matches[1];
			$item['img'] = $matches[2];
			$item['cmd'] = $matches[3];
			$item['create_time'] = $matches[4];
			$item['status'] = $matches[5];
			$item['name'] = $matches[7];
			list($status) = explode(' ', $item['status']);
			if(strtolower($status) == 'up' || strtolower($status) == 'running'){
				$item['status'] = 'running';
			}elseif(strtolower($status) == 'exited'){
				$item['status'] = 'exited';
			}else{
				$item['status'] = 'unknow';
			}
			$all[] = $item;
		}
		return $all;
	}
    }
    
    //从inspect执行结果中提取关键信息
    public static function getInspecInfo($resp){
    	if($resp['status'] != 1){
    		return false;
    	}else{
    		$result = '';
		foreach($resp['data'] as $line){
			$result = $result.$line;
		}
    		if(is_json($result)){
			$arr = json_decode($result, 1);
			if(isset($arr[0]['Id'])){
				$item['cid'] = $arr[0]['Id'];
				$item['created'] = $arr[0]['Created'];
				$item['cmdpath'] = $arr[0]['Path'];
				$item['cmdargs'] = $arr[0]['Args'];
				$item['status'] = $arr[0]['State']['Status'];
				$item['name'] = $arr[0]['Name'];
				$item['volumns'] = $arr[0]['HostConfig']['Binds'];
				$item['hostname'] = $arr[0]['Config']['Hostname'];
				$item['domain'] = $arr[0]['Config']['Domainname'];
				$item['images'] = $arr[0]['Config']['Image'];
				$network_name = 'unknow';
				if(isset($arr[0]['NetworkSettings']['Networks']) && count($arr[0]['NetworkSettings']['Networks']) > 0){
					foreach($arr[0]['NetworkSettings']['Networks'] as $key => $n){
						$network_name = $key;
						break;
					}
				}
				$item['ip'] = isset($arr[0]['NetworkSettings']['Networks'][$network_name])?$arr[0]['NetworkSettings']['Networks'][$network_name]['IPAddress']:'';
				$item['mac'] = isset($arr[0]['NetworkSettings']['Networks'][$network_name])?$arr[0]['NetworkSettings']['Networks'][$network_name]['MacAddress']:'';
				return $item;
			}
		}
		return false;
    	}
    }
}
