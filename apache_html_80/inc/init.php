<?php
header('x-frame-options: SAMEORIGIN');
header('x-content-type-options: nosniff');
require_once('config.php');
date_default_timezone_set ( Z_TIMEZONE );
require_once(Z_ABSPATH.'/inc/functions.php');
require_once(Z_ABSPATH.'/inc/autoload.php');
require_once(Z_ABSPATH.'/php-docker-compose/docker-compose.php');

if(!is_session_started())
	session_start();
if(!isset($_SESSION['token'])){
	$_SESSION['token'] = md5(get_salt());
}
z_debug_mode ();

if(!isset($nodb) || $nodb == false){

	try {
	    $DBH = new zPDO("mysql:host=" . Z_DB_HOST . ";dbname=" . Z_DB_NAME . ";charset=" . Z_DB_CHARSET, Z_DB_USER, Z_DB_PASSWORD, array(
		PDO::ATTR_PERSISTENT => true
	    ));
	    $DBH->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	    $DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	    $DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
	    $GLOBALS ["z_dbh"] = $DBH;
	} catch (Exception $e) {
	    header('Content-Type: text/plain; charset=utf-8');
	    if(Z_DEBUG)
		die("Error!: " . $e->getMessage());
	    else
		die("Connect database error.");
	}
}
