$(function () {
    $('[data-toggle="tooltip"]').tooltip();

    $(".divlink, .nlink").live("click", function () {
        var href = $(this).attr('href');
        if (href != '')
            $.gotoUrl(href);
        return false;
    });

    $.getToken = function () {
        return $("div#token").text();
    }

    $.getUrlParam = function (name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
        var r = window.location.search.substr(1).match(reg);
        if (r != null)
            return unescape(r[2]);
        return null;
    }

    $.isSafeStr = function (str) {
        var reg = /^([a-zA-Z0-9_-])+/;
        return reg.test(str);
    }
    
    $(".view-header .menu-login li:first").live("click", function () {
    	if($(".view-header .submenu").css('display') == 'none'){
    		$(".view-header .submenu").show();
    		$(".view-header .menu-login li:first").css('background-color', '#202931');
    	}else{
        	$(".view-header .submenu").hide();
        	$(".view-header .menu-login li:first").css('background-color', 'transparent');
        }
        return false;
    });
    
    $(".choose_lang").live("click", function () {
		var lang = $(this).attr('value');
		if(lang != ''){
			Cookies.set('lang', lang);
			location.reload();
		}
		return false;
    });
    
    $("a").live("click", function () {
		var href = $(this).attr('href');
		var target = $(this).attr('target');
		if(href == '' || href == '#'){
			if(target == "_blank"){
				return false;
			}
		}
    });
});
