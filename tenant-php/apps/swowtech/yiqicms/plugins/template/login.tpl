<!doctype html>
<html>

<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <meta charset="utf-8">
  <title>会员登录-{zzz:sitetitle}</title>
  <meta name="description" content="{zzz:sietdesc}" />
  <meta name="Keywords" content="{zzz:sitekeys}" />
  <meta name="author" content="http://www.zzzcms.com" />
  <script src="{zzz:sitepath}js/jquery.min.js" type="text/javascript"></script>
  <script src="{zzz:plugpath}layer/layer.min.js" type="text/javascript"></script>
  <script src="{zzz:sitepath}js/zzzcms.js" type="text/javascript"></script>
  <link href="{zzz:sitepath}plugins/Validform/Validform.css" rel="stylesheet" />
  <script src="{zzz:sitepath}plugins/Validform/Validform.min.js"></script>
  <link href="{zzz:plugpath}bootstrap/bootstrap.min.css" rel="stylesheet">
  <link href="{zzz:plugpath}bootstrap/font-awesome.min.css?v=4.4.0" rel="stylesheet">
  <link href="{zzz:plugpath}bootstrap/animate.min.css" rel="stylesheet">
  <link href="{zzz:plugpath}bootstrap/style.min.css" rel="stylesheet">
  <link href="{zzz:plugpath}template/css/login.min.css" rel="stylesheet">
</head>

<body class="signin">

  <div class="signinpanel">
    <div class="row">
      <div class="col-sm-12 animated fadeInDown">
        <div class="signin-info">
          <div class="logopanel m-b">
            <h1>会员登陆</h1>
            <h2>——　LOGIN　——</h2>
          </div>
          <div class="m-b"></div>
        </div>
      </div>
      <div class="col-sm-12 animated fadeInDown">
        <form class="registerform" method="post" action="{zzz:sitepath}form/?login">
          <input type="hidden" name="type" value="[user:type]">
          <input type="hidden" name="backurl" value="[user:backurl]">
         
          <p class="m-t-md"></p>
          <div class="formsub">
            <li>
              <div>
                <input type="text" value="" name="username" class="form-control" datatype="*5-18" placeholder="请输入账号 "
                  ajaxurl="{zzz:sitepath}plugins/sms/sms.php?act=checkname&checktype=1" errormsg="账号范围5~18 " />
                <i class="fa fa-user"></i>
              </div>
              <div class="Validform_checktip"></div>
            </li>
            <li>
              <div>
                <input type="password" value="" name="password" class="form-control" datatype="*5-16" placeholder="请输入密码"
                  errormsg="密码范围范围5~16" />
                <i class="fa fa-key"></i>
              </div>
              <div class="Validform_checktip"></div>
            </li>
          {if:{conf:usercode}=1}
            <li>
              <div>
                <input type="text " value="" name="code" id="imgcode" class="form-control code"
                  ajaxurl="{zzz:sitepath}plugins/sms/sms.php?act=checkcode" datatype="*4-4" placeholder="请输入验证码"
                  errormsg="验证失败" />
              </div>
              <div class="Validform_checktip"></div>
              <div class="imgcode"> <img src="{zzz:sitepath}inc/imgcode.php" id="SeedCode" align="absmiddle"
                  style="cursor:pointer;" border="0" /> </div>
            </li>
            {end if}
           
            <div class="row">
              <div class="col-sm-12"> <a
                  href="{zzz:sitepath}?location=user&act=reg&type=[user:type]&backurl=[user:backurl]" ><span id="reg">注册账号</span></a>
                <a href="{zzz:sitepath}?location=user&act=forget&type=[user:type]&backurl=[user:backurl]"
                  class="pull-right"><span id="forget">忘记密码？</span></a>
              </div>
            </div>
            <div class="action">
              <button class="btn btn-success btn-block m-t" type="submit">登陆</button>
            </div>
          </div>
        </form>
      </div>
      <div class="col-sm-12 animated fadeInUp m-t-xl">
        <div class="row">
          {if:check_dir('/plug/qqlogin/')=1}
          <div class="col-sm-4  text-center">
            <a class="m-t" class="btn" onClick="otherlogin('qq')">
              <span><img src="{zzz:plugpath}template/images/qq.png" width="60"></span>
              <span class="text-center text-xs block  text-info p-xxs">QQ登陆</span></a>
          </div>

          {end if}
          {if:check_dir('/plug/wxlogin/')=1}
          <div class="col-sm-4 text-center">
            <a class="m-t" class="btn" onClick="otherlogin('wx')">
              <span><img src="{zzz:plugpath}template/images/weixin.png" width="60"></span>
              <span class="text-center text-xs block  text-info p-xxs">微信登陆</span></a>
          </div>
          {end if}
          {if:check_dir('/plug/wblogin/')=1}
          <div class="col-sm-4  text-center">
            <a class="m-t" class="btn" onClick="otherlogin('wb')">
              <span> <img src="{zzz:plugpath}template/images/weibo.png" width="60"></span>
              <span class="text-center text-xs block text-info p-xxs">微博登陆</span></a>
          </div>
          {end if}

        </div>
      </div>
    </div>
    <div class="signup-footer animated fadeInUp m-t-xl">
      <div> {zzz:copyright} </div>
    </div>
  </div>
  <script src="{zzz:plugpath}bootstrap/bootstrap.min.js"></script>
  <script type="text/javascript">
    $(function () {
      $(".registerform").Validform({
        tiptype: 2
      });
    });

    function otherlogin(type) {
      if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        location.href = "../plug/" + type + "login/";
      } else {
        layer.open({
          type: 2,
          shadeClose: true,
          anim: 1,
          area: ['600px', '550px'],
          title: false,
          content: "../plug/" + type + "login/"
        })
      }
    }

    function toreg() {
      $("#reg").click();
    }

    function toforget() {
      $("#forget").click();
    }
  </script>
</body>

</html>