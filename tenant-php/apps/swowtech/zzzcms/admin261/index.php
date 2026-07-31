<?php
require '../inc/zzz_admin.php';
check_admin('login');
$act=safe_word(getform('act','get'));
$type=safe_key(getform('type','get'));
$id=safe_word(getform('id','get'));
$aid=safe_word(getform('aid','get'));
$sid=safe_word(getform('sid','get'));
$pid=safe_word(getform('pid','get'));
$cid=safe_word(getform('cid','get'));
$uid=safe_word(getform('uid','get'));
$stype=safe_word(getform('stype','get'));
$module=!empty($act) ? $act : 'index';
$GLOBALS['type']=$type;
switch ($module) { 
	case 'content':        
		if($cid){
			$data=db_load_sql_one('select *,b.sid,b.s_type from [dbpre]content a,[dbpre]sort b where b.sid=a.c_sid and cid='.$cid);			
			$GLOBALS['stype']=$data['s_type'];
			$GLOBALS['sid']=$data['sid'];			
		}else if(!empty($sid)){			
			$GLOBALS['stype']=$stype ? $stype :  db_select('sort','s_type',array('sid'=>$sid));            
            $GLOBALS['sid']=$sid;            
		}else{
           empty($stype) and die('未选择模型');
           $GLOBALS['stype']=$stype;
           $s=db_load_one('sort',array('s_type'=>$stype,'s_onoff'=>1),'sid',array('s_level'=>'desc'));
           $GLOBALS['sid']=$s['sid'];            
        }
		break;
	case 'contentlist':	
    case 'adds':		
		if ($sid==0){			
			$data=array('s_type'=>$stype,'s_name'=>'','sid'=>'');
		}else{
			$data=db_load_one('sort',$sid);		
		}
		break;
	case 'sort':	
		$GLOBALS['ID']='';
		if($sid){	
			$GLOBALS['ID']=$sid;
	   		$data=db_load_one('sort',array('sid'=>$sid));
		} elseif($pid){		
			$data=db_load_one('sort',array('sid'=>$pid));			
		}		
	   break;
	case 'about':
		if($sid){	
	   		$data=db_load_one('about',array('a_sid'=>$sid));
		} elseif($aid){		
			$data=db_load_one('about',array('aid'=>$aid));
		} 	
	   break;  
	case 'custom':
	case 'usercustom':  
    case 'gbookcustom':    
        $customid=getform('customid','get');
		if ($customid) $data=db_load_one('content_custom',array('customid'=>$customid));	
		break;	
	case 'siteedit':
		 $data = db_load_one('language','l_onoff=1');
		break;
	case 'admin':
		if ($uid) $data = db_load_one('user',array('uid'=>$uid));
		break;
	case 'usergroup':
	case 'admingroup':
        $gid=getform('gid','get');
		if ($gid) $data = db_load_one('user_group',array('gid'=>$gid));
		break;
	case 'htmllist':
		$folder=safe_key(getform('folder','get'));
        delfiles(RUN_DIR.'cache/','tpl','all');
		break;
	case 'filelist':
	case 'uploadlist':
	case 'templatelist':
	case 'codeedit':
	case 'templateedit':
		$folder=safe_url(getform('folder','get'));
		break;
	case 'baidusiteapi':
		$language = db_load_one('language','l_onoff=1','siteurl');
		if(check_file(RUN_DIR.'data/baidusiteapi.zzz',1)){
			$data['baidusiteapi']=file_get_contents( RUN_DIR.'data/baidusiteapi.zzz' );
		}
		$urls=[];
		$siteurl=rtrim(trim($language['siteurl']),'/');		
		if(conf('wapmark')==1){
			$j=2;
		}else{
			$j=1;
		}
		
		for($i=0;$i<$j;$i++){
			if($i==1){
				$siteurl.=rtrim('/'.conf('wappath'),'/');						
			}
			$urls[]=$siteurl;		
			$sort = db_load( 'sort','s_onoff=1','sid,s_name,s_type,s_url,s_filename' );
			foreach($sort as $v){
				$urls[]=$siteurl.getsortlink($v['s_type'],$v['sid'],$v['s_filename'], $v[ 's_url' ] );
			}

			$brand = db_load( 'brand', 'b_onoff=1', 'bid,b_enname' );
			foreach($brand as $v){
				$urls[]=$siteurl.getbrandlink( '', $v[ 'bid' ] , $v[ 'b_enname' ] );
			}

			$content = db_load( 'content,sort', 'c_sid=sid and c_onoff=1 and s_onoff=1', 'cid,c_pagename,s_type' );
			foreach($content as $v){
				$urls[]=$siteurl.getcontentlink( $v[ 'cid' ] , $v[ 'c_pagename' ], $v[ 's_type' ] );
			}			
		}
		$data['urls']=implode( "\r\n", $urls );
		break;
    case 'loginout':
		login_out();
		break;
	case 'loginesc':
		login_esc();
		break;
	default:
		if ($id) $data=db_load_one($module,$id);			
}
    //echop(getform());echop($module);echop($sid);echop ($data);
	$GLOBALS['r']=isset($data) ? arr_key($data) : '';
	//echop (parse_admin_tlp($module));die;
	include parse_admin_tlp($module);