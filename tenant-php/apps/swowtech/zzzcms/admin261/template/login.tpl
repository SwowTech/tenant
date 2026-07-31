<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="renderer" content="webkit">
  <meta http-equiv="Cache-Control" content="no-siteapp" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>{$siteTitle}-网站内容管理系统-Powered by{$version}</title>
  <script src="../js/jquery.min.js"></script>
  <script src="../plugins/layer/layer.min.js"></script>
  <script src="../js/zzzcms.js"></script>
  <script src="js/md5.js"></script>
  <script src="js/adminjs.js"></script>

  <link rel="stylesheet" type="text/css" href="../plugins/changebg/main.css" />
  <link rel="stylesheet" type="text/css" href="../plugins/changebg/bgstretcher.css" />
  <script src="../plugins/changebg/bgstretcher.js"></script>
  <link href="../plugins/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link href="../plugins/bootstrap/font-awesome.min.css?t=20230419" rel="stylesheet">
  <link href="../plugins/bootstrap/animate.min.css" rel="stylesheet">
  <link href="../plugins/bootstrap/style.min.css" rel="stylesheet">
  <link href="css/adminstyle.css" rel="stylesheet">
  <!--[if lte IE 9]>
<script src="../js/respond.min.js"></script>
<script src="../js/html5.js"></script>
<![endif]-->
</head>

<body>
  <div class="middle-box loginscreen  animated fadeInDown">
  {if !isnul(get_cookie("adminname"))}
    <div id="cookie_login">
      <div class="form-horizontal m-t">
        <div class="noborder ">
          <h3>请输入密码直接登陆</h3>
          <div class="m-b-md"> <img alt="image" class="img-circle circle-border face" src=""> </div>
          <h3>{$get_cookie 'adminname'}，<a href="./login.php?act=loginout">切换账号</a></h3>
          <p>欢迎您回来，请输入管理密码</p>
          <div class="form-horizontal m-t">
            <div class="form-group">
              <div class="col-sm-12">
                <input type="hidden" name="logintype" value="cookie">
                <input type="hidden" name="adminname" value="{$get_cookie 'adminname'}">
                <input type="text" name="adminpass" id="adminpass" autocomplete="off" class="form-control" placeholder="密码" tabindex="2">
                <a href="javascript:void(0)" onClick="hideShowPsw()" class="passico eye" style="right: 15px"><i class="fa fa-eye"></i></a>
              </div>
            </div>
            <button type="button" class="btn btn-primary block full-width m-t-10" onclick="checkForm()">登录</button>
            <div class="m-t-10">
              <a onclick="smslogin()" class="smslogin"  style="display:none">短信登陆</a>
            </div>
          </div>
        </div>
      </div>
    </div>
    {else}
    <div id="pass_login">
      <h1 class="logo-name"><img src="images/logo300.png"></h1>

      <div class="form-horizontal m-t">
        <div class="form-group noborder ">
          <div class="col-sm-12">
            <div class="input-group">
              <span class="input-group-addon"><i class="fa fa-user"></i></span>
              <input type="text" name="adminname" class="form-control" placeholder="用户名" autocomplete="off" data-required="*" tabindex="1">
            </div>
          </div>
        </div>
        <div class="form-group adminpass noborder ">
          <div class="col-sm-12">
            <div class="input-group">
              <span class="input-group-addon toques" title="忘记登陆密码？"><i class="fa fa-keyboard"></i></span>
              <input type="text" name="adminpass" id="adminpass" autocomplete="off" class="form-control" placeholder="密码" tabindex="2">
              <span class="input-group-addon eye" onClick="hideShowPsw()"><i class="fa fa-eye"></i></a></span>
            </div>
          </div>
        </div>
        <div class="form-group question noborder " style="display:none">
          <div class="col-sm-12">
            <div class="input-group">
              <span class="input-group-addon topass"><i class="fa fa-key"></i></span>
              <input type="text" name="question" class="form-control" placeholder="密码问题">
            </div>
          </div>
        </div>
        <div class="form-group answer noborder " style="display:none">
          <div class="col-sm-12">
            <div class="input-group">
              <span class="input-group-addon"><i class="fa fa-unlock"></i></span>
              <input type="text" name="answer" class="form-control" autocomplete="off" placeholder="密码答案">
            </div>
          </div>
        </div>
        {if (conf('iscode')==1)}
        <div class="form-group">
          <div class="col-sm-6">
            <div class="input-group">
              <span class="input-group-addon"><i class="fa fa-code"></i></span>
              <input type="text" name="code" id="imgcode" class="form-control" placeholder="验证码" data-required="*" tabindex="3">
            </div>
          </div>
          <div class="col-sm-6 imgcode"> <img id="SeedCode" src="../inc/imgcode.php" align="absmiddle" style="cursor:pointer;" border="0" /> </div>
        </div>
        {/if}
        <input type="hidden" name="logintype" id="logintype" value="pass">
        <button type="button" class="btn btn-primary block full-width  m-t-10" tabindex="4" onclick="checkForm()">登录</button>
        <div class="m-t-10">
          <a href="javascript:void(0)" class="toques pass_login" >忘记密码?</a> 
          <a href="javascript:void(0)" class="topass" style="display: none;">返回</a> 
          <a onclick="smslogin()"  class="smslogin"  style="display: none;">短信登陆</a>
        </div>
      </div>

    </div>
    
    {/if}
    {if (conf('smsmark')==1 && conf('managesendsms')==1)}
    <div id="sms_login" style="display: none;">
      <h1 class="logo-name"><img src="images/logo300.png"></h1>
      <div class="form-horizontal m-t">
        <div class="form-group ">
          <div class="col-sm-12">
            <div class="input-group">
              <span class="input-group-addon"><i class="fa fa-user-o"></i></span>
              <input type="text" name="mobile" id="phonenum" class="form-control" placeholder="手机号" autocomplete="off" data-required="*" tabindex="1">
            </div>
          </div>
        </div>
        <div class="form-group">
          <div class="col-sm-6">
            <div class="input-group">
              <span class="input-group-addon"><i class="fa fa-code"></i></span>
              <input type="text" name="code" id="imgcode" class="form-control" placeholder="验证码" data-required="*" tabindex="3">
            </div>
          </div>
          <div class="col-sm-6 imgcode"> <img id="SeedCode" src="../inc/imgcode.php" align="absmiddle" style="cursor:pointer;" border="0" /> </div>
        </div>
        <div class="form-group adminpass noborder ">
          <div class="col-sm-12">
            <div class="input-group">
              <span class="input-group-addon"><i class="fa fa-key"></i></span>
              <input type="text" value="" name="smscode" id="smscode" class="form-control code" datatype="n4-4" ajaxurl="../plugins/sms/sms.php?act=checkcode" placeholder="请输入短信验证码" errormsg="验证码错误">
              <span class="input-group-addon"><input id="sedcond" pagetype="manage" type="button" value="获取验证码"></span>
            </div>
          </div>
        </div>
        <button type="button" class="btn btn-primary block full-width  m-t-10" tabindex="4" onclick="sms_submit()">登录</button>
        <div class="m-t-10">
          <a href="./login.php?act=loginout" class="pass_login">密码登陆</a>
        </div>
      </div>
      <script src="../plugins/sms/sms.js" type="text/javascript"></script>
    </div>
    {/if}
  </div>
  <div class="signup-footer"> Powered by <a href="http://www.zzzcms.com" target="_blank">zzzcms {$zzz_version}</a>! Copyright &copy;2015-{$date 'Y'} </div>
  <script language="javascript">
    var demoInput = document.getElementById("adminpass");
    manage_login_type="{$conf 'manage_login_type'}";
    managesendsms="{$conf 'managesendsms'}";
    smsmark="{$conf 'smsmark'}";
    if(manage_login_type==3){     
      $("#pass_login").remove();
      $("#cookie_login").remove();
      $("#sms_login").show();
      $(".pass_login").hide();
    }
    if(managesendsms==1 && smsmark==1){
      $(".smslogin").show();
    }
    
    function hideShowPsw() {
      if (demoInput.type == "password") {
        demoInput.type = "text";
        $(".eye i").toggleClass("fa-eye-slash")
      } else {
        demoInput.type = "password";
        $(".eye i").toggleClass("fa-eye-slash")
      }
    }

    $("input").focus(function() {
      demoInput.type = "password";
    });
    $(".toques").click(function() {
      $(".toques").hide() 
      $(".topass").show() 
      $("#adminpass").val('')
      $(".adminpass").hide();
      $(".question").show();
      $(".answer").show();
      $("#logintype").val('question');
    })
    $(".topass").click(function() {
      $(".toques").show() 
      $(".topass").hide() 
      $(".adminpass").show();
      $(".question").hide();
      $(".answer").hide();
      $("#logintype").val('pass');
    })

    function smslogin() {
      $("#pass_login").hide();
      $("#cookie_login").hide();
      $("#sms_login").show();
    }

    function sms_submit(){
      adminname= $("input[name='mobile']").val();
      code = $("input[name='code']").val();
      smscode = $("input[name='smscode']").val();
      time = new Date().getTime();
      token = hex_md5(adminname + time)
      if (smscode.length != 4) {       
        layer.alert('手机验证码不正确');
        return false;
      }
      $.post("login.php?act=loginon", {
        logintype: 'sms',
        adminname: adminname,
        smscode: smscode,
        code: code,
        time: time,
        token: token
      }, function(result) {
        if (result.return_code == 1) {
          location.reload();
        } else {
          layer.alert(result.return_msg);
          $("#SeedCode").click();
          $("#imgcode").val('')
          return false;
        }
      }, 'json');
    }

    function checkForm() {
      logintype=$("input[name='logintype']").val();
      question = $("input[name='question']").val();
      if(logintype=='question'){      
        answer = $("input[name='answer']").val();
        adminpass = hex_md5(answer);
      }else{
        adminpass = $("input[name='adminpass']").val(); 
        adminpass = hex_md5(adminpass);
      }
      adminname = $("input[name='adminname']").val();
    
      code = $("input[name='code']").val();
      time = new Date().getTime();
      token = hex_md5(adminname + time)

      $.post("login.php?act=loginon", {
        logintype: logintype,
        adminname: adminname,
        question:question,
        adminpass: adminpass,
        code: code,
        time: time,
        token: token
      }, function(result) {
        if (result.return_code == 1) {
          location.reload();
        } else {
          layer.alert(result.return_msg);
          $("#SeedCode").click();
          $("#imgcode").val('')
          return false;
        }
      }, 'json');
      //console.log(adminname+adminpass+question+answer);
    }

    $(function() {
      if(get_cookie('adminface') == '' || get_cookie('adminface') == undefined ){
         face ='../plugins/face/noface.png';
      }else{
         face =get_cookie('adminface');
      }

      $(".face").attr('src', unescape(face));
      $('BODY').bgStretcher({
        images: ['images/bg/01.jpg', 'images/bg/02.jpg', 'images/bg/03.jpg', 'images/bg/04.jpg', 'images/bg/05.jpg', 'images/bg/06.jpg'],
        imageWidth: 1024,
        imageHeight: 768,
        slideDirection: 'N',
        slideShowSpeed: 3000,
        transitionEffect: 'fade',
        sequenceMode: 'normal',
        anchoring: 'left center',
        anchoringImg: 'left center'
      });
      var windowHeight = document.documentElement.clientHeight;
      $(".loginscreen").animate({
        marginTop: (windowHeight - 419) / 3
      });
    });
    $("#adminname").focus();
    $(document).keyup(function(event) {
      if (event.keyCode == 13) {
        checkForm()
      }
    });
  </script>
  </div>
</body>

</html>