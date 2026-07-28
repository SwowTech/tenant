<!DOCTYPE HTML
  PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3c.org/TR/1999/REC-html401-19991224/loose.dtd">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="../plugins/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link href="../plugins/bootstrap/font-awesome.min.css?t=20230419" rel="stylesheet">
  <link href="../plugins/bootstrap-table/bootstrap-table.min.css" rel="stylesheet">
  <link href="../plugins/bootstrap/animate.min.css" rel="stylesheet">
  <link href="../plugins/bootstrap/style.min.css" rel="stylesheet">
  <link href="css/adminstyle.css" rel="stylesheet">
  <script src="../js/jquery.min.js"></script>
  <script>var table = 'content', stype = '[r:s_type]', sid = '[r:sid]';</script>
  <script src="../plugins/layer/layer.min.js"></script>
  <!--[if lte IE 9]>
<script src="../js/respond.min.js"></script>
<script src="../js/html5.js"></script>
<![endif]-->
</head>
<?php
if(empty($r['s_name'])){   
    $sname=db_select('model','model_name',array('model_type'=>$r['s_type']));
}else{
    $sname=$r['s_name'];
}
?>

<body class="gray-bg">
  <div class="wrapper wrapper-content">
    <div class="ibox float-e-margins">
      <div class="row row-lg">
        <div class="col-sm-12">
          <div class="ibox-title">
            <h5>{$sname}管理</h5>
            <div class="ibox-tools"><code>位置：</code>><code>内容管理</code>><code>{$sname}</code></div>
          </div>
          <div class="ibox-content">
            <div id="toolbar">
              <div class="col-sm-6">
                <button id="add" onClick="opennew('?act=content&stype=[r:s_type]&sid=[r:sid]')" class="btn btn-primary"  type="button"><i class="fa fa-plus"></i> 添加</button>


                <div class="btn-group">
                  <button data-toggle="dropdown" class="btn btn-default dropdown-toggle" aria-expanded="false"><i class="fa fa-bars"></i> 批量操作
                    <span class="caret"></span>
                  </button>
                  <ul class="dropdown-menu">
                    <li><a href="javascript:void(0);"
                        onClick="opennew('?act=contentadds&stype=[r:s_type]&sid=[r:sid]')"><i class="fa fa-plus"></i>
                        批量图片添加</a>
                    </li>
                    <li><a href="javascript:void(0);" id="remove"><i class="fa fa-trash"></i> 多选删除</a>
                    </li>
                    <li><a href="javascript:void(0);" id="move" data-toggle="modal" data-target="#MoveModal"><i
                          class="fa fa-arrows-v"></i> 多选移动</a>
                    </li>
                    <li><a href="javascript:void(0);" id="copy" data-toggle="modal" data-target="#CopyModal"><i
                          class="fa fa-copy"></i> 多选复制</a>
                    </li>
                    <li><a href="javascript:void(0);" id="modify" data-toggle="modal" data-target="#ModifyModal"><i
                          class="fa fa-pencil"></i> 多选修改</a>
                    </li>
                     <li class="show-{$ai_conf['enable_ai']}"><a href="javascript:void(0);" onClick="openai('polish')"><i class="fa fa-paint-brush"></i> AI编辑内容</a></li>
                  </ul>
                </div>
               <button type="button" class="btn btn-success show-{$ai_conf['enable_ai']}" onClick="openai('generate')"><i class="fa fa-plus"></i> AI创造新内容</button>
                    
                
                <button onclick="parent.$('#custom a').click()" class="btn" type="button"><i class="fa fa-sliders"></i>
                  参数管理</button>
                <button onclick="parent.$('#recy a').click()" class="btn" type="button"><i class="fa fa-trash"></i>
                  回收站</button>
                <button onClick="location.reload()" class="btn" type="button"><i class="fa fa-refresh"></i> 刷新</button>
              </div>
            </div>
            <table {zzz:table} data-url="function.php?act=contentlist&id=[r:sid]&type=[r:s_type]">
              <thead>
                <tr>
                  <th class="tableid" data-checkbox="true"></th>
                  <th class="tableid" data-field="id" data-sortable="true">ID</th>
                  <th class="tableonoff" data-field="link">访问</th>
                  <th class="tabletitle" data-field="c_title" data-sortable="true">标题</th>
                  <th class="tableorder" data-field="c_sid" data-sortable="true">分类</th>
                  <th class="tabletime" data-field="c_addtime" data-sortable="true">时间</th>
                  <?php if($r['s_type']=='product' && check_dir(SITE_DIR.'shop'))echo '<th class="tableorder" data-field="zprice"  data-sortable="true">价格</th>'; ?>
                  <th class="tableorder" data-field="c_order" data-sortable="true">排序</th>
                  <th class="tableonoff" data-field="c_onoff" data-sortable="true">状态</th>
                  <th class="tabletype" data-field="istop" data-sortable="true">开关</th>
                  <th class="tabletype text-right" data-field="edit">操　作</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  </div>
  <div class="modal inmodal fade" id="MoveModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span
              class="sr-only">Close</span> </button>
          <h4 class="modal-title">移动内容到分类</h4>
        </div>
        <div class="modal-body">
          <p>已选择的ID：</p><input value="" name="cid" id="moveid" class="form-control m-b">
          <p>选择移动到分类：</p><select class="form-control m-b" id="moveSortID">
            <option value="">请选择分类</option>
          </select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
          <button type="button" onClick="moveid()" class="btn btn-primary">移动</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal inmodal fade" id="ModifyModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span
              class="sr-only">Close</span> </button>
          <h4 class="modal-title">批量调整参数</h4>
        </div>
        <div class="modal-body">
          <p>已选择的ID：</p><input value="" name="cid" id="modifyid" class="form-control m-b">
          <p>选择参数名称：</p><select class="form-control m-b" id="modifycustom">
            <option value="">请选择参数</option>
          </select>
          <p>修改内容为：</p><input value="" id="modifyvalue" class="form-control m-b">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
          <button type="button" onClick="modifyid()" class="btn btn-primary">修改</button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal inmodal fade" id="CopyModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span
              class="sr-only">Close</span> </button>
          <h4 class="modal-title">复制内容到分类</h4>
        </div>
        <div class="modal-body">
          <p>已选择的ID：</p><input value="" name="cid" id="copyid" class="form-control m-b">
          <p>选择复制到分类：</p><select class="form-control m-b" id="copySortID">
            <option value="">请选择分类</option>
          </select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
          <button type="button" onClick="copyid()" class="btn btn-primary">复制</button>
        </div>
      </div>
    </div>
  </div>
  <script src="../plugins/jedate/jedate.js"></script>
  <link type="text/css" rel="stylesheet" href="../plugins/jedate/jedate.css">
  <script src="../plugins/bootstrap/bootstrap.min.js"></script>
  <script src="../plugins/peity/jquery.peity.min.js"></script>
  <script src="../plugins/bootstrap-table/bootstrap-table.min.js"></script>
  <script src="../plugins/bootstrap-table/bootstrap-table-mobile.min.js"></script>
  <script src="../plugins/fancybox/jquery.fancybox.js"></script>
  <link href="../plugins/fancybox/jquery.fancybox.css" rel="stylesheet">
  <script src="js/adminjs.js?t=20231027"></script>
  <script>
    function openai(type) {
      if (type == 'generate') {
        opennew('?act=ai&type=' + type +'&table=content&stype='+stype+'&sid='+sid);
      } else {
        if (!ids) {
          layer.msg('请选择内容');
          return;
        } else {
          opennew('?act=ai&type=' + type + '&table=content&ids=' + ids);
        }
      }
    }
  </script>
</body>

</html>