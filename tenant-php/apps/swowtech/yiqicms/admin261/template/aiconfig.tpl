<!doctype html public "-//w3c//dtd html 4.01 transitional//en" "http://www.w3c.org/tr/1999/rec-html401-19991224/loose.dtd">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AI参数设置</title>
  <link href="../plugins/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link href="../plugins/bootstrap/font-awesome.min.css?t=20230419" rel="stylesheet">
  <link href="../plugins/bootstrap/animate.min.css" rel="stylesheet">
  <link href="../plugins/bootstrap/style.min.css" rel="stylesheet">
  <link href="css/adminstyle.css" rel="stylesheet">
  <link href="../plugins/icheck/icheck.css" rel="stylesheet">
  <script src="js/webconfig.php"></script>

  <script src="../js/jquery.min.js"></script>
  <script src="../plugins/layer/layer.min.js"></script>
  <script src="../plugins/colorpicker/bootstrap-colorpicker.min.js"></script>
  <!--[if lte ie 9]>
<script src="../js/respond.min.js"></script>
<script src="../js/html5.js"></script>
<![endif]-->
</head>

<body class="gray-bg">
  <div class="wrapper wrapper-content">
    <div class="row">
      <form method="post" class="form-horizontal" id="contentform">
        <div class="col-sm-12">
          <div class="tabs-container">
            <ul class="nav nav-tabs">
              <li class="tab1 active"><a data-toggle="tab" href="#tab-1"><i class="fa fa-cog"></i> 基础设置</a> </li>
              <li class="tab2"><a data-toggle="tab" href="#tab-2"><i class="fa fa-paw"></i> 百度文心一言</a> </li>
              <li class="tab3"><a data-toggle="tab" href="#tab-3"><i class="fa fa-cloud"></i> 阿里云通义千问</a> </li>
              <li class="tab4"><a data-toggle="tab" href="#tab-4"><i class="fab fa-qq"></i> 腾讯混元大模型</a> </li>
              <li class="tab5"><a data-toggle="tab" href="#tab-5"><i class="fa fa-microchip"></i> 智谱AI</a> </li>
              <li class="tab6"><a data-toggle="tab" href="#tab-6"><i class="fa fa-tiktok"></i> 豆包</a> </li>
              <li class="tab7"><a data-toggle="tab" href="#tab-7"><i class="fa fa-robot"></i> {$ai_conf['other']['api_name']}</a> </li>
              <li class="tab8"><a data-toggle="tab" href="#tab-8"><i class="fa fa-comments"></i> AI默认对话设置</a> </li>
            </ul>
            <div class="tab-content">
              <!-- 基础设置 -->
              <div id="tab-1" class="tab-pane active">
                <div class="panel-body">
                  <div class="form-group">
                    <div class="alert alert-info col-sm-10 col-md-offset-1"> AI服务配置中心，可在此设置各AI服务的API密钥和参数。
                      <span class="text-danger">注意：请确保API密钥保密，避免泄露。</span>
                      支持自定义AI服务，只需在配置文件中添加对应项即可。
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">AI功能开关</label>
                    <div class="col-sm-2" id="enable_ai">
                      <input type="checkbox" name="enable_ai" value="1" class="js-switch" {$check_onoff $ai_conf['enable_ai'],'checked'}>
                      <span class="help-block m-b-none">{$check_onoff $ai_conf['enable_ai'],'ch'}</span>
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> 开启后系统可使用AI功能</span>
                  </div>
                  <div id="showdefaultai">
                    <div class="form-group">
                      <label class="col-sm-2 control-label">默认AI服务</label>
                      <div class="col-sm-4">
                        <select name="default_ai" class="form-control">
                          <option value="baidu" {$check_on $ai_conf['default_ai'],"","selected"}>请选择</option>
                          <option value="baidu" {$check_on $ai_conf['default_ai'],'baidu',"selected"}>百度文心一言</option>
                          <option value="aliyun" {$check_on $ai_conf['default_ai'],'aliyun',"selected"}>阿里云通义千问</option>
                          <option value="tencent" {$check_on $ai_conf['default_ai'],'tencent',"selected"}>腾讯混元大模型</option>
                          <option value="zhipu" {$check_on $ai_conf['default_ai'],'zhipu',"selected"}>智谱AI</option>
                          <option value="doubao" {$check_on $ai_conf['default_ai'],'doubao',"selected"}>豆包</option>
                          <option value="other" {$check_on $ai_conf['default_ai'],'other',"selected"}>{$ai_conf['other']['api_name']}</option>
                        </select>
                      </div>
                      <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> 选择系统默认使用的AI服务</span>
                    </div>
                  </div>
                  <div id="showcommon">
                    <div class="form-group">
                      <div class="alert alert-success col-sm-10 col-md-offset-1"> 通用参数设置，如果不了解各项参数含义，建议保持默认值</div>
                    </div>

                    <div class="form-group">
                      <label class="col-sm-2 control-label">最大生成长度</label>
                      <div class="col-sm-2">
                        <input type="text" value="{$ai_conf['max_tokens']}" name="max_tokens" id="max_tokens" class="form-control">
                      </div>
                      <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> AI生成文本的最大长度,建议API最大输出</span>
                    </div>

                    <div class="form-group">
                      <label class="col-sm-2 control-label">温度参数</label>
                      <div class="col-sm-2">
                        <input type="text" value="{$ai_conf['temperature']}" name="temperature" id="temperature" class="form-control">
                      </div>
                      <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> 0-1之间，值越大生成内容越随机</span>
                    </div>

                    <div class="form-group">
                      <label class="col-sm-2 control-label">核采样参数</label>
                      <div class="col-sm-2">
                        <input type="text" value="{$ai_conf['top_p']}" name="top_p" id="top_p" class="form-control">
                      </div>
                      <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> 0-1之间，控制生成内容的多样性</span>
                    </div>
                    <div class="form-group">
                      <label class="col-sm-2 control-label">缓存时间(秒)</label>
                      <div class="col-sm-4">
                        <input type="text" value="{$ai_conf['cache_ttl']}" name="cache_ttl" id="cache_ttl" class="form-control">
                      </div>
                      <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> AI生成内容的缓存时间</span>
                    </div>

                    <div class="form-group">
                      <label class="col-sm-2 control-label">AI记录</label>
                      <div class="col-sm-2">
                        <button type="button" class="btn btn-danger" onclick="clearAIlog()" id="clearlog">
                          <i class="fa fa-trash"></i> 清理AI记录
                        </button>
                      </div>
                    </div>
                  </div>

                </div>
              </div>

              <!-- 百度文心一言 -->
              <div id="tab-2" class="tab-pane">
                <div class="panel-body">
                  <div class="form-group">
                    <div class="alert alert-info col-sm-10 col-md-offset-1"> 百度文心一言配置，请先在百度AI开放平台注册账号并创建应用获取API密钥。
                      <a href="https://console.bce.baidu.com/qianfan/overview" target="_blank">申请地址</a>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">启用百度文心一言</label>
                    <div class="col-sm-2" id="baidu_enable">
                      <input type="checkbox" name="baidu_enable" value="1" class="js-switch" {$check_onoff $ai_conf['baidu']['enable'],'checked'}>
                      <span class="help-block m-b-none">{$check_onoff $ai_conf['baidu']['enable'],'ch'}</span>
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> 开启后可使用百度文心一言服务</span>
                  </div>

                  <div class="form-group show-{$ai_conf['baidu']['enable']}">
                    <label class="col-sm-2 control-label">API Key</label>
                    <div class="col-sm-4">
                      <input type="text" value="{$ai_conf['baidu']['api_key']}" name="baidu_api_key" id="baidu_api_key" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> 百度AI开放平台的API Key
                      <a href="https://console.bce.baidu.com/iam/#/iam/apikey/list" target="_blank">获取API Key</a>
                    </span>
                  </div>

                  <div class="form-group show-{$ai_conf['baidu']['enable']}">
                    <label class="col-sm-2 control-label">API 地址</label>
                    <div class="col-sm-4">
                      <input type="text" value="{$ai_conf['baidu']['url']}" name="baidu_url" id="baidu_url" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> </span>
                  </div>

                  <div class="form-group show-{$ai_conf['baidu']['enable']}">
                    <label class="col-sm-2 control-label">默认模型</label>
                    <div class="col-sm-4">
                      <input type="text" value="{$ai_conf['baidu']['model']}" name="baidu_model" id="baidu_model" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> 选择默认使用的模型
                      <a href=" https://cloud.baidu.com/doc/WENXINWORKSHOP/s/Fm2vrveyu" target="_blank">查看模型列表</a>
                    </span>
                  </div>


                  <div class="form-group show-{$ai_conf['baidu']['enable']}">
                    <label class="col-sm-2 control-label">超时时间(秒)</label>
                    <div class="col-sm-2">
                      <input type="text" value="{$ai_conf['baidu']['timeout']}" name="baidu_timeout" id="baidu_timeout" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> API请求的超时时间</span>
                  </div>

                  <div class="form-group show-{$ai_conf['baidu']['enable']}">
                    <label class="col-sm-2 control-label">测试配置</label>
                    <div class="col-sm-2">
                      <button type="button" class="btn btn-info" onclick="tryAI('baidu')"><i class="fa fa-edit"></i> 发送Test</button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 阿里云通义千问 -->
              <div id="tab-3" class="tab-pane">
                <div class="panel-body">
                  <div class="form-group">
                    <div class="alert alert-info col-sm-10 col-md-offset-1"> 阿里云通义千问配置，请先在阿里云开通通义千问服务并获取AccessKey。
                      <a href="https://bailian.console.aliyun.com/&tab=doc?tab=model#/model-market" target="_blank">申请地址</a>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">启用阿里云通义千问</label>
                    <div class="col-sm-2" id="aliyun_enable">
                      <input type="checkbox" name="aliyun_enable" value="1" class="js-switch" {$check_onoff $ai_conf['aliyun']['enable'],'checked'}>
                      <span class="help-block m-b-none">{$check_onoff $ai_conf['aliyun']['enable'],'ch'}</span>
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> 开启后可使用阿里云通义千问服务</span>
                  </div>

                  <div class="form-group show-{$ai_conf['aliyun']['enable']}">
                    <label class="col-sm-2 control-label">API Key</label>
                    <div class="col-sm-4">
                      <input type="text" value="{$ai_conf['aliyun']['api_key']}" name="aliyun_api_key" id="aliyun_api_key" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> 阿里云API Key
                      <a href="https://bailian.console.aliyun.com/&tab=doc?tab=model#/api-key" target="_blank">获取API Key</a>
                    </span>
                  </div>
                  <div class="form-group show-{$ai_conf['aliyun']['enable']}">
                    <label class="col-sm-2 control-label">API 地址</label>
                    <div class="col-sm-4">
                      <input type="text" value="{$ai_conf['aliyun']['url']}" name="aliyun_url" id="aliyun_url" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> </span>
                  </div>


                  <div class="form-group show-{$ai_conf['aliyun']['enable']}">
                    <label class="col-sm-2 control-label">模型Code</label>
                    <div class="col-sm-4">
                      <input type="text" value="{$ai_conf['aliyun']['model']}" name="aliyun_model" id="aliyun_model" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> 模型名称
                      <a href="https://bailian.console.aliyun.com/&tab=doc?tab=model#/model" target="_blank">获取模型Code</a>
                    </span>
                  </div>

                  <div class="form-group show-{$ai_conf['aliyun']['enable']}">
                    <label class="col-sm-2 control-label">超时时间(秒)</label>
                    <div class="col-sm-2">
                      <input type="text" value="{$ai_conf['aliyun']['timeout']}" name="aliyun_timeout" id="aliyun_timeout" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> API请求的超时时间</span>
                  </div>
                  <div class="form-group show-{$ai_conf['aliyun']['enable']}">
                    <label class="col-sm-2 control-label">测试配置</label>
                    <div class="col-sm-2">
                      <button type="button" class="btn btn-info" onclick="tryAI('aliyun')"><i class="fa fa-edit"></i> 发送Test</button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 腾讯混元大模型 -->
              <div id="tab-4" class="tab-pane">
                <div class="panel-body">
                  <div class="form-group">
                    <div class="alert alert-info col-sm-10 col-md-offset-1"> 腾讯混元大模型配置，请先在腾讯云开通混元大模型服务并获取密钥。
                      <a href="https://hunyuan.cloud.tencent.com/#/app/apiKeyManage" target="_blank">申请地址</a>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">启用腾讯混元大模型</label>
                    <div class="col-sm-2" id="tencent_enable">
                      <input type="checkbox" name="tencent_enable" value="1" class="js-switch" {$check_onoff $ai_conf['tencent']['enable'],'checked'}>
                      <span class="help-block m-b-none">{$check_onoff $ai_conf['tencent']['enable'],'ch'}</span>
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> 开启后可使用腾讯混元大模型服务，请使用OpenAI SDK方式接入</span>
                  </div>

                  <div class="form-group show-{$ai_conf['tencent']['enable']}">
                    <label class="col-sm-2 control-label">API Key</label>
                    <div class="col-sm-4">
                      <input type="text" value="{$ai_conf['tencent']['api_key']}" name="tencent_api_key" id="tencent_api_key" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> 腾讯云API Key
                      <a href="https://hunyuan.cloud.tencent.com/#/app/apiKeyManage" target="_blank">获取API Key</a>
                    </span>
                  </div>
                  <div class="form-group show-{$ai_conf['tencent']['enable']}">
                    <label class="col-sm-2 control-label">API 地址</label>
                    <div class="col-sm-4">
                      <input type="text" value="{$ai_conf['tencent']['url']}" name="tencent_url" id="tencent_url" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> </span>
                  </div>

                  <div class="form-group show-{$ai_conf['tencent']['enable']}">
                    <label class="col-sm-2 control-label">默认模型</label>
                    <div class="col-sm-4">
                      <input type="text" value="{$ai_conf['tencent']['model']}" name="tencent_model" id="tencent_model" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> 选择默认使用的模型</span>
                  </div>

                  <div class="form-group show-{$ai_conf['tencent']['enable']}">
                    <label class="col-sm-2 control-label">超时时间(秒)</label>
                    <div class="col-sm-2">
                      <input type="text" value="{$ai_conf['tencent']['timeout']}" name="tencent_timeout" id="tencent_timeout" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> API请求的超时时间</span>
                  </div>

                  <div class="form-group show-{$ai_conf['tencent']['enable']}">
                    <label class="col-sm-2 control-label">测试配置</label>
                    <div class="col-sm-2">
                      <button type="button" class="btn btn-info" onclick="tryAI('tencent')"><i class="fa fa-edit"></i> 发送Test</button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 智谱AI -->
              <div id="tab-5" class="tab-pane">
                <div class="panel-body">
                  <div class="form-group">
                    <div class="alert alert-info col-sm-10 col-md-offset-1"> 智谱AI配置，请先在智谱AI开放平台注册账号并获取API密钥。
                      <a href="https://bigmodel.cn/console/modelcenter/square" target="_blank">申请地址</a>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">启用智谱AI</label>
                    <div class="col-sm-2" id="zhipu_enable">
                      <input type="checkbox" name="zhipu_enable" value="1" class="js-switch" {$check_onoff $ai_conf['zhipu']['enable'],'checked'}>
                      <span class="help-block m-b-none">{$check_onoff $ai_conf['zhipu']['enable'],'ch'}</span>
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> 开启后可使用智谱AI服务
                      <a href="https://bigmodel.cn/usercenter/proj-mgmt/apikeys" target="_blank">Key地址</a>
                    </span>
                  </div>

                  <div class="form-group show-{$ai_conf['zhipu']['enable']}">
                    <label class="col-sm-2 control-label">API Key</label>
                    <div class="col-sm-4">
                      <input type="text" value="{$ai_conf['zhipu']['api_key']}" name="zhipu_api_key" id="zhipu_api_key" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> 智谱AI开放平台的API Key</span>
                  </div>
                  <div class="form-group show-{$ai_conf['zhipu']['enable']}">
                    <label class="col-sm-2 control-label">API 地址</label>
                    <div class="col-sm-4">
                      <input type="text" value="{$ai_conf['zhipu']['url']}" name="zhipu_url" id="zhipu_url" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> </span>
                  </div>

                  <div class="form-group show-{$ai_conf['zhipu']['enable']}">
                    <label class="col-sm-2 control-label">默认模型</label>
                    <div class="col-sm-4">
                      <input type="text" value="{$ai_conf['zhipu']['model']}" name="zhipu_model" id="zhipu_model" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> 选择默认使用的模型</span>
                  </div>

                  <div class="form-group show-{$ai_conf['zhipu']['enable']}">
                    <label class="col-sm-2 control-label">超时时间(秒)</label>
                    <div class="col-sm-2">
                      <input type="text" value="{$ai_conf['zhipu']['timeout']}" name="zhipu_timeout" id="zhipu_timeout" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> API请求的超时时间</span>
                  </div>

                  <div class="form-group show-{$ai_conf['zhipu']['enable']}">
                    <label class="col-sm-2 control-label">测试配置</label>
                    <div class="col-sm-2">
                      <button type="button" class="btn btn-info" onclick="tryAI('zhipu')"><i class="fa fa-edit"></i> 发送Test</button>
                    </div>
                  </div>

                </div>
              </div>

              <!-- 豆包 -->
              <div id="tab-6" class="tab-pane">
                <div class="panel-body">
                  <div class="form-group">
                    <div class="alert alert-info col-sm-10 col-md-offset-1"> 豆包配置，请先在豆包开放平台注册账号并获取API密钥。
                      <a href="https://console.volcengine.com/ark/region:ark+cn-beijing/openManagement?LLM=%7B%7D&OpenModelVisible=false" target="_blank">申请地址</a>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">启用豆包</label>
                    <div class="col-sm-2" id="doubao_enable">
                      <input type="checkbox" name="doubao_enable" value="1" class="js-switch" {$check_onoff $ai_conf['doubao']['enable'],'checked'}>
                      <span class="help-block m-b-none">{$check_onoff $ai_conf['doubao']['enable'],'ch'}</span>
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> 开启后可使用豆包服务</span>
                  </div>

                  <div class="form-group show-{$ai_conf['doubao']['enable']}">
                    <label class="col-sm-2 control-label">API Key</label>
                    <div class="col-sm-4">
                      <input type="text" value="{$ai_conf['doubao']['api_key']}" name="doubao_api_key" id="doubao_api_key" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> 豆包开放平台的API Key <a class="btn btn-xs btn-info" href="https://console.volcengine.com/ark/region:ark+cn-beijing/apiKey" target="_blank">获取地址</a> </span>
                  </div>
                  <div class="form-group show-{$ai_conf['doubao']['enable']}">
                    <label class="col-sm-2 control-label">API 地址</label>
                    <div class="col-sm-4">
                      <input type="text" value="{$ai_conf['doubao']['url']}" name="doubao_url" id="doubao_url" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> <a class="btn btn-xs btn-info" href="https://console.volcengine.com/ark/region:ark+cn-beijing/openManagement?LLM=%7B%7D&OpenModelVisible=false" target="_blank">获取模型URL</a> </span>
                  </div>

                  <div class="form-group show-{$ai_conf['doubao']['enable']}">
                    <label class="col-sm-2 control-label">模型名称</label>
                    <div class="col-sm-4">
                      <input type="text" value="{$ai_conf['doubao']['model']}" name="doubao_model" id="doubao_model" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> <a class="btn btn-xs btn-info" href="https://console.volcengine.com/ark/region:ark+cn-beijing/openManagement?LLM=%7B%7D&OpenModelVisible=false" target="_blank">获取Model ID</a> </span>
                  </div>

                  <div class="form-group show-{$ai_conf['doubao']['enable']}">
                    <label class="col-sm-2 control-label">超时时间(秒)</label>
                    <div class="col-sm-2">
                      <input type="text" value="{$ai_conf['doubao']['timeout']}" name="doubao_timeout" id="doubao_timeout" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> API请求的超时时间</span>
                  </div>

                  <div class="form-group show-{$ai_conf['doubao']['enable']}">
                    <label class="col-sm-2 control-label">测试配置</label>
                    <div class="col-sm-2">
                      <button type="button" class="btn btn-info" onclick="tryAI('doubao')"><i class="fa fa-edit"></i> 发送Test</button>
                    </div>
                  </div>

                </div>
              </div>

              <!-- 自定义 -->
              <div id="tab-7" class="tab-pane">
                <div class="panel-body">
                  <div class="form-group">
                    <div class="alert alert-info col-sm-10 col-md-offset-1"> 自定义AI配置，请先在对应AI平台注册账号并获取API密钥。</div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">启用自定义AI</label>
                    <div class="col-sm-2" id="other_enable">
                      <input type="checkbox" name="other_enable" value="1" class="js-switch" {$check_onoff $ai_conf['other']['enable'],'checked'}>
                      <span class="help-block m-b-none">{$check_onoff $ai_conf['other']['enable'],'ch'}</span>
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> 开启后可使用自定义AI服务</span>
                  </div>
                  <div class="form-group show-{$ai_conf['other']['enable']}">
                    <label class="col-sm-2 control-label">自定义AI名称</label>
                    <div class="col-sm-4">
                      <input type="text" value="{$ai_conf['other']['api_name']}" name="other_api_name" id="other_api_name" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> 自定义AI服务的名称</span>
                  </div>

                  <div class="form-group show-{$ai_conf['other']['enable']}">
                    <label class="col-sm-2 control-label">API Key</label>
                    <div class="col-sm-4">
                      <input type="text" value="{$ai_conf['other']['api_key']}" name="other_api_key" id="other_api_key" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> 自定义AI开放平台的API Key </span>
                  </div>

                  <div class="form-group show-{$ai_conf['other']['enable']}">
                    <label class="col-sm-2 control-label">API 地址</label>
                    <div class="col-sm-4">
                      <input type="text" value="{$ai_conf['other']['url']}" name="other_url" id="other_url" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> 自定义AI开放平台的API地址</span>
                  </div>

                  <div class="form-group show-{$ai_conf['other']['enable']}">
                    <label class="col-sm-2 control-label">模型名称</label>
                    <div class="col-sm-4">
                      <input type="text" value="{$ai_conf['other']['model']}" name="other_model" id="other_model" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i>自定义AI开放平台的模型名称</span>
                  </div>

                  <div class="form-group show-{$ai_conf['other']['enable']}">
                    <label class="col-sm-2 control-label">超时时间(秒)</label>
                    <div class="col-sm-2">
                      <input type="text" value="{$ai_conf['other']['timeout']}" name="other_timeout" id="other_timeout" class="form-control">
                    </div>
                    <span class="help-block m-b-none"><i class="fa fa-info-circle"></i> API请求的超时时间</span>
                  </div>

                  <div class="form-group show-{$ai_conf['other']['enable']}">
                    <label class="col-sm-2 control-label">测试配置</label>
                    <div class="col-sm-2">
                      <button type="button" class="btn btn-info" onclick="tryAI('other')"><i class="fa fa-edit"></i> 发送Test</button>
                    </div>
                  </div>

                </div>
              </div>
              <div id="tab-8" class="tab-pane">
                <div class="panel-body">

                  <!-- 自定义默认对话设置 -->
                  <div class="form-group">
                    <div class="col-sm-10 col-md-offset-1">
                      <div class="alert alert-warning">
                        <i class="fa fa-exclamation-circle"></i> 自定义默认对话设置 - 修改后将影响AI助手的默认提示文本，<span class="text-danger">
                          <标题>
                            <内容>
                        </span>是替换字段，请注意保留格式。
                      </div>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">润色</label>
                    <div class="col-sm-6">
                      <textarea class="form-control" rows="3" name="prompt_polish" id="prompt_polish">{$ai_conf['prompt']['polish']}</textarea>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">创造</label>
                    <div class="col-sm-6">
                      <textarea class="form-control" rows="3" name="prompt_create" id="prompt_create">{$ai_conf['prompt']['create']}</textarea>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">排版</label>
                    <div class="col-sm-6">
                      <textarea class="form-control" rows="3" name="prompt_format" id="prompt_format">{$ai_conf['prompt']['format']}</textarea>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">翻译</label>
                    <div class="col-sm-6">
                      <textarea class="form-control" rows="3" name="prompt_translate" id="prompt_translate">{$ai_conf['prompt']['translate']}</textarea>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">安全</label>
                    <div class="col-sm-6">
                      <textarea class="form-control" rows="3" name="prompt_security" id="prompt_security">{$ai_conf['prompt']['security']}</textarea>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">艺术</label>
                    <div class="col-sm-6">
                      <textarea class="form-control" rows="3" name="prompt_art" id="prompt_art">{$ai_conf['prompt']['art']}</textarea>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">SEO</label>
                    <div class="col-sm-6">
                      <textarea class="form-control" rows="3" name="prompt_seo" id="prompt_seo">{$ai_conf['prompt']['seo']}</textarea>
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="col-sm-2 control-label">FAQ</label>
                    <div class="col-sm-6">
                      <textarea class="form-control" rows="3" name="prompt_faq" id="prompt_faq">{$ai_conf['prompt']['faq']}</textarea>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

        <div class="col-sm-12  m-t">
          <div class=" col-sm-10 col-md-offset-1">
            <button class="btn btn-primary" type="button" id="submit"><i class="fa fa-floppy-o"></i>　保存设置</button>
            <button onClick="location.reload()" class="btn" type="button"><i class="fa fa-refresh"></i> 刷新</button>
          </div>
        </div>
    </div>
    </form>
  </div>
  </div>
  <script src="../plugins/bootstrap/bootstrap.min.js"></script>
  <link href="../plugins/colorpicker/bootstrap-colorpicker.min.css" rel="stylesheet">
  <script src="../plugins/switchery/switchery.js"></script>
  <link href="../plugins/switchery/switchery.css" rel="stylesheet">
  <script src="../plugins/icheck/icheck.min.js"></script>
  <script src="js/adminjs.js?t=20231027"></script>
  <script>
    // 开关控制显示/隐藏
    $(function() {
      $(".i-checks").iCheck()
      var elems = Array.prototype.slice.call(document.querySelectorAll('.js-switch'));
      elems.forEach(function(html) {
        var switchery = new Switchery(html);
        html.onchange = function() {
          var help = $(this).parent().find(".help-block")
          if (html.checked == true) {
            help.text("已开启")
          } else {
            help.text("已关闭")
          }
        };
      });
      // AI功能开关
      $('#enable_ai input').change(function() {
        if ($(this).is(':checked')) {
          $('#showdefaultai, #showcommon').show();
        } else {
          $('#showdefaultai, #showcommon').hide();
        }
      });

      // 初始状态
      if (!$('#enable_ai input').is(':checked')) {
        $('#showdefaultai, #showcommon').hide();
      }

      // 各AI服务开关
      var services = ['baidu', 'aliyun', 'tencent', 'zhipu', 'doubao', 'other'];
      $.each(services, function(index, service) {
        $('#' + service + '_enable input').change(function() {
          $('.show-[' + service + '_enable]').toggle($(this).is(':checked'));
        });

        $('#' + service + '_enable').click(function() {
          if (!$('#' + service + '_enable input').is(':checked')) {
            $(this).parents('.panel-body').find('.show-1').removeClass('show-1').addClass('show-0');
          } else {
            $(this).parents('.panel-body').find('.show-0').removeClass('show-0').addClass('show-1');
          }
        });

      });

      // 表单提交验证
      $('#submit').click(function() {
        if ($('#enable_ai input').is(':checked')) {
          var hasEnabledService = false;
          $.each(services, function(index, service) {
            if ($('#' + service + '_enable input').is(':checked')) {
              hasEnabledService = true;
              // 验证必填项
              if (service === 'baidu' && $('#baidu_api_key').val() === '' && $('#baidu_secret_key').val() === '') {
                layer.msg('请填写百度文心一言的API密钥', {
                  icon: 5
                });
                return false;
              }
              if (service === 'aliyun' && $('#aliyun_api_key').val() === '' && $('#aliyun_access_key_secret').val() === '') {
                layer.msg('请填写阿里云通义千问的AccessKey', {
                  icon: 5
                });
                return false;
              }
              if (service === 'tencent' && $('#tencent_secret_id').val() === '' && $('#tencent_secret_key').val() === '') {
                layer.msg('请填写腾讯混元大模型的密钥', {
                  icon: 5
                });
                return false;
              }
              if (service === 'zhipu' && $('#zhipu_api_key').val() === '') {
                layer.msg('请填写智谱AI的API Key', {
                  icon: 5
                });
                return false;
              }
            }
          });

          if (!hasEnabledService) {
            layer.msg('请至少启用一个AI服务', {
              icon: 5
            });
            return false;
          }
        }

        // 验证通用参数
        if ($('#max_tokens').val() === '' || isNaN($('#max_tokens').val()) || $('#max_tokens').val() <= 0) {
          layer.msg('请填写有效的最大生成长度', {
            icon: 5
          });
          return false;
        }

        if ($('#temperature').val() === '' || isNaN($('#temperature').val()) || $('#temperature').val() < 0 || $('#temperature').val() > 1) {
          layer.msg('温度参数必须在0-1之间', {
            icon: 5
          });
          return false;
        }

        if ($('#top_p').val() === '' || isNaN($('#top_p').val()) || $('#top_p').val() < 0 || $('#top_p').val() > 1) {
          layer.msg('核采样参数必须在0-1之间', {
            icon: 5
          });
          return false;
        }

        if ($('#cache_ttl').val() === '' || isNaN($('#cache_ttl').val()) || $('#cache_ttl').val() < 0) {
          layer.msg('请填写有效的缓存时间', {
            icon: 5
          });
          return false;
        }
        $.post('save.php?act=save_aicfg', $(this).parents('form').serialize(), function(data) {
          if (data.return_code == 1) {
            layer.msg('保存成功', {
              icon: 6
            });
            setTimeout(function() {
              location.reload();
            }, 1000);
          } else {
            layer.msg(data.return_msg, {
              icon: 5
            });
          }
        }, 'json');

        return true;
      });
    });

    function tryAI(val) {
      layer.load();
      $.post('AI.php?act=try_ai', {
        'type': val
      }, function(data) {
        layer.closeAll('loading');
        if (data.return_code == 1) {
          layer.msg('测试成功，我是' + data.return_msg, {
            icon: 6
          });
        } else {
          layer.msg(data.return_msg, {
            icon: 5
          });
        }
      }, 'json');
    }

    function clearAIlog() {
      layer.confirm('确定要清理所有AI记录吗？此操作不可恢复！', {
        btn: ['确定', '取消']
      }, function() {
        layer.load();
        $.post('AI.php?act=clear_ai_log', {}, function(data) {
          layer.closeAll('loading');
          if (data.return_code == 1) {
            layer.msg('清理成功', {
              icon: 6
            });
          } else {
            layer.msg(data.return_msg, {
              icon: 5
            });
          }
        }, 'json');
      });
    }
  </script>
</body>

</html>