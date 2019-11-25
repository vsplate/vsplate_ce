<?php
if (! defined ( "Z_ABSPATH" )) {
	header ( "HTTP/1.0 404 Not Found" );
	exit ();
}

class User{
	public $id = NULL;
	private $dbh = NULL;
	
	public function __construct() {
		$this->dbh = $GLOBALS ['z_dbh'];
	}

	public function login($sid, $access_token){
		$user = $this->getDetail($sid);
		if($user == false)
			return false;
		$sid = $user['sid'];
		$old_sid = 'guest_'.session_id();
		$this->updateAccessToken($sid, $access_token);
		$this->lastLoginUpdate($sid);
		// 重新生成会话ID，防止会话固定漏洞
		session_regenerate_id();
		$_SESSION['token'] = md5(get_salt());
		$_SESSION['user']['sid'] = $user['sid'];
		$_SESSION['user']['username'] = $user['username'];
		$_SESSION['user']['type'] = $user['account_type'];
		$_SESSION['user']['timeout'] = ($user['timeout']==false)?intval(DEFAULT_TIMEOUT):intval($user['timeout']);
		// 更新用户登录前的项目超时时间
		$projectObj = new Project();
		$projectObj->updateSidTimeout($old_sid, $_SESSION['user']['timeout']);
		$projectObj->updateSid($old_sid, $sid);
		return true;
	}
	
	public function logout(){
		unset($_SESSION['user']);
		return true;
	}
	
	public function register($sid, $unique_id, $type, $username, $email){
		global $table_prefix;
		if ($this->isExists ( $sid )) {
			return FALSE;
		}
		$created_time = get_time();
		$created_ip = get_ip();
		try {
			$this->id = NULL;
			$sth = $this->dbh->prepare ( "INSERT INTO {$table_prefix}users(`sid`,`unique_id`,`account_type`,`username`,`email`,`created_time`,`created_ip`) VALUES(:sid, :unique_id, :type, :username, :email, :created_time, :created_ip)" );
			$sth->bindParam ( ':sid', $sid );
			$sth->bindParam ( ':unique_id', $unique_id );
			$sth->bindParam ( ':type', $type );
			$sth->bindParam ( ':username', $username );
			$sth->bindParam ( ':email', $email );
			$sth->bindParam ( ':created_time', $created_time );
			$sth->bindParam ( ':created_ip', $created_ip );
			$sth->execute ();
			if (! ($sth->rowCount () > 0)) {
				return FALSE;
			}
			$this->id = $this->dbh->lastInsertId ();
			return ($this->id!=false);
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
	
	public function update($sid, $unique_id, $type, $username, $email){
		global $table_prefix;
		try {
			$sth = $this->dbh->prepare ( "UPDATE {$table_prefix}users SET `unique_id`= :unique_id, `account_type` = :type, `username` = :username, `email` = :email  WHERE `sid` = :sid" );
			$sth->bindParam ( ':unique_id', $unique_id );
			$sth->bindParam ( ':type', $type );
			$sth->bindParam ( ':username', $username );
			$sth->bindParam ( ':email', $email );
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
	
	public function lastLoginUpdate($sid){
		global $table_prefix;
		$lastlogin_time = get_time();
		$lastlogin_ip = get_ip();
		try {
			$sth = $this->dbh->prepare ( "UPDATE {$table_prefix}users SET `lastlogin_time`= :lastlogin_time, `lastlogin_ip` = :lastlogin_ip  WHERE `sid` = :sid" );
			$sth->bindParam ( ':lastlogin_time', $lastlogin_time );
			$sth->bindParam ( ':lastlogin_ip', $lastlogin_ip );
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
	
	public function isExists($sid){
		global $table_prefix;
		try {
			$sth = $this->dbh->prepare ( "SELECT count(*) FROM {$table_prefix}users WHERE `sid` = :sid " );
			$sth->bindParam ( ':sid', $sid );
			$sth->execute ();
			$row = $sth->fetch ();
			if ($row [0] > 0) {
				return TRUE;
			} else {
				return FALSE;
			}
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
	
	public function getDetail($sid){
		global $table_prefix;
		try {
			$sth = $this->dbh->prepare ( "SELECT * FROM {$table_prefix}users WHERE `sid` = :sid " );
			$sth->bindParam ( ':sid', $sid );
			$sth->execute ();
			$result = $sth->fetch ( PDO::FETCH_ASSOC );
			return $result;
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
	
	public function updateAccessToken($sid, $token){
		global $table_prefix;
		try {
			$sth = $this->dbh->prepare ( "UPDATE {$table_prefix}users SET `access_token`= :access_token  WHERE `sid` = :sid" );
			$sth->bindParam ( ':access_token', $token );
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
	
	public static function genSid($type, $unique_id){
		return md5($type.'_'.$unique_id);
	}
	
	public static function isLogin(){
		return isset($_SESSION['user']['sid']);
	}
	
	public static function currentSid(){
		$sid = User::isLogin()?$_SESSION['user']['sid']:'guest_'.session_id();
		if($sid == false){
			die("Unexpected Error");
		}
		return $sid;
	}
	
	public static function currentUsername(){
		return $_SESSION['user']['username'];
	}
}
