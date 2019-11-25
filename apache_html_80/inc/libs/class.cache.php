<?php
if (! defined ( "Z_ABSPATH" )) {
	header ( "HTTP/1.0 404 Not Found" );
	exit ();
}

class Cache {
	public static function get_cache_file($name){
		return "/tmp/vskey_cache_".md5($name);
	}
	
	public static function read_cache($name){
		$f = self::get_cache_file($name);
		if(!file_exists($f)){
			return false;
		}
		$c = file_get_contents($f);
		if(!is_json($c)){
			return false;
		}
		$json = json_decode($c, 1);
		$create_time = isset($json['create_time'])?$json['create_time']:0;
		$timeout = isset($json['timeout'])?$json['timeout']:0;
		$data = isset($json['data'])?$json['data']:0;
		if($create_time == false || $timeout == false){
			return false;
		}
		return $json;
	}
	
	public static function is_cache($name){
		$json = self::read_cache($name);
		if($json == false){
			return false;
		}
		if($json['create_time']+$json['timeout']-time() <= 0){
			return false;
		}
		return true;
		
	}
	
	public static function set_cache($name, $value, $timeout = 3){
		$json = array(
			"create_time" => time(),
			"timeout" => intval($timeout),
			"data" => $value
		);
		$f = self::get_cache_file($name);
		return file_put_contents($f, json_encode($json));
	}
	
	public static function get_cache($name){
		$json = self::read_cache($name);
		return $json['data'];
	}
}
