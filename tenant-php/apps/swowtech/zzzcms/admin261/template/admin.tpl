<!doctype html public "-//w3c//dtd html 4.01 transitional//en" "http://www.w3c.org/tr/1999/rec-html401-19991224/loose.dtd">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{ways}管理员</title>
  <link href="../plugins/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link href="../plugins/bootstrap/font-awesome.min.css?t=20230419" rel="stylesheet">
  <link href="../plugins/bootstrap/animate.min.css" rel="stylesheet">
  <link href="../plugins/bootstrap/style.min.css" rel="stylesheet">
  <link href="css/adminstyle.css" rel="stylesheet">
  <script src="../js/jquery.min.js"></script>
  <script src="../plugins/colorpicker/bootstrap-colorpicker.min.js"></script>
  <script src="../plugins/imageselect/imageselect.js"></script>
  <link rel="stylesheet" type="text/css" href="../plugins/imageselect/imageselect.css" />
  <style>
    .pass_set{ position: relative; line-height: 24px; padding: 0;}
    .pass_set li{list-style: none; float: left; width: 95px; background: #efefef; border-right: 1px solid #fff; text-align: center;}
    </style>
  <!--[if lte ie 9]>
<script src="../js/respond.min.js"></script>
<script src="../js/html5.js"></script>
<![endif]-->
</head>

<body class="gray-bg">
  <div class="wrapper wrapper-content">
    <div class="ibox float-e-margins">
      <div class="row">
        <form method="post" action="save.php?act=user" class="form-horizontal" id="contentform">
          <input type="hidden" name="uid" value="[r:uid]">
          <input type="hidden" name="type" value="admin">
          <div class="col-sm-12">
            <div class="ibox float-e-margins">
              <div class="ibox-content">
                <div class="form-group">
                  <label class="col-sm-2 control-label  text-danger">管理员级别</label>
                  <div class="col-sm-4">
                    <select name='u_gid' id='u_gid' class="form-control">{$select_group [r u_gid],1} </select>
                  </div>
                  <label class="col-sm-2 control-label">选择头像</label>
                  <div class="col-sm-4">
                    <select name="face" id='face' class="form-control"> {$select_face [r face]}</select>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-2 control-label  text-danger">登录账号</label>
                  <div class="col-sm-4">
                    <input type="text" name="username" id="username" value="[r:username]" class="form-control" [r:readonly]>
                  </div>
                  <label class="col-sm-2 control-label  text-danger">管理员名称</label>
                  <div class="col-sm-4">
                    <input type="text" name="truename" id="truename" value="[r:truename]" class="form-control">
                  </div>

                  
                </div>
                
                <div class="form-group">
                  <label class="col-sm-2 control-label">登陆密码</label>
                  <div class="col-sm-4">
                    <input type="text" value="" name="password" id="password" class="form-control" placeholder="不修改密码请留空"  onkeyup="pwStrength(this.value)">
                    <ul class="pass_set">
                      <li id="strength_L">弱</li>
                      <li id="strength_M">中</li>
                      <li id="strength_H">强</li>
                    </ul>
                  </div>
                  <label class="col-sm-2 control-label">确认密码</label>
                  <div class="col-sm-4">
                    <input type="text" value="" name="repassword" id="repassword" class="form-control" placeholder="不修改密码请留空">
                  </div>
                </div>

                <div class="form-group">
                <label class="col-sm-2 control-label">用户手机</label>
                  <div class="col-sm-4">
                    <input type="text" value="[r:mobile]" name="mobile" id="mobile" class="form-control">
                  </div>
                  <label class="col-sm-2 control-label text-danger">性别</label>
                  <div class="col-sm-4">
                    <select value="[r:sex]" name="sex" id="sex" class="form-control">
                      <option value="">请选择</option>
                      <option value="男">男</option>
                      <option value="女">女</option>
                      <option value="保密">保密</option>
                    </select>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-2 control-label">密码问题</label>
                  <div class="col-sm-4">
                    <input type="text" name="question" id="question" value="[r:question]" class="form-control">
                  </div>
                  <label class="col-sm-2 control-label">密码答案</label>
                  <div class="col-sm-4">
                    <input type="text" name="answer" id="answer" value="[r:answer]" class="form-control">
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-sm-2 control-label">描述</label>
                  <div class="col-sm-10">
                    <textarea id="u_desc" name="u_desc" class="form-control">[r:u_desc]</textarea>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-2 control-label">注册时间</label>
                  <div class="col-sm-4">
                    <p class="form-control-static">[r:regtime] </p>
                  </div>
                  <label class="col-sm-2 control-label">登录时间</label>
                  <div class="col-sm-4">
                    <p class="form-control-static">[r:lastlogintime]</p>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-2 control-label">最后登录ip</label>
                  <div class="col-sm-4">
                    <p class="form-control-static">[r:lastloginip] </p>
                  </div>
                  <label class="col-sm-2 control-label">登录次数</label>
                  <div class="col-sm-4">
                    <p class="form-control-static">[r:logincount] </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-sm-12 m-t">
            <div class=" col-sm-10 col-md-offset-1">
              <button class="btn btn-primary" onclick="submitform('user','[r:uid]','contentform')" type="button" id="submit" title="快捷键：ctrl+enter"><i class="fa fa-floppy-o"></i>　保存内容</button>
              <button class="btn btn-white" onClick="closelayer()" type="reset"><i class="fa fa-close"></i> 返回</button>
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
  <script src="js/content.min.js?t=20230419"></script>
  <script src="js/adminjs.js?t=20231027"></script>
  <script>
    $(function() {
      $('select[name=face]').ImageSelect({
        dropdownHeight: 200,
        dropdownWidth: 100,
        height: 32
      });
    });

    
    //显示颜色  
function pwStrength(pwd) {
	O_color = "#eeeeee";
	L_color = "#F1DD07";
	M_color = "#31AADE";
	H_color = "#E73A40";
	if (pwd == null || pwd == '') {
		Lcolor = Mcolor = Hcolor = O_color;
	} else {
		S_level = checkStrong(pwd);
		switch (S_level) {
		case 0:
			Lcolor = Mcolor = Hcolor = O_color;
		case 1:
			Lcolor = L_color;
			Mcolor = Hcolor = O_color;
			break;
		case 2:
			Lcolor = L_color;
			Mcolor = M_color;
			Hcolor = O_color;
			break;
		default:
			Lcolor = L_color;
			Mcolor = M_color;
			Hcolor = H_color;
		}
	}
	$("#strength_L").css('background-color', Lcolor);
	$("#strength_M").css('background-color', Mcolor);
	$("#strength_H").css('background-color', Hcolor);
	return;
}
//判断输入密码的类型  
function CharMode(iN) {
	if (iN >= 48 && iN <= 57) //数字  
	return 1;
	if (iN >= 65 && iN <= 90) //大写  
	return 2;
	if (iN >= 97 && iN <= 122) //小写  
	return 4;
	else return 8;
}
//bitTotal函数  
//计算密码模式  
function bitTotal(num) {
	modes = 0;
	for (i = 0; i < 4; i++) {
		if (num & 1) modes++;
		num >>>= 1;
	}
	return modes;
}
//返回强度级别  
function checkStrong(sPW) {
	if (sPW.length <= 5) return 0; //密码太短  
	Modes = 0;
	for (i = 0; i < sPW.length; i++) {
		//密码模式  
		Modes |= CharMode(sPW.charCodeAt(i));
	}
	return bitTotal(Modes);
}

  </script>
</body>

</html>