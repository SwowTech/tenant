<!DOCTYPE HTML
    PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3c.org/TR/1999/REC-html401-19991224/loose.dtd">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../plugins/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="../plugins/bootstrap/font-awesome.min.css?t=20230419" rel="stylesheet">
    <link href="../plugins/bootstrap/animate.min.css" rel="stylesheet">
    <link href="../plugins/bootstrap/style.min.css" rel="stylesheet">
    <link href="css/adminstyle.css" rel="stylesheet">
    <script src="../js/jquery.min.js"></script>
    <link href="../plugins/codemirror/codemirror.css" rel="stylesheet">
    <link href="../plugins/codemirror/ambiance.css" rel="stylesheet">
    <title>模板编辑：
        <?php echo $folder ?>
    </title>
    <!--[if lte IE 9]>
<script src="../js/respond.min.js"></script>
<script src="../js/html5.js"></script>
<![endif]-->
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f0f2f5;
            color: #333;
            overflow: hidden;
            height: 100vh;
            width: 100vw;
        }

        /* 顶部工具栏 */
        .editor-header {
            height: 50px;
            background-color: #fff;
            border-bottom: 1px solid #e5e5e5;
            display: flex;
            align-items: center;
            padding: 0 20px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1002;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .header-logo {
            font-size: 18px;
            font-weight: 600;
            color: #1ab394;
            margin-right: 30px;
        }
        .header-info {
            width: 500px;
        }
        .header-info span {
            font-size: 14px;line-height: 36px; color: #666;
        }
         .header-info .form-control {
            width: 150px;
            float: right;
        }

        .header-actions {
            margin-left: auto;
            display: flex;
            gap: 10px;
        }

        .header-btn {
            padding: 6px 16px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: #1ab394;
            color: white;
        }

        .btn-primary:hover {
            background-color: #18a689;
        }

        .btn-default {
            background-color: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
        }

        .btn-default:hover {
            background-color: #e6e6e6;
        }

        .codecontent {
            overflow: hidden;
            position: relative;
            height: calc(100vh - 50px);
            margin-top: 50px;
        }

        .CodeMirror {
            position: relative;
            border-top: 1px solid black;
            border-bottom: 1px solid black;
            height: calc(100vh - 50px);
            min-height: 200px;
            overflow-y: auto;
        }
        .diff-textarea {
            display: none;            
        }
    </style>
</head>
<?php
	$ext=file_ext($folder);
	$safe_ext=array('xml','html','css','js','txt','zzz');
	if (ifch( $folder) ) $file=toutf($folder);
	$file_path=file_path($folder);
	$safe_path=array('upload','template','runtime');
	if(arr_search($file_path,$safe_path) && arr_search($ext,$safe_ext)){
		$content=replacestr(load_file(DOC_DIR.'/'.$folder),'</textarea>','&lt/textarea>');
        $sitepath=SITE_PATH;
        $paths = splits($folder, '/');
        $tmppath = '';
        foreach ($paths as $k => $v) {
            if ($k < 5) {
                $tmppath .= $v . '/';
            }
        }
        $temdir=str_replace('//','/',DOC_DIR.'/'.$tmppath);
        $file_list=path_list($temdir,'html');
        $option='<option value="">模板列表</option>';
        $option .= '<option value="?act=ai&type=createhtml&folder='.$folder.'\'">AI生成模板</option>';

        foreach ($file_list as $k => $v) {
            if(ifstrin($v['url'],'.html')){
                if($v['url']==$folder){
                    $option.='<option value="'.$v['url'].'" selected>'.$v['title'].'</option>';
                }else{
                    $option.='<option value="'.$v['url'].'">'.$v['title'].'</option>';
                }
            }
        }
	}else{
		die('此文件不允许编辑！');
	}
?>
<form id="contentform">
    <input type="hidden" name="filepath" value="<?php echo $folder ?>">

    <!-- 顶部工具栏 -->
    <div class="editor-header">
        <div class="header-logo">
            <i class="fa fa-magic"></i> 源码编辑器
        </div>
        <div class="header-info">
            <span>正在编辑：
                <?php echo $folder ?>
            </span>
             <select class="form-control" id="select_template">
                <?php echo $option; ?>
            </select>
        </div>

       
        <div class="header-actions">
            {if ifstrin($folder,'.html')}
            <button class="header-btn btn-default" type="button"
                onclick="window.location.href='?act=templateedit&folder=<?php echo $folder ?>'">
                <i class="fa fa-laptop"></i> 可视化编辑
            </button>
            {/if}
            <button class="header-btn btn-default" type="button" id="preview-button">
                <i class="fa fa-eye"></i> 预览
            </button>
            {if ifstrin($folder,[c adminpath])}
            <button class="header-btn btn-default" onclick="window.location.href='?act=templatelist&type=pc&folder=<?php echo $tmppath ?>'" type="button">
                    <i class="fa fa-list"></i> 返回列表
                </button>
            {else}
            <button class="header-btn btn-primary" type="button" onclick="submitform('editfile','<?php echo $folder ?>','contentform')" type="button" id="submit" title="快捷键：ctrl+enter">
                <i class="fa fa-save"></i> 保存
            </button>
           <button class="header-btn btn-default" onclick="window.location.href='?act=templatelist&type=pc&folder=<?php echo $tmppath ?>'" type="button">
                    <i class="fa fa-list"></i> 返回列表
                </button>
            {/if}
        </div>
    </div>

    <div class="codecontent">
        <textarea name="filecontent" id="CodeMirror" class="diff-textarea CodeMirror">{$content}</textarea>
    </div>

    <style type="text/css">

    </style>
    <script src="../plugins/bootstrap/bootstrap.min.js"></script>
    <script src="../plugins/codemirror/codemirror.js"></script>
    <script src="../plugins/codemirror/javascript.js"></script>
    <script src="../plugins/codemirror/active-line.js"></script>
    <script src="../plugins/codemirror/matchbrackets.js"></script>
    <script src="../plugins/layer/layer.min.js"></script>
    <script src="js/content.min.js?t=20230419"></script>
    <script src="js/adminjs.js?t=20231027"></script>
    <script>
       
        // 修复预览按钮功能
        document.getElementById('preview-button').addEventListener('click', function() {
            try {
                // 获取编辑器内容
                var content = editor.getValue();
                content=content.replace(/\{zzz:tmppath\}/g,'<?php echo $tmppath ?>');
                content=content.replace(/\{zzz:sitepath\}/g,'<?php echo $sitepath ?>');
                // 创建一个新窗口来预览内容
                var previewWindow = window.open('', '_blank', 'width=800,height=600,scrollbars=yes');
                
                if (previewWindow) {
                    // 写入预览内容
                    previewWindow.document.open();
                    previewWindow.document.write(content);
                    previewWindow.document.close();
                } else {
                    alert('无法打开预览窗口，请检查浏览器的弹出窗口设置。');
                }
            } catch (error) {
                console.error('预览错误:', error);
                alert('预览功能出错，请稍后重试。');
            }
        });
        // 监听模板选择变化
        $('#select_template').on('change', function() {
                var selectedValue = $(this).val();
                if (selectedValue.indexOf('act') !== -1) {
                    opennew(selectedValue);
                }else if (selectedValue) {
                    window.location.href = '?act=templateedit&folder=' + selectedValue;
                }
            });
        
        // 监听Ctrl+P快捷键实现预览
        document.addEventListener('keydown', function(event) {
            if (event.ctrlKey && event.key === 'p' && !event.altKey && !event.shiftKey) {
                event.preventDefault();
                document.getElementById('preview-button').click();
            }
        });
    </script>
    </body>

    </html>