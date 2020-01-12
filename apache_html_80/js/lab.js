$(function () {

//edit project title
$(".lab-name .btn-edit-name").live("click", function () {
	var title = $(this).closest("div.name").children("span").text();
	$(this).closest("td").children(".edit-project-title").children(".project-title").val(title);
	$(this).closest("td").children(".edit-project-title").children(".project-title").focus();
	$(this).closest("div.name").addClass("hidden");
	$(this).closest("td").children(".edit-project-title").removeClass("hidden");
	return false;
});

//save project title
$(".edit-project-title .btn-save").live("click", function () {
    var uuid = $(this).closest("tr").attr("uuid");
	var title = $(this).closest(".edit-project-title").children(".project-title").val();
	if(title == ''){
		return false;
	}
	var oldtitle = $(this).closest("td").children(".name").children("span").text();
	$(this).closest("td").children(".name").children("span").text(title);
	
	$(this).closest("td").children(".name").removeClass("hidden");
	$(this).closest(".edit-project-title").addClass("hidden");
	
	$.ajax({
		type:'POST',
		url: './api.php?mod=project&action=saveTitle&'+Math.random(),
		timeout : 20000,
		dataType:'json',
		cache: false,
		data:{
			"title": title,
			"uuid": uuid,
			"token": $("#token").text()
		},
		complete:function(XMLHttpRequest,status){
			//window.location.href="./labs.php";
			console.log("Project "+uuid+" chg title: "+oldtitle+" -> "+title);
		}
	});
	
	return false;
});

//cancel edit project title
$(".edit-project-title .btn-cancel").live("click", function () {
	$(this).closest("td").children("name").removeClass("hidden");
	$(this).closest(".edit-project-title").addClass("hidden");
	return false;
});

//console project
$(".btn-console").live("click", function () {
	if($(this).attr("disabled") == 'disabled'){
		return false;
	}
	if($(this).closest('.dropdown').hasClass('open')){
	    $(this).closest('.dropdown').removeClass('open');
	}else{
	    $(this).closest('.dropdown').addClass('open');
	}
});

//delete project
$(".btn-delete").live("click", function () {
	if($(this).attr("disabled") == 'disabled'){
		return false;
	}
	var uuid = $(this).closest("tr").attr("uuid");
	var r = confirm("Delete project: "+uuid+" ?");
	if(r != true){
		return false;
	}
	$.startLoading();
	$.ajax({
		type:'POST',
		url: './api.php?mod=project&action=delete&'+Math.random(),
		timeout : 20000,
		dataType:'json',
		cache: false,
		data:{
			"uuid": uuid,
			"token": $("#token").text()
		},
		success:function(data){
			if(!data || !data.hasOwnProperty('status') || !data.hasOwnProperty('msg')){
				alert("Error: Empty response.");
				console.log("Error: Empty response.");
			}else if(data.status == 1){
				alert("You have deleted project "+uuid+" successfully");
			}else{
				alert(data.msg);
				console.log("Error: "+data.msg);
			}
		},
		complete:function(XMLHttpRequest,status){
			if(status=='timeout'){
				alert("Error: Operation Timeout.");
				console.log("Error: Operation Timeout");
			}
			window.location.href="./labs.php";
		}
	});
	
	return false;
});

//start project
$(".btn-start").live("click", function () {
	if($(this).attr("disabled") == 'disabled'){
		return false;
	}
	var uuid = $(this).closest("tr").attr("uuid");
	$.startLoading();
	$.ajax({
		type:'POST',
		url: './api.php?mod=project&action=start&'+Math.random(),
		timeout : 60000,
		dataType:'json',
		cache: false,
		data:{
			"uuid": uuid,
			"token": $("#token").text()
		},
		success:function(data){
			if(!data || !data.hasOwnProperty('status') || !data.hasOwnProperty('msg')){
				alert("Error: Empty response.");
				console.log("Error: Empty response.");
			}else if(data.status == 1){
				window.location.href="./labs.php";
			}else{
				alert(data.msg);
				console.log("Error: "+data.msg);
			}
		},
		complete:function(XMLHttpRequest,status){
			if(status=='timeout'){
				alert("Error: Operation Timeout.");
				console.log("Error: Operation Timeout");
			}
			window.location.href="./labs.php";
		}
	});
	
	return false;
});

//stop project
$(".btn-stop").live("click", function () {
	if($(this).attr("disabled") == 'disabled'){
		return false;
	}
	var uuid = $(this).closest("tr").attr("uuid");
	$.startLoading();
	$.ajax({
		type:'POST',
		url: './api.php?mod=project&action=stop&'+Math.random(),
		timeout : 20000,
		dataType:'json',
		cache: false,
		data:{
			"uuid": uuid,
			"token": $("#token").text()
		},
		success:function(data){
			if(!data || !data.hasOwnProperty('status') || !data.hasOwnProperty('msg')){
				alert("Error: Empty response.");
				console.log("Error: Empty response.");
			}else if(data.status == 1){
				window.location.href="./labs.php";
			}else{
				alert(data.msg);
				console.log("Error: "+data.msg);
			}
		},
		complete:function(XMLHttpRequest,status){
			if(status=='timeout'){
				alert("Error: Operation Timeout.");
				console.log("Error: Operation Timeout");
			}
			window.location.href="./labs.php";
		}
	});
	
	return false;
});

});
