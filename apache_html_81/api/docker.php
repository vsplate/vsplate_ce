<?php
if (! defined ( "Z_ABSPATH" )) {
	header ( "HTTP/1.0 404 Not Found" );
	exit ();
}

class ApiDocker {
    
    private $data = false;
    
    public function __construct(){
        $input = file_get_contents('php://input');
        if($input != false && is_json($input)){
            $this->data = json_decode($input, 1);
        }
    }
    
    public function status(){
        $cmd = 'service docker status';
        $msg = 'docker status';
        $this->cmd_exec($cmd, $msg);
    }
    
    public function restart(){
        if(!isset($this->data['name']) || !is_safe_str($this->data['name'])){
            z_json_resp(0, 'Invalid name');
        }
        $cmd = 'docker inspect '.$this->data['name'].' >/dev/null 2>&1 && docker restart '.$this->data['name'];
        $this->cmd_exec($cmd);
    }
    
    public function start(){
        if(!isset($this->data['name']) || !is_safe_str($this->data['name'])){
            z_json_resp(0, 'Invalid name');
        }
        $cmd = 'docker inspect '.$this->data['name'].' >/dev/null 2>&1 && docker start '.$this->data['name'];
        $this->cmd_exec($cmd);
    }
    
    public function stop(){
        if(!isset($this->data['name']) || !is_safe_str($this->data['name'])){
            z_json_resp(0, 'Invalid name');
        }
        $cmd = 'docker inspect '.$this->data['name'].' >/dev/null 2>&1 && docker stop '.$this->data['name'];
        $this->cmd_exec($cmd);
    }
    
    public function remove(){
        if(!isset($this->data['name']) || !is_safe_str($this->data['name'])){
            z_json_resp(0, 'Invalid name');
        }
        $cmd = 'docker inspect '.$this->data['name'].' >/dev/null 2>&1 && docker rm '.$this->data['name'];
        $this->cmd_exec($cmd);
    }
    
    public function inspect(){
        if(!isset($this->data['name']) || !is_safe_str($this->data['name'])){
            z_json_resp(0, 'Invalid name');
        }
        $cmd = 'docker inspect '.$this->data['name'];
        $this->cmd_exec($cmd);
    }
    
    public function exec(){
        if(!isset($this->data['name']) || !is_safe_str($this->data['name'])){
            z_json_resp(0, 'Invalid name');
        }
        if(!isset($this->data['command'])){
            z_json_resp(0, 'Invalid command');
        }
        $command_b64 = base64_encode($this->data['command']);
        $command = 'echo '.$command_b64.' | base64 -d | /bin/sh';
        $cmd = 'docker inspect '.$this->data['name'].' >/dev/null 2>&1 && docker exec -d '.$this->data['name']." bash -c ".escapeshellarg($command);
        $this->cmd_exec($cmd);
    }
    
    public function ps(){
        $cmd = 'docker ps';
        $this->cmd_exec($cmd);
    }
    
    public function psA(){
        $cmd = 'docker ps -a';
        $this->cmd_exec($cmd);
    }
    
    private function cmd_exec($cmd, $msg = ''){
        $resp = array();
        $rc = null;
        exec($cmd, $resp, $rc);
        if($rc === 0){
            z_json_resp(1, $msg, $resp);
        }else{
            z_json_resp(0, $msg);
        }
    }

}
