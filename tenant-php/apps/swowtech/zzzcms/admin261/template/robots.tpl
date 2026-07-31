<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3c.org/TR/1999/REC-html401-19991224/loose.dtd">
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>创建默认robots</title>
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
<?php
$db_path='';
if($conf['db']['type'] == 'sqlite') $db_path='Disallow: /ZQAN8K9M/' ; 
$defaultRules = 'User-agent: *'.PHP_EOL.
    'Disallow: /plugins/'.PHP_EOL.    
    'Disallow: /config/'.PHP_EOL.    
    'Disallow: /template/'.PHP_EOL.
    'Disallow: /runtime/'.PHP_EOL.
    'Disallow: /inc/'.PHP_EOL.
    'Disallow: /form/'.PHP_EOL.
    'Disallow: /js/'.PHP_EOL.
    'Disallow: /upload/'.PHP_EOL.
    'Disallow: /cgi-bin/'.PHP_EOL.
     $db_path . PHP_EOL .
    'Allow: /upload/public/'.PHP_EOL.
    'Sitemap: https://'.$_SERVER['HTTP_HOST'].'/sitemap.xml';
?>
</head>

<body class="gray-bg">
    <div class="wrapper wrapper-content">
        <div class="ibox float-e-margins">
            <div class="row row-lg">
                <div class="col-sm-12">
                    <div class="ibox">
                        <div class="ibox-title">
                            <h5>创建默认robots</h5>
                        </div>
                    </div>
                    <div class="ibox-content">
                        <form method="post" class="form-horizontal" id="contentform">                            
           
                            <div class="form-group">
                                <label class="col-sm-2 control-label">默认内容</label>
                                <div class="col-sm-5">
                                    <textarea id="robots" name="robots" class="form-control" rows="20">{$defaultRules}</textarea>
                                </div>
                                <div class="col-sm-5"> robots.txt对搜索引擎的影响：<br/>
                                    1. 搜索引擎会根据robots.txt文件中的规则来判断哪些页面可以被索引。<br/>
                                    2. 如果robots.txt文件中没有指定某个页面的规则，搜索引擎默认会索引该页面。<br/>
                                    3. 请确保robots.txt文件的内容正确，避免对搜索引擎的影响。
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-sm-12 m-t">
                    <div class=" col-sm-10 col-md-offset-1">
                        <button class="btn btn-primary" onclick="submitform('robots','','contentform')" type="button" id="submit" title="快捷键：ctrl+enter"><i class="fa fa-floppy-o"></i>　保存内容</button>
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