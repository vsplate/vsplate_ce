<?php
require_once('./inc/init.php');

$title = "VSPLATE DASHBORAD";

require_once('header.inc.php');
?>
<style>
#new-project {
	width: 100%;
	height: 100%;
	overflow: auto;
}
.new-project-form {
	margin: 1em 0;
	padding: 2em;
	background: #FFF;
	border: 1px solid #ddd;
	border-top: 0.5em solid #337ab7;
}
.new-project-form h3{
	margin: 0;
	padding: 0;
	color: #333;
	font-weight: bold;
}
.new-project-navbar {
	width: 33em;
}
.new-project-navbar ul{
	width: 100%;
	height: 3em;
	list-style: none;
	margin: 1em 0 0.3em 0;
	padding: 0;
	border: 1px solid #ddd;
	background: #FFF;
}
.new-project-navbar ul li{
	float: left;
	padding: 0 2em;
	line-height: 3em;
	cursor: pointer;
	text-transform: uppercase;
}
.new-project-navbar ul li.selected{
	background: #ededed;
	font-weight: bold;
}
.new-project-navbar ul li:hover{
	background: #ededed;
}

.new-project-form form input[type="submit"]{
	border: 1px solid #337ab7;
	background-color: #337ab7;
	color: #FFF;
	padding: 0.6em 1em;
	width: 6em;
	text-align: center;
	font-weight: bold;
}
.new-project-form form input[type="text"]{
	border: 1px solid #ddd;
	background: #FFF;
	padding: 0.6em 1em;
	width: 70%;
}
.new-project-form form input[type="file"]{
	border: none;
	background: #FFF;
	padding: 0.4em 0;
	width: 70%;
}
.new-project-tips{
	line-height: 4em;
	color: #9d9d9d;
	width: 100%;
	text-align: left;
	margin: 0;
	padding: 0;
}
.new-project-form label{
	width: 100%;
}
.new-project-form textarea{
	border: 1px solid #ddd;
	width: 100%;
	height: 23em;
	font-size: 120%;
	padding: 0.6em 1em;
	resize: vertical;
	font-weight: lighter;
}
.new-project-upload input[type="file"]{
	display: none;
}
.new-project-upload label{
	width: 70%;
}
.choose-file-btn {
	border: 1px solid #ddd;
	width: 100%;
	padding: 0.6em 0;
}
.choose-file-btn span.filebtn {
	padding: 0.75em 0.8em;
	background: #ededed;
}
.choose-file-btn span.filepath {
	padding: 0.9em;
	font-weight: lighter;
}
#page-info {
	margin: 1em 0;
}
#page-info span.info{
	font-size: 70%;
	background-color: #f1f4f6;
	border-radius: 2px;
	color: #81939f;
	padding: 0.2em 0.5em;
}
#page-info span.error{
	font-size: 70%;
	background-color: #f2dede;
	border-radius: 2px;
	color: #a94442;
	padding: 0.2em 0.5em;
}
</style>
<script>
$(function () {
$(".new-project-nav").live("click", function () {
	$("#page-info").html('');
	$(".new-project-nav").removeClass("selected");
	$(this).addClass("selected");
	if($(this).hasClass("nav-upload")){
		$(".new-project-text").addClass("hidden");
		$(".new-project-upload").removeClass("hidden");
	}else{
		$(".new-project-text").removeClass("hidden");
		$(".new-project-upload").addClass("hidden");
	}
});

$('.new-project-form input[type="submit"]').live("click", function () {
	var upway = '';
	var docker_compose = $("#custom_compose").val();
	if($(".new-project-nav.selected").hasClass("nav-url")){
		upway = 'nav-url';
		url = $('.new-project-text input[type="text"]').val();
		if(url == '' && docker_compose == ''){
			return false;
		}
		if(!(/^(http|https):\/\/\w+/.test(url)) && docker_compose == ''){
			$.pageError("Please input a valid URL address.");
			return false;
		}
	}else if($(".new-project-nav.selected").hasClass("nav-upload")){
		upway = 'nav-upload'
		var fn = $('.new-project-upload input[type="file"]').val();
		var filename = fn.match(/[^\\/]*$/)[0];
		var ext = fn.match(/[^.]*$/)[0];
		if(fn == ''){
			return false;
		}
		if(ext.toLowerCase() != 'zip'){
			$.pageError("Error: Not a valid zip file.");
			return false;
		}
	}
	if(upway == ''){
		$.pageError("Error: Unknow Method");
		return false;
	}
	console.log("upway: "+upway);
	$('.new-project-form input[name="docker-compose"]').val(docker_compose);
});

$('.new-project-upload input[type="file"]').live('change', function(){
	var fn = $(this).val();
	var filename = fn.match(/[^\\/]*$/)[0];
	var ext = fn.match(/[^.]*$/)[0];
	$(".new-project-upload .filepath").text(filename);
	if(ext.toLowerCase() != 'zip'){
		$.pageError("Error: Not a valid zip file.");
	}
});

$.extend({
	pageInfo : function(str) {
		var html = '<span class="info">'+$.escapeHtml(str)+'</span>';
		$("#page-info").html(html);
	}
});

$.extend({
	pageError : function(str) {
		var html = '<span class="error">'+$.escapeHtml(str)+'</span>';
		$("#page-info").html(html);
	}
});

});
</script>
<div id="main">
    <div id="body-middle" style="margin-top: 5em;">
	    <div id="new-project">
		    <div class="new-project-form">
			    <h3>NEW PROJECT</h3>
			    <hr/>
			    <form action="./launch.php?upway=text" class="new-project-text" method="POST">
				    <input type="text" name="address" placeholder="Input a URL / a GitHub Repository with docker-compose.yml" />
				    <input type="hidden" name="docker-compose">
				    <input type="hidden" name="token" value="<?php echo isset($_SESSION['token'])?esc_html($_SESSION['token']):'';?>">
				    <input type="submit" name="submit" value="GO">
			    </form>
			    <form action="./launch.php?upway=upload" class="new-project-upload hidden" enctype="multipart/form-data" method="POST">
				    <label>
					    <div class="choose-file-btn">
						    <span class="filebtn">CHOOSE A FILE</span>
						    <span class="filepath">Only ZIP file supported</span>
					    </div>
					    <input type="file" name="file" placeholder="ZIP FILE" />
				    </label>
				    <input type="hidden" name="docker-compose">
				    <input type="hidden" name="token" value="<?php echo isset($_SESSION['token'])?esc_html($_SESSION['token']):'';?>">
				    <input type="submit" name="submit" value="GO">
			    </form>
			    <div id="page-info"></div>
			    <div class="new-project-navbar">
				    <ul>
					    <li class="new-project-nav nav-url selected">Github Repos / Docker Compose</li>
					    <li class="new-project-nav nav-upload">Upload Archive</li>
				    </ul>
			    </div>
			    <p class="new-project-tips">CHOOSE THE SOURCE OF YOUR PROJECT OR DOCKER-COMPOSER.YML. <a href="#"><span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span> How?</a></p>
			    <hr />
			    <label>
				    <p style="font-weight:bold;line-height: 2em;">CUSTOM YOUR DOCKER-COMPOSE.YML :</p>
				    <textarea id="custom_compose" placeholder="Custom your docker-compose.yml"></textarea>
			    </label>
		    </div>
	    </div>
    </div>
</div>
<?php
require_once('footer.inc.php');
?>
