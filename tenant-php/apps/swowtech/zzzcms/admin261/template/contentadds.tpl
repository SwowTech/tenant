<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3c.org/TR/1999/REC-html401-19991224/loose.dtd">

<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>批量添加内容</title>
<link href="../plugins/bootstrap/bootstrap.min.css" rel="stylesheet">
<link href="../plugins/bootstrap/font-awesome.min.css" rel="stylesheet">
<link href="../plugins/bootstrap/animate.min.css" rel="stylesheet">
<link href="../plugins/bootstrap/style.min.css" rel="stylesheet">
<link href="css/adminstyle.css" rel="stylesheet">
<script src="../js/jquery.min.js"></script>
<!--[if lte IE 9]>
<script src="../js/respond.min.js"></script>
<script src="../js/html5.js"></script>
<![endif]-->
<script>var upfolder="about";</script>
<script src="../plugins/bootstrap/bootstrap.min.js"></script>
<script src="../plugins/layer/layer.min.js"></script> 
<script src="js/content.min.js"></script> 
<script src="js/adminjs.js"></script>
<script src="../plugins/dragarrange/drag-arrange.js"></script>
<script type="text/javascript">
      $(function() {
          $('.file_item').arrangeable();
      });
    </script>
    <style>
   .table_li ul{
      padding: 10px;
      width: 100%;
      height: 100%;      
      border-bottom: 1px #ccc solid;
  }
    .table_li li{list-style: none;width: 100%; height:62px; line-height:32px;display: table;}
    .table_li li:nth-child(2n){ background-color: #f3f3f3;}
    .table_li li div{width: 100px;padding: 0 5px; display: inline-block;vertical-align: middle;}
    .table_li li div:nth-child(1){ width: 170px;}
    .table_li li div:nth-child(2){ width: 170px;}
    .table_li li div:nth-child(4){ width: 250px;}
    textarea{mib-height:90px}
    .spic{height:30px}
    </style>
</head>
<body class="gray-bg">
<div class="wrapper wrapper-content">
  <div class="ibox float-e-margins">
    <div class="row m-t">
      <form method="post" action="save.php?act=contentadds" class="form-horizontal" id="contentform">
       <input type="hidden" name="stype" value="{$stype}">
        <div class="col-sm-12">
          <div class="ibox float-e-margins"> 
            <div class="ibox-content">
            <label>批量添加内容使用说明：</label> 
              <div class="alert alert-warning">建议在本地电脑批量修改好图片名称后在执行，可事半功倍。</br>
                第一步：请选择分类</br>第二部：选择品牌（未启用则无法选择，可不选）</br>第三步：点击上传图片，批量选择</br>第四步：修改标题和内容（可为空,标题留空的作用是将附件附加到上一条内容的图册里,第一条内容标题不可以为空。）</br>最后：调整顺序后，点击保存后，完成批量添加内容</div>
              <div class="form-group">
                <label class="col-sm-1 control-label">分类</label>
                <div class="col-sm-4">
                 <select name="sid" required class="form-control">{$select_sort_content $stype,$sid} </select>
                </div>
               {$select_brand $stype,''} </div>
               </div>
          <div class="ibox-content m-t-20">
              <div id="table_head" style="display:none">
                <div class="table_li">               
                  <ul>
                    <li>
                    <div>内容标题</div>
                    <div>图片地址</div>
                    <div>图片</div>
                    <div>内容主体</div>
                    <div>操作</div>
                    </li>
                  </ul>
                </div>
              </div>
               <div id="table_body" style="display:none">
                <div class="table_li">               
                  <ul>
                  </ul>
                  </div>
               </div>
              <button type="button" class="btn btn-upload btn-info btn-block" onclick="adds_upload()"> <i class="fa fa-upload"></i> 上传图片 </button>
            </div>
          </div>
          <div class="ibox-content col-sm-12" id="adds_btn" style="display:none">
            <div class=" col-sm-10 col-md-offset-1">
              <div class="col-sm-6">
                <button class="btn btn-primary" type="submit"><i class="fa fa-floppy-o"></i>　保存内容</button>
                <button class="btn btn-white" onclick="closelayer()" type="reset"><i class="fa fa-close"></i> 返回</button>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
  <!-- End Panel Other --> 
</div>
<script>
function adds_upload(){
 $r='';del_cookie('upload');
  parent.layer.open({
			type: 2,
			area: ['950px', '690px'],
			content: "?act=imagelist&numtype=0&upfolder={$stype}",
			end : function(){
				r=get_cookie('upload');
				if(r){
					r=JSON.parse(r);				
					$.each(r,function(i,data){
						$r+='<li class="file_li"><div><input type="text" value='+data.title+' class="form-control" name="titles[]"></div>'+
            		'<div><input type="text" value='+data.url+' class="form-control" name="pics[]"></div>'+
                '<div><img src='+data.url+' class="spic" draggable="false"></div>'+
                '<div><textarea name="contents[]" class="form-control"></textarea></div>'+
                '<div><a class="btn cancel"><i class="fa fa-close"></i> 删除</a> <a class="btn move"><i class="fa fa-arrows"></i> 拖动</a> </div>'+
                '</li>';
					})					
				}		       
        $("#table_head").show();
        $("#table_body").show();        
				$("#table_body ul").prepend($r);
				$('.file_li').arrangeable({dragSelector: '.move'});
        $("#adds_btn").show();
			}
		})
}

$("#table_body").on('click','.cancel',function(){
    $(this).parents('li').remove();
})
</script>
</body>
</html>