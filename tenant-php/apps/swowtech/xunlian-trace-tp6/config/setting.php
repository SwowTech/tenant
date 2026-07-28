<?php

return [
    'app_id' => 'wx067623ae3e230867',
    'app_secret' => '0c2469578dd1744f66f24505d1049651',
    'psw_code'=>'rus5w2fr168',
    'pay_back_url' => 'www.tp1.com',
    'access_token_url' => "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s",
    'wx_login_url' => "https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code",//小程序登录，携带code换取openid
    'soft_del' => true, //true为真实删除
    'token_expire_in' => 60*60*8,  //token过期时间，单位秒
    'web_url' => 'http://xunlian.com',
    'public_path' => '/',

    //snsapi_userinfo或snsapi_base
    'gzh_code_url' => "https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect",
    'gzh_login_url' =>'https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code', //公众号登录，携带code换取openid
    
];
