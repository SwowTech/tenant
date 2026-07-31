<!DOCTYPE HTML
    PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3c.org/TR/1999/REC-html401-19991224/loose.dtd">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="../js/jquery.min.js"></script>
    <script src="js/webconfig.php"></script>

    <!-- 引入Font Awesome图标库 -->
    <link href="../plugins/bootstrap/bootstrap.min.css" rel="stylesheet">

    <link href="../plugins/bootstrap/font-awesome.min.css" rel="stylesheet">
    <!-- Visual Editor CSS -->
    <link href="../plugins/visualeditor/visualeditor.css" rel="stylesheet">
    <link href="../plugins/colorpicker/bootstrap-colorpicker.min.css" rel="stylesheet">

    <style>
        /* 全局样式重置 */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

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
            font-size: 14px;
            line-height: 36px;
            color: #666;
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

        /* 主要编辑区域容器 */
        .editor-container {
            position: relative;
            height: calc(100vh - 50px);
            margin-top: 50px;
        }

        /* 左侧组件面板 - 默认显示，可收缩 */
        .components-panel {
            width: 360px;
            height: calc(100vh - 80px);
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            overflow-y: auto;
            padding: 20px;
            position: fixed;
            left: 0;
            top: 70px;
            z-index: 1001;
            transition: all 0.3s ease;
            resize: horizontal;
        }

        /* 收缩状态 */
        .components-panel.collapsed {
            width: 50px;
            padding: 10px 5px;
        }

        .components-panel.collapsed .panel-title,
        .components-panel.collapsed .category-title,
        .components-panel.collapsed .component-item span {
            display: none;
        }

        /* 收缩状态下的组件类别和组件项样式 */
        .components-panel.collapsed .component-category {
            margin-bottom: 10px;
            padding: 5px;
        }

        .components-panel.collapsed .component-item {
            width: 40px;
            padding: 8px;
            margin-bottom: 5px;
            justify-content: center;
            min-height: 40px;
            margin-left: auto;
            margin-right: auto;
        }

        .components-panel.collapsed .component-item i {
            margin-right: 0;
            font-size: 20px;
            width: 32px;
            height: 32px;
        }

        /* 自定义滚动条样式 */
        .components-panel::-webkit-scrollbar {
            width: 6px;
        }

        .components-panel::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .components-panel::-webkit-scrollbar-thumb {
            background: #1ab394;
            border-radius: 3px;
        }

        .components-panel::-webkit-scrollbar-thumb:hover {
            background: #17a689;
        }

        /* 浮动显示/隐藏按钮 */
        .components-toggle-btn {
            position: absolute;
            left: 350px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #1ab394, #17a689);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1002;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            border: none;
        }

        .components-toggle-btn:hover {
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        /* 隐藏状态下的按钮位置 */
        .components-panel.hidden+.components-toggle-btn {
            left: 10px;
        }

        /* 拖动时的样式 */
        .components-panel.dragging,
        .properties-panel.dragging {
            opacity: 0.9;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: none;
            /* 拖动时禁用过渡效果 */
        }

        /* 面板标题 - 可拖动区域 */
        .panel-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            background: linear-gradient(135deg, #1ab394, #17a689);
            cursor: move;
            text-align: center;
            box-shadow: 0 2px 8px rgba(26, 179, 148, 0.3);
        }

        .component-category {
            margin-bottom: 25px;
            background-color: #fff;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .category-title {
            font-size: 14px;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f0f0f0;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 100%;
        }

        .component-item {
            width: calc(33.333% - 10px);
            padding: 12px;
            background-color: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            cursor: move;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            min-height: 80px;
            text-align: center;
        }

        .component-item:hover {
            background-color: #f5f5f5;
            border-color: #1ab394;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .component-item i {
            font-size: 24px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f0f8f7;
            border-radius: 6px;
            color: #1ab394;
        }

        .component-item span {
            color: #333;
            font-size: 14px;
            font-weight: 500;
            flex: 1;
        }

        /* 中间编辑区域 - 根据组件库状态调整位置 */
        .edit-area {
            width: calc(100% - 370px);
            /* 默认留出组件库宽度+边距 */
            height: 100%;
            background-color: #f8f9fa;
            overflow: auto;
            padding: 20px;
            transition: all 0.3s ease;
            margin-left: 370px;
        }

        /* 隐藏组件库时的编辑区域 */
        .components-panel.hidden~.edit-area {
            width: calc(100% - 60px);
            margin-left: 60px;
        }

        /* 组件库隐藏状态 */
        .components-panel.hidden {
            transform: translateX(-100%);
            opacity: 0;
            visibility: hidden;
        }

        /* 当组件库收缩时调整编辑区域 */
        .components-panel.collapsed~.edit-area {
            width: calc(100% - 60px);
            /* 收缩时留出的宽度 */
            margin-left: 60px;
        }

        .edit-stage {
            background-color: white;
            min-width: 960px;
            height: 100%;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }

        .visual-editor-iframe {
            width: 100%;
            height: 100%;
            min-height: 600px;
            border: none;
            display: block;
        }

        /* 右侧属性面板 - 浮动可拖动模式，默认隐藏 */
        .properties-panel {
            width: 280px;
            height: calc(100vh - 70px);
            background-color: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            padding: 15px;
            position: absolute;
            right: 0px;
            /* 默认隐藏在右侧 */
            top: 10px;
            z-index: 1001;
            display: none;
            cursor: move;
            resize: horizontal;
            transition: right 0.3s ease;
        }

        .properties-panel.show {
            display: block;
        }

        /* 属性面板手柄 */
        .properties-panel-handle {
            position: absolute;
            left: -30px;
            top: 50%;
            transform: translateY(-50%);
            background-color: #1ab394;
            color: white;
            padding: 10px 5px;
            border-radius: 4px 0 0 4px;
            cursor: pointer;
            font-size: 12px;
            width: 30px;
            text-align: center;
            z-index: 1002;
        }

        .property-group {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }

        .property-group-header {
            align-items: center;
            margin-bottom: 15px;
        }

        .property-group-title {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .property-close-btn {
            background: none;
            border: none;
            font-size: 18px;
            color: #999;
            cursor: pointer;
            padding: 0;
            width: 20px;
            height: 20px;
            align-items: center;
            justify-content: center;
            position: absolute;
            right: 10px;
            top: 20px;
        }

        .property-close-btn:hover {
            color: #666;
        }

        .property-select {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            background-color: white;
        }

        .property-buttons-group {
            display: flex;
            gap: 5px;
        }

        .property-button {
            flex: 1;
            padding: 6px 10px;
            border: 1px solid #ddd;
            background-color: white;
            cursor: pointer;
            border-radius: 3px;
            font-size: 12px;
        }

        .property-button:hover {
            background-color: #f5f5f5;
        }

        .property-button.active {
            background-color: #1ab394;
            color: white;
            border-color: #1ab394;
        }

        .property-item {
            margin-bottom: 15px;
        }

        .property-label {
            display: block;
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
        }

        .property-input {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
            transition: border-color 0.3s ease;
        }

        .property-input:focus {
            outline: none;
            border-color: #1ab394;
        }

        .property-textarea {
            width: 100%;
            min-height: 80px;
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
            resize: vertical;
        }

        /* 选中元素的编辑框样式 */
        .element-selected {
            outline: 2px solid #1ab394 !important;
            outline-offset: 2px;
            border: 1px dashed #ccc !important;
            padding: 5px !important;
            margin: 2px 0 !important;
            cursor: pointer !important;
        }

        /* 鼠标悬停元素样式 */
        .element-hover {
            border: 1px dashed #ccc !important;
            padding: 5px !important;
            margin: 2px 0 !important;
            cursor: pointer !important;
        }

        /* 元素操作按钮容器 */
        .element-actions {
            position: absolute;
            top: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            display: flex;
            gap: 1px;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        /* 显示操作按钮 */
        .element-hover .element-actions,
        .element-selected .element-actions {
            opacity: 1;
        }

        /* 操作按钮样式 */
        .element-action-btn {
            width: 28px;
            height: 28px;
            border: none;
            background: #fff;
            color: #666;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .element-action-btn:hover {
            background: #1ab394;
            color: white;
        }

        /* 编辑按钮 */
        .action-edit:hover {
            background: #1ab394;
        }

        /* 移动按钮 */
        .action-move-up:hover {
            background: #47bac1;
        }

        .action-move-down:hover {
            background: #47bac1;
        }

        /* 属性按钮 */
        .action-properties:hover {
            background: #f8ac59;
        }

        /* 删除按钮 */
        .action-delete:hover {
            background: #ed5565;
        }

        /* 响应式布局 */
        @media (max-width: 1200px) {
            .components-panel {
                width: 200px;
            }

            .properties-panel {
                width: 240px;
            }

            .edit-stage {
                min-width: 768px;
            }
        }

        /* 动画效果 */
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>
    <title>模板编辑：
        <?php echo $folder ?>
    </title>
</head>
<?php
// CMS标签处理函数
//require '../inc/zzz_template.php';
// 保存原始内容用于恢复
define('WAPPATH', conf('wappath'));
define('SITEPATH', conf('sitepath'));
$data = db_load_one('language', "l_alias='ch'", 'pctemplate,waptemplate,pchtmlpath,waphtmlpath,sitekeys,sitedesc,siteurl,sitewapurl');
define('PCTPL', $data['pctemplate']);
define('WAPTPL', $data['waptemplate']);
define('TPL_DIR', SITE_DIR . 'template/pc/' . PCTPL . $data['pchtmlpath']);
define('HTML_PATH', SITE_PATH);
define('HTML_DIR', SITE_DIR);
if (empty($folder)) {
    $folder = '/template/pc/' . PCTPL . $data['pchtmlpath'] . 'index.html';
} 
$paths = splits($folder, '/');
$tempath = '';
$tmppath = '';
foreach ($paths as $k => $v) {
    if ($k < 4) {
        $tempath .= $v . '/';
    }
    if ($k < 5) {
        $tmppath .= $v . '/';
    }
}
$temdir = str_replace('//', '/', DOC_DIR . '/' . $tmppath);
$file_list = path_list($temdir, 'html');
$option = '<option value="">模板列表</option>';
$option .= '<option value="?act=ai&type=createhtml&folder='.$folder.'\'">AI生成模板</option>';
foreach ($file_list as $k => $v) {
    if (ifstrin($v['url'], '.html')) {
        if ($v['url'] ==  $folder) {
            $option .= '<option value="' . $v['url'] . '" selected>' . $v['title'] . '</option>';
        } else {
            $option .= '<option value="' . $v['url'] . '">' . $v['title'] . '</option>';
        }
    }
}

$ext = file_ext(DOC_DIR . $folder);
$safe_ext = array('html', 'tpl');
$file_path = file_path($folder);
$safe_path = array('upload', 'template', 'runtime');
if (arr_search($file_path, $safe_path) && arr_search($ext, $safe_ext)) {
    $original_content = load_file($folder);
    $zcontent = replacestr($original_content, '</textarea>', '&lt/textarea>');
    
    $zcontent = str_replace( ['{zzz:top}','{zzz:head}'], '{zzz:template src=head.html}', $zcontent );
    $zcontent = str_replace( ['{zzz:foot}','{zzz:end}'], '{zzz:template src=foot.html}', $zcontent );
    $zcontent = str_replace( '{zzz:left}', '{zzz:template src=left.html}', $zcontent );
    $zcontent = str_replace( '{zzz:right}', '{zzz:template src=right.html}', $zcontent );
    $pattern = '/\{zzz:template src=\s?([\"\']?)([\w\.\-\/]+)([\"\']?)\s*\}/';
    if ( preg_match_all( $pattern, $zcontent, $matches ) ) {
            $arr = $matches[ 0 ];
			$name = $matches[ 2 ];

			$count = count( $arr );		
            for ( $i = 0; $i < $count; $i++ ) {	
                $scontent = file_get_contents( TPL_DIR . $name[$i] );
                if ($scontent === false) {
                    die('模板文件不存在：' . TPL_DIR . $name[$i]);
                }else{
                    $scontent = "<!--$name[$i]-->" . $scontent . "<!--/$name[$i]-->";
                }
                $zcontent = str_replace( $arr[$i], $scontent, $zcontent );
            }
    }
   
    $zcontent = str_replace('{zzz:tempath}', $tempath, $zcontent);
    $zcontent = str_replace('{zzz:sitepath}', SITE_PATH, $zcontent);
    $zcontent = str_replace('{zzz:plugpath}', SITE_PATH . 'plugins/', $zcontent);
    $visual_content = $zcontent;
} else {
    die('此文件不允许编辑！');
}
?>

<body>
    <form id="contentform">
        <input type="hidden" name="filepath" value="<?php echo $folder ?>">
        <input type="hidden" name="filecontent" id="filecontent" value="<?php echo encode($visual_content); ?>">

        <!-- 顶部工具栏 -->
        <div class="editor-header">
            <div class="header-logo">
                <i class="fa fa-magic"></i> 可视化编辑器
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
                <button class="header-btn btn-default" type="button"
                    onclick="window.location.href='?act=codeedit&folder=<?php echo $folder ?>'">
                    <i class="fa fa-code"></i> 源码模式
                </button>

                <button class="header-btn btn-default" type="button" id="preview-button">
                    <i class="fa fa-eye"></i> 预览
                </button>
                <button class="header-btn btn-default" type="button" id="undo-button" title="撤销 (Ctrl+Z)">
                    <i class="fa fa-undo"></i> 撤销
                </button>
                <button class="header-btn btn-default" type="button" id="redo-button" title="重做 (Ctrl+Y)">
                    <i class="fa fa-repeat"></i> 重做
                </button>
                <button class="header-btn btn-default" type="button" id="history-button" title="操作历史">
                    <i class="fa fa-history"></i> 历史
                </button>
                <button class="header-btn btn-primary" type="button" id="save-button" title="快捷键：ctrl+enter">
                    <i class="fa fa-save"></i> 保存
                </button>
                <button class="header-btn btn-default" onclick="window.location.href='?act=templatelist&type=pc&folder=<?php echo $tmppath ?>'" type="button">
                    <i class="fa fa-list"></i> 返回列表
                </button>
            </div>
        </div>

        <!-- 组件库配置 -->
        <script>
            // JavaScript组件库配置
            var componentLibrary = {
                categories: [{
                        id: 'text',
                        name: '文本组件',
                        icon: 'fa-font',
                        components: [{
                                id: 'text',
                                name: '文本段落',
                                icon: 'fa-paragraph'
                            },
                            {
                                id: 'heading',
                                name: '标题',
                                icon: 'fa-header'
                            },
                            {
                                id: 'subtitle',
                                name: '副标题',
                                icon: 'fa-text-height'
                            },
                            {
                                id: 'link',
                                name: '链接',
                                icon: 'fa-link'
                            },
                            {
                                id: 'list',
                                name: '列表',
                                icon: 'fa-list-ul'
                            }
                        ]
                    },
                    {
                        id: 'media',
                        name: '媒体组件',
                        icon: 'fa-picture-o',
                        components: [{
                                id: 'image',
                                name: '图片',
                                icon: 'fa-image'
                            },
                            {
                                id: 'video',
                                name: '视频',
                                icon: 'fa-film'
                            },
                        ]
                    },
                    {
                        id: 'layout',
                        name: '布局组件',
                        icon: 'fa-th-large',
                        components: [{
                                id: 'row',
                                name: '行布局',
                                icon: 'fa-columns'
                            },
                            {
                                id: 'column',
                                name: '列布局',
                                icon: 'fa-align-left'
                            },
                            {
                                id: 'divider',
                                name: '分隔线',
                                icon: 'fa-minus'
                            },
                            {
                                id: 'container',
                                name: '容器',
                                icon: 'fa-square-o'
                            }
                        ]
                    },
                    {
                        id: 'function',
                        name: '功能组件',
                        icon: 'fa-cogs',
                        components: [{
                                id: 'button',
                                name: '按钮',
                                icon: 'fa-hand-pointer-o'
                            },
                            {
                                id: 'form',
                                name: '表单',
                                icon: 'fa-list-alt'
                            },
                            {
                                id: 'table',
                                name: '表格',
                                icon: 'fa-table'
                            }
                        ]
                    }
                ]
            };

            // 全局标志，确保组件库只生成一次
            var componentLibraryGenerated = false;

            // 动态生成组件库面板
            function generateComponentLibrary() {
                // 防止重复生成
                if (componentLibraryGenerated) {
                    console.log('组件库已经生成过，跳过重复生成');
                    return;
                }

                var panel = $('.components-panel');
                if (!panel.length) return;

                // 确保只操作编辑器容器内的组件面板
                if (!panel.closest('.editor-container').length) {
                    console.log('跳过非编辑器容器内的组件面板');
                    return;
                }

                // 完全清空面板内容，确保没有残留元素
                panel.empty();

                // 重新创建必要的面板结构
                panel.append('<div class="panel-title">组件库</div>');

                // 生成组件类别和组件项
                componentLibrary.categories.forEach(function(category) {
                    var categoryDiv = $('<div class="component-category"></div>');

                    // 类别标题
                    var categoryTitle = $('<div class="category-title"></div>');
                    categoryTitle.html('<i class="fa ' + category.icon + '"></i> ' + category.name);
                    categoryDiv.append(categoryTitle);

                    // 组件项
                    category.components.forEach(function(component) {
                        var componentItem = $('<div class="component-item" draggable="true"></div>');
                        componentItem.data('component', component.id);
                        componentItem.html('<i class="fa ' + component.icon + '"></i> <span>' + component.name + '</span>');

                        // 添加双击事件，实现双击插入组件到焦点位置
                        componentItem.on('dblclick', function(e) {
                            e.stopPropagation();
                            var componentType = $(this).data('component');
                            insertComponentAtCursor(componentType);
                        });

                        categoryDiv.append(componentItem);
                    });

                    panel.append(categoryDiv);
                });

                // 设置生成标志
                componentLibraryGenerated = true;
                console.log('组件库已从JavaScript配置动态生成');
            }

            // 在编辑器当前焦点位置插入组件
            function insertComponentAtCursor(componentType) {
                try {
                    var iframe = window.editorIframe || document.getElementById('visual-editor');
                    if (!iframe || !iframe.contentDocument) {
                        console.error('编辑器iframe不可用');
                        return;
                    }

                    var iframeDoc = iframe.contentDocument;
                    var selection = iframeDoc.getSelection();

                    // 创建组件元素
                    var wrapper = iframeDoc.createElement('div');
                    wrapper.className = 'visual-component';
                    wrapper.setAttribute('data-type', componentType);

                    // 根据组件类型创建内部内容
                    var newElement;
                    switch (componentType) {
                        case 'text':
                            newElement = iframeDoc.createElement('p');
                            newElement.innerHTML = '点击编辑文本内容';
                            newElement.style.margin = '10px 0';
                            wrapper.appendChild(newElement);
                            break;
                        case 'heading':
                            newElement = iframeDoc.createElement('h2');
                            newElement.innerHTML = '标题文本';
                            newElement.style.margin = '20px 0 10px';
                            wrapper.appendChild(newElement);
                            break;
                        case 'subtitle':
                            newElement = iframeDoc.createElement('h3');
                            newElement.innerHTML = '副标题文本';
                            newElement.style.margin = '15px 0 8px';
                            newElement.style.fontSize = '18px';
                            newElement.style.color = '#666';
                            wrapper.appendChild(newElement);
                            break;
                        case 'image':
                            newElement = iframeDoc.createElement('img');
                            newElement.src = '/plugins/visualeditor/images/new.png';
                            newElement.alt = '图片';
                            newElement.style.maxWidth = '100%';
                            wrapper.appendChild(newElement);
                            break;

                        case 'button':
                            newElement = iframeDoc.createElement('button');
                            newElement.innerHTML = '按钮';
                            newElement.style.padding = '8px 16px';
                            newElement.style.backgroundColor = '#1ab394';
                            newElement.style.color = 'white';
                            newElement.style.border = 'none';
                            newElement.style.borderRadius = '4px';
                            wrapper.appendChild(newElement);
                            break;
                        case 'divider':
                            newElement = iframeDoc.createElement('hr');
                            newElement.style.margin = '20px 0';
                            wrapper.appendChild(newElement);
                            break;
                        case 'video':
                            newElement = iframeDoc.createElement('video');
                            newElement.controls = true;
                            newElement.style.maxWidth = '100%';
                            newElement.style.height = 'auto';
                            newElement.style.backgroundColor = '#f5f5f5';
                            // 添加提示文字
                            var source = iframeDoc.createElement('source');
                            source.type = 'video/mp4';
                            newElement.appendChild(source);
                            newElement.innerHTML += '您的浏览器不支持视频播放。请点击属性面板上传视频。';
                            wrapper.appendChild(newElement);
                            break;
                        case 'list':
                            newElement = iframeDoc.createElement('ul');
                            newElement.style.margin = '10px 0';
                            newElement.style.paddingLeft = '20px';
                            // 添加几个列表项
                            var listItem1 = iframeDoc.createElement('li');
                            listItem1.innerHTML = '列表项 1';
                            newElement.appendChild(listItem1);
                            var listItem2 = iframeDoc.createElement('li');
                            listItem2.innerHTML = '列表项 2';
                            newElement.appendChild(listItem2);
                            var listItem3 = iframeDoc.createElement('li');
                            listItem3.innerHTML = '列表项 3';
                            newElement.appendChild(listItem3);
                            wrapper.appendChild(newElement);
                            break;
                        case 'link':
                            newElement = iframeDoc.createElement('a');
                            newElement.href = '#';
                            newElement.innerHTML = '点击这里';
                            newElement.style.color = '#1ab394';
                            newElement.style.textDecoration = 'underline';
                            newElement.setAttribute('target', '_blank');
                            wrapper.appendChild(newElement);
                            break;
                        case 'row':
                            newElement = iframeDoc.createElement('div');
                            newElement.style.display = 'flex';
                            newElement.style.flexDirection = 'row';
                            newElement.style.flexWrap = 'wrap';
                            newElement.style.margin = '10px 0';
                            // 添加两个子列作为示例
                            var col1 = iframeDoc.createElement('div');
                            col1.style.flex = '1';
                            col1.style.minWidth = '200px';
                            col1.style.padding = '10px';
                            col1.style.border = '1px dashed #ddd';
                            col1.style.margin = '5px';
                            col1.innerHTML = '左列';
                            newElement.appendChild(col1);
                            var col2 = iframeDoc.createElement('div');
                            col2.style.flex = '1';
                            col2.style.minWidth = '200px';
                            col2.style.padding = '10px';
                            col2.style.border = '1px dashed #ddd';
                            col2.style.margin = '5px';
                            col2.innerHTML = '右列';
                            newElement.appendChild(col2);
                            wrapper.appendChild(newElement);
                            break;
                        case 'column':
                            newElement = iframeDoc.createElement('div');
                            newElement.style.display = 'flex';
                            newElement.style.flexDirection = 'column';
                            newElement.style.margin = '10px 0';
                            // 添加两个子行作为示例
                            var row1 = iframeDoc.createElement('div');
                            row1.style.padding = '10px';
                            row1.style.border = '1px dashed #ddd';
                            row1.style.margin = '5px 0';
                            row1.innerHTML = '上行';
                            newElement.appendChild(row1);
                            var row2 = iframeDoc.createElement('div');
                            row2.style.padding = '10px';
                            row2.style.border = '1px dashed #ddd';
                            row2.style.margin = '5px 0';
                            row2.innerHTML = '下行';
                            newElement.appendChild(row2);
                            wrapper.appendChild(newElement);
                            break;
                        case 'container':
                            newElement = iframeDoc.createElement('div');
                            newElement.style.maxWidth = '1200px';
                            newElement.style.margin = '0 auto';
                            newElement.style.padding = '20px';
                            newElement.style.border = '1px solid #eee';
                            newElement.innerHTML = '容器内容区域';
                            wrapper.appendChild(newElement);
                            break;
                        case 'form':
                            newElement = iframeDoc.createElement('form');
                            newElement.style.padding = '20px';
                            newElement.style.border = '1px solid #eee';
                            newElement.style.borderRadius = '4px';

                            // 添加表单字段
                            var formGroup1 = iframeDoc.createElement('div');
                            formGroup1.style.marginBottom = '15px';
                            formGroup1.innerHTML = '<label style="display:block;margin-bottom:5px;">姓名：</label>' +
                                '<input type="text" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;" placeholder="请输入姓名">';
                            newElement.appendChild(formGroup1);

                            var formGroup2 = iframeDoc.createElement('div');
                            formGroup2.style.marginBottom = '15px';
                            formGroup2.innerHTML = '<label style="display:block;margin-bottom:5px;">邮箱：</label>' +
                                '<input type="email" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;" placeholder="请输入邮箱">';
                            newElement.appendChild(formGroup2);

                            var formGroup3 = iframeDoc.createElement('div');
                            formGroup3.style.marginBottom = '15px';
                            formGroup3.innerHTML = '<label style="display:block;margin-bottom:5px;">留言：</label>' +
                                '<textarea style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;min-height:100px;" placeholder="请输入留言内容"></textarea>';
                            newElement.appendChild(formGroup3);

                            var submitBtn = iframeDoc.createElement('button');
                            submitBtn.type = 'submit';
                            submitBtn.innerHTML = '提交';
                            submitBtn.style.padding = '10px 20px';
                            submitBtn.style.backgroundColor = '#1ab394';
                            submitBtn.style.color = 'white';
                            submitBtn.style.border = 'none';
                            submitBtn.style.borderRadius = '4px';
                            submitBtn.style.cursor = 'pointer';
                            newElement.appendChild(submitBtn);

                            wrapper.appendChild(newElement);
                            break;
                        case 'table':
                            newElement = iframeDoc.createElement('table');
                            newElement.style.width = '100%';
                            newElement.style.borderCollapse = 'collapse';
                            newElement.style.border = '1px solid #ddd';

                            // 添加表头
                            var thead = iframeDoc.createElement('thead');
                            thead.innerHTML = '<tr style="background-color:#f5f5f5;">' +
                                '<th style="padding:12px;border:1px solid #ddd;text-align:left;">列 1</th>' +
                                '<th style="padding:12px;border:1px solid #ddd;text-align:left;">列 2</th>' +
                                '<th style="padding:12px;border:1px solid #ddd;text-align:left;">列 3</th>' +
                                '</tr>';
                            newElement.appendChild(thead);

                            // 添加表体
                            var tbody = iframeDoc.createElement('tbody');
                            tbody.innerHTML = '<tr>' +
                                '<td style="padding:12px;border:1px solid #ddd;">数据 1-1</td>' +
                                '<td style="padding:12px;border:1px solid #ddd;">数据 1-2</td>' +
                                '<td style="padding:12px;border:1px solid #ddd;">数据 1-3</td>' +
                                '</tr>' +
                                '<tr>' +
                                '<td style="padding:12px;border:1px solid #ddd;">数据 2-1</td>' +
                                '<td style="padding:12px;border:1px solid #ddd;">数据 2-2</td>' +
                                '<td style="padding:12px;border:1px solid #ddd;">数据 2-3</td>' +
                                '</tr>' +
                                '<tr>' +
                                '<td style="padding:12px;border:1px solid #ddd;">数据 3-1</td>' +
                                '<td style="padding:12px;border:1px solid #ddd;">数据 3-2</td>' +
                                '<td style="padding:12px;border:1px solid #ddd;">数据 3-3</td>' +
                                '</tr>';
                            newElement.appendChild(tbody);

                            wrapper.appendChild(newElement);
                            break;
                        default:
                            newElement = iframeDoc.createElement('div');
                            newElement.innerHTML = '新组件';
                            newElement.style.padding = '10px';
                            newElement.style.border = '1px dashed #ccc';
                            wrapper.appendChild(newElement);
                    }

                    // 尝试在光标位置插入
                    if (selection && selection.rangeCount > 0) {
                        var range = selection.getRangeAt(0);
                        var parentElement = range.commonAncestorContainer;

                        // 如果光标在文本节点中，找到其父元素
                        if (parentElement.nodeType === 3) {
                            parentElement = parentElement.parentNode;
                        }

                        // 检查父元素是否是可编辑容器
                        var editableContainer = parentElement.closest('.visual-component, div, section, article, main, header, footer');
                        if (editableContainer) {
                            // 在光标位置插入组件
                            range.insertNode(wrapper);
                            // 移动光标到组件后面
                            range.setStartAfter(wrapper);
                            range.setEndAfter(wrapper);
                            selection.removeAllRanges();
                            selection.addRange(range);
                        } else {
                            // 如果没有找到合适的插入点，默认添加到body末尾
                            iframeDoc.body.appendChild(wrapper);
                        }
                    } else {
                        // 如果没有选区，默认添加到body末尾
                        iframeDoc.body.appendChild(wrapper);
                    }

                    // 设置组件元素属性
                    setupComponentElement(wrapper);

                    // 选中新创建的组件
                    $(iframeDoc).find('.visual-component-selected').removeClass('visual-component-selected');
                    $(wrapper).addClass('element-selected visual-component-selected');

                    // 更新属性面板
                    updatePropertyPanel(wrapper);

                    console.log('组件已通过双击插入到焦点位置:', componentType);
                } catch (error) {
                    console.error('插入组件时出错:', error);
                    // 出错时的后备方案：添加到body末尾
                    try {
                        var iframe = window.editorIframe || document.getElementById('visual-editor');

                    } catch (innerError) {
                        console.error('后备插入方案也失败:', innerError);
                    }
                }
            }

            // 在文档加载完成后生成组件库
            $(document).ready(function() {
                // 移除任何可能存在的重复组件面板
                $('.components-panel').not($('.editor-container .components-panel')).remove();

                // 使用延迟确保DOM完全准备就绪
                setTimeout(function() {
                    // 再次检查并移除可能的重复组件面板
                    $('.components-panel').not($('.editor-container .components-panel')).remove();

                    // 确保组件库只生成一次
                    if (!$('.components-panel').find('.component-category').length && !componentLibraryGenerated) {
                        generateComponentLibrary();
                    }
                }, 100);
            });
        </script>

        <!-- 主要编辑区域 -->
        <div class="editor-container">
            <!-- 左侧组件面板 - 完全由JavaScript动态生成 -->
            <div class="components-panel hidden"></div>
            <!-- 浮动收缩/展开按钮 -->
            <button type="button" class="components-toggle-btn" id="components-toggle-btn"><i class="fa fa-chevron-right"></i></button>

            <!-- 中间编辑区域 -->
            <div class="edit-area">
                <div class="edit-stage">
                    <iframe id="visual-editor" class="visual-editor-iframe" frameborder="0"></iframe>
                </div>
            </div>

            <!-- 右侧属性面板 -->
            <div class="properties-panel">
                <div class="properties-panel-handle" id="properties-handle">▶</div>
                <div class="panel-title">属性设置 </div>

                <!-- Tab切换按钮 -->
                <div class="property-tabs">
                    <a class="property-tab active" data-tab="element">基础属性</a>
                    <a class="property-tab" data-tab="style">样式设置</a>
                </div>

                <div id="element-properties" class="property-group tab-content">

                    <div class="property-item">
                        <label class="property-label">元素ID</label>
                        <input type="text" class="property-input" id="element-id" placeholder="设置元素ID">
                    </div>

                    <div class="property-item">
                        <label class="property-label">CSS类名</label>
                        <input type="text" class="property-input" id="element-class" placeholder="设置CSS类名">
                    </div>

                    <div class="property-item">
                        <label class="property-label" id="element-content-label">内容</label>
                        <div class="property-buttons-group">
                            <textarea class="property-textarea" id="element-content" placeholder="编辑元素内容"></textarea>
                            <button type="button" class="btn btn-primary btn-upload" id="image-upload-btn" style="display: none;" onclick="uploadForCurrentElement()"><i class="fa fa-upload"></i> 上传文件</button>
                        </div>
                    </div>
                </div>
                <style>
                    .property-buttons-group {
                        display: flex;
                        flex-direction: column;
                        gap: 8px;
                    }

                    .btn-upload {
                        padding: 6px 12px;
                        font-size: 14px;
                        align-self: flex-start;
                    }
                </style>

                <div id="style-properties" class="property-group tab-content" style="display: none;">

                    <div class="property-item">
                        <label class="property-label">文字颜色</label>
                        <div class="input-group colorpicker-component">
                            <input type="text" class="property-input" id="text-color" placeholder="#000000">
                            <span class="input-group-addon"><i></i></span>
                        </div>
                    </div>

                    <div class="property-item">
                        <label class="property-label">背景颜色</label>
                        <div class="input-group colorpicker-component">
                            <input type="text" class="property-input" id="bg-color" placeholder="#ffffff">
                            <span class="input-group-addon"><i></i></span>
                        </div>
                    </div>

                    <div class="property-item">
                        <label class="property-label">字体大小</label>
                        <input type="text" class="property-input" id="font-size" placeholder="14px">
                    </div>

                    <div class="property-item">
                        <label class="property-label">背景图</label>
                        <div class="property-buttons-group">

                            <input type="text" class="property-input" id="bg-image" placeholder="图片URL">
                            <button type="button" class="btn btn-primary btn-upload" onclick="uploadWithCallback('image','1','bg-image','bg-image')"><i class="fa fa-upload"></i> 上传</button>
                        </div>
                    </div>

                    <div class="property-item">
                        <label class="property-label">字体</label>
                        <select class="property-select" id="font-family">
                            <option value="">默认字体</option>
                            <option value="Arial, sans-serif">Arial</option>
                            <option value="'Helvetica Neue', Helvetica, Arial, sans-serif">Helvetica</option>
                            <option value="'Times New Roman', Times, serif">Times New Roman</option>
                            <option value="'Courier New', Courier, monospace">Courier New</option>
                            <option value="Georgia, serif">Georgia</option>
                            <option value="'Microsoft YaHei', SimHei, sans-serif">微软雅黑</option>
                            <option value="SimSun, serif">宋体</option>
                            <option value="KaiTi, serif">楷体</option>
                        </select>
                    </div>

                    <div class="property-item">
                        <label class="property-label">文字对齐</label>
                        <div class="property-buttons-group" id="text-align-group">
                            <button type="button" class="property-button" data-align="left" title="左对齐">左对齐</button>
                            <button type="button" class="property-button" data-align="center" title="居中">居中</button>
                            <button type="button" class="property-button" data-align="right" title="右对齐">右对齐</button>
                            <button type="button" class="property-button" data-align="justify"
                                title="两端对齐">两端对齐</button>
                        </div>
                    </div>

                    <div class="property-item">
                        <label class="property-label">行间距</label>
                        <input type="text" class="property-input" id="line-height" placeholder="1.5">
                    </div>

                    <div class="property-item">
                        <label class="property-label">链接URL</label>
                        <input type="text" class="property-input" id="link-url" placeholder="http://">
                    </div>
                </div>
            </div>
        </div>
    </form>

    <style type="text/css">
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f3f3f4;
            margin: 0;
            padding: 0;
        }

        .wrapper {
            padding: 20px;
        }

        .wrapper-content {
            margin: 0 auto;
            max-width: 1400px;
        }

        .ibox {
            clear: both;
            margin-bottom: 25px;
            margin-top: 0;
            padding: 0;
        }

        .ibox-title {
            background-color: #ffffff;
            border-color: #e7eaec;
            border-image: none;
            border-style: solid solid none;
            border-width: 4px 0px 0;
            color: inherit;
            margin-bottom: 0;
            padding: 14px 15px 7px;
            min-height: 48px;
        }

        .ibox-title h5 {
            display: inline-block;
            font-size: 16px;
            margin: 0 0 7px;
            padding: 0;
            text-overflow: ellipsis;
            float: left;
            font-weight: 600;
        }

        .ibox-content {
            background-color: #ffffff;
            color: inherit;
            padding: 15px 20px 20px 20px;
            border-color: #e7eaec;
            border-image: none;
            border-style: solid solid none;
            border-width: 1px 0px;
        }

        .editor-container {
            width: 100%;
        }

        /* 修改为iframe样式 */
        .visual-editor-iframe {
            min-height: 600px;
            width: 100%;
            border: 1px solid #ddd;
            background-color: white;
            border-radius: 3px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: box-shadow 0.3s ease;
        }

        .visual-editor-iframe:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        /* 属性面板Tab样式 */
        .property-tabs {
            display: flex;
            border-bottom: 1px solid #e7eaec;
            margin-bottom: 15px;
        }

        .property-tab {
            flex: 1;
            padding: 10px 15px;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            font-size: 14px;
            color: #666;
            transition: all 0.3s ease;
        }

        .property-tab:hover {
            color: #1ab394;
            background-color: #f9f9f9;
        }

        .property-tab.active {
            color: #1ab394;
            border-bottom-color: #1ab394;
            background-color: #fff;
        }

        .tab-content {
            min-height: 200px;
        }

        /* 按钮样式美化 */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #fff;
            border-top: 1px solid #e7eaec;
            padding: 10px 20px;
            z-index: 1000;
        }

        .footer .arrow {
            float: right;
        }

        .btn {
            border-radius: 3px;
            margin-left: 10px;
            transition: all 0.3s ease;
            padding: 8px 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .save-btn {
            background-color: #1ab394;
            border-color: #1ab394;
            color: white;
        }

        .save-btn:hover {
            background-color: #18a689;
            border-color: #18a689;
            color: white;
        }

        .cancel-btn {
            background-color: #ffffff;
            border-color: #e7eaec;
            color: inherit;
        }

        .cancel-btn:hover {
            background-color: #f5f5f5;
        }

        /* CMS标签样式 */
        .cms-tag {
            display: inline-block;
            padding: 2px 8px;
            margin: 2px;
            border-radius: 3px;
            background-color: #e1f3d8;
            border: 1px solid #c6e0b4;
            color: #27ae60;
            font-family: monospace;
            cursor: pointer;
            user-select: none;
        }

        .cms-tag:hover {
            background-color: #d5e8c8;
            border-color: #b8d998;
        }

        .cms-condition-tag {
            background-color: #f8e9c0;
            border-color: #f0d48a;
            color: #e67e22;
        }

        .cms-condition-tag:hover {
            background-color: #f3ddaa;
            border-color: #e6c36d;
        }

        .cms-loop-tag {
            background-color: #d4e6f1;
            border-color: #a9cce3;
            color: #3498db;
        }

        .cms-loop-tag:hover {
            background-color: #c8e0f4;
            border-color: #85c1e9;
        }

        /* CMS标签工具栏 */
        .cms-tag-toolbar {
            position: absolute;
            display: none;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            padding: 5px;
            min-width: 150px;
        }

        .cms-tag-toolbar.show {
            display: block;
        }

        .cms-tag-toolbar button {
            display: block;
            width: 100%;
            text-align: left;
            padding: 5px 10px;
            margin: 2px 0;
            border: none;
            background: none;
            border-radius: 2px;
            cursor: pointer;
        }

        .cms-tag-toolbar button:hover {
            background-color: #f5f5f5;
        }
    </style>
    <script src="../plugins/colorpicker/bootstrap-colorpicker.min.js"></script>
    <script>
        $(function() {
            try {
                // 为文字颜色选择器初始化
                $('#text-color').closest('.colorpicker-component').colorpicker({
                    format: 'hex',
                    useAlpha: false
                }).on('changeColor', function(e) {
                    var color = e.color.toHex();
                    $('#text-color').val(color);

                    // 更新选中元素样式
                    var selectedElement = iframe.contentDocument.querySelector('.element-selected');
                    if (selectedElement) {
                        selectedElement.style.color = color;
                    }
                });

                // 为背景颜色选择器初始化
                $('#bg-color').closest('.colorpicker-component').colorpicker({
                    format: 'hex',
                    useAlpha: false
                }).on('changeColor', function(e) {
                    var color = e.color.toHex();
                    $('#bg-color').val(color);

                    // 更新选中元素样式
                    var selectedElement = iframe.contentDocument.querySelector('.element-selected');
                    if (selectedElement) {
                        selectedElement.style.backgroundColor = color;
                    }
                });
            } catch (e) {
                console.log('颜色选择器初始化错误:', e);
            }
        });
    </script>
    <script src="../plugins/bootstrap/bootstrap.min.js"></script>
    <script src="js/adminjs.js?t=20231027"></script>
    <script src="../plugins/layer/layer.min.js"></script>
    <script src="../plugins/jedate/jedate.js"></script>
    <script>
        // 带回调的上传函数
        function uploadWithCallback(file_type, num_type, folder, backid) {
            // 保存当前输入框的值
            var originalValue = $('#' + backid).val();

            // 调用原始上传函数
            open_upload(file_type, num_type, folder, backid);

            // 设置定时器检查值变化
            var checkInterval = setInterval(function() {
                var currentValue = $('#' + backid).val();
                // 当值发生变化时触发input事件并清除定时器
                if (currentValue !== originalValue) {
                    $('#' + backid).trigger('input');
                    clearInterval(checkInterval);
                }
            }, 500);

            // 防止定时器无限运行
            setTimeout(function() {
                clearInterval(checkInterval);
            }, 30000); // 30秒后自动清除
        }
    </script>
    <script src="../plugins/visualeditor/visualeditor_config.js"></script>
    <script src="../plugins/visualeditor/visualeditor.js"></script>
    <script>
        // 初始化编辑器
        $(document).ready(function() {
                       // 监听模板选择变化
           $('#select_template').on('change', function() {
                var selectedValue = $(this).val();
                if (selectedValue.indexOf('act') !== -1) {
                    opennew(selectedValue);
                }else if (selectedValue) {
                    window.location.href = '?act=templateedit&folder=' + selectedValue;
                }
            });

            // 添加全局点击事件监听器，确保选项卡能正常工作
            $(document).on('click', '.cms-tab, [data-toggle="tab"]', function(e) {
                // 允许选项卡的默认点击行为
                return true;
            });

            // 获取预处理后的内容
            var initialContent = document.getElementById('filecontent').value;
            var iframe = document.getElementById('visual-editor');

            // 添加调试输出
            //console.log('Initial content length:', initialContent.length);
            //console.log('Initial content preview:', initialContent.substring(0, 100) + (initialContent.length > 100 ? '...' : ''));

            // 检查内容是否为空，如果为空则设置默认内容
            if (!initialContent || $.trim(initialContent) === '') {
                console.log('Warning: Initial content is empty, setting default content');
                initialContent = '<!DOCTYPE html>\n' +
                    '<html>\n' +
                    '<head>\n' +
                    '  <meta charset="utf-8">\n' +
                    '  <title>空白模板</title>\n' +
                    '</head>\n' +
                    '<body>\n' +
                    '  <div style="padding: 20px; text-align: center; color: #999;">\n' +
                    '    <h3>模板内容区域</h3>\n' +
                    '    <p>此区域将显示模板的HTML内容</p>\n' +
                    '    <p style="font-size: 12px;">(空白内容可能是由于模板解析问题或文件内容为空)</p>\n' +
                    '  </div>\n' +
                    '</body>\n' +
                    '</html>';
            }

            // 确保内容是完整的HTML文档
            if (!initialContent.toLowerCase().includes('<!doctype') && !initialContent.toLowerCase().includes('<html')) {
                // 如果不是完整HTML文档，添加基本结构
                initialContent = '<!DOCTYPE html>\n' +
                    '<html>\n' +
                    '<head>\n' +
                    '  <meta charset="utf-8">\n' +
                    '  <title>模板内容</title>\n' +
                    '</head>\n' +
                    '<body>\n' +
                    initialContent + '\n' +
                    '</body>\n' +
                    '</html>';
            }

            // 等待iframe加载完成
            iframe.onload = function() {
                try {
                    // 启用iframe内容可编辑
                    var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                    iframeDoc.body.contentEditable = true;
                    console.log('Iframe content made editable');

                    // 全局存储iframe引用，方便其他函数访问
                    window.editorIframe = iframe;

                    // 在iframe的window上添加捕获阶段的事件监听器，确保选项卡点击能被正确处理
                    iframe.contentWindow.addEventListener('click', function(e) {
                        // 检查是否点击了选项卡元素 - 更全面的检查
                        var target = e.target;
                        var isTabElement = false;
                        var tabElement = null;

                        // 检查元素自身或父元素是否包含选项卡相关类或属性
                        var checkElement = target;
                        while (checkElement && checkElement !== iframeDoc.body) {
                            // 扩大选项卡元素的识别范围
                            if (checkElement.classList.contains('cms-tab') ||
                                checkElement.getAttribute('data-toggle') === 'tab' ||
                                checkElement.classList.contains('nav-tabs') ||
                                checkElement.classList.contains('tab') ||
                                checkElement.classList.contains('tabs') ||
                                // 额外添加对行业解决方案选项卡的识别
                                checkElement.classList.contains('tab-item') ||
                                checkElement.classList.contains('tab-nav') ||
                                checkElement.classList.contains('tab-title') ||
                                checkElement.classList.contains('tab-heading')) {
                                isTabElement = true;
                                tabElement = checkElement;
                                break;
                            }
                            checkElement = checkElement.parentElement;
                        }

                        // 特殊处理：如果点击了选项卡区域内的任何元素，向上查找最可能的选项卡元素
                        if (!isTabElement && target.closest('.tab-content, .tabs-content') === null) {
                            // 查找可能的选项卡容器
                            var tabContainer = target.closest('.tab-container, .tabs-container, .tab-group');
                            if (tabContainer && tabContainer.querySelector('.tab-title, .tab-nav')) {
                                // 如果在选项卡容器内但不是内容区域，认为是在点击选项卡
                                var closestTabItem = target.closest('[data-target], [href^="#"], .tab-item, .tab-title');
                                if (closestTabItem) {
                                    isTabElement = true;
                                    tabElement = closestTabItem;
                                }
                            }
                        }

                        if (isTabElement) {
                            // 阻止事件冒泡到主页面，避免被编辑模式的事件处理程序捕获
                            e.stopPropagation();

                            // 确保我们有一个有效的选项卡元素
                            if (!tabElement) {
                                tabElement = target.closest('[data-target], [href^="#"], .tab-item, .tab-title');
                            }

                            // 手动实现选项卡切换功能
                            if (tabElement) {
                                // 确保选项卡元素获得焦点和选中状态
                                tabElement.classList.add('active');

                                // 移除兄弟选项卡的active类
                                var tabParent = tabElement.parentElement;
                                if (tabParent) {
                                    Array.from(tabParent.children).forEach(function(sibling) {
                                        if (sibling !== tabElement) {
                                            sibling.classList.remove('active');
                                        }
                                    });
                                }

                                // 全面的内容面板切换逻辑
                                // 1. 获取选项卡索引，这是最可靠的关联方式
                                var tabIndex = Array.from(tabParent.children).indexOf(tabElement);

                                // 2. 查找所有可能的内容面板容器和面板
                                var contentContainers = iframeDoc.querySelectorAll('.tab-content, .tabs-content, .tab-panels, .tab-pane-container');
                                var allPanels = [];

                                // 收集所有内容面板
                                contentContainers.forEach(function(container) {
                                    var panels = container.querySelectorAll('div, section');
                                    panels.forEach(function(panel) {
                                        allPanels.push(panel);
                                    });
                                });

                                // 如果没有找到内容容器，尝试直接查找所有可能的面板元素
                                if (allPanels.length === 0) {
                                    allPanels = iframeDoc.querySelectorAll('.tab-pane, .tab-panel, .tab-content-item, .tab-item-content, .tab-body, [data-tab]');
                                }

                                // 3. 隐藏所有内容面板
                                allPanels.forEach(function(panel) {
                                    panel.classList.remove('active');
                                    panel.classList.remove('show');
                                    panel.style.display = 'none';
                                });

                                // 4. 尝试通过多种方式查找并显示对应面板
                                var targetPanel = null;

                                // 方法1：通过索引匹配（最可靠）
                                if (tabIndex >= 0 && tabIndex < allPanels.length) {
                                    targetPanel = allPanels[tabIndex];
                                }

                                // 方法2：通过ID或data属性匹配
                                if (!targetPanel) {
                                    var tabId = tabElement.getAttribute('data-target') || tabElement.getAttribute('href');
                                    if (tabId && tabId !== '#') {
                                        // 尝试多种选择器格式
                                        var selectors = [];
                                        if (tabId.startsWith('#')) {
                                            selectors.push(tabId); // #id格式
                                            selectors.push('[id="' + tabId.substring(1) + '"]'); // [id="id"]格式
                                        }
                                        selectors.push('[data-tab="' + tabElement.id + '"]'); // 通过data-tab属性匹配
                                        selectors.push('[data-target="' + tabId + '"]'); // 通过data-target属性匹配

                                        // 尝试所有选择器
                                        for (var i = 0; i < selectors.length && !targetPanel; i++) {
                                            targetPanel = iframeDoc.querySelector(selectors[i]);
                                        }
                                    }
                                }

                                // 方法3：通过选项卡和面板的ID关联（常见模式）
                                if (!targetPanel && tabElement.id) {
                                    // 尝试常见的ID关联模式
                                    var panelIdPatterns = [
                                        tabElement.id + '-panel',
                                        tabElement.id + '-content',
                                        'panel-' + tabElement.id,
                                        'content-' + tabElement.id
                                    ];

                                    for (var i = 0; i < panelIdPatterns.length && !targetPanel; i++) {
                                        targetPanel = iframeDoc.getElementById(panelIdPatterns[i]);
                                    }
                                }

                                // 方法4：查找与选项卡同级的内容容器中的面板
                                if (!targetPanel) {
                                    var siblingContainer = tabParent.nextElementSibling;
                                    if (siblingContainer && (siblingContainer.classList.contains('tab-content') ||
                                            siblingContainer.classList.contains('tabs-content'))) {
                                        var siblingPanels = siblingContainer.children;
                                        if (tabIndex >= 0 && tabIndex < siblingPanels.length) {
                                            targetPanel = siblingPanels[tabIndex];
                                        }
                                    }
                                }

                                // 5. 如果找到目标面板，显示它
                                if (targetPanel) {
                                    // 添加所有常见的激活类
                                    targetPanel.classList.add('active');
                                    targetPanel.classList.add('show');
                                    // 确保显示
                                    targetPanel.style.display = 'block';
                                    targetPanel.style.visibility = 'visible';
                                    targetPanel.style.opacity = '1';

                                    console.log('显示目标面板:', targetPanel);
                                } else {
                                    console.log('未找到对应内容面板，索引:', tabIndex);
                                }

                                // 6. 触发可能需要的自定义事件
                                if (targetPanel) {
                                    var event = new CustomEvent('tabChanged', {
                                        detail: {
                                            tabElement: tabElement,
                                            panelElement: targetPanel,
                                            tabIndex: tabIndex
                                        },
                                        bubbles: true
                                    });
                                    targetPanel.dispatchEvent(event);
                                }
                            }

                            // 返回true表示事件已处理
                            return true;
                        }
                    }, true); // true表示在捕获阶段处理事件

                    // 向iframe中插入编辑相关的CSS样式 - 通过外部文件引入
                    var link = iframeDoc.createElement('link');
                    link.rel = 'stylesheet';
                    link.type = 'text/css';
                    link.href = 'css/editor-styles.css';
                    link.setAttribute('data-editor-style', 'true'); // 添加标记以便后续清理
                    iframeDoc.head.appendChild(link);
                    console.log('Editor CSS styles injected into iframe');

                    // 在iframe内初始化可编辑标签功能
                    setTimeout(() => {
                        // Pass the iframe document to the function
                        initEditableTagsInIframe(iframeDoc);
                    }, 500);
                } catch (error) {
                    console.error('Error making iframe content editable:', error);
                }
            };

            // 向iframe写入内容
            try {
                var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                iframeDoc.open();
                iframeDoc.write(initialContent);
                iframeDoc.close();
                console.log('Content written to iframe successfully');
            } catch (error) {
                console.error('Error writing to iframe:', error);
            }

            // 在iframe中初始化可编辑标签功能
            function initEditableTagsInIframe(doc) {
                // 处理iframe中的文本节点（段落、标题等）
                $(doc).find('p,li, h1, h2, h3, h4, h5, h6, span,img, div,a,td,button').each(function() {
                    var $element = $(this);

                    // 检查是否已经被处理过
                    if (!$element.attr('data-editable-tag')) $element.attr('data-editable-tag', 'true');

                    // 添加鼠标悬停时的边框效果
                    $element.hover(
                        function() {
                            // 只有未选中的元素添加悬停效果
                            if (!$(this).hasClass('element-selected')) {
                                $(this).addClass('element-hover');
                            }
                        },
                        function() {
                            // 只有未选中的元素移除悬停效果
                            if (!$(this).hasClass('element-selected')) {
                                $(this).removeClass('element-hover');
                            }
                        }
                    );

                    // 点击元素时显示操作按钮
                    $element.on('click', function(e) {
                        // 确保这不是由其他按钮触发的点击事件
                        if (!$(e.target).closest('.floating-element-actions').length) {
                            console.error('显示浮动按钮');
                            ensureElementActions($(this));
                        }
                    });

                    // 添加点击事件
                    $element.on('click', function(e) {
                        if (!$(e.target).is('.cms-tag, .cms-condition-tag, .cms-loop-tag')) {
                            console.error('选中元素');
                            e.stopPropagation();
                            // 点击事件已在handleElementSelection函数中处理
                        } else {
                            console.error('显示浮动按钮');
                            ensureElementActions($(this));
                        }
                    });

                });

                // 处理iframe中的图片元素
                $(doc).find('img').each(function() {
                    var $image = $(this);

                    // 跳过已有的CMS标签图片
                    if ($image.hasClass('cms-tag-image') || $image.attr('data-editable-tag')) {
                        return;
                    }

                    // 添加数据属性标记
                    $image.attr('data-editable-tag', 'true');

                    // 添加图片容器
                    if (!$image.parent().hasClass('image-container')) {
                        $image.wrap('<div class="image-container"></div>');
                        $image.parent().attr('data-editable-tag', 'true');
                    }

                    // 为图片容器添加鼠标悬停效果
                    $image.parent().hover(
                        function() {
                            if (!$(this).hasClass('element-selected')) {
                                $(this).addClass('element-hover');
                            }
                        },
                        function() {
                            if (!$(this).hasClass('element-selected')) {
                                $(this).removeClass('element-hover');
                            }
                        }
                    );

                    // 点击图片容器时显示操作按钮
                    $image.parent().on('click', function(e) {
                        // 确保这不是由其他按钮触发的点击事件
                        if (!$(e.target).closest('.floating-element-actions').length) {
                            ensureElementActions($(this));
                        }
                    });

                    // 选中图片容器时添加操作按钮
                    $image.parent().on('click', function() {
                        ensureElementActions($(this));
                    });

                    // 为图片容器添加点击事件
                    $image.parent().on('click', function(e) {
                        e.stopPropagation();
                        if (!$(this).hasClass('element-selected')) {
                            $(this).addClass('element-selected');
                        }
                    });
                });
            }

            // 保存对iframe的引用，用于后续操作
            window.editorIframe = iframe;

            // 监听iframe加载完成事件作为额外保障
            iframe.addEventListener('load', function() {
                console.log('iframe loaded, triggering editor initialization');
                initVisualEditor();
            });

            // 获取选中元素的辅助函数，带错误处理
            function getSelectedElement() {
                try {
                    var iframe = window.editorIframe || document.getElementById('visual-editor');
                    if (!iframe || !iframe.contentDocument) {
                        return null;
                    }
                    return iframe.contentDocument.querySelector('.element-selected');
                } catch (e) {
                    console.error('获取选中元素失败:', e);
                    return null;
                }
            }

            // 在iframe内容加载完成后初始化VisualEditor
            function initVisualEditor() {
                try {
                    // 检查iframe是否存在
                    var iframe = window.editorIframe || document.getElementById('visual-editor');
                    if (!iframe) {
                        console.error('编辑器iframe未找到');
                        setTimeout(initVisualEditor, 100);
                        return;
                    }

                    // 检查iframe内容是否已加载
                    if (!iframe.contentDocument || !iframe.contentDocument.body) {
                        console.warn('iframe content not loaded yet, retrying...');
                        setTimeout(initVisualEditor, 100);
                        return;
                    }

                    // 定义编辑器配置
                    var editorConfig = {
                        container: iframe.contentDocument.body,
                        historyEnabled: true,
                        autoSave: false,
                        onSave: function(content) {
                            // 这里可以添加保存前的处理逻辑
                            console.log('Editor content ready to save:', content);
                            // 保存操作将由保存按钮触发
                        }
                    };

                    // 使用工厂方法创建VisualEditor实例
                    window.templateEditor = VisualEditor.create(editorConfig);

                    // 初始化模板编辑器的核心功能
                    setTimeout(function() {
                        // 初始化可编辑标签
                        initEditableTagsInIframe(iframe.contentDocument);

                        // 设置元素选择处理
                        handleElementSelection();

                        // 设置属性面板监听器
                        setupPropertyListeners();

                        // 设置拖拽功能
                        setupDragAndDrop();
                        setupComponentsToggle();
                        console.log('Template editor fully initialized');
                    }, 100);

                } catch (error) {
                    console.error('VisualEditor initialization error:', error);
                    console.error('Error stack:', error.stack);

                    // 如果初始化失败，尝试重新初始化
                    setTimeout(initVisualEditor, 1000);
                }
            }

            // 开始初始化VisualEditor
            initVisualEditor();

            // 监听iframe内容变化
            function setupContentChangeListener() {
                try {
                    if (!iframe.contentWindow) {
                        setTimeout(setupContentChangeListener, 100);
                        return;
                    }

                    iframe.contentWindow.addEventListener('input', function() {
                        try {
                            // 获取iframe document
                            var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                            if (!iframeDoc) return;

                            setTimeout(() => {
                                initEditableTagsInIframe(iframeDoc);
                                // 如果templateEditor实例存在，触发内容变化事件
                                if (window.templateEditor && typeof window.templateEditor.onContentChange === 'function') {
                                    window.templateEditor.onContentChange();
                                    console.log('Editor content changed and history saved');
                                }
                            }, 300);
                        } catch (error) {
                            console.error('Error handling content change:', error);
                        }
                    });
                } catch (error) {
                    console.error('Failed to setup content change listener:', error);
                    setTimeout(setupContentChangeListener, 100);
                }
            }

            // 设置内容变化监听器
            setupContentChangeListener();

            // 添加页面卸载时的清理逻辑，防止内存泄漏
            window.addEventListener('beforeunload', function() {
                    if (window.templateEditor) {
                        try {
                            // 停止自动保存
                            window.templateEditor.stopAutoSave();
                            console.log('VisualEditor resources cleaned up');
                        } catch (error) {
                            console.error('Error cleaning up VisualEditor resources:', error);
                        }
                    }
                }


            );

            // 绑定撤销按钮点击事件
            $('#undo-button').on('click', function() {
                if (window.templateEditor && typeof window.templateEditor.undo === 'function') {
                    window.templateEditor.undo();
                }
            });

            // 绑定重做按钮点击事件
            $('#redo-button').on('click', function() {
                if (window.templateEditor && typeof window.templateEditor.redo === 'function') {
                    window.templateEditor.redo();
                }
            });


            // 绑定操作历史按钮点击事件
            $('#history-button').on('click', function() {
                if (window.templateEditor && typeof window.templateEditor.showHistory === 'function') {
                    // 调用编辑器内置的历史记录功能
                    window.templateEditor.showHistory();
                } else {
                    // 如果编辑器没有内置历史记录功能，实现一个简单的历史记录面板
                    // 检查是否有自动保存的历史记录
                    var historyList = [];

                    // 尝试从localStorage获取自动保存的历史记录
                    try {
                        var savedHistory = localStorage.getItem('editor_operation_history');
                        if (savedHistory) {
                            historyList = JSON.parse(savedHistory);
                        }
                    } catch (e) {
                        console.error('读取历史记录失败:', e);
                    }

                    if (historyList.length === 0) {
                        layer.msg('暂无操作历史记录');
                        return;
                    }

                    // 创建历史记录面板
                    var historyHtml = '<div class="history-panel" style="max-height: 400px; overflow-y: auto;">' +
                        '<h4 style="margin-top: 0;">操作历史记录</h4>' +
                        '<ul style="list-style: none; padding: 0;">';

                    // 添加历史记录项
                    for (var i = 0; i < historyList.length; i++) {
                        var item = historyList[i];
                        var timestamp = new Date(item.timestamp).toLocaleString();
                        historyHtml += '<li style="padding: 8px; border-bottom: 1px solid #eee; cursor: pointer;" ' +
                            'data-index="' + i + '">' +
                            '<strong>' + (item.action || '编辑') + '</strong> - ' + timestamp +
                            '</li>';
                    }

                    historyHtml += '</ul></div>';

                    // 使用layer弹出历史记录面板
                    layer.open({
                        type: 1,
                        title: '操作历史',
                        content: historyHtml,
                        area: ['400px', '500px'],
                        btn: ['关闭'],
                        success: function(layero) {
                            // 绑定历史记录项的点击事件，实现还原功能
                            layero.find('.history-panel li').on('click', function() {
                                var index = parseInt($(this).attr('data-index'));
                                var historyItem = historyList[index];

                                if (historyItem.content && window.templateEditor && typeof window.templateEditor.setContent === 'function') {
                                    if (layer.confirm('确定要还原到这个历史版本吗？当前未保存的更改将会丢失。')) {
                                        window.templateEditor.setContent(historyItem.content);
                                        layer.msg('已还原到所选历史版本');
                                    }
                                } else {
                                    layer.msg('无法还原此历史版本');
                                }
                            });
                        }
                    });
                }
            });

            // 监听编辑器内容变化，自动保存历史记录
            function setupAutoSaveHistory() {
                // 定义保存历史记录的函数
                function saveToHistory() {
                    try {
                        // 获取当前编辑器内容
                        var currentContent = '';
                        if (window.templateEditor && typeof window.templateEditor.getContent === 'function') {
                            currentContent = window.templateEditor.getContent();
                        } else if (iframe && iframe.contentDocument) {
                            currentContent = iframe.contentDocument.body.innerHTML;
                        }

                        if (!currentContent) return;

                        // 获取现有历史记录
                        var historyList = [];
                        var savedHistory = localStorage.getItem('editor_operation_history');
                        if (savedHistory) {
                            historyList = JSON.parse(savedHistory);
                        }

                        // 添加新的历史记录项
                        historyList.unshift({
                            timestamp: new Date().toISOString(),
                            content: currentContent,
                            action: '自动保存'
                        });

                        // 限制历史记录数量
                        if (historyList.length > 50) {
                            historyList = historyList.slice(0, 50);
                        }

                        // 保存回localStorage
                        localStorage.setItem('editor_operation_history', JSON.stringify(historyList));
                    } catch (e) {
                        console.error('保存历史记录失败:', e);
                    }
                }

                // 定期保存历史记录（每30秒）
                setInterval(saveToHistory, 30000);

                // 监听编辑器内容变化，实时保存
                if (iframe && iframe.contentDocument) {
                    iframe.contentDocument.body.addEventListener('input', function() {
                        // 防抖处理，避免过于频繁的保存
                        clearTimeout(window._historySaveTimer);
                        window._historySaveTimer = setTimeout(saveToHistory, 2000);
                    });
                }
            }

            // 初始化自动保存历史记录
            setupAutoSaveHistory();

            // 绑定保存按钮点击事件
            $('#save-button').on('click', function() {
                try {
                    // 获取内容的统一方法
                    function getContentForSaving() {
                        // 优先使用编辑器获取内容
                        if (window.templateEditor && typeof window.templateEditor.getContent === 'function') {
                            try {
                                var content = window.templateEditor.getContent();
                                console.log('编辑器保存成功');
                                return content;
                            } catch (editorError) {
                                console.error('编辑器保存失败：', editorError);
                            }
                        }

                        // 降级方案：直接从iframe获取内容
                        console.log('降级方案：直接从iframe获取内容');
                        var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                        return iframeDoc && iframeDoc.body ? iframeDoc.body.innerHTML : '';
                    }

                    // 获取并设置内容
                    var content = getContentForSaving();
                    if (content) {
                        $('#content').val(content);
                    }

                    // 触发表单提交
                    $('#contentform').submit();
                } catch (error) {
                    console.error(' 即使出错也尝试提交表单，确保基本功能可用:', error);
                    $('#contentform').submit();
                }
            });

            // 预览按钮点击事件
            $('#preview-button').on('click', function() {
                try {
                    // 保存编辑器状态（如果可用）
                    if (window.templateEditor && typeof window.templateEditor.saveHistory === 'function') {
                        saveEditorState();
                    }

                    // 获取iframe内容并预览
                    var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                    var content = iframeDoc.documentElement.outerHTML;

                    var previewWindow = window.open('', '_blank');
                    previewWindow.document.write(content);
                    previewWindow.document.close();
                } catch (error) {
                    alert('预览失败，请稍后重试');
                }
            });

            // 辅助函数：保存编辑器状态
            function saveEditorState() {
                try {
                    window.templateEditor.saveHistory();
                    console.log('Editor state saved before preview');
                } catch (editorError) {
                    console.error('Error saving editor history:', editorError);
                }
            }

            // 在保存前清理编辑辅助标签的函数
            function cleanContentForSaving() {
                var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;

                // 创建文档副本用于清理
                var tempDoc = document.implementation.createHTMLDocument('temp');
                tempDoc.body.innerHTML = iframeDoc.body.innerHTML;

                // 清理body内容
                $(tempDoc).find('[data-editable-tag]').removeAttr('data-editable-tag');
                $(tempDoc).find('.element-selected, .element-hover').removeClass('element-selected element-hover');

                // 处理图片容器 - 移除容器，保留图片
                $(tempDoc).find('.image-container').each(function() {
                    var img = $(this).find('img').detach();
                    $(this).replaceWith(img);
                });

                // 隐藏浮动操作按钮
                hideFloatingActions();

                // 清理head内容
                var cleanedHead = iframeDoc.head.cloneNode(true);
                $(cleanedHead).find('link[data-editor-style="true"]').remove();

                // 移除包含编辑器样式的style标签
                $(cleanedHead).find('style').filter(function() {
                    var text = $(this).text();
                    return text.indexOf('.element-selected') !== -1 || text.indexOf('.element-hover') !== -1;
                }).remove();

                // 返回清理后的完整HTML
                return '<!DOCTYPE html>\n' +
                    '<html>\n' +
                    '<head>\n' + cleanedHead.innerHTML + '\n</head>\n' +
                    '<body>\n' + tempDoc.body.innerHTML + '\n</body>\n' +
                    '</html>';
            }

            // 表单提交前，从iframe获取内容
            $('#contentform').on('submit', function(e) {
                e.preventDefault(); // 阻止默认提交行为
                try {
                    // 获取清理后的内容
                    var cleanedContent = cleanContentForSaving();
                    document.getElementById('filecontent').value = cleanedContent;
                    console.log('Cleaned content prepared for submission');
                } catch (error) {
                    console.error('Error cleaning content for submission:', error);
                    // 出错时使用原始获取方式作为备份
                    try {
                        var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                        var content = iframeDoc.documentElement.outerHTML;
                        document.getElementById('filecontent').value = content;
                        console.log('Fallback: Using raw content for submission');
                    } catch (fallbackError) {
                        console.error('Fallback also failed:', fallbackError);
                    }
                }
                // 手动处理提交
                $.post("save.php?act=editfile", $("#contentform").serialize(), function(result) {
                    if (result.return_code > 0) {
                        layer.msg(result.return_msg);
                    } else {
                        layer.msg(result.return_msg);
                    }
                }, 'json')
            });

            // 添加Ctrl+Enter快捷键保存
            $(document).on('keydown', function(e) {
                if (e.ctrlKey && e.keyCode === 13) { // Ctrl+Enter
                    $('#save-button').click();
                    return false;
                }
            });

            // 获取选中元素的辅助函数，添加错误处理
            function getSelectedElement() {
                try {
                    var iframe = window.editorIframe || document.getElementById('visual-editor');
                    if (!iframe || !iframe.contentDocument) {
                        return null;
                    }
                    var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                    return $(iframeDoc).find('.element-selected')[0] || null;
                } catch (error) {
                    console.error('获取选中元素失败:', error);
                    return null;
                }
            }

            // 元素选择处理 - 优化版本
            function handleElementSelection() {
                var iframe = window.editorIframe || document.getElementById('visual-editor');
                if (!iframe || !iframe.contentDocument) {
                    console.error('编辑器iframe未找到或未加载完成');
                    return;
                }
                var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                var lastSelectedElement = null;

                // 获取可编辑元素选择器
                var editableSelector = 'p, h1, h2, h3, h4, h5, h6, span, img,li,  button, a, table, tr, td, th, ul, ol, input, select, textarea, .cms-tag, div,.cms-condition-tag, .cms-loop-tag, header, footer, section, article, nav, aside, main';

                // 根据当前选中元素类型上传文件
                window.uploadForCurrentElement = function() {
                    var element = getElementToUpdate();
                    if (!element) return;

                    var tagName = element.tagName.toLowerCase();
                    var fileType = tagName === 'img' ? 'image' : 'video';
                    uploadWithCallback(fileType, '1', fileType, 'element-content');
                };

                // 选中元素的核心函数
                function selectElement(element) {
                    // 防止重复选择同一元素
                    if (lastSelectedElement === element) {
                        return;
                    }

                    try {
                        // 重置之前的选中状态
                        $(iframeDoc).find('.element-selected').removeClass('element-selected');
                        $(iframeDoc).find('.visual-component-selected').removeClass('visual-component-selected');

                        // 隐藏浮动操作按钮
                        if (typeof hideFloatingActions === 'function') {
                            hideFloatingActions();
                        }

                        // 设置新选中元素 - 确保全局可访问
                        lastSelectedElement = element;
                        $(element).addClass('element-selected visual-component-selected');
                        // 只设置全局变量，确保在所有函数中可访问
                        window.currentTargetElement = element;

                        // 显示操作按钮
                        if (typeof ensureElementActions === 'function') {
                            ensureElementActions($(element));
                        }

                        // 强制更新属性面板
                        if (typeof updatePropertyPanel === 'function') {
                            updatePropertyPanel(element);
                        }

                        // 确保属性面板显示
                        $('.properties-panel').show();
                        $('#no-selection-info').hide();
                        $('#element-properties, #style-properties').show();

                        // 显示属性面板（如果隐藏）
                        if (!$('.properties-panel').hasClass('show')) {
                            $('.properties-panel').addClass('show');
                        }

                        // 触发内容变化事件
                        $(document).trigger('contentchanged');
                        console.log('元素已选中并更新属性面板:', element.tagName);
                    } catch (e) {
                        console.error('选择元素并更新属性面板时出错:', e);
                    }
                }

                // 移除所有现有的点击事件，避免重复绑定
                $(iframeDoc).off('click', '*');
                $(iframeDoc).find('*').off('click');

                // 为iframe中的所有元素添加点击事件
                $(iframeDoc).on('click', '*', function(e) {
                    // 阻止默认行为和事件冒泡
                    e.preventDefault();
                    e.stopPropagation();

                    // 获取被点击的元素
                    var clickedElement = e.target;

                    // 检查是否点击的是选项卡相关元素
                    var isTabElement = $(clickedElement).closest('.cms-tab, [data-toggle="tab"], .nav-tabs, .tab, .tabs').length > 0;

                    // 如果是选项卡元素，执行选项卡切换功能
                    if (isTabElement) {
                        // 手动触发选项卡切换功能
                        var tabId = $(clickedElement).attr('data-target') || $(clickedElement).attr('href');
                        if (tabId) {
                            $(iframeDoc).find(tabId).addClass('active').siblings().removeClass('active');
                            $(clickedElement).addClass('active').siblings().removeClass('active');
                        }
                    }

                    // 检查是否是可编辑元素
                    var isEditableElement = $(clickedElement).is(editableSelector);

                    // 如果不是直接可编辑的元素，尝试找到最近的可编辑父元素
                    if (!isEditableElement) {
                        var parentEditable = $(clickedElement).closest(editableSelector);
                        if (parentEditable.length) {
                            clickedElement = parentEditable[0];
                            isEditableElement = true;
                        } else {
                            // 清除选择状态，但仍然显示属性面板（只是不更新内容）
                            $(iframeDoc).find('.element-selected').removeClass('element-selected');
                            $(iframeDoc).find('.visual-component-selected').removeClass('visual-component-selected');
                            if (typeof hideFloatingActions === 'function') {
                                hideFloatingActions();
                            }
                            lastSelectedElement = null;
                            return;
                        }
                    }

                    // 确保选中的是visual-component，如果不是则尝试找到其所属的visual-component
                    // 但保留img元素的直接选择
                    if (clickedElement.tagName.toLowerCase() !== 'img') {
                        var visualComponent = $(clickedElement).closest('.visual-component');
                        if (visualComponent.length) {
                            clickedElement = visualComponent[0];
                        }
                    }

                    // 调用选择元素函数","}]}}}
                    e.stopPropagation();

                    // 选中元素并更新属性面板
                    try {
                        selectElement(clickedElement);
                        // 直接调用updatePropertyPanel确保属性面板更新
                        if (typeof updatePropertyPanel === 'function') {
                            updatePropertyPanel(clickedElement);
                        }
                    } catch (e) {
                        console.error('选择元素时出错:', e);
                    }

                    // 辅助函数：更新属性面板内容 - 增强版
                    function updatePropertyPanel(element) {
                        try {
                            console.log('更新属性面板开始，元素:', element ? (element.tagName || '未知') : 'null');

                            // 确保属性面板容器存在
                            var propertiesPanel = $('.properties-panel');
                            if (!propertiesPanel.length) {
                                console.warn('属性面板容器不存在');
                                return;
                            }

                            // 确保属性面板显示
                            propertiesPanel.show();

                            // 获取各种面板元素的引用
                            var elementPropsPanel = $('#element-properties');
                            var stylePropsPanel = $('#style-properties');
                            var noSelectionInfo = $('#no-selection-info');
                            var propertiesHandle = $('#properties-handle');

                            if (!element || element.nodeType !== 1) {
                                // 无元素选中时的处理
                                console.log('未选中任何有效元素，显示空状态');
                                if (elementPropsPanel.length) elementPropsPanel.hide();
                                if (stylePropsPanel.length) stylePropsPanel.hide();
                                if (noSelectionInfo.length) noSelectionInfo.show();
                                return;
                            }

                            // 显示元素属性和样式属性面板
                            if (elementPropsPanel.length) elementPropsPanel.show();
                            if (stylePropsPanel.length) stylePropsPanel.show();
                            if (noSelectionInfo.length) noSelectionInfo.hide();

                            // 确保属性面板正确显示
                            propertiesPanel.addClass('show');
                            // 基本属性 - 确保字段存在再设置值
                            try {
                                if ($('#element-id').length) {
                                    $('#element-id').val((element.id || '').trim());
                                    console.log('设置元素ID:', element.id || '空');
                                }
                                if ($('#element-class').length) {
                                    $('#element-class').val((element.className || '').trim());
                                    console.log('设置元素类名:', element.className || '空');
                                }
                            } catch (e) {
                                console.error('设置基本属性失败:', e);
                            }

                            // 获取元素类型
                            var tagName = (element.tagName || '').toLowerCase();
                            console.log('处理元素类型:', tagName);

                            // 根据元素类型设置内容属性并动态调整UI
                            var contentField = $('#element-content');
                            var contentLabel = $('#element-content-label');
                            $('#image-upload-btn').hide();
                            // 只有当内容字段存在时才进行操作
                            if (contentField.length && contentLabel.length) {
                                // 动态调整内容字段的类型和属性
                                if (tagName === 'img' || tagName === 'video') {
                                    contentField.attr('placeholder', tagName === 'img' ? '请输入图片URL' : '请输入视频URL');
                                    contentLabel.text(tagName === 'img' ? '图片URL' : '视频URL');
                                    // 确保src属性存在且为字符串
                                    if (tagName === 'video') {
                                        // 对于video元素，获取第一个source元素的src
                                        var source = element.querySelector('source');
                                        contentField.val(source ? source.src : '');
                                        console.log('设置视频URL:', source ? source.src : '空');
                                    } else {
                                        contentField.val(element.src || '');
                                        console.log('设置图片URL:', element.src || '空');
                                    }
                                    // 显示上传按钮
                                    $('#image-upload-btn').show();
                                } else if (['input', 'textarea', 'select'].includes(tagName)) {
                                    contentField.attr('placeholder', '请输入表单值');
                                    contentLabel.text('表单值');
                                    contentField.val(element.value || '');
                                    console.log('设置表单值:', element.value || '空');
                                } else if (tagName === 'a') {
                                    contentField.attr('placeholder', '请输入链接文本内容');
                                    contentLabel.text('链接文本');
                                    contentField.val(element.textContent.trim());
                                    console.log('设置链接文本:', element.textContent.trim() || '空');
                                } else {
                                    contentField.attr('placeholder', '请输入元素内容');
                                    contentLabel.text('内容');
                                    contentField.val(element.innerHTML);
                                    console.log('设置元素内容:', 'HTML内容');
                                }
                            }

                            // 获取并设置样式属性 - 增强版，多方案回退避免跨域问题
                            try {
                                // 1. 尝试获取计算样式
                                var computedStyle = null;
                                try {
                                    // 先尝试直接的getComputedStyle
                                    computedStyle = window.getComputedStyle(element);
                                } catch (e1) {
                                    console.warn('直接获取计算样式失败:', e1);
                                    // 2. 尝试使用element.style作为回退
                                    if (element.style) {
                                        console.log('使用element.style作为回退');
                                        computedStyle = element.style;
                                    }
                                }

                                // 为每个样式属性单独设置，带回退值和存在性检查
                                // 设置文字颜色
                                if ($('#text-color').length) {
                                    var color = '#000000'; // 默认黑色
                                    try {
                                        color = computedStyle ? computedStyle.color : element.style.color || color;
                                    } catch (e) {}
                                    $('#text-color').val(color);
                                    console.log('设置文字颜色:', color);
                                }

                                // 设置背景颜色
                                if ($('#bg-color').length) {
                                    var bgColor = ''; // 默认透明
                                    try {
                                        if (computedStyle) {
                                            bgColor = computedStyle.backgroundColor;
                                            // 处理透明背景
                                            if (bgColor === 'rgba(0, 0, 0, 0)' || bgColor === 'transparent') {
                                                bgColor = '';
                                            }
                                        }
                                    } catch (e) {}
                                    $('#bg-color').val(bgColor);
                                    console.log('设置背景颜色:', bgColor || '透明');
                                }

                                // 设置字体大小
                                if ($('#font-size').length) {
                                    var fontSize = '16px'; // 默认字体大小
                                    try {
                                        fontSize = computedStyle ? computedStyle.fontSize : element.style.fontSize || fontSize;
                                    } catch (e) {}
                                    $('#font-size').val(fontSize);
                                    console.log('设置字体大小:', fontSize);
                                }

                                // 设置背景图片
                                if ($('#bg-image').length) {
                                    var bgImage = '';
                                    try {
                                        if (computedStyle && computedStyle.backgroundImage) {
                                            bgImage = computedStyle.backgroundImage;
                                            // 提取URL中的内容
                                            var urlMatch = bgImage.match(/^url\(['"]?(.+?)['"]?\)$/);
                                            if (urlMatch && urlMatch[1]) {
                                                bgImage = urlMatch[1];
                                            } else if (bgImage === 'none') {
                                                bgImage = '';
                                            }
                                        }
                                    } catch (e) {}
                                    $('#bg-image').val(bgImage);
                                    console.log('设置背景图片:', bgImage || '无');
                                }

                                // 设置字体
                                if ($('#font-family').length) {
                                    var fontFamily = 'Arial, sans-serif'; // 默认字体
                                    try {
                                        fontFamily = computedStyle ? computedStyle.fontFamily : element.style.fontFamily || fontFamily;
                                    } catch (e) {}
                                    $('#font-family').val(fontFamily);
                                    console.log('设置字体:', fontFamily);
                                }

                                // 设置行高
                                if ($('#line-height').length) {
                                    var lineHeight = '1.5'; // 默认行高
                                    try {
                                        lineHeight = computedStyle ? computedStyle.lineHeight : element.style.lineHeight || lineHeight;
                                    } catch (e) {}
                                    $('#line-height').val(lineHeight);
                                    console.log('设置行高:', lineHeight);
                                }

                                // 设置文字对齐按钮状态
                                if ($('#text-align-group').length) {
                                    $('#text-align-group .property-button').removeClass('active');
                                    var textAlign = 'left'; // 默认左对齐
                                    try {
                                        if (computedStyle) {
                                            textAlign = computedStyle.textAlign || textAlign;
                                            // 处理可能的对齐值差异
                                            try {
                                                if (textAlign === 'start' && window.getComputedStyle && window.getComputedStyle(element).direction === 'ltr') {
                                                    textAlign = 'left';
                                                } else if (textAlign === 'end' && window.getComputedStyle && window.getComputedStyle(element).direction === 'ltr') {
                                                    textAlign = 'right';
                                                }
                                            } catch (e) {
                                                // 如果direction检查失败，保持原textAlign值
                                            }
                                        }
                                    } catch (e) {}

                                    // 确保只添加到存在的按钮
                                    var alignButton = $('#text-align-group .property-button[data-align="' + textAlign + '"]');
                                    if (alignButton.length) {
                                        alignButton.addClass('active');
                                    }
                                    console.log('设置文字对齐:', textAlign);
                                }
                            } catch (e) {
                                console.error('处理样式属性时出错:', e);
                            }

                            // 设置链接URL（处理链接元素）
                            $('#link-url').val(tagName === 'a' ? element.href : '');

                            // 动态显示/隐藏特定字段组
                            if (tagName === 'img') {
                                // 图片元素显示特定选项
                                $('#text-properties-group').hide();
                                $('#link-properties-group').hide();
                                $('#bg-properties-group').hide();
                            } else if (['input', 'textarea', 'select'].includes(tagName)) {
                                // 表单元素
                                $('#text-properties-group').hide();
                                $('#link-properties-group').hide();
                                $('#bg-properties-group').hide();
                            } else if (tagName === 'a') {
                                // 链接元素
                                $('#text-properties-group').show();
                                $('#link-properties-group').show();
                                $('#bg-properties-group').show();
                            } else {
                                // 普通文本元素
                                $('#text-properties-group').show();
                                $('#link-properties-group').hide();
                                $('#bg-properties-group').show();
                            }

                            // 确保基本属性组始终显示
                            $('#basic-properties').show();

                            // 更新属性面板标题，显示当前选中元素的类型
                            var elementName = getElementDisplayName(element);
                            $('.properties-panel h3').text('属性设置 - ' + elementName);
                            $('#properties-panel-title').text(elementName + ' 属性');

                            // 强制重绘属性面板
                            $('#properties-panel').css('opacity', '0.99').delay(10).css('opacity', '1');

                            // 触发属性面板更新事件
                            $(document).trigger('propertypanelupdated', [element]);

                            // 添加简单的动画效果，提高用户体验
                            $('.properties-panel').addClass('updating').delay(100).removeClass('updating');

                            console.log('属性面板更新完成，元素类型:', tagName);
                        } catch (e) {
                            console.error('更新属性面板时出错:', e);
                        }
                    }

                    // 辅助函数：获取元素的友好显示名称
                    function getElementDisplayName(element) {
                        var tagName = element.tagName.toLowerCase();
                        var displayNames = {
                            'p': '段落',
                            'h1': '标题1',
                            'h2': '标题2',
                            'h3': '标题3',
                            'h4': '标题4',
                            'h5': '标题5',
                            'h6': '标题6',
                            'div': '容器',
                            'span': '文本',
                            'img': '图片',
                            'a': '链接',
                            'button': '按钮',
                            'table': '表格',
                            'tr': '表格行',
                            'td': '表格单元格',
                            'th': '表头单元格',
                            'ul': '无序列表',
                            'ol': '有序列表',
                            'li': '列表项',
                            'input': '输入框',
                            'select': '下拉选择',
                            'textarea': '文本域',
                            'header': '页眉',
                            'footer': '页脚',
                            'section': '区块',
                            'article': '文章',
                            'nav': '导航',
                            'aside': '侧边栏',
                            'main': '主内容'
                        };

                        // 检查是否是CMS特殊标签
                        if ($(element).hasClass('cms-tag')) return 'CMS标签';
                        if ($(element).hasClass('cms-condition-tag')) return '条件标签';
                        if ($(element).hasClass('cms-loop-tag')) return '循环标签';

                        // 如果元素有ID或class，添加到显示名称中
                        var additionalInfo = '';
                        if (element.id) {
                            additionalInfo = '#' + element.id;
                        } else if (element.className && element.className.trim()) {
                            var classes = element.className.trim().split(/\s+/);
                            additionalInfo = '.' + classes[0];
                        }

                        return (displayNames[tagName] || tagName) + additionalInfo;
                    }

                    // 移除原来的元素选择处理函数，避免重复绑定
                    $(iframeDoc).find('p, h1, h2, h3, h4, h5, h6,  img, button, a,div, li').off('click');

                    // 重新获取iframeDoc变量，确保在这个作用域中可用
                    var iframe = window.editorIframe || document.getElementById('visual-editor');
                    var iframeDoc = iframe ? (iframe.contentDocument || iframe.contentWindow.document) : null;
                    if (!iframeDoc) return;

                })
            }

            // 监听属性变化并应用到选中元素
            function setupPropertyListeners() {
                //console.error('监听属性变化并应用到选中元素');            // 获取iframe元素
                var iframe = window.editorIframe || document.getElementById('visual-editor');
                if (!iframe) {
                    console.error('无法找到编辑器iframe');
                    return;
                }

                // 为属性面板Tab标签绑定点击事件
                $('.property-tab').off('click').on('click', function(e) {
                    //console.error('点击属性面板tab');
                    try {
                        var tab = $(this).data('tab');

                        // 切换tab激活状态
                        $('.property-tab').removeClass('active');
                        $(this).addClass('active');

                        // 切换内容显示
                        $('.tab-content').hide();
                        $('#' + tab + '-properties').show();

                        // 只防止事件冒泡到document，避免触发隐藏功能，但不阻止默认行为
                        e.stopPropagation();
                    } catch (error) {
                        console.error('切换属性面板tab时出错:', error);
                    }
                });

                // 确保iframe已加载完成
                if (!iframe.contentDocument || !iframe.contentWindow) {
                    console.error('iframe内容未加载完成');
                    return;
                }

                // 定义iframeDoc变量，确保在整个函数中都可用
                var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;

                // 移除所有可能存在的旧事件监听器，避免重复绑定
                $('#element-id, #element-class, #element-content, #text-color, #bg-color, #font-size, #bg-image, #link-url, #line-height').off('input');
                $('#font-family').off('change');
                $('#text-align-group .property-button').off('click');

                // 获取选中元素的辅助函数 - 优化版本
                function getElementToUpdate() {
                    try {
                        // 1. 优先使用window.currentTargetElement全局变量
                        if (typeof window.currentTargetElement !== 'undefined' && window.currentTargetElement && window.currentTargetElement.nodeType === 1) {
                            console.log('使用window.currentTargetElement获取选中元素');
                            return window.currentTargetElement;
                        }

                        // 2. 备用方案1：直接从iframe中查找所有可能的选中类名
                        var possibleSelectors = ['.element-selected', '.selected-element', '[data-selected="true"]'];
                        for (var i = 0; i < possibleSelectors.length; i++) {
                            var selectedElement = iframeDoc.querySelector(possibleSelectors[i]);
                            if (selectedElement) {
                                console.log('使用选择器', possibleSelectors[i], '获取选中元素');
                                return selectedElement;
                            }
                        }

                        // 3. 备用方案2：使用全局getSelectedElement函数
                        if (window.getSelectedElement) {
                            var globalSelected = window.getSelectedElement();
                            if (globalSelected && globalSelected.nodeType === 1) {
                                console.log('使用全局getSelectedElement获取选中元素');
                                return globalSelected;
                            }
                        }

                        // 4. 最后检查：直接查找可能的选中元素
                        var activeElements = iframeDoc.querySelectorAll('[style*="outline"], [style*="border"]');
                        if (activeElements.length > 0) {
                            console.log('找到可能的选中元素，使用第一个');
                            return activeElements[0];
                        }

                        console.log('未找到选中元素');
                        return null;
                    } catch (e) {
                        console.error('获取选中元素失败:', e);
                        return null;
                    }
                }

                // 创建通用属性更新函数，减少重复代码
                function updateElementProperty(element, property, value, isStyle = false, customHandler = null) {
                    if (!element) {
                        console.error('尝试更新不存在的元素属性');
                        return false;
                    }

                    try {
                        if (customHandler) {
                            customHandler(element, value);
                            console.log('使用自定义处理器更新属性:', property, '值:', value);
                        } else if (isStyle) {
                            // 移除严格的ownerDocument检查，确保样式能正常更新
                            element.style[property] = value;
                            console.log('更新样式属性:', property, '值:', value);
                        } else if (property) {
                            // 仅当属性名不为空时更新
                            element[property] = value;
                            console.log('更新元素属性:', property, '值:', value);
                        }

                        // 触发内容变化事件
                        if (iframeDoc) {
                            $(iframeDoc).trigger('contentchanged');
                            console.log('触发内容变化事件');
                        }
                        return true;
                    } catch (err) {
                        console.error('更新元素属性时出错:', err);
                        return false;
                    }
                }

                // ID属性
                $('#element-id').on('input', function() {
                    updateElementProperty(getElementToUpdate(), 'id', $(this).val());
                });

                // 类名属性
                $('#element-class').on('input', function() {
                    updateElementProperty(getElementToUpdate(), 'className', $(this).val());
                });

                // 内容属性
                $('#element-content').on('input', function() {
                    updateElementProperty(getElementToUpdate(), '', $(this).val(), false, function(element, value) {
                        var tagName = element.tagName.toLowerCase();
                        if (tagName === 'img') {
                            element.src = value;
                        } else if (tagName === 'video') {
                            // 对于video元素，更新第一个source元素的src
                            var source = element.querySelector('source');
                            if (source) {
                                source.src = value;
                            }
                        } else {
                            element.innerHTML = value;
                        }
                    });
                });

                // 文字颜色
                $('#text-color').on('input', function() {
                    updateElementProperty(getElementToUpdate(), 'color', $(this).val(), true);
                });

                // 背景颜色
                $('#bg-color').on('input', function() {
                    updateElementProperty(getElementToUpdate(), 'backgroundColor', $(this).val(), true);
                });

                // 字体大小
                $('#font-size').on('input', function() {
                    updateElementProperty(getElementToUpdate(), 'fontSize', $(this).val(), true);
                });

                // 背景图
                $('#bg-image').on('input', function() {
                    updateElementProperty(getElementToUpdate(), 'backgroundImage', $(this).val() ? 'url(' + $(this).val() + ')' : 'none', true);
                });

                // 字体
                $('#font-family').on('change', function() {
                    updateElementProperty(getElementToUpdate(), 'fontFamily', $(this).val() || '', true);
                });

                // 文字对齐
                $('#text-align-group .property-button').on('click', function() {
                    var button = $(this);
                    var align = button.data('align');

                    // 先更新按钮选中状态
                    $('#text-align-group .property-button').removeClass('active');
                    button.addClass('active');

                    // 然后更新元素属性
                    updateElementProperty(getElementToUpdate(), 'textAlign', align, true);
                });

                // 行间距
                $('#line-height').on('input', function() {
                    updateElementProperty(getElementToUpdate(), 'lineHeight', $(this).val(), true);
                });

                // 链接URL - 增强版处理逻辑
                $('#link-url').on('input', function() {
                    try {
                        var url = $(this).val();
                        console.log('处理链接URL:', url);

                        var selected = getElementToUpdate();
                        if (!selected) {
                            console.warn('没有选中的元素可更新链接');
                            return;
                        }

                        var selectedTagName = selected.tagName.toLowerCase();
                        console.log('当前选中元素标签:', selectedTagName);

                        if (selectedTagName === 'a') {
                            // 处理现有链接元素
                            if (url) {
                                // 更新链接URL
                                console.log('更新现有链接href:', url);
                                selected.href = url;
                            } else {
                                // 清空URL时，将链接转换回普通元素
                                console.log('将链接转换为普通元素');
                                try {
                                    // 获取链接的第一个子元素，如果是文本节点则处理
                                    if (selected.childNodes.length > 0) {
                                        var tempSpan = iframeDoc.createElement('span');
                                        // 复制内容
                                        while (selected.firstChild) {
                                            tempSpan.appendChild(selected.firstChild);
                                        }
                                        // 复制样式
                                        var computedStyle = window.getComputedStyle(selected);
                                        for (var i = 0; i < computedStyle.length; i++) {
                                            var prop = computedStyle[i];
                                            tempSpan.style[prop] = computedStyle[prop];
                                        }
                                        // 复制其他属性
                                        if (selected.id) tempSpan.id = selected.id;
                                        if (selected.className) tempSpan.className = selected.className;

                                        // 替换元素
                                        selected.parentNode.replaceChild(tempSpan, selected);

                                        // 更新选中状态
                                        $(tempSpan).addClass('element-selected');
                                        window.currentTargetElement = tempSpan;
                                    }
                                } catch (e) {
                                    console.error('将链接转换为普通元素失败:', e);
                                }
                            }
                        } else {
                            // 处理非链接元素
                            if (url) {
                                console.log('将普通元素转换为链接');
                                try {
                                    var a = iframeDoc.createElement('a');
                                    a.href = url;

                                    // 复制内容
                                    while (selected.firstChild) {
                                        a.appendChild(selected.firstChild);
                                    }

                                    // 复制样式
                                    var computedStyle = window.getComputedStyle(selected);
                                    for (var i = 0; i < computedStyle.length; i++) {
                                        var prop = computedStyle[i];
                                        a.style[prop] = computedStyle[prop];
                                    }

                                    // 复制其他属性
                                    if (selected.id) a.id = selected.id;
                                    if (selected.className) a.className = selected.className;

                                    // 替换元素
                                    selected.parentNode.replaceChild(a, selected);

                                    // 更新选中状态
                                    $(a).addClass('element-selected');
                                    window.currentTargetElement = a;
                                } catch (e) {
                                    console.error('创建链接元素失败:', e);
                                }
                            }
                            // 如果URL为空且不是链接元素，则无需处理
                        }

                        // 触发内容变化事件
                        $(iframeDoc).trigger('contentchanged');
                        console.log('链接URL处理完成，触发内容变化事件');

                        // 确保属性面板同步更新
                        if (typeof updatePropertyPanel === 'function') {
                            setTimeout(function() {
                                updatePropertyPanel();
                            }, 100);
                        }
                    } catch (error) {
                        console.error('处理链接URL时发生错误:', error);
                    }
                });

                // iframeDoc变量已在函数开头定义
            }


            // 设置组件拖拽功能
            function setupDragAndDrop() {
                try {
                    // 添加拖拽相关的CSS样式到iframe中
                    var styleId = 'drag-drop-styles';
                    var iframe = window.editorIframe || document.getElementById('visual-editor');

                    if (iframe && iframe.contentDocument) {
                        var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                        if (!iframeDoc.getElementById(styleId)) {
                            var style = iframeDoc.createElement('style');
                            style.id = styleId;
                            style.textContent = `
                            /* 拖拽时的样式 */
                            .visual-component {
                                position: relative;
                                min-height: 20px;
                                cursor: move;
                            }
                            .visual-component.drag-over {
                                background-color: #f0f7ff;
                                border: 2px dashed #428bca;
                            }
                            .visual-component.dragging {
                                opacity: 0.5;
                                cursor: move;
                            }
                            .visual-component.element-selected {
                                outline: 2px solid #428bca;
                                outline-offset: 2px;
                            }
                        `;
                            iframeDoc.head.appendChild(style);
                        }
                    }

                    // 优先使用window.editorIframe，确保与visualeditor.js协调
                    if (!iframe || !iframe.contentDocument) {
                        console.error('编辑器iframe未找到或未加载完成');
                        return;
                    }

                    iframeDoc = iframe.contentDocument || iframe.contentWindow.document;

                    // 完全禁用visualeditor.js的拖拽功能，避免冲突
                    if (window.templateEditor && typeof window.templateEditor.config.behavior !== 'undefined') {
                        window.templateEditor.config.behavior.enableDrag = false;
                    }

                    // 移除所有可能存在的旧拖拽相关事件监听器
                    // 先移除iframe中的所有拖拽事件监听器
                    $(iframeDoc).off('dragover dragenter dragleave drop dragstart drag dragend');
                    // 再移除组件面板中的拖拽事件
                    $('.components-panel').find('.component-item').off('dragstart drag dragend');

                    // 为组件面板中的项目添加拖拽事件
                    $('.component-item').on('dragstart', function(e) {
                        // 只设置component类型数据，不设置其他数据避免冲突
                        e.originalEvent.dataTransfer.setData('component-type', $(this).data('component'));
                        e.originalEvent.dataTransfer.effectAllowed = 'copy';
                        // 使用自定义数据类型，避免与系统默认的'text/plain'冲突
                        e.originalEvent.dataTransfer.setData('application/x-component-library', 'true');

                        // 阻止事件冒泡，防止其他拖拽处理器干扰
                        e.stopPropagation();
                    });

                    // 为编辑区域添加拖放事件 - 直接绑定到document而不是body
                    $(iframeDoc).on('dragover', function(e) {
                        e.preventDefault();
                        e.stopPropagation();

                        // 检查是否是从组件库拖拽或编辑器内部拖拽
                        if (window.draggedElement || e.originalEvent.dataTransfer.getData('application/x-component-library') === 'true') {
                            e.originalEvent.dataTransfer.dropEffect = 'copy';
                        }
                    });

                    $(iframeDoc).on('dragenter', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                    });

                    $(iframeDoc).on('dragleave', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                    });

                    // 为visual-component元素添加dragover事件，支持内部拖拽排序
                    $(iframeDoc).on('dragover', '.visual-component', function(e) {
                        if (window.draggedElement && window.draggedElement !== this) {
                            e.preventDefault();
                            e.stopPropagation();
                            e.originalEvent.dataTransfer.dropEffect = 'move';
                            $(this).addClass('drag-over');
                        }
                    });

                    $(iframeDoc).on('dragleave', '.visual-component', function(e) {
                        $(this).removeClass('drag-over');
                    });

                    $(iframeDoc).on('drop', '.visual-component', function(e) {
                        if (window.draggedElement && window.draggedElement !== this) {
                            e.preventDefault();
                            e.stopPropagation();

                            $(this).removeClass('drag-over');

                            // 计算鼠标位置决定插入方式
                            var rect = this.getBoundingClientRect();
                            var dropPosition = e.clientY - rect.top;
                            var midPoint = rect.height / 2;

                            var parent = this.parentNode;

                            if (dropPosition > midPoint) {
                                // 如果鼠标在元素下半部分，插入到目标元素之后
                                if (this.nextSibling) {
                                    parent.insertBefore(window.draggedElement, this.nextSibling);
                                } else {
                                    parent.appendChild(window.draggedElement);
                                }
                            } else {
                                // 如果鼠标在元素上半部分，插入到目标元素之前
                                parent.insertBefore(window.draggedElement, this);
                            }

                            // 触发内容变化事件
                            $(iframeDoc).trigger('contentchanged');
                            if (window.templateEditor && typeof window.templateEditor.onContentChange === 'function') {
                                window.templateEditor.onContentChange();
                            }
                        }
                    });

                    $(iframeDoc).on('drop', function(e) {
                        // 完全阻止默认行为和事件传播
                        e.preventDefault();
                        e.stopPropagation();

                        // 尝试获取自定义数据类型
                        var isComponentLibrary = e.originalEvent.dataTransfer.getData('application/x-component-library') === 'true';
                        var componentType = e.originalEvent.dataTransfer.getData('component-type');

                        // 只有从组件库拖拽且有有效的组件类型时才处理
                        if (!isComponentLibrary || !componentType) {
                            return; // 忽略其他拖拽操作
                        }

                        // 根据组件类型创建对应的HTML元素
                        var newElement;
                        try {
                            // 创建带有visual-component类的包装div，与visualeditor.js保持一致
                            var wrapper = iframeDoc.createElement('div');
                            wrapper.className = 'visual-component';
                            wrapper.setAttribute('data-type', componentType);

                            // 根据组件类型创建内部内容
                            switch (componentType) {
                                case 'text':
                                    newElement = iframeDoc.createElement('p');
                                    newElement.innerHTML = '点击编辑文本内容';
                                    newElement.style.margin = '10px 0';
                                    wrapper.appendChild(newElement);
                                    break;
                                case 'heading':
                                    newElement = iframeDoc.createElement('h2');
                                    newElement.innerHTML = '标题文本';
                                    newElement.style.margin = '20px 0 10px';
                                    wrapper.appendChild(newElement);
                                    break;
                                case 'image':
                                    newElement = iframeDoc.createElement('img');
                                    newElement.src = '/plugins/visualeditor/images/new.png';
                                    newElement.alt = '图片';
                                    newElement.style.maxWidth = '100%';
                                    wrapper.appendChild(newElement);
                                    break;
                                case 'button':
                                    newElement = iframeDoc.createElement('button');
                                    newElement.innerHTML = '按钮';
                                    newElement.style.padding = '8px 16px';
                                    newElement.style.backgroundColor = '#1ab394';
                                    newElement.style.color = 'white';
                                    newElement.style.border = 'none';
                                    newElement.style.borderRadius = '4px';
                                    wrapper.appendChild(newElement);
                                    break;
                                case 'divider':
                                    newElement = iframeDoc.createElement('hr');
                                    newElement.style.margin = '20px 0';
                                    wrapper.appendChild(newElement);
                                    break;
                            }

                            // 获取鼠标位置下的元素
                            var dropTarget = e.target;

                            // 支持拖拽到任意位置：找到最接近的可插入目标
                            while (dropTarget !== iframeDoc.body &&
                                (dropTarget.nodeType !== 1 ||
                                    dropTarget.tagName.toLowerCase() === 'script' ||
                                    dropTarget.tagName.toLowerCase() === 'style')) {
                                dropTarget = dropTarget.parentNode;
                            }

                            // 根据鼠标位置决定插入方式
                            if (dropTarget === iframeDoc.body) {
                                // 如果目标是body，直接添加到末尾
                                iframeDoc.body.appendChild(wrapper);
                            } else {
                                // 否则，在目标元素之前插入新元素
                                var rect = dropTarget.getBoundingClientRect();
                                var dropPosition = e.clientY - rect.top;
                                var midPoint = rect.height / 2;

                                if (dropPosition > midPoint) {
                                    // 如果鼠标在元素下半部分，插入到目标元素之后
                                    if (dropTarget.nextSibling) {
                                        dropTarget.parentNode.insertBefore(wrapper, dropTarget.nextSibling);
                                    } else {
                                        dropTarget.parentNode.appendChild(wrapper);
                                    }
                                } else {
                                    // 如果鼠标在元素上半部分，插入到目标元素之前
                                    dropTarget.parentNode.insertBefore(wrapper, dropTarget);
                                }
                            }

                            // 为新组件添加draggable属性和必要的事件监听器
                            function setupComponentElement(element) {
                                // 确保元素有visual-component类
                                if (!$(element).hasClass('visual-component')) {
                                    $(element).addClass('visual-component');
                                }

                                // 设置为可拖拽
                                element.setAttribute('draggable', 'true');

                                // 添加鼠标悬停效果
                                $(element).hover(
                                    function() {
                                        if (!$(this).hasClass('element-selected')) {
                                            $(this).addClass('element-hover');
                                        }
                                    },
                                    function() {
                                        if (!$(this).hasClass('element-selected')) {
                                            $(this).removeClass('element-hover');
                                        }
                                    }
                                );

                                // 添加点击事件以显示浮动按钮和更新选择状态
                                $(element).on('click', function(e) {
                                    // 阻止事件冒泡，防止触发父元素的点击事件
                                    e.stopPropagation();

                                    // 确保这不是由浮动按钮触发的点击事件
                                    if (!$(e.target).closest('.floating-element-actions').length) {
                                        // 移除页面上所有元素的选中状态
                                        var iframeDoc = $(this).prop('ownerDocument');
                                        $(iframeDoc).find('.element-selected').removeClass('element-selected');
                                        $(iframeDoc).find('.visual-component-selected').removeClass('visual-component-selected');

                                        // 添加当前元素的选中状态
                                        $(this).addClass('element-selected visual-component-selected');

                                        // 显示浮动操作按钮
                                        ensureElementActions($(this));

                                        // 更新属性面板
                                        if (typeof updatePropertyPanel === 'function') {
                                            updatePropertyPanel(this);
                                        }
                                    }
                                });

                                // 添加拖拽开始事件
                                $(element).on('dragstart', function(e) {
                                    e.originalEvent.dataTransfer.setData('text/plain', ''); // 必须设置一些数据才能触发拖拽
                                    $(this).addClass('dragging');
                                    // 记录被拖拽的元素
                                    window.draggedElement = this;

                                    // 设置拖动时的视觉效果
                                    if (e.originalEvent.dataTransfer.setDragImage) {
                                        var dragImage = $(this).clone()[0];
                                        $(dragImage).css({
                                            opacity: '0.5',
                                            position: 'absolute',
                                            left: '-9999px'
                                        });
                                        $(document.body).append(dragImage);
                                        e.originalEvent.dataTransfer.setDragImage(dragImage, 50, 25);
                                        setTimeout(function() {
                                            $(dragImage).remove();
                                        }, 0);
                                    }
                                });

                                // 添加拖拽结束事件
                                $(element).on('dragend', function() {
                                    $(this).removeClass('dragging');
                                    window.draggedElement = null;

                                    // 移除所有元素的drag-over类
                                    var iframeDoc = $(this).prop('ownerDocument');
                                    $(iframeDoc).find('.drag-over').removeClass('drag-over');
                                });

                                // 确保组件有唯一ID
                                if (!element.id) {
                                    element.id = 'component-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
                                }
                            }

                            // 触发元素选择和初始化
                            setTimeout(function() {
                                // 移除旧的选中状态
                                $(iframeDoc).find('.element-selected').removeClass('element-selected');
                                $(iframeDoc).find('.visual-component-selected').removeClass('visual-component-selected');
                                // 添加新的选中状态 - 同时添加两种选中样式
                                $(wrapper).addClass('element-selected visual-component-selected');

                                // 为包装器添加draggable属性和事件监听器
                                setupComponentElement(wrapper);

                                // 如果是图片组件，添加特殊处理以确保点击能正确触发元素选择和浮动按钮显示
                                if (newElement.tagName.toLowerCase() === 'img') {
                                    // 为图片元素添加点击事件，确保点击图片时选中其父容器（visual-component）
                                    $(newElement).on('click', function(e) {
                                        e.stopPropagation();
                                        var componentWrapper = $(this).closest('.visual-component');
                                        if (componentWrapper.length > 0) {
                                            // 移除页面上所有元素的选中状态
                                            var iframeDoc = componentWrapper[0].ownerDocument;
                                            $(iframeDoc).find('.element-selected').removeClass('element-selected');
                                            $(iframeDoc).find('.visual-component-selected').removeClass('visual-component-selected');

                                            // 选中并高亮组件包装器
                                            componentWrapper.addClass('element-selected visual-component-selected');

                                            // 显示浮动操作按钮
                                            ensureElementActions(componentWrapper);

                                            // 更新属性面板
                                            if (typeof updatePropertyPanel === 'function') {
                                                updatePropertyPanel(componentWrapper[0]);
                                            }
                                        }
                                    });
                                }

                                // 更新属性面板
                                $('#no-selection-info').hide();
                                $('#element-properties, #style-properties').show();

                                // 填充属性值
                                $('#element-id').val(wrapper.id || '');
                                $('#element-class').val(wrapper.className || '');

                                if (newElement.tagName.toLowerCase() === 'img') {
                                    $('#element-content').val(newElement.src);
                                } else {
                                    $('#element-content').val(newElement.innerHTML);
                                }

                                // 触发内容变化事件，确保与visualeditor.js同步
                                $(iframeDoc).trigger('contentchanged');

                                // 如果templateEditor实例存在，调用其onContentChange方法
                                if (window.templateEditor && typeof window.templateEditor.onContentChange === 'function') {
                                    window.templateEditor.onContentChange();
                                }

                                console.log('新组件已初始化，可拖拽且能显示浮动按钮');
                            }, 100);
                        } catch (err) {
                            console.error('拖拽组件添加失败:', err);
                        }
                    });

                    console.log('组件库拖拽功能已设置，已禁用visualeditor.js的拖拽功能以避免冲突');
                } catch (error) {
                    console.error('设置拖拽功能失败:', error);
                }
            }
            // 设置组件库显示/隐藏功能
            function setupComponentsToggle() {
                try {
                    // 绑定显示/隐藏按钮事件
                    $('#components-toggle-btn').off('click').on('click', function(e) {
                        e.stopPropagation();
                        const panel = $('.components-panel');
                        const icon = $(this).find('i');

                        // 切换组件库显示/隐藏状态
                        panel.toggleClass('hidden');

                        // 切换按钮图标方向
                        if (panel.hasClass('hidden')) {
                            icon.removeClass('fa-chevron-left').addClass('fa-chevron-right');
                        } else {
                            icon.removeClass('fa-chevron-right').addClass('fa-chevron-left');
                        }

                        console.log('组件库已' + (panel.hasClass('hidden') ? '隐藏' : '显示'));
                    });

                    console.log('组件库显示/隐藏功能已设置');
                } catch (err) {
                    console.error('设置组件库显示/隐藏功能时出错:', err);
                }
            }
            // 获取选中元素的辅助函数，带错误处理
            function getSelectedElement() {
                try {
                    var iframe = window.editorIframe || document.getElementById('visual-editor');
                    if (!iframe || !iframe.contentDocument) {
                        return null;
                    }
                    return iframe.contentDocument.querySelector('.element-selected');
                } catch (e) {
                    console.error('获取选中元素失败:', e);
                    return null;
                }
            }

            // 浮动操作按钮相关变量和函数
            // 调试模式开关 - 设置为true启用详细日志输出
            var DEBUG_MODE = true;

            // 调试日志函数
            function debugLog(message, data) {
                if (DEBUG_MODE) {
                    console.log('[TemplateEditor Debug] ' + message);
                    if (data !== undefined) {
                        console.log('[TemplateEditor Debug] Data:', data);
                    }
                }
            }

            var floatingActionsContainer;
            var currentTargetElement = null;

            // 初始化浮动操作按钮容器
            function initFloatingActions() {
                debugLog('initFloatingActions被调用');

                // 确保变量已定义
                if (typeof floatingActionsContainer === 'undefined' || floatingActionsContainer === null) {
                    // 创建浮动操作按钮容器
                    floatingActionsContainer = $('<div class="floating-element-actions"></div>');

                    // 创建各个操作按钮
                    var actionsHtml = '<button type="button" class="element-action-btn action-edit" title="编辑内容"><i class="fa fa-edit"></i></button>' +
                        '<button type="button" class="element-action-btn action-properties" title="属性设置"><i class="fa fa-cog"></i></button>' +
                        '<button type="button" class="element-action-btn action-move-up" title="向上移动"><i class="fa fa-arrow-up"></i></button>' +
                        '<button type="button" class="element-action-btn action-move-down" title="向下移动"><i class="fa fa-arrow-down"></i></button>' +
                        '<button type="button" class="element-action-btn action-cancel" title="取消选择"><i class="fa fa-times-circle"></i></button>' +
                        '<button type="button" class="element-action-btn action-delete" title="删除元素"><i class="fa fa-close"></i></button>';

                    floatingActionsContainer.html(actionsHtml);
                    floatingActionsContainer.css({
                        position: 'fixed',
                        zIndex: 10000,
                        display: 'none',
                        backgroundColor: 'rgba(255, 255, 255, 0.95)',
                        border: '1px solid #ddd',
                        borderRadius: '4px',
                        boxShadow: '0 2px 8px rgba(0,0,0,0.15)',
                        padding: '2px',
                        display: 'flex',
                        gap: '2px'
                    });

                    // 添加到页面
                    $(document.body).append(floatingActionsContainer);
                    debugLog('浮动按钮容器已创建并添加到页面');

                    // 添加全局点击事件，点击页面其他区域时隐藏浮动按钮
                    $(document).on('click', function(e) {
                        // 检查点击目标是否是浮动按钮容器或其内部元素
                        if (!$(e.target).closest('.floating-element-actions').length &&
                            !$(e.target).closest('.visual-component').length) {
                            hideFloatingActions();
                        }
                    });

                    // 编辑按钮点击事件
                    floatingActionsContainer.find('.action-edit').on('click', function(e) {
                        e.stopPropagation();
                        console.log('编辑按钮点击事件触发');
                        if (currentTargetElement) {
                            currentTargetElement.contentEditable = true;
                            currentTargetElement.focus();

                            // 显示属性面板并显示选项卡1（基础属性）
                            $('.properties-panel').addClass('show');
                            // 显示基础属性tab（选项卡1）
                            $('#element-properties').show();
                            $('#style-properties').hide();
                            $('.property-tab[data-tab="element"]').addClass('active');
                            $('.property-tab[data-tab="style"]').removeClass('active');

                            // 填充属性值
                            $('#element-id').val(currentTargetElement.id || '');
                            $('#element-class').val(currentTargetElement.className || '');

                            if (currentTargetElement.tagName.toLowerCase() === 'img') {
                                $('#element-content').val(currentTargetElement.src);
                            } else {
                                $('#element-content').val(currentTargetElement.innerHTML);
                            }

                            // 设置链接URL
                            if (currentTargetElement.tagName.toLowerCase() === 'a') {
                                $('#link-url').val(currentTargetElement.href);
                            } else {
                                $('#link-url').val('');
                            }
                        }
                    });

                    // 向上移动按钮点击事件
                    floatingActionsContainer.find('.action-move-up').on('click', function(e) {
                        e.stopPropagation();
                        try {
                            var iframe = window.editorIframe || document.getElementById('visual-editor');
                            var iframeDoc = iframe ? (iframe.contentDocument || iframe.contentWindow.document) : null;

                            if (currentTargetElement && currentTargetElement.previousElementSibling) {
                                // 将当前元素移动到前一个元素前面
                                currentTargetElement.parentNode.insertBefore(currentTargetElement, currentTargetElement.previousElementSibling);
                                // 触发内容变化事件
                                if (iframeDoc) {
                                    $(iframeDoc).trigger('contentchanged');
                                }
                            } else {
                                alert('已经是第一个元素了，无法继续向上移动');
                            }
                        } catch (err) {
                            console.error('移动元素失败:', err);
                        }
                    });

                    // 向下移动按钮点击事件
                    floatingActionsContainer.find('.action-move-down').on('click', function(e) {
                        e.stopPropagation();
                        try {
                            var iframe = window.editorIframe || document.getElementById('visual-editor');
                            var iframeDoc = iframe ? (iframe.contentDocument || iframe.contentWindow.document) : null;

                            if (currentTargetElement && currentTargetElement.nextElementSibling) {
                                // 将当前元素移动到后一个元素后面
                                currentTargetElement.parentNode.insertBefore(currentTargetElement.nextElementSibling, currentTargetElement);
                                // 触发内容变化事件
                                if (iframeDoc) {
                                    $(iframeDoc).trigger('contentchanged');
                                }
                            } else {
                                alert('已经是最后一个元素了，无法继续向下移动');
                            }
                        } catch (err) {
                            console.error('移动元素失败:', err);
                        }
                    });

                    // 属性按钮点击事件
                    floatingActionsContainer.find('.action-properties').on('click', function(e) {
                        e.stopPropagation();
                        if (currentTargetElement) {
                            // 显示属性面板
                            $('.properties-panel').addClass('show');

                            // 显示样式设置tab（选项卡2）
                            $('#element-properties').hide();
                            $('#style-properties').show();
                            $('.property-tab[data-tab="element"]').removeClass('active');
                            $('.property-tab[data-tab="style"]').addClass('active');

                            // 填充样式属性值
                            var computedStyle = window.getComputedStyle(currentTargetElement);
                            $('#text-color').val(computedStyle.color);
                            $('#bg-color').val(computedStyle.backgroundColor);
                            $('#font-size').val(computedStyle.fontSize);
                            $('#bg-image').val(computedStyle.backgroundImage.replace(/^url\(['"](.+)['"]\)$/, '$1') || '');
                            $('#font-family').val(computedStyle.fontFamily);
                            $('#line-height').val(computedStyle.lineHeight);

                            // 设置文字对齐按钮状态
                            $('#text-align-group .property-button').removeClass('active');
                            $('#text-align-group .property-button[data-align="' + computedStyle.textAlign + '"]').addClass('active');

                            // 同时也需要填充基础属性值，确保数据一致性
                            $('#element-id').val(currentTargetElement.id || '');
                            $('#element-class').val(currentTargetElement.className || '');

                            if (currentTargetElement.tagName.toLowerCase() === 'img') {
                                $('#element-content').val(currentTargetElement.src);
                            } else {
                                $('#element-content').val(currentTargetElement.innerHTML);
                            }

                            if (currentTargetElement.tagName.toLowerCase() === 'a') {
                                $('#link-url').val(currentTargetElement.href);
                            } else {
                                $('#link-url').val('');
                            }
                        }
                    });

                    // 取消选择按钮点击事件
                    floatingActionsContainer.find('.action-cancel').on('click', function(e) {
                        e.stopPropagation();
                        hideFloatingActions();
                        // 隐藏属性面板
                        $('.properties-panel').removeClass('show');
                        $('#properties-handle').text('▶');
                        $('#element-properties, #style-properties').hide();
                        $('#no-selection-info').show();
                        // 移除元素选中状态
                        if (currentTargetElement) {
                            $(currentTargetElement).removeClass('element-selected');
                        }
                    });

                    // 删除按钮点击事件
                    floatingActionsContainer.find('.action-delete').on('click', function(e) {
                        e.stopPropagation();
                        try {
                            var iframe = window.editorIframe || document.getElementById('visual-editor');
                            var iframeDoc = iframe ? (iframe.contentDocument || iframe.contentWindow.document) : null;

                            if (currentTargetElement) {
                                if (layer.confirm('确定要删除这个元素吗？')) {
                                    $(currentTargetElement).remove();
                                    hideFloatingActions();
                                    // 隐藏属性面板
                                    $('#element-properties, #style-properties').hide();
                                    $('#no-selection-info').show();
                                    // 触发内容变化事件
                                    if (iframeDoc) {
                                        $(iframeDoc).trigger('contentchanged');
                                    }
                                }
                            }
                        } catch (err) {
                            console.error('删除元素失败:', err);
                        }
                    });

                    // 阻止操作按钮容器的点击事件冒泡
                    floatingActionsContainer.on('click', function(e) {
                        e.stopPropagation();
                    });
                }
            }

            // 显示浮动操作按钮
            function showFloatingActions(element) {
                debugLog('showFloatingActions被调用:', element);

                // 检查元素有效性
                if (!element) {
                    debugLog('错误: 元素为空，无法显示浮动按钮');
                    return;
                }

                // 初始化浮动按钮
                initFloatingActions();

                // 保存当前目标元素
                currentTargetElement = element;
                debugLog('设置当前目标元素:', currentTargetElement);

                // 获取iframe
                var iframe = window.editorIframe || document.getElementById('visual-editor');
                if (!iframe) {
                    debugLog('错误: 无法获取iframe引用');
                    return;
                }

                try {
                    // 获取元素在iframe中的位置
                    var elementRect = element.getBoundingClientRect();
                    // 获取iframe在页面中的位置
                    var iframeRect = iframe.getBoundingClientRect();
                    debugLog('元素位置:', elementRect);
                    debugLog('iframe位置:', iframeRect);

                    // 计算操作按钮的位置（显示在元素右上角外部）
                    var top = iframeRect.top + elementRect.top - 5; // 稍微向上偏移
                    var left = iframeRect.left + elementRect.right + 5; // 显示在右侧

                    // 确保按钮不会超出视口右侧
                    var actionsWidth = floatingActionsContainer.outerWidth() || 120;
                    if (left + actionsWidth > window.innerWidth) {
                        // 如果右侧空间不足，显示在左侧
                        left = iframeRect.left + elementRect.left - actionsWidth - 5;
                        // 确保不会超出左侧边界
                        if (left < 0) {
                            left = 10;
                        }
                    }

                    // 确保不会超出顶部边界
                    if (top < 10) {
                        top = 10;
                    }

                    // 设置操作按钮的位置并显示
                    debugLog('设置浮动按钮位置: top=' + top + 'px, left=' + left + 'px');
                    floatingActionsContainer.css({
                        top: top + 'px',
                        left: left + 'px',
                        display: 'flex',
                        visibility: 'visible'
                    });
                    debugLog('浮动按钮已显示');
                } catch (error) {
                    debugLog('显示浮动按钮时出错:', error);
                    // 安全模式：如果计算位置失败，使用默认位置
                    floatingActionsContainer.css({
                        top: '100px',
                        left: '100px',
                        display: 'flex',
                        visibility: 'visible'
                    });
                }
            }

            // 隐藏浮动操作按钮
            function hideFloatingActions() {
                if (floatingActionsContainer) {
                    floatingActionsContainer.hide();
                    currentTargetElement = null;
                }
            }

            // 修改确保元素操作按钮存在的函数 - 使用浮动按钮
            function ensureElementActions($element) {
                debugLog('ensureElementActions被调用:', $element);
                showFloatingActions($element[0]);
            }

            // 初始化属性编辑功能
            setTimeout(function() {
                handleElementSelection();
                setupPropertyListeners();
                // 初始化浮动按钮组件，确保它们在页面加载时就准备就绪
                initFloatingActions();
                // 移除重复调用setupDragAndDrop()，避免拖拽事件被多次绑定
                initPanelDragging();
            }, 600);

            // 初始化面板拖动功能
            function initPanelDragging() {
                // 左侧组件面板拖动
                makePanelDraggable($('.components-panel'), $('.components-panel .panel-title'));

                // 右侧属性面板拖动
                makePanelDraggable($('.properties-panel'), $('.properties-panel .panel-title'));

                // 属性面板显示/隐藏切换
                $('#properties-handle').on('click', function(e) {
                    e.stopPropagation();
                    const panel = $('.properties-panel');

                    if (panel.hasClass('show')) {
                        panel.removeClass('show');
                        $(this).text('▶');
                    } else {
                        panel.addClass('show');
                    }
                });
            }

            // 使面板可拖动的通用函数
            function makePanelDraggable(panel, handle) {
                let isDragging = false;
                let offsetX, offsetY;

                // 只有点击handle时才触发拖动
                handle.on('mousedown', function(e) {
                    isDragging = true;

                    // 计算鼠标相对于面板左上角的偏移
                    const panelOffset = panel.offset();
                    offsetX = e.clientX - panelOffset.left;
                    offsetY = e.clientY - panelOffset.top;

                    // 添加拖动时的样式
                    panel.addClass('dragging');
                    $(document.body).css('user-select', 'none');
                });

                // 鼠标移动时拖动面板
                $(document).on('mousemove', function(e) {
                    if (isDragging) {
                        // 计算新位置（减去偏移量）
                        let newX = e.clientX - offsetX;
                        let newY = e.clientY - offsetY;

                        // 限制面板不能移出可视区域
                        const viewportWidth = $(window).width();
                        const viewportHeight = $(window).height();
                        const panelWidth = panel.outerWidth();
                        const panelHeight = panel.outerHeight();

                        newX = Math.max(0, Math.min(newX, viewportWidth - panelWidth));
                        newY = Math.max(50, Math.min(newY, viewportHeight - panelHeight - 20));

                        // 应用新位置
                        panel.css({
                            left: newX + 'px',
                            top: newY + 'px',
                            right: 'auto' // 覆盖之前的right属性
                        });

                        // 对于左侧面板，当拖动时自动展开
                        if (panel.hasClass('components-panel')) {
                            panel.css('left', Math.min(newX, viewportWidth / 2) + 'px');
                        }
                    }
                });

                // 鼠标松开时停止拖动
                $(document).on('mouseup', function() {
                    if (isDragging) {
                        isDragging = false;
                        panel.removeClass('dragging');
                        $(document.body).css('user-select', '');
                    }
                });

            }
 
        });
    </script>
    <script src="js/content.min.js?t=20230419"></script>
    <script src="js/adminjs.js?t=20231027"></script>
</body>

</html>