<?php
if (php_sapi_name() != "cli") {
	die('Access Denied');
}
while(1){
	system("php /var/www/html/crond/clean_containers.php");
	system("php /var/www/html/crond/clean_projects_dir.php");
	sleep(60);
}
