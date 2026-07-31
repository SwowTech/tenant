<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3c.org/TR/1999/REC-html401-19991224/loose.dtd">
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>网站地图</title>
    <link href="../plugins/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="../plugins/bootstrap/font-awesome.min.css?t=20230419" rel="stylesheet">
    <link href="../plugins/bootstrap-table/bootstrap-table.min.css" rel="stylesheet">
    <link href="../plugins/bootstrap/animate.min.css" rel="stylesheet">
    <link href="../plugins/bootstrap/style.min.css" rel="stylesheet">
    <link href="css/adminstyle.css" rel="stylesheet">
    <script src="../js/jquery.min.js"></script>
    <script>
        var table = '';
    </script>
    <!--[if lte IE 9]>
<script src="../js/respond.min.js"></script>
<script src="../js/html5.js"></script>
<![endif]-->
</head>

<body class="gray-bg">
    <div class="wrapper wrapper-content">
        <div class="ibox float-e-margins">
            <div class="row row-lg">
                <div class="col-sm-12">
                    <div class="ibox">
                        <div class="ibox-title">
                            <h5>搜索站长提交API</h5>
                         
                        </div>
                    </div>
                    <div class="ibox-content">

                        <form method="post" class="form-horizontal" id="contentform">
                            <div class="form-group">
                                <label class="col-sm-2 control-label">API完整地址</label>
                                <div class="col-sm-5">
                                    <input type="text" value="[r:baidusiteapi]" name="baidusiteapi" id="baidusiteapi" class="form-control" placeholder="点击【接口调用地址】获取">
                                </div>
                                <div class="col-sm-5"></div>
                            </div>
           
                            <div class="form-group">
                                <label class="col-sm-2 control-label">推送内容</label>
                                <div class="col-sm-5">
                                    <textarea id="urls" name="urls" class="form-control" rows="20">[r:urls]</textarea>
                                </div>
                                <div class="col-sm-5"> 
                                     <a href="https://ziyuan.baidu.com/linksubmit/index" target="_blank">百度站长</a>
                                    <p>请在【API完整地址】中输入您的网站地图地址，接口调用地址：http://data.zz.baidu.com/urls?site=www.***.com&token={apiKey}</p>
                                   
                                    <a href="https://www.bing.com/webmaster" target="_blank">Bing站长</a>
                                    <p>请在【API完整地址】中输入您的网站地图地址，格式为：https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlBatch?apikey={apiKey}</p>

                                    <a href="https://www.google.com/webmasters" target="_blank">谷歌站长</a>
                                    <p>谷歌站长需要科学上网才能访问</p>

                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-sm-12 m-t">
                    <div class=" col-sm-10 col-md-offset-1">
                        <button class="btn btn-primary" onclick="submitform('sitemap','','contentform')" type="button" id="submit" title="快捷键：ctrl+enter"><i class="fa fa-floppy-o"></i>　保存内容</button>
                        <button class="btn btn-white" onClick="closelayer()" type="reset"><i class="fa fa-close"></i> 返回</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Panel Other -->
    </div>
    <script src="js/adminjs.js?t=20231027"></script>
    <script src="../plugins/bootstrap/bootstrap.min.js"></script>
    <script src="../plugins/bootstrap-table/bootstrap-table.min.js"></script>
    <script src="../plugins/bootstrap-table/bootstrap-table-mobile.min.js"></script>
    <script src="../plugins/layer/layer.min.js"></script>
    <script src="js/content.min.js?t=20230419"></script> 
    <script src="js/adminjs.js?t=20231027"></script>
    <script src="../js/zzzcms.js"></script>
</body>

</html>