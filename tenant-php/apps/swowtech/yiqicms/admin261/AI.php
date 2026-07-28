<?php
require '../inc/zzz_admin.php';
require  '../inc/zzz_ai.php';
check_admin('die');
$act = safe_word(getform('act', 'both'));
$type = safe_word(getform('type', 'both'));

switch ($act) {
    case 'save':
        saveAI();
        break;
    case 'try_ai':
        tryAI($type);
        break;
    case 'polish':
        polishAI();
        break;
    case 'createhtml':
        createhtmlAI();
        break;
    case 'generateHtml':
        generateHtml();
        break;
    case 'preview':
        previewAI();
        break;
    case 'generate':
        generateAI();
        break;
}

function saveAI()
{
    $actionType = safe_word(getform('actionType', 'post'));
    $table = safe_word(getform('table', 'post'));
    if (empty($table)) {
        returnmsg('json', 0, '表名不能为空');
    }

    $url = safe_word(getform('url', 'post'));
    if (empty($url)) {
        returnmsg('json', 0, 'URL不能为空');
    }

    $sid = safe_word(getform('sid', 'post'));
    if (empty($sid) && $actionType == 'generate') {
        returnmsg('json', 0, 'SID不能为空');
    }
    $id = safe_word(getform('id', 'post'));
    if (empty($id) && $actionType == 'overwrite') {
        returnmsg('json', 0, '内容ID不能为空');
    }

    // 处理预览操作
    if ($actionType == 'overwrite') {
        // 读取AI处理结果
        $file = RUN_DIR . 'data/ai/' . $url . '.log';
        if (!file_exists($file)) {
            returnmsg('json', 0, 'AI处理结果文件不存在');
        }
        $result = file_get_contents($file);
        if (empty($result)) {
            returnmsg('json', 0, '文件内容为空');
        }
        $r = json_decode($result, true);
        if (!isset($r['title']) || !isset($r['content'])) {
            returnmsg('json', 0, '返回数据格式错误');
        }

        // 处理Markdown内容
        require_once '../inc/zzz_markdown.php';
        $markdown = new MarkdownToHtml();
        $content = preg_replace('/# ' . $r['title'] . '/', '', $r['content'], 1);
        $html = $markdown->text($content);
        $html = htmlspecialchars_decode($html);
        switch ($table) {
            case 'content':
                $updateData = [
                    'c_title' => $r['title'],
                    'c_pagetitle' => $r['title'],
                    'c_pagekey' => $r['keywords'],
                    'c_pagedesc' => $r['description'],
                    'c_content' => $html,
                    'c_tag' => $r['keywords']
                ];
                $c_type = db_select('content', 'c_type', ['cid' => $id]);
                $link= getcontentlink($id, '', $c_type);
                $success = db_update('content', ['cid' => $id], $updateData);
                break;
            case 'about':
                $updateData = [
                    'a_content' => $html,
                    'a_key' => $r['keywords'],
                    'a_desc' => $r['description'],
                ];
                $sid=db_select('about', 'a_sid', ['aid' => $id]);
                $link= getsortlink( 'about', $sid);
                $success = db_update('about', ['aid' => $id], $updateData);
                break;
            case 'brand':
                $updateData = [
                    'b_content' => $html,
                    'b_key' => $r['keywords'],
                    'b_desc' => $r['description'],
                ];
                $link = getbrandlink( 'brand', $id);
                $success = db_update('brand', ['bid' => $id], $updateData);
                break;
            case 'labels':
                $updateData = [
                    'label_content' => $html,
                    'label_desc' => $r['description'],
                ];
                $success = db_update('labels', ['labelid' => $id], $updateData);
                break;
            default:
                returnmsg('json', 0, '不支持的表名');
                break;
        }

        if ($success) {
            // 更新标签
            if($table == 'content'){                
                $tag = $r['keywords'];
                $tags = splits($tag, ",");
                foreach ($tags as $v) {
                    if (db_count('tag', array('t_name' => $v)) == 0) {
                        $enname = pinyin($v);
                        db_insert('tag', ['t_name' => $v, 't_enname' => $enname, 't_addtime' => date('Y-m-d H:i:s'), 't_order' => 1, 't_lid' => 1, 't_onoff' => 1]);
                    }
                }
            }
            $messages = $link ? '<a href="' . $link. '" target="_blank">' . $r['title'] . '</a> 已更新' : $r['title'] . ' 已更新';
            returnmsg('json', 1, $messages);
        } else {
            returnmsg('json', 0, '保存失败');
        }
    } else if ($actionType == 'generate') {
        $file = RUN_DIR . 'data/ai/' . $url . '.log';
        $result = file_get_contents($file);
        if (empty($result)) {
            returnmsg('json', 0, '文件内容为空');
        }
        $r = json_decode($result, true);
        if (!isset($r['title']) || !isset($r['content'])) {
            returnmsg('json', 0, '返回数据格式错误');
        }
        require_once '../inc/zzz_markdown.php';
        $markdown = new MarkdownToHtml();
        $content = preg_replace('/# ' . $r['title'] . '/', '', $r['content'], 1);
        $html = $markdown->text($content);
        $html = htmlspecialchars_decode($html);
        $c_type = db_select('sort', 's_type', ['sid' => $sid]);
        $keywords = is_array($r['keywords']) ? implode(',',$r['keywords']) : $r['keywords'];
        $tag = $r['keywords'];
        $tags = is_array($tag) ? $tag : splits($tag, ",");
        foreach ($tags as $v) {
            if (db_count('tag', array('t_name' => $v)) == 0) {
                $enname = pinyin($v);
                db_insert('tag', ['t_name' => $v, 't_enname' => $enname, 't_addtime' => date('Y-m-d H:i:s'), 't_order' => 1, 't_lid' => 1, 't_onoff' => 1]);
            }
        }
        $id = db_insert('content', ['c_onoff' => 1, 'c_order' => 9, 'c_sid' => $sid, 'c_type' => $c_type, 'c_title' => $r['title'], 'c_addtime' => date('Y-m-d H:i:s'), 'c_pagetitle' => $r['title'], 'c_pagekey' => $keywords, 'c_pagedesc' => $r['description'], 'c_content' => $html, 'c_tag' => $keywords]);
        if ($id) {
            $messages = '<a href="' . getcontentlink($id, '', $c_type) . '" target="_blank">' . $r['title'] . '</a>';
            returnmsg('json', 1, $messages);
        } else {
            returnmsg('json', 0, '保存失败');
        }
    }
}

function tryAI($type)
{
    $zzzai = new zzz_ai();
    $prompt = "测试,马上返回你的大模型名称，必须以标准json格式返回，禁止额外的文字描述。";
    $result = $zzzai->generateText($prompt, $type);
    if (isset($result['error'])) {
        returnmsg('json', 0, $result['error'] . $result['message']);
    } else {
        $r = json_decode($result['text'], true);        
        returnmsg('json', 1, $r);
    }
}
function createhtmlAI(){
    $zzzai = new zzz_ai();
    $prompt = getform('prompt', 'post');
    $folder = safe_key(getform('folder', 'post'));
    if (empty($prompt)) {
        returnmsg('json', 0, '请输入创建HTML内容');
    }
    $pattern = '/文件名：([^，,]+)/';
    $match=preg_match($pattern, $prompt, $matches);
    if ( $match) {
        $filename = trim($matches[1]);
    } else {
        returnmsg('json', 0, '对话中必须包含文件名，例如：文件名：about.html');
    }    
    if(!preg_match('/\.html$/', $filename)){
        returnmsg('json', 0, '文件名必须以.html结尾');
    }
    if(is_file(SITE_DIR.$folder.$filename)){
        returnmsg('json', 0, '模板'.$filename.'已存在,请更换名称');
    }
    $prompt = str_replace('文件名：'.$filename, '', $prompt);
    $prompt .= "可使用jquery框架，路径如下:/js/jquery.min.js";
    $prompt .= "可使用bootstrap框架，路径如下:/plugins/bootstrap/，文件名称:bootstrap.min.css|animate.min.css|bootstrap.min.js|jquery.easing.min.js";
    $prompt .= "可使用flexslider框架，路径如下:/plugins/FlexSlider/，文件名称:flexslider.css|flexslider.js";
    $prompt .= "可使用字体路径如下:/plugins/bootstrap/fonts，文件名称:fontawesome-webfont.woff|glyphicons-halflings-regular.woff";
    $prompt .= "可使用banner图:/upload/slide/banner1.jpg|banner2.jpg|banner3.jpg";
    $prompt .= "可使用logo图:/images/logo.png";
    $prompt .="只输出html代码，不要解释";

    $result = $zzzai->generateHtml($prompt, SITE_DIR.$folder.$filename);
    if (isset($result['error'])) {
        returnmsg('json', 0, $result['error'] . $result['message']);
    } else {
        returnmsg('json', 1, '模板已保存，路径为：'.$result['text']);
    }
}

function polishAI()
{
    $zzzai = new zzz_ai();
    $prompt = getform('prompt', 'post');
    if (empty($prompt)) {
        returnmsg('json', 0, '请输入润色优化内容');
    }
    $table = safe_key(getform('table', 'post'));
    if (empty($table)) {
        returnmsg('json', 0, '请输入表名');
    }
    $id = safe_key(getform('id', 'post'));
    if (empty($id)) {
        returnmsg('json', 0, '请输入内容ID');
    }
    switch ($table) {
        case 'content':
            $field = 'c_title as title,c_content as content';
            break;
        case 'about':
            $field = 'a_name as title,a_content as content';
            break;
        case 'brand':
            $field = 'b_name as title,b_content as content';
            break;
        case 'labels':
            $field = 'label_title as title,lebel_content as content';
            break;

        default:
            returnmsg('json', 0, '表名错误');
    }
    $data = db_load_one($table, $id, $field);
    if (empty($data)) {
        returnmsg('json', 0, '内容不存在');
    }
    $text = html_AI(decode($data['content']));
    $prompt=str_replace('<标题>', $data['title'], $prompt);
    $prompt=str_replace('<内容>', $text, $prompt);
    $prompt .= "保留内容中的图片等多媒体元素，不需要分析".PHP_EOL;
    $prompt .= "请返回标准的json格式。需要包含title、keywords、description、content(是markdown格式)".PHP_EOL;
    $prompt .= "确保可解析，不要包含 JSON 字符，不要解释";
    $result = $zzzai->generateText($prompt);
    if (isset($result['error'])) {
        returnmsg('json', 0, $result['message']);
    } else {
        $r = json_decode($result['text'], true);
        if (!isset($r['title']) || !isset($r['content'])) {
            returnmsg('json', 0,$result['text']);
        }
        $content = preg_replace('/# ' . $r['title'] . '/', '', $r['content'], 1);
        require_once '../inc/zzz_markdown.php';
        $markdown = new MarkdownToHtml();
        $html = $markdown->text($content);
        $html = "<h1>{$r['title']}</h1>
        <div class='keywords text-danger'>关键词：{$r['keywords']}</div>
        <div class='description text-info'>描述：{$r['description']}</div>
        <hr/>
        {$html}";
        returnmsg('json', 1, $html, $result['log_name']);
    }
}

function generateAI()
{
    $zzzai = new zzz_ai();
    $prompt = getform('prompt', 'post');
    $prompt .= "请返回json格式。需要包含title、keywords、description、content(是markdown格式)，输出后检查 JSON 语法，确保可解析，不要解释";
    $result = $zzzai->generateText($prompt);
    if (isset($result['error'])) {
        returnmsg('json', 0, $result['message']);
    } else {
        $r = json_decode($result['text'], true);
        if (!isset($r['title']) || !isset($r['content'])) {
            returnmsg('json', 0, '返回数据格式错误');
        }
         if(isset($r['keywords'])){
            $keywords=is_array($r['keywords']) ? implode(',',$r['keywords']) : $r['keywords'];
        }else{
            $keywords='';
        }

        $content = preg_replace('/# ' . $r['title'] . '/', '', $r['content'], 1);
        require_once '../inc/zzz_markdown.php';
        $markdown = new MarkdownToHtml();
        $html = $markdown->text($content);
        $html = "<h1>{$r['title']}</h1>
        <div class='keywords text-danger'>关键词：{$keywords}</div>
        <div class='description text-info'>描述：{$r['description']}</div>
        <hr/>
        {$html}";
        returnmsg('json', 1, $html, $result['log_name']);
    }
}

/**
 * 预览AI
 */
/**
 * 生成HTML模板并保存到指定路径
 */
function generateHtml()
{
    $zzzai = new zzz_ai();
    
    // 获取参数
    $prompt = safe_word(getform('prompt', 'post'));
    $save_path = safe_word(getform('save_path', 'post'));
    $file_name = safe_word(getform('file_name', 'post'));
    
    // 参数验证
    if (empty($prompt)) {
        returnmsg('json', 0, '请输入HTML模板生成要求');
    }
    
    // 设置默认保存路径和文件名
    if (empty($save_path)) {
        $save_path = '../runtime/data/ai/html';
    }
    if (empty($file_name)) {
        $file_name = 'template_' . date('YmdHis') . '.html';
    }
    
    // 确保文件有.html扩展名
    if (strtolower(pathinfo($file_name, PATHINFO_EXTENSION)) !== 'html') {
        $file_name .= '.html';
    }
    
    // 构建完整的保存路径
    $full_path = $save_path . '/' . $file_name;
    
    // 确保目录存在
    if (!is_dir($save_path)) {
        if (!mkdir($save_path, 0755, true)) {
            returnmsg('json', 0, '无法创建保存目录');
        }
    }
    
    // 生成HTML模板的提示词增强
    $prompt .= "\n请使用以下资源路径：";
    $prompt .= "\n- jQuery: /js/jquery.min.js";
    $prompt .= "\n- Bootstrap CSS: /plugins/bootstrap/bootstrap.min.css";
    $prompt .= "\n- Bootstrap JS: /plugins/bootstrap/bootstrap.min.js";
    $prompt .= "\n- 字体路径: /plugins/bootstrap/fonts/";
    $prompt .= "\n请生成完整的、美观的HTML页面，包括<!DOCTYPE html>声明、head和body部分。";
    $prompt .= "\n只输出HTML代码，不要包含任何解释或说明。";
    
    // 调用AI生成HTML
    $result = $zzzai->generateHtml($prompt);
    
    if (isset($result['error'])) {
        returnmsg('json', 0, 'AI生成失败: ' . $result['message']);
    }
    
    // 提取纯HTML代码（去除可能的markdown代码块标记）
    $html_content = $result['text'];
    
    // 清理可能的代码块标记
    $html_content = preg_replace('/^```html\n/', '', $html_content);
    $html_content = preg_replace('/```$/', '', $html_content);
    $html_content = trim($html_content);
    
    // 保存到文件
    if (!file_put_contents($full_path, $html_content)) {
        returnmsg('json', 0, '保存文件失败');
    }
    
    // 生成可访问的URL路径（相对路径）
    $access_path = str_replace('../', '', $full_path);
    
    // 返回成功信息和文件地址
    returnmsg('json', 1, array(
        'file_path' => $full_path,
        'access_url' => $access_path,
        'file_name' => $file_name,
        'log_name' => isset($result['log_name']) ? $result['log_name'] : ''
    ));
}

function previewAI()
{
    $id = safe_key(getform('id', 'post'));
    if (empty($id)) {
        returnmsg('json', 0, '请输入内容ID');
    }
    $url = safe_key(getform('url', 'post'));
    if (empty($url)) {
        returnmsg('json', 0, '请输入内容URL');
    }
    $file = RUN_DIR . 'data/ai/' . $url . '.log';
    $result = file_get_contents($file);
    if (empty($result)) {
        returnmsg('json', 0, '文件内容为空');
    }
    $r = json_decode($result, true);
    if (!isset($r['title']) || !isset($r['content'])) {
        returnmsg('json', 0, '返回数据格式错误');
    }
    $content = preg_replace('/# ' . $r['title'] . '/', '', $r['content'], 1);
    require_once '../inc/zzz_markdown.php';
    $markdown = new MarkdownToHtml();
    $html = $markdown->text($content);
    $html = "<h1>{$r['title']}</h1>
    <div class='keywords text-danger'>关键词：{$r['keywords']}</div>
    <div class='description text-info'>描述：{$r['description']}</div>
    <hr/>
    {$html}";
    returnmsg('json', 1, $html);
}

/**
 * 使用示例函数
 */
function tryAI1($type)
{
    // 创建全局实例
    if (!isset($zzzai)) {
        $zzzai = new zzz_ai($type);
    }
    global $zzzai;

    // 检查AI功能是否开启
    if (!$zzzai->isEnabled()) {
        echo "AI功能未开启，请在配置文件中启用<br/>";
        return;
    }

    echo "===== AI基础方法使用示例 =====<br/>";

    // 获取可用的AI服务
    $services = $zzzai->getAvailableServices();
    if (empty($services)) {
        echo "没有可用的AI服务，请在配置文件中配置并启用<br/>";
        return;
    }

    echo "可用的AI服务：<br/>";
    foreach ($services as $key => $name) {
        echo "- $key: $name<br/>";
    }
    echo "<br/>";

    // 使用默认AI服务生成文本
    echo "===== 使用默认AI服务 =====<br/>";
    $prompt = "请简要介绍一下人工智能的发展历程";
    echo "提示词: $prompt<br/>";

    $result = $zzzai->generateText($prompt);
    if (isset($result['error'])) {
        echo "错误: " . $result['error'] . "<br/>";
        if (isset($result['details'])) {
            echo "详细信息: " . json_encode($result['details']) . "<br/>";
        }
    } else {
        echo "生成结果: " . $result['text'] . "<br/>";
        if (isset($result['from_cache']) && $result['from_cache']) {
            echo "(结果来自缓存)<br/>";
        }
    }
    echo "<br/>";

    // 使用指定AI服务生成文本
    $first_service = array_key_first($services);
    echo "===== 使用指定AI服务 ($first_service) =====<br/>";

    // 获取该服务支持的模型
    $models = $zzzai->getAvailableModels($first_service);
    if (!empty($models)) {
        echo "支持的模型：<br/>";
        foreach ($models as $key => $name) {
            echo "- $key: $name<br/>";
        }
        echo "<br/>";
    }

    // 自定义参数
    $custom_params = array(
        'max_tokens' => 1000,
        'temperature' => 0.5,
        'top_p' => 0.8
    );

    echo "自定义参数: " . json_encode($custom_params) . "<br/>";
    $result = $zzzai->generateText($prompt, $custom_params, $first_service);

    if (isset($result['error'])) {
        echo "错误: " . $result['error'] . "<br/>";
        if (isset($result['details'])) {
            echo "详细信息: " . json_encode($result['details']) . "<br/>";
        }
    } else {
        echo "生成结果: " . $result['text'] . "<br/>";
    }
    echo "<br/>";

    // 使用多轮对话示例
    echo "===== 多轮对话示例 =====<br/>";

    $messages = array(
        array('role' => 'user', 'content' => '你好，我想了解一下PHP编程语言'),
        array('role' => 'assistant', 'content' => 'PHP是一种广泛使用的开源通用脚本语言，特别适合Web开发并可嵌入HTML中。'),
        array('role' => 'user', 'content' => '它和Python相比有什么优势？')
    );

    $result = $zzzai->generateText($messages);

    if (isset($result['error'])) {
        echo "错误: " . $result['error'] . "<br/>";
    } else {
        echo "多轮对话结果: " . $result['text'] . "<br/>";
    }
}
