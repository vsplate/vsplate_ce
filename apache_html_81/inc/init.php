<?php
header('x-frame-options: SAMEORIGIN');
header('x-content-type-options: nosniff');
require_once('config.php');
date_default_timezone_set ( Z_TIMEZONE );
require_once(Z_ABSPATH.'/inc/functions.php');
require_once(Z_ABSPATH.'/inc/autoload.php');
z_debug_mode ();
