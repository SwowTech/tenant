<?php
require '../inc/zzz_admin.php';
$r=check_admin();  
if(!$r) die(tojson(['state'=>'登陆失效']));
$action = safe_word(getform('action','get'));
$upfolder = safe_word(getform('upfolder','get'));
$CONFIG=[
    "imageActionName"=>"uploadimage",
    "imageFieldName"=>"upfile",
    "imageMaxSize"=>2048000,
    "imageAllowFiles"=>[".png", ".jpg", ".jpeg", ".gif", ".bmp"],
    "imageCompressEnable"=>false,
    "imageCompressBorder"=>1600,
    "imageInsertAlign"=>"none",
    "imageUrlPrefix"=>"",
    "scrawlActionName"=>"uploadscrawl",
    "scrawlFieldName"=>"upfile",
    "scrawlUrlPrefix"=>"",
    "scrawlInsertAlign"=>"none",
    "snapscreenActionName"=>"uploadimage",
    "snapscreenUrlPrefix"=>"",
    "catcherLocalDomain"=>["127.0.0.1", "localhost", "img.baidu.com"],
    "catcherActionName"=>"catchimage",
    "catcherFieldName"=>"source",
    "catcherUrlPrefix"=>"",
    "videoActionName"=>"uploadvideo",
    "videoFieldName"=>"upfile",
    "videoUrlPrefix"=>"",
    "videoMaxSize"=>102400000,
    "videoAllowFiles"=>[".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg", ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid"],
    "fileActionName"=>"uploadfile",
    "fileFieldName"=>"upfile",
    "fileUrlPrefix"=>"",
    "fileMaxSize"=>51200000,
    "fileAllowFiles"=>[".png", ".jpg", ".jpeg", ".gif", ".bmp", ".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg", ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid", ".rar", ".zip", ".tar", ".gz", ".7z", ".bz2", ".cab", ".iso", ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".pdf", ".txt", ".md", ".xml"],
    "imageManagerActionName"=>"listimage",
    "imageManagerListSize"=>20,
    "imageManagerUrlPrefix"=>"",
    "imageManagerInsertAlign"=>"none",
    "fileManagerActionName"=>"listfile",
    "fileManagerUrlPrefix"=>"",
    "fileManagerListSize"=>20,
];
switch ($action) {
    case 'config':
        $result = json_encode($CONFIG);
        break;    
    /* 上传图片 */
	
    case 'uploadimage':
		$r=upload($_FILES['upfile'],'image',$upfolder);
		if($r['code']>0){
			$result =tojson(['state'=>'SUCCESS','title'=>$r['title'],'url'=>$r['url']]);
		}else{
			$result =tojson(['state'=>$r['data']]);
		}
        break;   
    /* 上传涂鸦 */
    case 'uploadscrawl':   
		$upfile=getform('upfile','post');
    	$result =tojson(up_base64($upfile,$upfolder));
        break;   
    /* 上传文件 */
    case 'uploadfile':
       $r =upload($_FILES['upfile'],'file',$upfolder);
	   if($r['code']>0){
			$result =tojson(['state'=>'SUCCESS','title'=>$r['title'],'url'=>$r['url']]);
		}else{
			$result =tojson(['state'=>$r['data']]);
		}
        break;    
    /* 上传视频 */
	case 'uploadvideo':
		$r=upload($_FILES['upfile'],'video',$upfolder);
		if($r['code']>0){
			$result =tojson(['state'=>'SUCCESS','title'=>$r['title'],'url'=>$r['url']]);
		}else{
			$result =tojson(['state'=>$r['data']]);
		}
        break;  
	 /* 列出图片 */
    case 'listimage':
		$size=safe_word(getform('size','get'));
		$start=safe_word(getform('start','get'));
		$uporder=safe_word(getform('uporder','get'));
		$end = $start + $size;
		$allowFiles=str_replace(",","|",conf('imageext'));
		$path = getform('path','get') ;
		$path = $path ? SITE_DIR.$path.'/' : UPLOAD_DIR.$upfolder.'/';
		$path=str_replace('//','/',$path);
		$files = path_list($path, $allowFiles);
		foreach($files as $k=>$v){
			$sizes[$k] = $v['size'];
			$times[$k] = $v['mtime'];
			$names[$k] = $v['title'];
		}
		switch($uporder){
			case'size1'	: array_multisort($sizes,SORT_DESC,SORT_STRING, $files);break;
			case'size2'	: array_multisort($sizes,SORT_ASC,SORT_STRING, $files);break;	
			case'name1'	: array_multisort($names,SORT_DESC,SORT_STRING, $files);break;			
			case'mtime2'	: array_multisort($times,SORT_ASC,SORT_STRING, $files);break;	
			case'mtime1': array_multisort($times,SORT_DESC,SORT_STRING, $files);break;
			default;
		}	
		if (! count($files)) {
			return json_encode(array(
				"state" => "no match file",
				"list" => array(),
				"start" => $start,
				"total" => count($files)
			));
		}
		$len = count($files);

		for ($i =$start,$list = array(); $i <= $len-1 &&  $i <= $end; $i ++) {
			$list[] = $files[$i];			
		}
		$result = json_encode(array(
			"state" => "SUCCESS",
			"list" => $list,
			"start" => $start,
			"total" => count($files)
		));		
        break;
    /* 列出文件 */
    case 'listfile':
		$size=safe_word(getform('size','get'));
		$start=safe_word(getform('start','get'));
		$uporder=safe_word(getform('uporder','get'));
		$allowFiles=str_replace(",","|",conf('fileext'));
		$path = getform('path','get') ;
		$path = $path ? SITE_DIR.$path.'/' : UPLOAD_DIR.$upfolder.'/';
		$path=str_replace('//','/',$path);
		//echop($path);
		$files = path_list($path, $allowFiles);			
		$len = count($files);

		for ($i =$start,$list = array(); $i <= $len-1 &&  $i <= $len ; $i ++) {
			$list[] = $files[$i];			
		}
		$result = json_encode(array(
			"state" => "SUCCESS",
			"list" => $list,
			"start" => $start,
			"path" => $path,
			"total" => count($files)
		));		
        break;
    
    /* 抓取远程文件 */
    case 'catchimage':
		$source=getform('source','post');
		$list = array();$msg='';
     	foreach ($source as $imgUrl) {
			$info =down_url(safe_url($imgUrl),$upfolder); 
			if ($info['size']>0){
				$state="SUCCESS";
				array_push($list, array(			
					"state" => "SUCCESS",				
					"title" => $info["title"],
					"url" => $info["url"],
					"size" => $info["size"],
					"source"=>$imgUrl
				));
			}else{
				array_push($list, array(			
					"state" => "SUCCESS",			
					"title" => $info["msg"],
					"url" => $imgUrl
				));
			}
		}
		$result =  json_encode(array(
			'state' => 'SUCCESS',
			'list' => $list			
		));
        break;
    default:
        $result = json_encode(array(
            'state' => '请求地址出错'
        ));
        break;
}
/* 输出结果 */
if (isset($_GET["callback"])) {
    if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
        echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
    } else {
        echo json_encode(array(
            'state' => 'callback参数不合法'
        ));
    }
} else {
    echo($result);
}