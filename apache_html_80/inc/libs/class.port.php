<?php
if (! defined ( "Z_ABSPATH" )) {
	header ( "HTTP/1.0 404 Not Found" );
	exit ();
}

/**
* REF: https://stackoverflow.com/questions/11680025/how-to-generate-random-number-without-repeat-in-database-using-php
SELECT random_num
FROM (
  SELECT FLOOR(RAND() * 65000) AS random_num 
  UNION
  SELECT FLOOR(RAND() * 65000) AS random_num
) AS numbers_mst_plus_1
WHERE `random_num` NOT IN (SELECT target FROM z_ports)
AND `random_num` > 6500
LIMIT 1
**/
class Port {
	public $id = NULL;
	private $dbh = NULL;
	
	public function __construct() {
		$this->dbh = $GLOBALS ['z_dbh'];
	}
	
	public function add($pid, $container_name, $orig, $target = ''){
		global $table_prefix;
		$pid = intval($pid);
		$container_name = trim($container_name);
		$orig = intval($orig);
		$target = intval($target);
		$time = get_time();
		if($pid == false || $container_name == false || $orig == false){
			return false;
		}
		if($target != false){
			$sql = "INSERT INTO {$table_prefix}ports(`project_id`,`container_name`,`orig`,`target`,`time`) VALUES(:pid,:container_name, :orig, :target, :time)";
			try {
				$this->id = NULL;
				$sth = $this->dbh->prepare ( $sql );
				$sth->bindParam ( ':pid', $pid );
				$sth->bindParam ( ':container_name', $container_name );
				$sth->bindParam ( ':orig', $orig );
				$sth->bindParam ( ':target', $target );
				$sth->bindParam ( ':time', $time );
				$sth->execute ();
				if (! ($sth->rowCount () > 0)) {
					return FALSE;
				}
				$this->id = $this->dbh->lastInsertId ();
				return true;
			} catch ( PDOExecption $e ) {
				echo "<br>Error: " . $e->getMessage ();
			}
		}else{
			$sql = "INSERT INTO {$table_prefix}ports(`project_id`,`container_name`,`orig`,`target`,`time`) VALUES(:pid,:container_name, :orig, (select random_num from (SELECT random_num
FROM (
  SELECT FLOOR(RAND() * 65000) AS random_num 
  UNION
  SELECT FLOOR(RAND() * 65000) AS random_num
) AS numbers_mst_plus_1
WHERE `random_num` NOT IN (SELECT target FROM {$table_prefix}ports)
AND `random_num` > 6500
LIMIT 1) as a), :time)";
			try {
				$this->id = NULL;
				$sth = $this->dbh->prepare ( $sql );
				$sth->bindParam ( ':pid', $pid );
				$sth->bindParam ( ':container_name', $container_name );
				$sth->bindParam ( ':orig', $orig );
				$sth->bindParam ( ':time', $time );
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
	}
	
	public function delete($id){
		global $table_prefix;
		$id = intval($id);
		try {
			$sth = $this->dbh->prepare ( "DELETE FROM {$table_prefix}ports  WHERE `id` = :id" );
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
	
	public function deleteProject($pid){
		global $table_prefix;
		$pid = intval($pid);
		try {
			$sth = $this->dbh->prepare ( "DELETE FROM {$table_prefix}ports  WHERE `project_id` = :pid" );
			$sth->bindParam ( ':pid', $pid );
			$sth->execute ();
			if (! ($sth->rowCount () > 0))
				return FALSE;
			else
				return TRUE;
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
	
	public function isExists($id){
		global $table_prefix;
		try {
			$sth = $this->dbh->prepare ( "SELECT count(*) FROM {$table_prefix}ports WHERE `id` = :id " );
			$sth->bindParam ( ':id', $id );
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
	
	public function getDetail($id){
		global $table_prefix;
		try {
			$sth = $this->dbh->prepare ( "SELECT * FROM {$table_prefix}ports WHERE `id` = :id " );
			$sth->bindParam ( ':id', $id );
			$sth->execute ();
			$result = $sth->fetch ( PDO::FETCH_ASSOC );
			return $result;
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
	
	public function getProjectDetail($pid){
		global $table_prefix;
		try {
			$sth = $this->dbh->prepare ( "SELECT * FROM {$table_prefix}ports WHERE `project_id` = :pid " );
			$sth->bindParam ( ':pid', $pid );
			$sth->execute ();
			$result = $sth->fetchAll ( PDO::FETCH_ASSOC );
			return $result;
		} catch ( PDOExecption $e ) {
			echo "<br>Error: " . $e->getMessage ();
		}
	}
	
}
