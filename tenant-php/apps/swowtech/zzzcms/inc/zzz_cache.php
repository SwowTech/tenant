<?php
function cache_new() {   
    $cacheType = conf('cachetype');
    
    if($cacheType=='Redis'){
        if(extension_loaded('redis')) {
            try {
                $redis = new Redis();
                $redis->connect('localhost', 6379);
                
                $redisUser = conf('redis_user');
                $redisPass = conf('redis_pass');
                if(!empty($redisPass)){
                    if(!empty($redisUser)){
                        try {
                            $redis->auth($redisUser . ':' . $redisPass);
                        } catch (RedisException $e) {
                            $redis->auth($redisPass);
                        }
                    } else {
                        $redis->auth($redisPass);
                    }
                }
                
                return $redis;
            } catch (RedisException $e) {
                return false;
            }
        } else {
            return false;
        }
    }
    
    if($cacheType=='Memcache'){
        if(extension_loaded('Memcache')) {
            $memcache = new Memcache();
            $memcache->connect('localhost', 11211);
            return $memcache;    
        } else {
            return false;
        }
    }
    
    return false;
}

function cache_key($name){
    $prefix = conf('redis_prefix');
    return !empty($prefix) ? $prefix . $name : $name;
}

function set_cache( $name, $value, $expire=7200) {
	if ( !empty( $name ) ) {
        $cache=cache_new();
        $key = cache_key($name);
        if($cache){
            $cache->setEx($key,$expire,$value);         
        }else{
            $filepath=RUN_DIR.'cache/'.$name.'.zzz';        
            check_file( $filepath, 1);
            return file_put_contents($filepath,$value);          
        }		
	}else{
        return '';
    }
}

function get_cache( $name, $time=7200) {
	if ( !is_null( $name ) ) {       
        $cache=cache_new();
        $key = cache_key($name);
        if($cache){
            if($cache instanceof Redis){
                return $cache->get($key);
            }else{
                return $cache->get($key);
            }
        }else{
            $filepath=RUN_DIR.'cache/'.$name.'.zzz';
            if ( is_file( $filepath ) ) {
                if(time()-filemtime( $filepath ) <$time){
                    return file_get_contents($filepath);
                }else{
                    unlink( $filepath );
                    return '';
                }
            }
        }
	}
	return '';
}

function del_cache( $name) {
    $cache=cache_new();
    $key = cache_key($name);
    if($cache){
        if($cache instanceof Redis){
            return $cache->del($key);
        }else{
            return $cache->delete($key);
        }
    }else{
        $filepath=RUN_DIR.'cache/'.$name.'.zzz';
        if ( unlink( $filepath ) ) {
            return true;
        }
    }
}


function sitemap( $pid ,$type='table') {
    $list = '';
    $contentlist = '';
    if(!defined('WAPPATH'))  define( 'WAPPATH', '');	
    $siteurl=db_select('language','siteurl','isdefault=1');
    $data = sitesort( $pid );
    foreach ( $data as $value ) {
        if ( $value[ 'num' ] > 0 ) {
            $content = sitecontent( $value[ 'sid' ] );
            foreach ( $content as $clink ) {
                $contentlist .='<a target="_blank" href='. $siteurl.$clink[ 'link' ].'>'.$clink['title'].'</a>';
            }
        } else {
            $contentlist = '';
        }
        if($type=='table'){
             $list .= '<tr>
				<td>' . $value[ 'sid' ] . '</td>				
				<td> <a target="_blank" href='. $siteurl.$value[ 'slink' ] . '>'.$value['sname'].'</a></td>
				<td>' . $value[ 'stype' ] . '</td>
				<td>' . $value[ 'num' ] . '</td>
				<td>' . $contentlist . '</td>
			</tr>';
        }else  if($type=='api'){

        }
        if ( $value[ 'count' ] > 0 )$list .= sitemap( $value[ 'sid' ] );
    }
    return $list;
}

function sitesort( $pid ,$type='table') {
    $list = array();
    $data = db_load_sql( 'select sid,s_name,s_type,s_edittime,s_url,model_name,(select count(*) from [dbpre]sort where s_pid=t.sid and (s_onoff=0 or s_onoff=1)) as c from [dbpre]model as a,[dbpre]sort t where s_type=model_type and (s_onoff=0 or s_onoff=1) and s_pid=' . $pid . ' order by s_order asc , sid asc' );
    foreach ( $data as $value ) {
        $i = $value[ 'sid' ];
        $stype = $value[ 'model_name' ];
        $outlink = $stype == 'links' ? $value[ 's_url' ] : '';
        $num = db_count( 'content', 'c_onoff=1 and c_sid=' . $i );
        $sortlink = getsortlink( $value[ 's_type' ], $i, $outlink ) ;
        array_push( $list, array(
            'sid' => $i,
            'slink' => $sortlink,
            'sname' => $value[ 's_name' ] ,
            'stype' => $stype,
            'num' => $num,
            'count' => $value[ 'c' ]
        ) );
    }
    return $list;
}

function sitebrand( $pid ,$type='table') {
    $list = array();
    $data = db_load( 'brand', 'b_onoff=1', 'bid,b_name,b_enname,b_edittime' );
    foreach ( $data as $value ) {
        $i = $value[ 'bid' ];
        $link = getbrandlink( '', $value[ 'b_enname' ] );
        array_push( $list, array(
            'id' => $i,
            'link' => $link,
            'title' => $value[ 'b_name' ],
            'time' => $value[ 'b_edittime' ]
        ) );
    }
    return $list;
}

function sitecontent( $sid ,$type='table') {
    $list = array();
    $data = db_load( 'content', 'c_onoff=1 and c_sid=' . $sid, 'cid,c_title,c_type,c_link,c_pagename,c_edittime' );
    foreach ( $data as $value ) {
        $i = $value[ 'cid' ];
        $link = getcontentlink( $i, $value[ 'c_pagename' ], $value[ 'c_type' ], $value[ 'c_link' ] );
        array_push( $list, array(
            'id' => $i,
            'link' => $link,
            'title' => $value[ 'c_title' ],
            'time' => $value[ 'c_edittime' ]
        ) );
    }
    return $list;
}
?>