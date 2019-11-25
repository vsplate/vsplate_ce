<?php
if (! defined ( "Z_ABSPATH" )) {
	header ( "HTTP/1.0 404 Not Found" );
	exit ();
}

class ApiProjects{
	public function getList(){
		$projObj = new Project();
		$sid = User::currentSid();
		$list = $projObj->getUserList($sid);
		if($list == false || !is_array($list)){
			z_json_resp(0, '');
		}
		$tmp = array();
		foreach($list as $item){
			
			$tmp[] = array(
				"uuid"	=> $item['uuid'],
				"name"	=> $item['name'],
				"start_time"	=> $item['start_time'],
				"create_time"	=> $item['create_time']
			);
		}
		z_json_resp(1, '', $tmp);
	}
}
