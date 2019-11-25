<?php
//首页不需要数据库连接，加快运行
$nodb = true;

require_once('./inc/init.php');
$title = "VSPLATE - Launch Your Project";
$desc = "Share your works to the world and make users have an better trail experience.";

//是否自动提交
$autogo = isset($_REQUEST['autogo'])?intval(boolval($_REQUEST['autogo'])):0;
//GitHub地址
$github = isset($_GET['github'])?trim($_GET['github'], '/'):'';
//docker-compose.yml地址
$docker_compose = isset($_GET['docker-compose'])?trim($_GET['docker-compose']):'';
if($github != false){
	if(preg_match('/^.+\/.+$/i', $github)) {
		$github = 'https://github.com/'.$github;
	}else{
		$github = '';
	}
}
$address = '';
if($docker_compose != false){
	$address = $docker_compose;
}elseif($github != false){
	$address = $github;
}
require_once('header.inc.php');
?>
<script>
var autogo = <?php echo $autogo;?>;
$(function () {
$("#vsgobtn").live("click", function () {
	$.launchDemo();
});

$.extend({
	pageInfo : function(str) {
		var html = '<span class="info">'+$.escapeHtml(str)+'</span>';
		$("#index-page-info").html(html);
	}
});

$.extend({
	pageError : function(str) {
		var html = '<span class="error">'+$.escapeHtml(str)+'</span>';
		$("#index-page-info").html(html);
	}
});

$.extend({
	sendGitHubLaunch : function(url,upway) {
		var repos = url.replace(/https:\/\/github.com\//, "");
		var apiurl = 'https://api.github.com/repos/'+repos;
		var updated_at = (new Date()).getTime();
		$.ajax({
			type:'GET',
			url: apiurl,
			timeout : 10000,
			cache: false,
			dataType:'json',
			success:function(data){
				if(!data || !data.hasOwnProperty('updated_at')){
					console.log("Error: Fail to fetch GitHub Repos updated_at (Empty Response).");
				}else if(data.updated_at){
					updated_at = data.updated_at;
					console.log("GitHub Repos updated_at: "+updated_at);
				}
			},
			error:function(){
				console.log("Error: Fail to fetch GitHub Repos updated_at (Request Error).");
			},
			complete:function(XMLHttpRequest,status){
				if(status=='timeout'){
					//ajaxTimeoutTest.abort();
					console.log("Timeout: Fail to fetch GitHub Repos updated_at.");
				}
				$.sendLaunch(url,upway,updated_at);
			}
		});
	}
});

$.extend({
	sendLaunch : function(url,upway,github_updated_at) {
		var suc = false;
		try{
			$.ajax({
				type:'POST',
				url: './launch.php?upway=text&isajax=1',
				timeout : 60000,
				dataType:'json',
				cache: false,
				data:{
					"address": url,
					"index-upway": upway,
					"github_updated_at": github_updated_at,
					"token": $("#token").text()
				},
				success:function(data){
					if(!data || !data.hasOwnProperty('status') || !data.hasOwnProperty('msg')){
						$.pageError("Error: Empty response.");
						console.log("Error: Empty response.");
					}else if(data.status == 1){
						var uuid = data.data.uuid;
						suc = true;
						$.gotoUrl('/labs.php?autostart=1&uuid='+uuid+'&'+Math.random());
					}else{
						$.pageError(data.msg);
						console.log("Error: "+data.msg);
					}
				},
				error:function(XMLHttpRequest, textStatus, errorThrown){
					$.pageError("Error "+$.escapeHtml(XMLHttpRequest.status)+": Fail to launch the project (Request Error).");
					console.log("Error "+$.escapeHtml(XMLHttpRequest.status)+": Fail to launch the project (Request Error).");
				},
				complete:function(XMLHttpRequest,status){
					if(status=='timeout'){
						$.pageError("Error: Operation Timeout.");
						console.log("Error: Operation Timeout");
					}
					if(suc != true){
						$.stopLoading();
					}
				}
			});
		}catch(err){
			$.pageError("Error: "+$.escapeHtml(err));
			console.log(err);
			$.stopLoading();
		}
	}
});

$.extend({
	launchDemo : function() {
		var url = $("#vsgoaddress").val();
		var upway = 'docker-compose';
		var github_updated_at = (new Date()).getTime();
		if(url == '')
			url = $("#vsgoaddress").attr('placeholder')
		if(!(/^(http|https):\/\/\w+/.test(url))){
			$.pageError("Please input a valid URL address.");
			return false;
		}
		$.startLoading();
		if(/^https:\/\/github.com\/\w+/.test(url) && !(/.yml$/.test(url))){
			upway = 'github-repos';
			$.sendGitHubLaunch(url,upway);
		}else{
			$.sendLaunch(url,upway,github_updated_at);
		}
	}
});

});
</script>
<script>
$(function () {
if(autogo == 1){
	$("#vsgobtn").trigger('click');
}
});
</script>
<style>
#index-page {
	width: 100%;
	height: 100%;
	text-align: center;
	padding-top: 10em;
	color: #444;
}
#index-page h1{
	font-size: 380%;
	margin-bottom: 0.7em;
	font-weight: bold;
}
#index-page-menu {
	font-size: 160%;
}
#index-page-form {
	margin-top: 3em;
}
#index-page-form input[type="text"] {
    width: 70%;
    max-width: 50em;
    height: 3em;
    font-size: 80%;
    outline: none;
    margin: 0;
    border: 1px solid #dcdcdc;
    padding: 0.5em 1em;
    border-radius: 2em;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}
#index-page-form input[type="button"] {
    margin: 0;
    margin-left: -0.4em;
    color: #FFF;
    height: 3em;
    width: 20%;
    max-width: 8em;
    font-size: 80%;
    background: #337ab7;
    border: 1px solid #2196f3;
    border-radius: 2em;
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}
#index-page-info {
	margin: 1em 0;
}
#index-page-info span.info{
	font-size: 70%;
	background-color: #f1f4f6;
	border-radius: 2px;
	color: #81939f;
	padding: 0.2em 0.5em;
}
#index-page-info span.error{
	font-size: 70%;
	background-color: #f2dede;
	border-radius: 2px;
	color: #a94442;
	padding: 0.2em 0.5em;
}
#index-page-footer {
	display: none;
	text-align: center;
}
</style>
<div id="main" style="background: #FFF;">
	<div id="index-page">
		<h1>Launch Your Project</h1>
		<div id="index-page-menu">
			<span style="padding:0 0.3em;background:#337ab7;color:#FFF;">VIA</span>
			<span> VSPLATE.COM</span>
		</h2>
		<div id="index-page-form">
			<input type="text" id="vsgoaddress" autocomplete="off" name="address" placeholder="https://github.com/vulnspy/SampleCMS/" value="<?php echo isset($address)?esc_html($address):'';?>">
			<input id="vsgobtn" type="button" name="btn" value="GO">
		</div>
		<div id="index-page-info">
			<span class="info">LAUNCH WITH REMOTE DOCKER-COMPOSE.YML FILE /<br/> GITHUB REPOSITORY CONTAINS DOCKER-COMPOSE.YML FILE</span>
		</div>
	</div>
	<div id="index-page-footer">
		<p>&copy; <?php echo date('Y');?></p>
	</div>
</div>
<?php
require_once('footer.inc.php');
?>
