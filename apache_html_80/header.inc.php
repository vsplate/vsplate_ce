<?php
if (! defined ( "Z_ABSPATH" )) {
	header ( "HTTP/1.0 404 Not Found" );
	exit ();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="zh-CN" lang="zh-CN">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="author" content="Ambulong">
<title><?php echo (isset($title))?$title:""; ?></title>
<script>
var pageActive = true;
var hiddenProperty = 'hidden' in document ? 'hidden' :    
    'webkitHidden' in document ? 'webkitHidden' :    
    'mozHidden' in document ? 'mozHidden' :    
    null;
var visibilityChangeEvent = hiddenProperty.replace(/hidden/i, 'visibilitychange');
var onVisibilityChange = function(){
    if (!document[hiddenProperty]) {    
        console.log('Hello');
        pageActive = true;
    }else{
        console.log('Bye');
        pageActive = false;
    }
}
document.addEventListener(visibilityChangeEvent, onVisibilityChange);
</script>
<script src="<?php echo Z_STATIC_URL;?>js/jquery-1.11.3.min.js?<?php echo Z_VERSION;?>"></script>
<script src="<?php echo Z_STATIC_URL;?>js/js.cookie-2.2.0.min.js?<?php echo Z_VERSION;?>"></script>
<script src="<?php echo Z_STATIC_URL;?>js/jquery-migrate-1.2.1.min.js?<?php echo Z_VERSION;?>"></script>
<link href="<?php echo Z_STATIC_URL;?>css/bootstrap.min.css?<?php echo Z_VERSION;?>" rel="stylesheet">
<link href="<?php echo Z_STATIC_URL;?>css/ie10-viewport-bug-workaround.css?<?php echo Z_VERSION;?>" rel="stylesheet">
<!--[if lt IE 9]><script src="<?php echo Z_STATIC_URL;?>js/ie8-responsive-file-warning.js?<?php echo Z_VERSION;?>"></script><![endif]-->
<script src="<?php echo Z_STATIC_URL;?>js/ie-emulation-modes-warning.js?<?php echo Z_VERSION;?>"></script>
<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
<![endif]-->
<script src="<?php echo Z_STATIC_URL;?>js/bootstrap.min.js?<?php echo Z_VERSION;?>"></script>
<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
<script src="<?php echo Z_STATIC_URL;?>js/ie10-viewport-bug-workaround.js?<?php echo Z_VERSION;?>"></script>
<link rel="stylesheet" href="./css/custom.css?<?php echo Z_VERSION;?>">
<script src="<?php echo Z_STATIC_URL;?>js/functions.js?<?php echo Z_VERSION;?>"></script>
<script src="<?php echo Z_STATIC_URL;?>js/custom.js?<?php echo Z_VERSION;?>"></script>
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="//www.googletagmanager.com/gtag/js?id=UA-108867384-1"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-108867384-1');
</script>
</head>
<body>
<style>
    .menu-login{
        position: relative;
        margin-right: 4em;
    }
    .menu-login:hover{
        background: #202931;
    }
    .menu-logout .username{
    	color: #FFF;
    	font-weight: bold;
    }
    .menu-logout .username:hover{
    	background: transparent;
    }
    ul.submenu{
        position: absolute;
        top: 100%;
        width: 100%;
        margin: 0;
        padding: 0;
        display: none;
    }
    ul.submenu li{
        width: 160%;
        margin: 0;
        color: #FFF;
        padding-left: 1em;
        background: #202931;
        cursor: pointer;
    }
    ul.submenu li span{
        background: no-repeat left center;
        background-size: auto 55%;
        padding: 1em;
        margin-right: 1em;
    }
</style>
<div class="view-header" style="z-index: 999;">
    <ul>
        <li><a href="./"><h1><img style="width:2em;height: auto;margin-top: -0.4em;" src="<?php echo Z_STATIC_URL;?>images/logo.png" /></h1></a></li>
        <li><a href="./">VSPLATE</a></li>
        <li><a href="./labs.php">LABS</a></li>
        <?php if(User::isLogin()){?>
        <div class="pull-right menu-logout">
            <li class="username"><?php echo lang('Hi');?>, <?php echo esc_html(User::currentUsername());?></li>
            <li><a href="./auth/logout.php?token=<?php echo $_SESSION['token'];?>"><?php echo lang('Logout');?></a></li>
        </div>
        <?php }else{ ?>
        <div class="pull-right menu-login">
            <li style="min-width:8em;text-align:center;"><a href="#" style="width:100%;"><?php echo lang('SIGN IN');?> <span style="font-weight: bold;" class="glyphicon glyphicon-menu-down" aria-hidden="true"></span></a></li>
            <ul class="submenu">
                <li><a href="./auth/?type=github"><span style="background-image: url(<?php echo Z_STATIC_URL;?>images/github.svg);"></span><?php echo lang('GitHub');?></a></li>
                <li><a href="./auth/?type=google"><span style="background-image: url(<?php echo Z_STATIC_URL;?>images/google.svg);"></span><?php echo lang('Google');?></a></li>
            </ul>
        </div>
        <?php } ?>
    </ul>
</div>
