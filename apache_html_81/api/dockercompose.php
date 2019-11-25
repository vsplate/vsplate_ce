<?php
if (! defined ( "Z_ABSPATH" )) {
	header ( "HTTP/1.0 404 Not Found" );
	exit ();
}

class ApiDockerCompose {

    private $data = false;
    
    public function __construct(){
        $input = file_get_contents('php://input');
        if($input != false && is_json($input)){
            $this->data = json_decode($input, 1);
        }
    }
    
    public function status(){
        $cmd = 'docker-compose version';
        $msg = 'docker-compose version';
        $this->cmd_exec($cmd, $msg);
    }
    
    public static function getUUIDDir($uuid){
        if(!is_safe_str($uuid)){
            return false;
        }
        return Z_DATADIR.DS.$uuid;
    }
    
    public static function getYmlFile($uuid){
        if(!is_safe_str($uuid)){
            return false;
        }
        $dir = self::getUUIDDir($uuid);
        if($dir == false){
            return false;
        }
        return $dir.DS.'data'.DS.'docker-compose.yml';
    }

    public function up(){
        if(!isset($this->data['uuid']) || !is_safe_str($this->data['uuid'])){
            z_json_resp(0, 'Invalid uuid');
        }
        
        $uuid = $this->data['uuid'];
        $url = $this->data['url'];
        $docker_compose = $this->data['docker_compose'];
        if($url == false && $docker_compose == false){
            z_json_resp(0, "URL and Docker_compose not found");
        }
        if($url != false){
            if(!isset($this->data['url']) || !preg_match('/^https:\/\/\github.com\//', strtolower($this->data['url'])) || !filter_var($this->data['url'], FILTER_VALIDATE_URL)){
                z_json_resp(0, 'Invalid url');
            }
        }
        //获取项目目录
        $uuid_dir = self::getUUIDDir($uuid);
        if(is_dir($uuid_dir)){
            deleteDir($uuid_dir);
        }
        if(!is_dir($uuid_dir)){
            mkdir($uuid_dir);
        }
        if(!is_dir($uuid_dir)){
            deleteDir($uuid_dir);
            z_json_resp(0, 'Make download DIR failed');
        }
        //添加下载任务
        try {
            $client = new Predis\Client('tcp://localhost:6379');
            $conf = json_encode(array(
                "uuid" => $uuid,
                "url" => $url,
                "docker_compose" => $docker_compose
            ));
            $data = json_encode(array(
                "action" => "start_up",
                "data" => $conf
            ));
            $client->set('task:prodcons:queue', $data);
        }catch (Exception $e){
		    if(Z_DEBUG){
		        deleteDir($uuid_dir);
			    z_json_resp(0, "Download files failed. ".$e->getMessage());
		    }
		    deleteDir($uuid_dir);
		    z_json_resp(0, "Download files failed");
	    }
        z_json_resp(1, 'success');
    }
    
    public function rm(){
        if(!isset($this->data['uuid']) || !is_safe_str($this->data['uuid'])){
            z_json_resp(0, 'Invalid uuid');
        }
        $uuid = $this->data['uuid'];
        $uuid_dir = self::getUUIDDir($uuid);
        $yml_file = self::getYmlFile($uuid);
        $cmd = 'test -f '.$yml_file.' && docker-compose -f '.$yml_file.' -p '.$uuid.' down && rm -rf '.$uuid_dir;
        $this->cmd_exec($cmd);
    }
    
    public function uuidstatus(){
        if(!isset($this->data['uuid']) || !is_safe_str($this->data['uuid'])){
            z_json_resp(0, 'Invalid uuid');
        }
        $uuid = $this->data['uuid'];
        $uuid_dir = self::getUUIDDir($uuid);
        $fc = $uuid.DS.'creating.lock';
        $fe = $uuid.DS.'error.lock';
        $fd = $uuid.DS.'done.lock';
        $status = 'unknow';
        if(file_exists($fc)){
            $status = 'creating';
        }elseif(file_exists($fe)){
            $status = 'error';
        }if(file_exists($fd)){
            $status = 'done';
        }
        z_json_resp(1, $status, $status);
    }
    
    public function uplogs(){
        if(!isset($this->data['uuid']) || !is_safe_str($this->data['uuid'])){
            z_json_resp(0, 'Invalid uuid');
        }
        $uuid = $this->data['uuid'];
        $yml_file = self::getYmlFile($uuid);
        $cmd = 'test -f '.$yml_file.' && docker-compose -f '.$yml_file.' -p '.$uuid.' logs';
        $this->cmd_exec($cmd);
    }
    
    public function isexists(){
        if(!isset($this->data['uuid']) || !is_safe_str($this->data['uuid'])){
            z_json_resp(0, 'Invalid uuid');
        }
        $uuid = $this->data['uuid'];
        $yml_file = self::getYmlFile($uuid);
        if(file_exists($yml_file)){
            z_json_resp(1, 'Exists');
        }else{
            z_json_resp(0, 'Not found');
        }
    }
    
    public function ps(){
        if(!isset($this->data['uuid']) || !is_safe_str($this->data['uuid'])){
            z_json_resp(0, 'Invalid uuid');
        }
        $uuid = $this->data['uuid'];
        $yml_file = self::getYmlFile($uuid);
        $cmd = 'test -f '.$yml_file.' && docker-compose -f '.$yml_file.' -p '.$uuid.' ps';
        $this->cmd_exec($cmd);
    }
    
    public function all(){
        $files = scandir(Z_DATADIR);
        if($files == false || !is_array($files)){
	        z_json_resp(0, 'Failed to open data dir');
        }
        $list = array();
        foreach($files as $dir){
	        if(in_array($dir, array('.','..'))){
		        continue;
	        }
	        $list[] = $dir;
	    }
	    z_json_resp(1, '', $list);
    }
    
    private static function download($url, $dest){
        if (!preg_match('/^(http|https):\/\/\w+/', strtolower($url))) {
		    return false;
	    }
	    if(file_exists($dest)){
            return false;
        }
	    set_time_limit(0);
        $fp = fopen ($dest, 'w+');
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_BINARYTRANSFER, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 30 );
        curl_setopt( $ch, CURLOPT_FILE, $fp );
        $response = curl_exec( $ch );
        curl_close( $ch );
        fclose( $fp );
        if($response === false) {
            // Update as of PHP 5.3 use of Namespaces Exception() becomes \Exception()
            //throw new \Exception('Curl error: ' . curl_error($curl));
            if(file_exists($dest)){
                unlink($dest);
            }
            return false;
        }
        return true;
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
