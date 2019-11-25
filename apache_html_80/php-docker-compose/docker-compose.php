<?php
require_once(__DIR__."/vendor/autoload.php");

use Symfony\Component\Yaml\Yaml;

class PHPDockerCompose{

	public $orig_content;
	public $orig_arr;
	public $filtered_content;
	public $filtered_arr;
	public $final_content;
	public $final_arr;
	public $project;
	
	public $debug = false;
	
	public $default_service_options = array(
		"mem_limit" => "100M",
		"expose" => array(5999,8080,8000,22,9999,9000,8888,88,80)
		/*"deploy" => array(
			"resources" => array(
				"limits" => array("cpus"=>"0.1","memory"=>"100M")
			)
		)*/
	);
	
	function __construct($content="", $project = "", $debug = false){
		$this->debug = $debug;
		$this->setContent($content, $project);
	}
	
	public function setContent($content, $project = ""){
		$this->project = $project;
		$this->orig_content = trim($content);
		$this->orig_arr = Yaml::parse($this->orig_content);
		$this->filtered_arr = $this->filterCompose($this->orig_arr);
		$this->filtered_content = Yaml::dump($this->filtered_arr, 10);
		$this->final_arr = $this->finalCompose($this->filtered_arr);
		$this->final_content = Yaml::dump($this->final_arr, 10);
	}
	
	public function finalCompose($compose) {
		if(!is_array($compose)){
			return false;
		}
		if(!isset($compose['services']) || !is_array($compose['services'])){
			return false;
		}
		
		foreach($compose['services'] as $key => $val){
			//加入service默认设置
			foreach($this->default_service_options as $okey => $oval){
				$compose['services'][$key][$okey] =  $oval;
			}
			//添加service容器名
			if($this->project != false){
				$compose['services'][$key]['container_name'] = md5($this->project).'_'.$key;
			}
		}
		$compose['version'] = '2';
		return $compose;
	}
	
	public function filterCompose($compose) {
		$schema = array(
			"version" => 'filterVersion',
			"services" => 'filterServices',
		);
		if(!is_array($compose) || $compose == false){
			return false;
		}
		$tmp_compose = array();
		foreach($compose as $key => $val){
			if(isset($schema[$key]) && self::function_exists($schema[$key])){
				$func = get_called_class().'::'.($schema[$key]);
				$filtered = $func($val);
				if($filtered != false){
					$tmp_compose[$key] = $filtered;			
				}
			}
		}
		return $tmp_compose;
	}
	
	//过滤version
	public static function filterVersion($version) {
		$version = trim($version);
		if(!is_numeric($version)){
			return false;
		}
		return $version;
	}
	
	//过滤services
	public static function filterServices($services) {
		if(!is_array($services)){
			return false;
		}
		$tmp_services = array();
		foreach($services as $key => $val){
			if(self::is_safe_str($key) && is_array($val)){
				$filtered = self::filterService($val);
				if($filtered != false){
					$tmp_services[$key] = $filtered;
				}
			}
		}
		return $tmp_services;
	}
	
	//过滤service
	public static function filterService($service) {
		$schema = array(
			"image" => 'filter_service_image',
			"ports" => 'filter_service_ports',
			"environment" => 'filter_service_environment',
			"depends_on" => 'filter_service_depends_on',
			"links" => 'filter_service_links',
			"volumes" => 'filter_service_volumes',
			"command" => 'filter_service_command',
			"expose" => 'filter_service_expose'
		);
		if(!is_array($service) || $service == false){
			return false;
		}
		$tmp_service = array();
		foreach($service as $key => $val){
			if(isset($schema[$key]) && self::function_exists($schema[$key])){
				$func = get_called_class().'::'.($schema[$key]);
				$filtered = $func($val);
				if($filtered != false){
					$tmp_service[$key] = $filtered;			
				}
			}
		}
		return $tmp_service;
	}
	
	public static function filter_service_image($image){
		if (preg_match('/^[_\.\-\/:0-9a-zA-Z]+$/i', $image)) {
			return $image;
		} else {
			return false;
		}
	}
	
	public static function filter_service_ports($ports){
		if(!is_array($ports)){
			return false;
		}
		$tmp_ports = array();
		foreach($ports as $val){
			if(preg_match('/^[\d]+:[\d]+(\/tcp|\/udp){0,1}$/i', $val)){
				list($val) = explode('/', $val);
				$tmp_ports[] = $val;
			}
		}
		return array_unique($tmp_ports);
	}
	
	public static function filter_service_environment($env){
		if(!is_array($env)){
			return false;
		}
		$tmp_env = array();
		foreach($env as $key => $val){
			if(self::is_safe_str($key) && self::is_safe_str($val)){
				$tmp_env[$key] = $val;
			}
		}
		return $tmp_env;
	}
	
	public static function filter_service_depends_on($depends_on){
		if(!is_array($depends_on)){
			return false;
		}
		$tmp_depends_on = array();
		foreach($depends_on as $val){
			if(self::is_safe_str($val)){
				$tmp_depends_on[] = $val;
			}
		}
		return $tmp_depends_on;
	}
	
	public static function filter_service_links($links){
		if(!is_array($links)){
			return false;
		}
		$tmp_links = array();
		foreach($links as $val){
			if(self::is_safe_str($val)){
				$tmp_links[] = $val;
			}
		}
		return $tmp_links;
	}
	
	public static function filter_service_volumes($volumes){
		if(!is_array($volumes)){
			return false;
		}
		$tmp_volumes = array();
		foreach($volumes as $val){
			if(self::is_safe_volumes($val)){
				$tmp_volumes[] = $val;
			}
		}
		return $tmp_volumes;
	}
	
	public static function filter_service_command($cmd){
		$cmd = trim($cmd);
		if($cmd ==  false){
			return false;
		}
		if (preg_match('/^[_\.\-\/\s\[\]\'"<,>&:|0-9a-zA-Z]+$/i', $cmd)) {
			return $cmd;
		}
		return false;
	}
	
	public static function filter_service_expose($expose){
		if(!is_array($expose)){
			return false;
		}
		$tmp_expose = array();
		foreach($expose as $val){
			if(is_int($val)){
				$tmp_expose[] = intval($val);
			}
		}
		return $tmp_expose;
	}
	
	public static function function_exists($func){
		return method_exists(get_class(), $func);
	}
	
	public static function is_safe_volumes($str){
		$str = trim($str);
		$paths = explode(":", $str);
		if(count($paths) != 2){
			return false;
		}
		$path1 = trim($paths[0]);
		$path2 = trim($paths[1]);
		if(substr($path1,0,1) == '/' || strpos($path1, '..') !== false || preg_match('/^[_\.0-9a-zA-Z]{1}$/i',substr($path1,0,1)) != true || self::is_safe_path($path1) != true){
			return false;
		}
		if(substr($path2,0,1) != '/' || strpos($path1, '..') !== false || self::is_safe_path($path2) != true){
			return false;
		}
		return true;
	}
	
	public static function is_safe_path($str){
		if(strpos($str, '..') !== false){
			return false;
		}
		if (preg_match('/^[_\.\-\/:0-9a-zA-Z]+$/i', $str)) {
			return true;
		} else {
			return false;
		}
	}
	
	public static function is_safe_str($str) {
		if (preg_match('/^[_\.\-:0-9a-zA-Z]+$/i', $str)) {
			return true;
		} else {
			return false;
		}
	}
	
	public static function is_num_str($str) {
		if (preg_match('/^[0-9]+$/i', $str)) {
			return true;
		} else {
			return false;
		}
	}

	public static function is_alphanum_str($str) {
		if (preg_match('/^[0-9a-zA-Z]+$/i', $str)) {
			return true;
		} else {
			return false;
		}
	}
	
}
