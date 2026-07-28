<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3c.org/TR/1999/REC-html401-19991224/loose.dtd">
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="css/adminstyle.css" rel="stylesheet">
<link href="../plugins/bootstrap/bootstrap.min.css" rel="stylesheet">
<link href="../plugins/bootstrap/font-awesome.min.css?t=20230419" rel="stylesheet">
<link href="../plugins/bootstrap-table/bootstrap-table.min.css" rel="stylesheet">
<link href="../plugins/bootstrap/animate.min.css" rel="stylesheet">
<link href="../plugins/bootstrap/style.min.css" rel="stylesheet">
<script src="../js/jquery.min.js"></script>
<!--[if lte IE 9]>
<script src="../js/respond.min.js"></script>
<script src="../js/html5.js"></script>
<![endif]-->
</head>

<body class="gray-bg sidebar-content">
<div class="sidebard-panel"> {if [c tianqimark]==1}
  <div class="tianqi">
    <div class='onoff'> <a onclick="openonoff('save.php?act=tianqi&type=0')" title='关闭天气展示，可加快后台打开速度'> <i class='fa fa-times'></i> </a> </div>
    <iframe src='//i.tianqi.com/index.php?c=code&id=7' frameborder=0 scrolling=no width='220' height='110'  style='margin-left:-15px;'></iframe>
  </div>
  {else}
  <div class="tianqi">
    <div class='onoff'> <a onclick="openonoff('save.php?act=tianqi&type=1')" title='开启天气展示'> <i class='fa fa-soundcloud'></i> </a> </div>
    <div class="clock">
      <div id="Date"></div>
      <ul>
        <li id="hours"></li>
        <li id="point">:</li>
        <li id="min"></li>
        <li id="point">:</li>
        <li id="sec"> </li>
      </ul>
    </div>
  </div>
  <script type="text/javascript">
	$(function() {
		var monthNames = [ "1月", "2月", "3月", "4月", "5月", "6月", "7月", "8月", "9月", "10月", "11月", "12月" ]; 
		var dayNames= ["星期日","星期一","星期二","星期三","星期四","星期五","星期六"]
		var newDate = new Date();
		newDate.setDate(newDate.getDate());
		$('#Date').html(newDate.getFullYear() + "年" + monthNames[newDate.getMonth()] + '' + newDate.getDate() + '日 ' + dayNames[newDate.getDay()]);
		setInterval( function() {
			var seconds = new Date().getSeconds();
			$("#sec").html(( seconds < 10 ? "0" : "" ) + seconds);
		},1000);	
		setInterval( function() {
			var minutes = new Date().getMinutes();
			$("#min").html(( minutes < 10 ? "0" : "" ) + minutes);
		},1000);	
		setInterval( function() {
			var hours = new Date().getHours();
			$("#hours").html(( hours < 10 ? "0" : "" ) + hours);
		}, 1000);		
	}); 
	</script> 
  {/if}
  <div class="ibox-title"> <span class="label label-primary pull-right">总</span>
    <h5>内容统计</h5>
  </div>
  <div class="ibox-content">
    <ul class="list-group">
      {$right_count 'all'}
    </ul>
  </div>
  <?php
  if( conf('adminpath')=='admin/'){
     echo '<div class="panel panel-warning">
          <div class="panel-heading"><i class="fa fa-warning"></i> 安全提醒 <button onclick="openonoff(\'save.php?act=cookie&type=path\')" class="close" >×</button></div>
            <div class="panel-body">
              <p>不建议后台管理目录使用admin！</p>
              <a onclick="layer.prompt({title: \'请输入新目录名称，如newadmin\'},function(path, index){$.post(\'save.php?act=upadmin\',{\'path\':path},function(data){if(data==true){parent.location=\'../\'+path}else{parent.layer.alert(data)}})});" class="label label-primary ">立即修改</a>
              </p>
           </div>
         </div>';
    }
    if(db_count('user',"username = 'admin' and u_onoff=1")>0){
      echo '<div class="panel panel-danger">
             <div class="panel-heading"><i class="fa fa-warning"></i> 安全提醒 <button onclick="openonoff(\'save.php?act=cookie&type=pass\')"  class="close" >×</button></div>
             <div class="panel-body">
               <p>禁止使用admin作为管理员账号，建议新建管理员并删除admin账号</p>
               <p> <a onclick="goparent(\'admi\')"  class="label label-primary">立即处理</a></p>
             </div>
           </div>';
       }
 if(get_cookie('adminpass')=='1'){
   echo '<div class="panel panel-danger">
          <div class="panel-heading"><i class="fa fa-warning"></i> 安全提醒 <button onclick="openonoff(\'save.php?act=cookie&type=pass\')"  class="close" >×</button></div>
          <div class="panel-body">
            <p>管理密码过于简单，请及时修改！</p>
            <p> <a onclick="opennew(\'?act=admin&uid='.get_session("adminid").'\')"  class="label label-primary">立即修改</a></p>
          </div>
        </div>';
    }
    if(check_dir('../install/')){
      echo '<div class="panel panel-danger">
      <div class="panel-heading"><i class="fa fa-warning"></i> 安全提醒 </div>
      <div class="panel-body">
        <p>您的安装目录(install目录)没有删除</p>
        <a onclick="layer.confirm(\'确认删除安装目录吗？\',{icon: 3},function(index){$.post(\'save.php?act=delfile\',{type:\'install\'},function(){location.reload()})});" class="label label-primary ">立即删除</a>
      </div>
    </div>';
    }
  ?>
</div>
</div>
<div class="wrapper">
  <div class="row">
    <div class="alert alert-success">
      <h3>欢迎您{$get_session 'adminname'}:，今天是{php echo date("Y/m/d")}</h3>    
      [c:welcome]</div>
    <div class="clear"></div>
    <div class="row"> {$center_count ''} </div>
    <div class="row">
      <div class="col-sm-6">
        <div class="ibox float-e-margins">
          <div class="ibox-content no-padding">
            <table data-toggle="table">
              <thead>
                <tr>
                  <th>项目</th>
                  <th>状态</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>操作系统</td>
                  <td>{php echo PHP_OS;} {php echo _SERVER('SERVER_SOFTWARE');}</td>
                </tr>
                <tr>
                  <td>PHP版本</td>
                  <td>{php echo PHP_VERSION;}</td>
                </tr>
                <tr>
                  <td>服务器IP</td>
                  <td>{php echo gethostbyname($_SERVER['SERVER_NAME'])}</td>
                </tr>
                <tr>
                  <td>客户端IP</td>
                  <td>{php echo $ip;}</td>
                </tr>
                <tr>
                  <td>超时时间</td>
                  <td>{php echo ini_get("max_execution_time")}秒 <a href="https://help.zzzcms.com/web/#/659354606/227970768" target="_blank" title="超时时间是指脚本在服务器上运行的最大时间，超过这个时间脚本将被强制停止。"><i class="fa fa-question-circle"></i></a></td>
                </tr>
                <tr>
                  <td>服务器引擎</td>
                  <td>{php echo _SERVER('SERVER_SOFTWARE');}</td>
                </tr>
                <tr>
                  <td>缓存组件</td>
                  <td>{php echo isset($conf['cachetype']) ? $conf['cachetype'] :  '本地缓存';}</td>
                </tr>
                <tr>
                  <td>运行状态</td>
                  <td>
                    <?php
                     if (conf('webmode')==1){
                      echo '<span class="label label-success">运行中</span> ';
                     }else{
                      echo '<span class="label label-success">关闭中</span> ';
                     }
                     if(conf('runmode')==1){
                       echo '<span class="label label-success">静态模式</span> ';
                     }else  if(conf('runmode')==2){
                        echo '<span class="label label-success">伪静态模式</span> ';   
                     }else{
                      echo '<span class="label label-success">动态模式</span> ';
                    }
                     if(conf('iscache')==1){
                       echo '<span class="label label-success">开启缓存</span>';
                     }else{
                      echo '<span class="label label-success">未开启缓存</span>';
                     }
                    ?></td>
                </tr>
                <tr>
                  <td>上传限制（图片、附件、视频）</td>
                  <td><?php 
                      $upload_max_filesize=intval(get_cfg_var('upload_max_filesize'));
                      $post_max_size=intval(get_cfg_var('post_max_size'));
                      $memory_limit=intval(get_cfg_var('memory_limit'));
                      $imagemaxsize= min(intval(conf('imagemaxsize')),$upload_max_filesize,$post_max_size,$memory_limit).'M';
                      $imagemaxtitle="<div class=wrapper><h2>图片最大上传文件大小: $imagemaxsize <h2/><hr><p>服务器设置上传文件的最大值：  <span class=".check_on($upload_max_filesize,$imagemaxsize,'label').">".$upload_max_filesize."M </span></p><p>服务器设置最大POST数据限制： $post_max_size M</p><p>服务器设置最大内存限制： $memory_limit M</p><p>系统设置图片上传最大值： <span class=".check_on(intval(conf('imagemaxsize')),$imagemaxsize,'label').">".intval(conf('imagemaxsize'))."M </span></p><p class=label>取以上数值中的最小值</p></div>";
                      $filemaxsize=  min(intval(conf('filemaxsize')),$upload_max_filesize,$post_max_size,$memory_limit).'M';
                      $filemaxtitle="<div class=wrapper><h2>附件最大上传文件大小: $filemaxsize <h2/><hr><p>服务器设置上传文件的最大值： <span class=".check_on($upload_max_filesize,$filemaxsize,'label').">".$upload_max_filesize."M </span> </p><p>服务器设置最大POST数据限制： $post_max_size M</p><p>服务器设置最大内存限制： $memory_limit M</p><p>系统设置附件上传最大值： <span class=".check_on(intval(conf('filemaxsize')),$filemaxsize,'label').">".intval(conf('filemaxsize'))."M </span> </p><p class=label>取以上数值中的最小值</p></div>";
                      $videomaxsize=  min(intval(conf('videomaxsize')),$upload_max_filesize,$post_max_size,$memory_limit).'M';
                      $videomaxtitle="<div class=wrapper><h2>视频最大上传文件大小: $videomaxsize <h2/><hr><p>服务器设置上传文件的最大值：  <span class=".check_on($upload_max_filesize,$videomaxsize,'label').">".$upload_max_filesize."M </span></p><p>服务器设置最大POST数据限制： $post_max_size M</p><p>服务器设置最大内存限制： $memory_limit M</p><p>系统设置视频上传最大值： <span class=".check_on(intval(conf('videomaxsize')),$videomaxsize,'label').">".intval(conf('videomaxsize'))."M </span>  </p><p class=label>取以上数值中的最小值</p></div>";
                   ?>
                  <button class="btn btn-success btn-xs" title="图片最大上传文件大小" onclick="opendiv('<?php echo $imagemaxtitle?>')"><i class="fa fa-image"> </i> <?php echo $imagemaxsize?></button>
                  <button class="btn btn-success btn-xs" title="附件最大上传文件大小" onclick="opendiv('<?php echo $filemaxtitle?>')"><i class="fa fa-file"> </i> <?php echo $filemaxsize?></button>
                  <button class="btn btn-success btn-xs" title="视频最大上传文件大小" onclick="opendiv('<?php echo $videomaxtitle?>')"><i class="fa fa-video"> </i>  <?php echo $videomaxsize?></button>
                   </td>
                 </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="col-sm-6">
        <div class="ibox float-e-margins">
          <div class="ibox-content no-padding">
            <table data-toggle="table">
              <thead>
                <tr>
                  <th>项目</th>
                  <th>状态</th>
                </tr>
              </thead>
              <tr>
                <td>系统名称</td>
                <td>ZZZCMS建站系统</td>
              </tr>
              <tr>
                <td>系统版本</td>
                <td>{php echo ZZZ_VERSION} Build{php echo ZZZ_VERDATE}</td>
              </tr>
              <tr>
                <td>版本时间</td>
                <td>{php echo ZZZ_VERTIME}</td>
              </tr>
              <tr>
                <td>系统更新</td>
                <td>
                  <?php
                  if (!class_exists('ZipArchive')) {
                     echo '<a class="label label-danger" href="https://help.zzzcms.com/web/#/659354606/227970769" target="_blank">zip组件未开启</a> 不支持自动更新';
                  }else{
                    echo ' <a href="javascript:void(0)" onclick=opennew(\'?act=updata\')  class="label label-danger"><i class="fa fa-cloud-download"></i> 在线更新</a>';
                  }
                  ?>
                <a href="javascript:void(0)" onclick=opennew('https://www.zzzcms.com/zzzphp/')  class="text-navy"> <i class="fa fa-file-code-o"></i> 更新日志</a></td>
              </tr>
              <tr>
                <td>系统官网</td>
                <td><a href='https://www.zzzcms.com' target="_blank">zzzcms.com</a></td>
              </tr>
              <tr>
                <td>版本描述</td>
                <td><a href='https://{php echo ZZZ_VERURL}' target="_blank">{php echo ZZZ_VERDESC}</a></td>
              </tr>
              <tr>
                <td>帮助文档</td>
                <td><a href="http://help.zzzcms.com/" target="_blank">help.zzzcms.com</a></td>
              </tr>
              <tr>
                <td>系统作者</td>
                <td>升起</td>
              </tr>
              <tr>
                <td>微信交流群</td>
                <td>
                <a href="javascript:void(0)" onClick="openimg('http://www.zzzcms.com/images/weixin.jpg')"> 微信交流群</a>     
              
                </td>
              </tr>
              
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
<script src="../plugins/bootstrap/bootstrap.min.js"></script>
<script src="../plugins/bootstrap-table/bootstrap-table.min.js"></script>
<script src="../plugins/bootstrap-table/bootstrap-table-mobile.min.js"></script>
<script src="../plugins/switchery/switchery.js"></script>
<link href="../plugins/switchery/switcherybig.css" rel="stylesheet">
<script src="../plugins/layer/layer.min.js"></script>
<script src="js/adminjs.js?t=20231027"></script>
</html>