$(function () {

$.updateStatus = function (obj, data) {
	status = data.status.toLowerCase();
	time_left = data.time_left;
	if(status == 'running' && time_left < 0){
		//status = 'exited';
		time_left = 0;
	}
	time_left = new Date(time_left * 1000).toISOString().substr(11, 8);
	label = '<span class="label label-default">unknow</span>';
	if(status == 'running'){
		label = '<span class="label label-success">running</span>';
	}else if(status == 'pending'){
		label = '<span class="label label-info">pending</span>';
	}else if(status == 'creating'){
		label = '<span class="label label-info">creating</span>';
	}else{
		label = '<span class="label label-default">'+status+'</span>';
	}
	if(obj){
	    var oldstatus = $(obj).find(".lab-status span").text();
		if(status == 'running'){
		    $(obj).children(".lab-timeleft").text(time_left);
		    $(obj).find('.btn-start').attr('disabled','disabled');
		    $(obj).find('.btn-stop').removeAttr('disabled');
		    $(obj).find('.btn-console').removeAttr('disabled');
		    $(obj).find('.btn-delete').removeAttr('disabled');
		}else if(status == 'pending'){
		    $(obj).find('.btn-start').attr('disabled','disabled');
		    $(obj).find('.btn-stop').attr('disabled','disabled');
		    $(obj).find('.btn-console').attr('disabled','disabled');
		    $(obj).find('.btn-delete').attr('disabled','disabled');
		}else{
		    $(obj).find('.btn-start').removeAttr('disabled');
		    $(obj).find('.btn-stop').attr('disabled','disabled');
		    $(obj).find('.btn-console').attr('disabled','disabled');
		    $(obj).find('.btn-delete').removeAttr('disabled');
		}
		$(obj).children(".lab-status").html(label);
		//如果状态发生改变，刷新页面
		if(oldstatus != '...' && oldstatus != status){
		    window.location.href="./labs.php";
		}
		console.log("uuid "+$(obj).attr('uuid')+": "+status);
	}else{
		console.log('unknow obj');
	}
	
}

$.loadStatus = function () {
	if(pageActive != true){
		return;
	}
	//console.log(pageActive);

	$("#projects-list tr").each(function(){
		var uuid = $(this).attr('uuid');
		var obj = $(this);
		var proj_data = false;
		if(typeof($(obj).attr('statuslock')) == 'undefined' && uuid){
		    $(obj).attr('statuslock', '1');
			try{
				$.ajax({
					type:'POST',
					url: './api.php?mod=project&action=status&'+Math.random(),
					timeout : 30000,
					dataType:'json',
					data:{
						"uuid": uuid,
						"token": $("#token").text()
					},
					success:function(data){
						if(!data || !data.hasOwnProperty('status') || !data.hasOwnProperty('msg')){
							console.log("Error: Empty response: "+uuid);
						}else if(data.status == 1){
							//console.log("Status "+uuid+": "+data.data.status);
							proj_data = data.data;
						}else{
							console.log("Error "+uuid+": "+data.msg);
						}
					},
					error:function(XMLHttpRequest, textStatus, errorThrown){
						console.log("Error "+uuid+":"+$.escapeHtml(XMLHttpRequest.status));
					},
					complete:function(XMLHttpRequest,status){
					    $(obj).removeAttr('statuslock');
						if(status=='timeout'){
							console.log("Error "+uuid+": Operation Timeout");
						}
						if(proj_data){
							$.updateStatus(obj, proj_data);
						}
					}
				});
			}catch(err){
			    $(obj).removeAttr('statuslock');
				console.log(err);
			}
		}
	});
}
$.loadStatus();
var i_loadStatus = window.setInterval("$.loadStatus()",5000);

});
