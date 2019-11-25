<?php
define('Z_ABSPATH', '/var/www/html');

define('LOGIN_REQUIRED',  false);

define('Z_HOST', 'www.vsplate.com');

define('Z_VERSION', 'v3');

define('Z_STATIC_URL', './');

//docker-compose 端口
//define('DOCKER_API_API', 'http://docker.vsplate.com:81');
define('DOCKER_API_API', 'http://172.17.0.1:81');
define('DOCKER_API_KEY', '***');

//py-download 接口
define('DOWNLOAD_API_API', 'http://127.0.0.1:82');
define('DOWNLOAD_API_KEY', '***');

//保存用户上传的文件
define('USR_FILES_DIR', '/var/www/data/');

//OAuth登录
define('AUTH_CLIENT_ID', 'www.vsplate.com');
define('AUTH_SECRET', '***');
define('AUTH_REDIRECT_URI', 'https://www.vsplate.com/auth/callback.php?type=vsplate');

//GITHUB KEY
define('GITHUB_CLIENT_ID', '***');
define('GITHUB_SECRET', '***');

define('SERVICE_DOMAIN', 'vsplate.me');
define('DEFAULT_TIMEOUT', 7200); //登录用户demo运行时间seconds
define('DEFAULT_MAX_NUM', 999); //登录用户demo最大数量
define('GUEST_TIMEOUT', 666); //临时用户demo运行时间seconds
define('GUEST_MAX_NUM', 6); //临时用户demo最大数量
define('MAX_RUNNING', 5); //运行demo最大数量
define('PROJ_MAX_SIZE', 20480); //1024*20=20M

/**
 * The name of the MySQL database
 */
define ( 'Z_DB_NAME', 'vsplate' );

/**
 * MySQL database username
 */
define ( 'Z_DB_USER', 'root' );

/**
 * MySQL database password
 */
define ( 'Z_DB_PASSWORD', 'toor' );

/**
 * MySQL hostname
 */
define ( 'Z_DB_HOST', 'localhost' );

/**
 * Database Charset to use in creating database tables.
 */
define ( 'Z_DB_CHARSET', 'utf8' );

define ( 'Z_TIMEZONE', 'Asia/Shanghai' );

/**
 * For developers
 */
define ( 'Z_DEBUG', true );

/**
 * Database Table prefix.
 */
$table_prefix = 'z_';
