<!doctype html public "-//w3c//dtd html 4.01 transitional//en" "http://www.w3c.org/tr/1999/rec-html401-19991224/loose.dtd">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="../plugins/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link href="../plugins/bootstrap/font-awesome.min.css?t=20230419" rel="stylesheet">
  <link href="../plugins/bootstrap/animate.min.css" rel="stylesheet">
  <link href="../plugins/bootstrap/style.min.css" rel="stylesheet">
  <link href="css/adminstyle.css" rel="stylesheet">
  <script src="../js/jquery.min.js"></script>
  <script src="../plugins/colorpicker/bootstrap-colorpicker.min.js"></script>
  <!--[if lte ie 9]>
<script src="../js/respond.min.js"></script>
<script src="../js/html5.js"></script>
<![endif]-->
<?php
if(get_session('adminmark')<10000){
  die ('<h1 class="text-center">※ 您没有操作权，请联系管理员 ※</h1>');
}
?>
</head>

<body class="gray-bg">
  <div class="wrapper wrapper-content">
    <div class="ibox float-e-margins">
      <div class="row">
        <form method="post" action="save.php?act=adminmenu" class="form-horizontal" id="contentform">
          <div class="col-sm-12 ">
            <div class="ibox float-e-margins">
              <div class="ibox-title">
                <h5>功能权限</h5>
                <div class="ibox-tools"><i class="fa fa-info-circle"></i>直接单击按钮，即可开启或关闭功能，只有创始人权限可使用本功能</div>
              </div>
              <div class="ibox-content">
                <div class="form-group">
                  <div class="col-sm-12">
                  <div class="m-t-10">
                    {$check_menu 0} 
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

       
          </div>
        </form>
      </div>
    </div>
    <!-- end panel other -->
  </div>
  <script src="../plugins/bootstrap/bootstrap.min.js"></script>
  <link href="../plugins/colorpicker/bootstrap-colorpicker.min.css" rel="stylesheet">
  <script src="../plugins/switchery/switchery.js"></script>
  <link href="../plugins/switchery/switchery.css" rel="stylesheet">
  <script src="../plugins/layer/layer.min.js"></script>
  <script>
    $(".menu").click(function() {
      var id = $(this).data('id');
      var val = $(this).data('val');
      $.post("save.php?act=setcol", { table:'menu',col:'m_onoff', id: id,colval:val})
      location.reload();     
    })
  </script>
  <script src="js/adminjs.js?t=20231027"></script>
  <script src="js/content.min.js?t=20230419"></script>
</body>

</html>