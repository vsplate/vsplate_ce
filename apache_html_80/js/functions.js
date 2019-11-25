$(function(){
        $.extend({
                escapeHtml : function(str) {
                        var entityMap = {
                                "&": "&amp;",
                                "<": "&lt;",
                                ">": "&gt;",
                                '"': '&quot;',
                                "'": '&#39;',
                                "/": '&#x2F;'
                        };
                        return String(str).replace(/[&<>"'\/]/g, function (s) {
                                return entityMap[s];
                        });
                }
	});
	
	$.extend({
                btnLoadingStart : function(obj) {
                        if(!obj){
                        	return;
                        }
                        $(obj).addClass('btnloading');
                }
	});
	
	$.extend({
                btnLoadingStop : function(obj) {
                        if(!obj){
                        	return;
                        }
                        $(obj).removeClass('btnloading');
                }
	});
        
        
	
	$.extend({
		gotoUrl : function(url) {
			window.location.href = url;
		}
	});
	$.extend({
	    startLoading : function() {
		    // Cat loading start
		    $("#loading").fadeIn(200);
		    if(typeof(loading_interval) == 'undefined'){
			    loading_interval = window.setInterval("$.loading()", 20);
		    }
		    $("#vsgobtn").attr('disabled','disabled');
		    $("#vsgobtn").attr('value','Loading...');
		    $("#vsgoaddress").attr('disabled','disabled');
	    }
    });

    $.extend({
	    stopLoading : function() {
		    // Cat loading stop
		    $("#vsgobtn").removeAttr('disabled');
		    $("#vsgobtn").attr('value','GO');
		    $("#loading").fadeOut(200);
		    $("#vsgoaddress").removeAttr('disabled');
		    loading_interval = window.clearInterval(loading_interval);
	    }
    });
});
