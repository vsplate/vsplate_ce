<?php
if (! defined ( "Z_ABSPATH" )) {
	header ( "HTTP/1.0 404 Not Found" );
	exit ();
}
?>
<div class="modal fade" id="modal-login" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo lang('LOGIN INVITATION');?></h4>
      </div>
      <div class="modal-body">
      	<p style="font-size: 130%;line-height:2em;text-align:center;"><?php echo lang('Login to vsplate.com to gain better experience.');?></p>
      	<hr />
      	<div class="nlink login-btn" href="/auth/?type=github"><span style="background-image: url(<?php echo Z_STATIC_URL;?>images/github.svg);"></span>GitHub</div>
      	<div class="nlink login-btn" href="/auth/?type=google"><span style="background-image: url(<?php echo Z_STATIC_URL;?>images/google.svg);"></span>Google</div>
      	<?php if(!LOGIN_REQUIRED){?>
      	<center style="font-size: 110%;margin: 2em 0 1em 0;"><a id="free-trial" href="#"><?php echo lang("Don't wanna to login? Make a free trial");?></a></center>
      	<?php }?>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<div id="loading">
	<img src="<?php echo Z_STATIC_URL;?>images/loading.png" />
	<h4><?php echo lang('Operation in progress, please stay on that page...');?></h4>
</div>
<script>
$("#loading img").attr('loadings', '0');
$.extend({
	loading : function() {
		if($("#loading img").css('opacity') <= 0){
			$("#loading img").attr('loadings', '1');
		}else if($("#loading img").css('opacity') >= 1){
			$("#loading img").attr('loadings', '0');
		}
		if($("#loading img").attr('loadings') == '0'){
			$("#loading img").css('opacity', parseFloat($("#loading img").css('opacity')) - 0.01);
		}else{
			$("#loading img").css('opacity', parseFloat($("#loading img").css('opacity')) + 0.01);
		}
	}
});
$("#free-trial").live("click", function () {
  	Cookies.set('login_invitation', 1, { expires: 1 });
  	$('#modal-login').modal('hide');
	return false;
});
</script>
<div class="hidden" id="token"><?php echo isset($_SESSION['token'])?esc_html($_SESSION['token']):'';?></div>
</body>
</html>
