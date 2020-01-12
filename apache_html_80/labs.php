<?php
require_once('./inc/init.php');

$projObj = new Project();
$sid = User::currentSid();
$list = $projObj->getUserList($sid);

$autostart_uuid = isset($_REQUEST['uuid'])?trim($_REQUEST['uuid']):0;
$autostart = isset($_REQUEST['autostart'])?boolval($_REQUEST['autostart']):false;
if(!is_safe_str($autostart_uuid)){
    $autostart_uuid = 0;
}

$title = "VSPLATE DASHBORAD";

require_once('header.inc.php');
?>
<script src="./js/lab.js?<?php echo Z_VERSION;?>"></script>
<script src="./js/sync.js?<?php echo Z_VERSION;?>"></script>
<script>
var autostart_uuid = '<?php echo esc_html($autostart_uuid);?>';
var autostart = <?php echo intval($autostart);?>;
$(function () {
if(autostart == 1){
    if(typeof($("tr[uuid='"+autostart_uuid+"']").html()) == 'string'){
        $("tr[uuid='"+autostart_uuid+"']").find(".btn-start").removeAttr("disabled");
        $("tr[uuid='"+autostart_uuid+"']").find(".btn-start").trigger("click");
    }
}

$(".m-notice p a").live("click", function () {
  	$('#modal-login').modal('show');
	return false;
});

<?php if(LOGIN_REQUIRED && !User::isLogin()){?>
if(typeof($(".m-notice").html()) == 'string'){
    $('#modal-login').modal('show');
}
<?php }elseif(!User::isLogin()){?>
if(Cookies.get('login_invitation') != 1){
    if(typeof($(".m-notice").html()) == 'string'){
        $('#modal-login').modal('show');
    }
}
<?php }?>
});
</script>
<style>
.m-notice {
	margin-top: -2em;
	margin-bottom: 2em;
	background: #fcf8e3;
}
.m-notice p{
    font-size: 130%;
    padding: 1em;
}
.m-panel{
    padding: 1em;
    border: 1px solid #ddd;
    border-top: 0.5em solid #337ab7;
    margin-bottom: 1em;
}
.m-panel-title h3{
    padding: 0;
    margin: 0.2em 0;
    font-size: 200%;
    font-weight: bold;
    color: #333;
}
.m-panel-title p{
    color: #767676;
    line-height: 1.5em;
    margin-top: 0.9em;
    font-size: 130%;
}

.m-panel-body{
    padding: 1em 0 0 0;
}

.m-panel-body table{
    margin: 0;
}

.m-panel-body table tr td, .m-panel-body table tr th{
    font-size: 123%;
    padding: 0.7em 0.5em;
    word-wrap: break-word;
}

.lab-name {
    max-width: 20em;
}

.lab-target ul {
    padding: 0;
    margin: 0;
}

.lab-target ul li{
    list-style: none;
    margin-top: 0.5em;
}

.lab-target ul li:first-child {
    margin-top: 0;
}

.lab-target ul li div {
    float: left;
    text-align: right;
    margin-right: 0.5em;
    margin-top: 0.5em;
    width: 6em;
    font-size: 40%;
    color: #e91e63;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.lab-target li.exited-project{
    opacity: 0.3;
}

.lab-operation {
	white-space:nowrap;
}

.lab-operation a{
    padding: 0 0.3em;
}

.btn-delete {
    color: #d9534f;
}

.btn-delete:hover {
    color: #ac2925;
}
.btn-edit-name {
    margin-left: 0.5em;
}
</style>
<div id="main">
    <div id="body-middle" style="margin-top: 7em;">
        <?php if(!User::isLogin()){ ?>
        <div class="m-notice">
            <p>The following projects will not be saved after closing this page, you can <a href="#">sign in</a> vsplate.com to gain more running time and more better experience.</p>
        </div>
        <?php } ?>
        <div class="m-panel">
            <div class="m-panel-title">
                <h3>
                    <span>Projects List</span>
                    <a href="./new.php" style="margin-top:-0.4em;margin-left:1em;font-size:60%;" class="btn btn-primary">NEW PROJECT</a>
                </h3>
                <p>Those projects will stop automatically when the timer expires. The maximum number of running projects is <?php echo intval(MAX_RUNNING);?>.</p>
            </div>
            <div id="projects-list" class="m-panel-body">
                <table class="table"> 
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th><span style="font-size:40%;color:#e91e63;font-weight:lighter;padding-left:2.5em;">source &#x203A;</span> Target Address</th>
                            <th>Status</th>
                            <th>Time Left</th>
                            <th>Operation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!is_array($list) || count($list) <= 0){?>
                        <tr><td style="text-align:center;color: #767676;" colspan="10" id="labs-list-empty">Empty Projects</td></tr>
                        <!--<tr><td style="text-align:center;color: #767676;" colspan="10" id="labs-list-empty"><div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div></td></tr>-->
                        <?php }else{ ?>
                        <?php
                        $list = array_reverse($list);//反向排序，使最新添加的在前
                        foreach($list as $key => $item){
                            $uuid = trim($item['uuid']);
                            $title = $item['name'];
                            $dir = $item['dir'];
                            $target_html = '';
                            $start_time = $item['start_time'];
                            if($start_time == false || $start_time == '0000-00-00 00:00:00'){
                                $start_time = false;
                            }
                            
                            //如果项目已启动将会有端口映射信息
                            $portObj = new Port();
                            $ports = $portObj->getProjectDetail($item['id']);
                            if($ports != false && is_array($ports)){
                                $li = array();
                                foreach($ports as $port){
                                    $o = $port['orig'];
                                    $t = $port['target'];
                                    $container_name = $port['container_name'];
                                    list($hash, $name) = explode('_', $container_name);
                                    //以8开头的一般为http服务
                                    if(substr($o,0,1) == '8'){
                                        $href = "http://".$_SERVER['HTTP_HOST'].':'.$t;
                                        $target = $_SERVER['HTTP_HOST'];
                                        $li[] = '<li><div>'.esc_html($name).':'.esc_html($o).' &#x203A; </div><a target="_blank" href="'.esc_html($href).'">'.$target.':'.esc_html($t).'</a></li>';
                                    }else{
                                        $target = $_SERVER['HTTP_HOST'];
                                        $li[] = '<li><div>'.esc_html($name).':'.esc_html($o).' &#x203A; </div>'.$target.':'.esc_html($t).'</li>';
                                    }
                                }
                                if($li != false){
                                    $target_html = "<ul>\n".implode("\n",$li)."\n</ul>";
                                }
                            }else{
                                //通过docker-compose获取项目服务信息
                                $absdir = false;
                                $docker_compose = false;
                                if($dir != false && is_safe_str($dir)){
                                    $absdir = USR_FILES_DIR.'/'.$dir;
                                }
                                if($absdir != false && is_dir($absdir)){
                                    $ymlfile = $absdir.'/docker-compose.yml';
                                    if(file_exists($ymlfile)){
                                        $docker_compose = file_get_contents($ymlfile);
                                    }
                                }
                                $default_compose = file_get_contents(Z_ABSPATH.'/default.yml');

                                if($docker_compose == false && !file_exists($absdir.'/creating.lock')){
                                    file_put_contents($ymlfile, $default_compose);
                                    $docker_compose = $default_compose;
                                }

                                $dObj = new PHPDockerCompose($docker_compose, $item['compose_name']);
                                //判断docker-compose是否符合格式
                                if($dObj->final_arr != false && isset($dObj->final_arr['services'])){
                                    $li = array();
                                    foreach($dObj->final_arr['services'] as $k => $service){
                                        $name = $k; //获取服务名
                                        $ports = isset($service['ports'])?$service['ports']:'';
                                        $container_name = isset($service['container_name'])?$service['container_name']:'';
                                        if(is_array($ports)){
                                            foreach($ports as $k2 => $val){
                                                list($t, $o) = explode(':', $val);
                                                $target = $_SERVER['HTTP_HOST'];
                                                $li[] = '<li class="exited-project" title="You need to start this project in first."><div>'.esc_html($name).':'.esc_html($o).' &#x203A; </div>'.$target.'</li>';
                                            }
                                        }
                                    }
                                    if($li != false){
                                        $target_html = "<ul>\n".implode("\n",$li)."\n</ul>";
                                    }
                                }
                            }
                            echo '<tr uuid="'.esc_html($uuid).'">
                            <td class="lab-id">'.($key+1).'</td>
                            <td class="lab-name">
                                <div class="name">
                                    <span>'.esc_html($title).'</span>
                                    <button class="btn btn-xs btn-edit-name">Edit</button>
                                </div>
                                <form class="edit-project-title hidden" action="#" method="POST">
                                    <input name="project-title" class="project-title" type="text">
                                    <button class="btn btn-xs btn-primary btn-save">Save</button>
                                    <button class="btn btn-xs btn-default btn-cancel">Cancel</button>
                                </form>
                            </td>
                            <td class="lab-target">'.$target_html.'</td>
                            <td class="lab-status"><span class="label label-default">...</span></td>
                            <td class="lab-timeleft">00:00:00</td>
                            <td class="lab-operation">
                                <a class="btn-start btn" disabled="disabled" title="Start" href="#"><span class="glyphicon glyphicon-play" aria-hidden="true"></span></a>
                                <a class="btn-stop btn" disabled="disabled" title="Shutdown" href="#"><span class="glyphicon glyphicon-off" aria-hidden="true"></span></a>
                                <a class="btn-delete btn" disabled="disabled" title="Delete" href="#"><span class="glyphicon glyphicon-trash" aria-hidden="true"></span></a>
                            </td>
                        </tr>';
                        }
                        ?>
                        <?php }?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php
require_once('footer.inc.php');
?>
