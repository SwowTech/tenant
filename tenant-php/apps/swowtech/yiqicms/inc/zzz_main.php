<?php
	
function echop( $str,$time='') {
	if($time){
		$starttime=G('endtime',G('starttime'));
		$time='，步时：'.formatnum(microtime( 1 )-$starttime,'time');
		$GLOBALS[ 'endtime' ]=microtime( 1 );
	}
	if ( !is_array( $str ) ) {
		print_r( $str .$time. "<br>\r\n" );
	} else {
		print_r( $str );
	}
}

function echov( $str ) {
	var_dump( $str );
}

function isnul( $str ) {
	if ( !isset( $str ) ) return true;
	if ( empty( $str ) ) return true;
	if ( $str == 'layer' ) return true;
	$s = array( " ", "　", "\t", "\n", "\r", "null" );
	$str = str_replace( $s, '', $str );
	if ( empty( $str ) ) {
		return true;
	} else {
		return false;
	}
}

function repkong( $str ) {
	$s = array( " ", "　", "\t", "\n", "\r" );
	return str_replace( $s, '', $str );
}

function isnum( $str ) {
	if ( isnul( $str ) ) return 0;
	if ( preg_match( "/^\d*$/", $str ) ) {
		return ( int )$str;
	} else {
		return 0;
	}
}

function getnum( $str ) {
	if ( empty( $str ) ) return 0;
	$arr=[];
	if(preg_match('/\d+/',$str,$arr)){
		return $arr[0];
	}
}

function ifstrin( $s, $str ) {
	if ( empty( $s ) || empty( $str ) ) return false;
	if ( is_array( $s ) ) {
		return in_array( $str, $s );
	}else if ( is_array( $str ) ) {
		foreach($str as $v){
			if (in_array($v, $s)) {
				return true;
			}
		}
		return false;
	} else {
		return strpos( $s, $str ) !== false ? true : false;
	}
}

function isact( $str ) {
	if ( preg_match( "/^[a-z_]+$/", $str ) ) {
		return true;
	}
}

function phpgo( $url="/" ) {
	header( 'location:' . $url );
	exit;
}

function ifnum( $str ) {
	if ( is_array( $str ) ) return false;
	if ( preg_match( "/^\d*$/", $str ) ) {
		return true;
	}
}

function ifch( $str ) {
	if ( is_array( $str ) || $str === null ) return false;
	if ( preg_match( '/[^\x00-\x80]/', $str ) ) {
		return true;
	}
	return false;
}

function lenstr( $s ) {
	return mb_strlen( $s, 'UTF-8' );
}

function leftstr( $s, $len = NULL ) {
	if ( empty( $s ) ) return '';
	if ( is_null( $len ) ) {
		$arr = splits( $s, ',' );
		$s = '';
		$len = end( $arr );
		for ( $x = 0; $x < count( $arr ) - 1; $x++ ) {
			$s .= $arr[ $x ];
		}
	}
	if ( mb_strlen( $s, 'UTF-8' ) > $len ) {
		return mb_substr( $s, 0, intval($len), 'UTF-8' ) . '...';
	} else {
		return $s;
	}
}

function hidestr( $s, $len = NULL ) {
	if ( is_null( $len ) ) {
		$arr = splits( $s, ',' );
		$s = $arr[ 0 ];
		$len = isset( $arr[ 1 ] ) ? $arr[ 1 ] : 10;
	}
	$strlen = mb_strlen( $s, 'utf8' );
	if ( $strlen > $len ) {
		$start = ( $strlen - $len ) / 2;
		return substr_replace( $s, '****', $start, $len );
	} else {
		return $s;
	}
}

function cleft( $str, $start = 0, $length = 0 ,$append =0) {
	$str = trim($str);
	$strlength = iconv_strlen($str,"UTF-8"); 
	if ($length == $strlength || $length >= $strlength || $length == 0) {
	  return $str; //截取长度等于或大于等于本字符串的长度，返回字符串本身
	} elseif ($length < 0 ) {//如果截取长度为负数
	  $length = $strlength + $length;//那么截取长度就等于字符串长度减去截取长度
	  if ($length >=  $strlength ){ //如果截取长度的绝对值大于字符串本身长度，则截取长度取字符串本身的长度
		return $str;
	  }
	}
	$newstr = mb_substr($str, $start, intval($length), 'utf8');	
	if ($append && $str != $newstr) {
	  $newstr .= '...';
	}
	return $newstr;
}

function cright( $str, $num = 1 ) {
	$var = trim( $str );
	$result = substr( $var, -$num );
	return $result;
}

function sub_left( $str, $val ) {
	$var = trim( $str );
	$pos = stripos( $str, $val );
	if ( $pos == 0 ) return $str;
	$result = substr( $var, 0, $pos );
	return $result;
}

function sub_right( $str, $val ) {
	$var = trim( $str );
	if ( empty( $str ) || empty( $val ) ) return $str;
	$pos = stripos( $str, $val );
	if ( $pos == 0 ) {
		$result = $str;
	} else {
		$result = splits( $str, $val );
		$result = end( $result );
	}
	return $result;
}

function md5_16( $str ) {
	return substr( md5( $str ), 8, 16 );
}

function script( $str ) {
	echo "<script>" . $str . "</script>";
}

function reload() {
	echo "<script>location.href='" . $_SERVER[ "HTTP_REFERER" ] . "';</script>";
}

function confirm( $q, $y, $n ) {
	if ( $y == '-1' ) {
		$y = "{location.href='javascript:history.go(-1)'}";
	} else {
		$y = "{location.href='" . $y . "'}";
	}
	if ( $n == '-1' ) {
		$n = "{location.href='javascript:history.go(-1)'}";
	} else {
		$n = "{location.href='" . $n . "'}";
	}
	echo "<script>if(confirm('" . $q . "')) " . $y . "  else " . $n . "</script>";
}

function loading( $type = 0, $time = 10000 ) {
	echo "<script>var index = layer.load(" . $type . ", {time: " . $time . "}); </script>";
}

function layermsg( $str ) {
	echo "<script>{parent.layer.msg('" . $str . "',{icon: 1,time: 1500}, function(){parent.layer.closeAll()});}</script>";
}

function layeropen( $str,$type=2 ,$title='') {
	echo "<script>{parent.layer.open({ title:'".$title."',type: ".$type.",area: ['1000px', '600px'],  content:'" . $str . "'})}</script>";
}

function layertrue( $str ) {
	echo "<script>parent.layer.open({title:'保存成功',content:'" . $str . "',icon: 1,time: 3000,end:function(){parent.layer.closeAll();parent.tablerefresh();}}); </script>";
	exit();
}

function layertruego( $str, $url, $parent = 'parent.' ) {
	echo "<script>parent.layer.open({title:false,content:'" . $str . "',icon: 1,time: 3000,end:function(){" . $parent . "location.href='" . $url . "'}}); </script>";
	exit();
}

function layernext($str){
	echo "<script>{parent.layer.confirm('" . $str . "',function(){parent.layer.closeAll('dialog')},function(){parent.location.reload();});}</script>" ;
}

function layermsggo( $str, $url ) {
	echo( "<script>{parent.layer.msg('" . $str . "',{icon: 1,time: 1500}, function(){location.href='" . $url . "'});}</script>" );
	exit();
}

function layererr( $str, $url = 'back' ) {
	if ( $url == "" || $url == 'close' ) {
		$url = "end:function(){parent.layer.closeAll()}";
	} elseif ( $url == '-1' || $url == 'back' ) {
		$url = "end:function(){location.href='javascript:history.go(-1)'}";
	} else {
		$url = "end:function(){location.href='" . $url . "'}";
	}
	echo "<script>parent.layer.open({title:'错误提示',content:'" . $str . "',icon: 2,time: 3000," . $url . "}); </script>";
	exit();
}

function layerclose( $type = 0 ) {
	if ( $type == 0 ) {
		echo( "<script>{parent.layer.closeAll()};</script>" );		
	} elseif ( $type == 1 ) {
		echo( "<script>layer.closeAll();</script>" );
	}
    exit();
}

function alert( $strs ) {
	echo "<script>{parent.layer.msg('" . $strs . "')}</script>";
}

function alertgo( $str, $url ) {
	if ( $url == '-1' && isset( $_SERVER[ 'HTTP_REFERER' ] ) ) {
		$url = $_SERVER[ 'HTTP_REFERER' ];
	}
	echo '<script>alert("' . $str . '");location.href="' . $url . '";</script>';
	exit();
}

function back( $str ) {
	echo "<script>alert('" . $str . "');location.href='javascript:history.go(-1)'</script>";
	exit();
}

function setCheck( $num ) {
	if ( $num == 1 ) {
		return "checked";
	}
}

function check_on( $str, $val, $back = 'checked' ) {
	if ( $str == $val ) {
		return $back;
	} elseif ( ifstrin( $str, $val ) ) {
		return $back;
	}
}

//根据开关，返回汉字
function check_onoff( $onoff, $caseval, $no = "", $num = 1 ) {
	$onoff = isnum( $onoff );
	switch ( $caseval ) {
		case "ch":
			return $onoff == $num ? "已开启" : "已关闭";
			break;
		case "en":
			return $onoff == $num ? "ON" : "OFF";
			break;
		case "db":
			return $onoff == $num ? "MSsql" : "Access";
			break;
		case "hide":
			return $onoff == $num ? "" : "style='display: none;'";
		case "show":
			return $onoff == $num ? "style='display: block;'" : "";
			break;
		default:
			return $onoff == $num ? $caseval : $no;
	}
}

function returnmsg( $type, $code=0, $message = '', $backurl = '' ) {
    if ( empty( $message ) ) {
        $message = $code > 0 ? '操作成功' : '操作失败';
    }
    switch ( $type ) {
        case 'json':
            echo tojson( array( 'return_code' => $code, 'return_msg' => $message, 'return_url' => $backurl ) );
            break;         
        case 'ok':
            $code > 0 ? ok( $message, $backurl ) : error( $message, $backurl );
            break;
        case 'open':
            $code > 0 ? layertrue( $message ) : layererr( $message );
            break;
		case 'layer':
            $code > 0 ? layermsggo( $message,$backurl ) : layererr( $message,$backurl );
            break;				
        case 'back':
            back( $message );
            break;
        case 'parent':
            if ( $code > 0 ) {
                echo( '<script>alert("' . $message . '");parent.location.href="' . $backurl . '";</script>' );
            } else {
                back( $message );
            }
            break;
		case 'close':
			echo( '<script>parent.layer.closeAll()</script>' );
		case 'wap':
            $backurl =SITE_PATH=="/" ? '/wap'.$backurl : str_replace(SITE_PATH,SITE_PATH.WAPPATH,$backurl);
        default:
            $code > 0 ? phpgo( $backurl ) : back( $message );
    }
    exit;
}

function str_log_post_data() {
	$method = $_SERVER[ 'method' ];
	if ( $method != 'POST' ) return;
	$post = $_POST;
	isset( $post[ 'password' ] )AND $post[ 'password' ] = '******'; // 干掉密码信息
	isset( $post[ 'password_new' ] )AND $post[ 'password_new' ] = '******'; // 干掉密码信息
	isset( $post[ 'password_old' ] )AND $post[ 'password_old' ] = '******'; // 干掉密码信息

	str_log( tojson( $post ), 'post_data' );
}

// 中断流程很危险！可能会导致数据问题，线上模式不允许中断流程！
function error_handle( $errno, $errstr, $errfile, $errline ) {
	// 如果放在 register_shutdown_function 里面，文件句柄会被关闭，然后这里就写入不了文件了！
	// if(strpos($s, 'error_log(') !== FALSE) return TRUE;
	$time = $_SERVER[ 'time' ];
	$ajax = $_SERVER[ 'ajax' ];
	IN_CMD AND $errstr = str_replace( '<br>', "\n", $errstr );

	$subject = "Error[$errno]: $errstr, File: $errfile, Line: $errline";
	if(DEBUG==1){
		$message = array();
	}else if(DEBUG==2){
		str_log( $subject, 'error' ); // 所有PHP错误报告都记录日志
		return;
	}else{
		return FALSE;
	}
	$message = array();
	str_log( $subject, 'error' ); // 所有PHP错误报告都记录日志
	$arr = debug_backtrace();
	array_shift( $arr );
	foreach ( $arr as $v ) {
		$args = '';
		if ( !empty( $v[ 'args' ] ) && is_array( $v[ 'args' ] ) )
			foreach ( $v[ 'args' ] as $v2 )$args .= ( $args ? ' , ' : '' ) . ( is_array( $v2 ) ? 'array(' . count( $v2 ) . ')' : ( is_object( $v2 ) ? 'object' : $v2 ) );
		!isset( $v[ 'file' ] )AND $v[ 'file' ] = '';
		!isset( $v[ 'line' ] )AND $v[ 'line' ] = '';
		$message[] = "File: $v[file], Line: $v[line], $v[function]($args) ";
	}
	$txt = $subject . "\r\n" . implode( "\r\n", $message );
	$html = $s = "<fieldset class=\"fieldset small notice\">
			<b>" . $subject . "</b>
			<div>" . implode( "<br>\r\n", $message ) . "</div>
		</fieldset>";
	echo( $ajax || IN_CMD ) ? $txt : $html;
	return TRUE;
}

// 使用全局变量记录错误信息
function xn_error( $no, $str, $return = FALSE ) {
	global $errno, $errstr;
	$errno = $no;
	$errstr = $str;
	return $return;
}

function error( $string, $url = null, $time = 3 ) {
	$uri = $_SERVER[ 'REQUEST_URI' ];
	if(stristr( $uri, "from" ) || stristr( $uri, "isapp") || stristr( $uri, "source" )){
		$uri=str_replace(array(WAPPATH,'?tdsourcetag=s_pctim_aiomsg','?from=singlemessage','=&from=singlemessage','&isappinstalled=0','?from=timeline','=&from=timeline'),'',$uri);
		phpgo($uri);
	}	
    if ( DEBUG ){
        $arr = debug_backtrace();
		$str='';
        array_shift( $arr );
        foreach ( $arr as $v ) {
            $args = '';
            if ( !empty( $v[ 'args' ] ) && is_array( $v[ 'args' ] ) )
                foreach ( $v[ 'args' ] as $v2 )$args .= ( $args ? ' , ' : '' ) . ( is_array( $v2 ) ? 'array(' . count( $v2 ) . ')' : ( is_object( $v2 ) ? 'object' : $v2 ) );
            !isset( $v[ 'file' ] )AND $v[ 'file' ] = '';
            !isset( $v[ 'line' ] )AND $v[ 'line' ] = '';			
            $str .= "文件: $v[file]; 第: $v[line]行; $v[function]($args) </br>";
        }		
		$str=str_replace('\\','/',$str);
		$str=str_replace(',',';',$str);
		$string.=','.$str;
    }
    //str_log( $string, 'error' ); // 所有PHP错误报告都记录日志
	if (!defined('TPL_DIR')){
		$err_tpl =PLUG_DIR . 'template/error.html';
	}else{
		$err_tpl =TPL_DIR. 'error.html' ?:  PLUG_DIR . 'template/error.html';
	}
	if ( $url == '-1' && isset( $_SERVER[ 'HTTP_REFERER' ] ) ) {
		$url = $_SERVER[ 'HTTP_REFERER' ];
	} 
	parse_error( $err_tpl, $string, $url, $time );
}

function ok( $string, $url = null, $time = 3 ) {
	if ( !$string ) 	$string = '未知错误,请联系管理员！';
	if (!defined('TPL_DIR')){
		$err_tpl = PLUG_DIR . 'template/ok.html';
	}else{
		$err_tpl = TPL_DIR. 'ok.html' ?:  PLUG_DIR . 'template/ok.html';
	}

	if ( $url == '-1' && isset( $_SERVER[ 'HTTP_REFERER' ] ) ) {
		$url = $_SERVER[ 'HTTP_REFERER' ];
	}
	parse_error( $err_tpl, '1,'.$string, $url, $time );
}

function parse_error( $info_tpl, $string, $url, $time ) {
	if ( !file_exists( $info_tpl ) )  	$info_tpl = PLUG_DIR . 'template/error.html';
	if ( file_exists( $info_tpl ) ) {
		$tpl_content = file_get_contents( $info_tpl );
		if ( $url == 'close' ) {
			$timeout_js = '';
			$url = 'javascript:void(0)';
			$btn= '关　闭';
			if( is_weixin()){
				$click = "onclick=\"WeixinJSBridge.call('closeWindow');\"" ;				
			}else{
				$click = "onclick=\"javascript:window.opener=null;window.open('','_self');window.close();\"";
			}
		} elseif ( $url ) {
				$click = "";
				$btn= '确　定';
				$timeout_js = "<script>var timeout = {time};var showbox = document.getElementById('time');show();function show(){showbox.innerHTML = timeout+ ' 秒后自动跳转';timeout--;if (timeout == 0) {window.location.href = '{url}';}else {setTimeout(function(){show();}, 1000);}}</script>";
		}else{
			$click = "";
			$timeout_js = '';
			$btn= '首　页';
			$url = SITE_PATH;
		}
		$errstr = explode( ",", $string . ',,' );
		if( is_numeric($errstr[ 0 ])){
			if($errstr[ 0 ]>1000){
				$img='503';
			}else if(!is_file( PLUG_DIR.'/template/images/'.$errstr[ 0 ].'.png' )){
				$img='500';
			}else{
				$img=$errstr[ 0 ];
			}
			$code=$errstr[ 0 ];
			$title=$errstr[ 1 ];
			$info=$errstr[ 2 ];
			$desc=$errstr[ 3 ];
		}else{
			$img='500';
			$code='500';
			$title=$errstr[0 ];
			$info=$errstr[ 1 ];
			$desc=$errstr[ 2 ];
		}
		$desc=str_replace(SITE_DIR,'',$desc);
		$tpl_content = str_replace( '{sitepath}', SITE_PATH, $tpl_content );
		$tpl_content = str_replace( '{js}', $timeout_js, $tpl_content );
		$tpl_content = str_replace( '{click}', $click, $tpl_content );
		$tpl_content = str_replace( '{btn}', $btn, $tpl_content );
		$tpl_content = str_replace( '{img}', $img , $tpl_content );
		$tpl_content = str_replace( '{code}', $code , $tpl_content );
		$tpl_content = str_replace( '{title}', $title , $tpl_content );
		$tpl_content = str_replace( '{info}',$info, $tpl_content );
		$tpl_content = str_replace( '{desc}', $desc, $tpl_content );
		$tpl_content = str_replace( '{url}', $url, $tpl_content );
		$tpl_content = str_replace( '{year}', date('Y'), $tpl_content );
		$tpl_content = str_replace( '{time}', $time, $tpl_content );
		echo $tpl_content;
		exit;
	} else {
		exit( '<div style="font-size:50px;">:(</div>提示信息的模板文件不存在！' . $info_tpl );
	}
}

function checkarr( $key, $value, $str, $name = NULL ) {
	switch ( $key ) {
		case 'max':
			return ( int )$str <= ( int )$value ? '' : array('code'=>0,'err'=>'很抱歉，' . $name . '最大不能超过' . $value);
			break;
		case 'min':
			return ( int )$str >= ( int )$value ? '' : array('code'=>0,'err'=>'很抱歉，' . $name . '最小不能低于' . $value);
			break;
		case 'lenmax':
			return lenstr( $str ) <= ( int )$value ? '' :  array('code'=>0,'err'=>'很抱歉，'. $name . '最多不能超过' . $value . '位');
			break;
		case 'lenmin':
			return lenstr( $str ) >= ( int )$value ? '' :  array('code'=>0,'err'=>'很抱歉，' . $name . '最低不能少于' . $value . '位');
			break;
	}
}

function name_title( $name ) {
    if(ifch($name ))  return $name ;
	$arr = arr_split( $name, '_', 1 );
	$name = !empty( $arr ) ? $arr : $name;
	switch ( $name ) {
		case 'json':
			return $name ;
			break;
		case 'name':
        case 'title':
			return '标题';
			break;       
		case 'enname':
			return '拼音';
			break;
		case 'type':
		case 'customclass':
			return '类型';
			break;
		case 'slideimg':
			return '图片地址';
			break;
		case 'cid':
			return '分组';
			break;
		case 'gid':
			return '会员分组';
			break;
		case 'username':
			return '登录账号';
			break;
		case 'truename':
			return '真实姓名';
			break;        
		case 'mobile':
			return '手机';
			break; 
		default:
			$custom = db_select( 'content_custom', 'customname', array( 'custom' => $name ) );
			$name = empty( $custom ) ? $name : $custom;
			return $name;
	}
}

function checkstr( $str, $type, $name = NULL ) {
	$err = '';
	$code = 1;
	if ( empty( $type ) ) return array( 'code' => 1, 'err' => '' );
	if ( is_array( $type ) ) {
		foreach ( $type as $key => $value ) {
			$arr = checkarr( $key, $value, $str, $name );
			if ( isset( $arr[ 'code' ] ) ) {
			 $code = 0;
			 $err .= $arr[ 'err' ];				
			}
		}
	}
    $title=name_title( $name );
	switch ( $type ) {
		case 'nul':
		case 's':
        case '*':    
			if ( isnul( $str ) ) {
				$code = 0;
				$err = '很抱歉,' . $title . '不能为空！';
			}
			break;
		case 'sel':
			if ( isnul( $str ) ) {
				$code = 0;
				$err = '很抱歉,' . $title . '未选择！';
			}
			break;
		case 'num':
		case 'n':
			if ( !is_numeric( $str ) ) {
				$code = 0;
				$err = '很抱歉,' . $title . '必须为数字！';
			}
			break;
		case 'int':
			if ( !preg_match( '/^[0-9]+$/', $str ) ) {
				$code = 0;
				$err = '很抱歉,' . $title . '必须为整数！';
			}
			break;
		case 'en':
			if ( !preg_match( '/^[a-z_]+$/', $str ) ) {
				$code = 0;
				$err = '很抱歉,' . $title . '必须为小写字母！';
			}
			break;
		case 'name':
			if ( !preg_match( '/^[a-z0-9]+$/', $str ) ) {
				$code = 0;
				$err = '很抱歉,' . $title . '必须为小写字母或数字！';
			}
			break;
		case 'cn':
			if ( !preg_match( '^[\u4e00-\u9fa5]+$', $str ) ) {
				$code = 0;
				$err = '很抱歉,' . $title . '必须为中文！';
			}
			break;
		case 'date':
			if ( !strtotime( $str ) ) {
				$code = 0;
				$err = '很抱歉,时间必须为日期类型！';
			}
			break;
		case 'array':
			if ( !is_array( $str ) ) {
				$code = 0;
				$err = '很抱歉,' . $title . '必须为数组类型！';
			}
			break;
		case 'gbookcode':
		case 'usercode':
			if ( conf( $type ) == 1 ) {
				if ( lenstr( $str ) != 4 ) {
					$code = 0;
					$err = '很抱歉,验证码位数不正确！';
				} elseif ( $str != _SESSION( 'code' ) ) {
					$code = 0;
					$err = '验证码错误,请检查验证码是否正确！';
				}
			}
			break;
		case 'code':
			if ( conf( 'iscode' ) == 1 ) {
				if ( lenstr( $str ) != 4 ) {
					$code = 0;
					$err = '很抱歉,验证码位数不正确！';
				} elseif ( $str != _SESSION( 'code' ) ) {
					$code = 0;
					$err = '验证码错误,请检查验证码是否正确！';
				}
			}
			break;
		case 'mobile':
		case 'm':
			if ( !preg_match( "/^1[3-8]\d{9}$/", $str ) ) {
				$code = 0;
				$err = '很抱歉,手机格式不正确！';
			}
			break;
		case 'tel':
			if ( !preg_match( '^(\+?\d{2,3})?0?1(3\d|5\d|8[06789])\d{8}$|((\(\+?\d{2,3}\))|(\+?\d{2,3}\-))?(\(0?\d{2,3}\)|0?\d{2,3}-)?[1-9]\d{4,7}(\-\d{1,4})?$', $str ) ) {
				$code = 0;
				$err = '很抱歉,电话号格式不正确！';
			}
			break;
		case 'email':
		case 'e':
			if ( !preg_match( "/\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/", $str ) ) {
				$code = 0;
				$err = '很抱歉,邮箱格式不正确！';
			}
			break;
		case 'qq':
			if ( !preg_match( "[1-9]\d{5}(?!\d)", $str ) ) {
				$code = 0;
				$err = '很抱歉,qq格式不正确！';
			}
			break;
		case 'cardid':
			if ( !preg_match("/^(([1][1-5])|([2][1-3])|([3][1-7])|([4][1-6])|([5][0-4])|([6][1-5])|([7][1])|([8][1-2]))\d{4}(([1][9]\d{2})|([2]\d{3}))(([0][1-9])|([1][0-2]))(([0][1-9])|([1-2][0-9])|([3][0-1]))\d{3}[0-9xX]$/", $str ) ) {
				$code = 0;
				$err = '很抱歉,身份证格式不正确！';
			}
			break;
		case 'url':
			if (!preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", $str ) ) {
				$code = 0;
				$err = '很抱歉,网址格式不正确！';
			}
			break;
		case 'pass':
			$level=0;
			if ( lenstr( $str ) > 5 &&   lenstr( $str ) < 20) {			
				$preg_rules = array(					
					'/[a-z]+/',// 至少一个小写字母					
					'/[A-Z]+/',// 至少一个大写字母					
					'/\d+/',// 至少一个数字					
					'/[@#$%^&*()]/'// 至少一个特殊字符
				);
				foreach ($preg_rules as $preg_rule) {
					if (preg_match($preg_rule, $str)) {
						$level++;
					}
				}
				if ( $level<=1 ) {
					$code = 0;
					$err = '很抱歉,密码强度不够，请确保数字、字母或符号两种以上组合！';
				}
			}else{
				$code = 0;
				$err = '很抱歉,密码必须为6-20位';
			}
			
			break;
	}
	if ( is_null( $name ) ) {
		if ( $code == 1 ) {
			return true;
		} else {
			return false;
		}
	}else if (  $name =='json' &&  $code == 0  ) {
		returnmsg('json',$code,$err);
	} else {
		return array( 'code' => $code, 'err' => $err );
	}
}

function getheaders($name=NULL){
	foreach ($_SERVER as $key => $value)	{
		if (substr($key, 0, 5) == 'HTTP_'){
			$headers[str_replace(' ', '-', strtolower(str_replace('_', ' ', substr($key, 5))))] = $value;
		}
	}
	if($name){
		$name=strtolower($name);
		return isset($headers[$name]) ? $headers[$name] : $headers;
	}else{
		return $headers;
	}	
}


function getform( $name, $source = 'both', $type = NULL, $default = NULL ) {
	switch ( $source ) {
		case 'post':
			$data = _POST( $name );
			break;
		case 'get':
			$data = _GET( $name );
			break;
		case 'cookie':
			$data = safe_key(_REQUEST($name),50);
            if($data) {
                 setcookie( $name,$data,3600, SITE_PATH );
            }else{
                $data=safe_key(_COOKIE($name));
            }
			break;
		case 'both':
			$data = _POST( $name ) ? : _GET( $name );
			break;
	}
    if($name=='act'){
        if(_REQUEST('act')!=''){
            $referer=_SERVER('HTTP_REFERER');
            if(!defined('WAPPATH') && $referer) {         
                if ( strpos( $referer, conf('wappath' )) !== FALSE ) {  
                    define( 'WAPPATH', conf('wappath' ));		
                }
            }
        }
    }
	if ( !is_null( $type ) ) {
        if(ifch($default)){
            $err = checkstr( $data, $type, $default );
        }else{
            $err = checkstr( $data, $type, $name );
        }
		if ( $err[ 'code' ] == 0 ){
			if ( $default == 'layer' ) {
				layererr( $err[ 'err' ] );
			}else if( $default == 'json' ){
				returnmsg('json',0,$err[ 'err' ]);
			}else{
				back($err[ 'err' ]);
			}
        }  
	}
	if ( !is_null( $default ) && !ifch( $default) ) {
		$data = empty( $data ) ? $default : $data;
	}
	return txt_html( $data );
}

function get_highlight($s,$key){
	if($key){
		$tempArr = get_searchkey($key,'array');
		if(is_array($tempArr)){
			foreach($tempArr as $k){
				$s= preg_replace("/($k)/i", "<font color=red><b>\\1</b></font>", $s);  
			}
		}
	}
	return $s;
}

/**
 * 模糊搜索中文分词
 */
function get_searchkey($words,$type='key'){	
	if(ifch($words)){
		$tempArr = str_split($words);
		$wordArr = array();
		$temp = '';
		$count = 0;
		$chineseLen = 3;
		foreach($tempArr as $word){
			if ($count == $chineseLen){
				$wordArr[] = $temp;
				$temp = '';
				$count = 0;
			}
	
			// 中文
			if(ord($word) > 127){
				$temp .= $word;
				++$count;
			}else if (ord($word) != 32){
				$wordArr[] = $word;
			}
		}
	
		if ($count == $chineseLen){
			$wordArr[] = $temp;
		}
		if($type=='key'){
			return '%'.implode('%', $wordArr).'%';
		}else if($type=='array'){
			return $wordArr;
		}
	}else if($words){
		return '%'.$words.'%';
	}else{
		return '';
	}
}

function get_session( $name ) {
	if ( !is_null( $name ) ) {
		if(conf('cachetype')=='Redis'){
			$data=get_cache($name);
		}else if ( ISSESSION == 1 ) {
			$data = isset( $_SESSION[ $_SERVER[ 'prefix' ] . $name ] ) ? $_SESSION[ $_SERVER[ 'prefix' ] . $name ] : NULL;
		} elseif ( ISSESSION == 0 ) {
			$data = isset( $_COOKIE[ $_SERVER[ 'prefix' ] . $name ] ) ? $_COOKIE[ $_SERVER[ 'prefix' ] . $name ] : NULL;
		}
		return $data;
	}
}

function del_session( $name ) {
	if ( !is_null( $name ) ) {
		if(conf('cachetype')=='Redis'){
			del_cache($name);
		}else if ( ISSESSION == 1 ) {
			unset( $_SESSION[ $_SERVER[ 'prefix' ] . $name ] );
		} elseif ( ISSESSION == 0 ) {
			setcookie( $_SERVER[ 'prefix' ] . $name, '', time() - 1, '/' );
		}
	}
}

function set_session( $name, $value ) {
	if ( !is_null( $name ) ) {
		if(conf('cachetype')=='Redis'){
			set_cache($name,$value);
		}else if ( ISSESSION == 1 ) {
			$_SESSION[ $_SERVER[ 'prefix' ] . $name ] = $value;
		} elseif ( ISSESSION == 0 ) {
			setcookie( $_SERVER[ 'prefix' ] . $name, $value, time() + 3600 * 24 * 365, '/' );
		}
		return $value;
	}
}

function get_cookie( $name ) {
	if ( is_null( $name ) ) return '';
	$data = isset( $_COOKIE[ $_SERVER[ 'prefix' ] . $name ] ) ? $_COOKIE[ $_SERVER[ 'prefix' ] . $name ] : NULL;
	return safe_url($data);
}
//secure
function set_cookie( $name, $value, $day =90,$secure=true) {
	//setcookie(string $name, string $value = "", int $expires = 0, string $path = "", string $domain = "", bool $secure = false, bool $httponly = false)
	if ( !is_null( $name ) ) {
		$value = is_null($value) ? '' : $value;
		$expires = is_numeric( $day ) ? time() + 3600 * 24 * $day :  time() + 3600 * 24 * 7;
		$path =SITE_PATH;	
		if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'){
			$secure= $secure;
			$httponly = $secure;	
			$domain=$_SERVER["HTTP_HOST"] ?: $_SERVER[ 'SERVER_NAME' ];
		}else{
			$secure= false;
			$httponly = false;
			$domain="";
		}
		return setcookie( $_SERVER[ 'prefix' ] . $name, $value,$expires,$path, $domain,$secure ,$httponly);
	}
}

function del_cookie( $name ) {
	if ( !is_null( $name ) ) {
		$path =SITE_PATH;	
		if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'){
			$secure= true;
			$httponly = true;	
			$domain=$_SERVER["HTTP_HOST"] ?: $_SERVER[ 'SERVER_NAME' ];
		}else{
			$secure= false;
			$httponly = false;
			$domain="";
		}
		return setcookie( $_SERVER[ 'prefix' ] . $name, '', time() - 1, $path, $domain, $secure, $httponly);
	}
}

// 安全过滤字符串，仅仅保留 [a-zA-Z0-9_]，最长255字符
function safe_word( $s, $len=255) {
    if(is_null($s)) return ;
	$s = preg_replace( '#\W+#', '', $s );
	$s = substr( $s, 0, $len );
	return $s;
}
// 安全过滤字符串，仅仅保留 [汉字、数字、字母及=<>,_/]，最长255字符
function safe_key( $s , $len=255) {
    $s = decode(is_array($s) ? @implode(",",$s) : $s);
	if(is_null($s)) return ;
    preg_match_all('/[\x{4e00}-\x{9fa5}a-zA-Z0-9<>,.:=@?_\-\/\s]/u',$s,$result);
	$temp =join('',$result[0]);     
	$s = substr( $temp, 0, $len );	
	return $s;
}
// 安全过滤字符串，仅仅保留 [数字、字母及,.:=@?_/]，最长255字符
function safe_url( $s, $len=255) {
    if(is_null($s)) return ;
    preg_match_all('/[a-zA-Z0-9,.:=@?_、\-\/\s]/u',$s,$result);
    $temp =join('',$result[0]);     
    $s = substr( $temp, 0, $len );
	return $s;
}
//过滤危险字符，保留正常字符
function danger_key($s,$type=0) {
	if($type==1){
		$s= htmlspecialchars($s);
		$s =preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/','',$s);
		$s =preg_replace("/&(?!(#[0-9]+|[a-z]+);)/si",'&',$s); 
        $s =str_replace( array("php","\0", "%00", "\r","<", ">","'", '"', "{","}", "%3"), '', $s);
	}
	$str= $s;
	$danger=array('preg','server','chr','decode','md5','post','get','request','file','cookie','session','sql','mkdir','copy','fwrite','del','encrypt','$','system','exec','shell','open','ini_','chroot','eval','passthru','include','require','assert','union','create','func','symlink','sleep','ascii','print','echo','base_','replace','_map','_dump','_array','regexp','select','dbpre','zzz_','{if','curl','certutil');
   foreach ($danger as $val){
	   if(strpos($str,$val) !==false){
		   error('很抱歉，执行出错,系统限制使用【'.$val.'】，请点击返回重新操作,如此问题为误报，请联系管理员');
	  }
   }
	return $s;
}

function lang( $key, $arr = array() ) {
	$lang = $_SERVER[ 'lang' ];
	if ( !isset( $lang[ $key ] ) ) return 'lang[' . $key . ']';
	$s = $lang[ $key ];
	if ( !empty( $arr ) ) {
		foreach ( $arr as $k => $v ) {
			$s = str_replace( '{' . $k . '}', $v, $s );
		}
	}
	return $s;
}

function jsgo( $message, $url = '', $delay = 3 ) {
	//$ajax = $_SERVER['ajax'];
	//if($ajax) return $message;
	if ( !$url ) return $message;
	$url == 'back'
	AND $url = 'javascript:history.back()';
	$htmladd = '<script>setTimeout(function() {window.location=\'' . $url . '\'}, ' . ( $delay * 1000 ) . ');</script>';
	return '<a href="' . $url . '">' . $message . '</a>' . $htmladd;
}

// txt 转换到 html
function txt_html( $s ) {    
	if ( !$s ) return $s;
	if ( is_array( $s ) ) { // 数组处理
		foreach ( $s as $key => $value ) {
			$string[ $key ] = txt_html( $value );
		}
	} else {       
		$s = trim( $s );
		//array("'"=>"&apos;",'"'=>"&quot;",'<'=> "&lt;",'>'=> "&gt;");     
        $s = htmlspecialchars( $s,ENT_QUOTES,'UTF-8' );  
		$s = str_replace( "\t", ' &nbsp; &nbsp; &nbsp; &nbsp;', $s );		
		$s = preg_replace('/script/i', 'scr1pt', $s );
		$s = preg_replace('/document/i', 'd0cument', $s );		
		$s = preg_replace('/\.php/i', '．php', $s );
        $s = preg_replace('/ascii/i', 'asc11', $s );
		$s = preg_replace('/eval/i' , 'eva1', $s );
		$s = str_replace( array("base64_decode", "assert", ""), "", $s );
		$s = str_replace( array("\r\n","\n"), "<br/>", $s );
	}
	return $s;
}
// html 转换到 txt
function html_txt( $s ) {
	if ( !$s ) return $s;
	$s = decode( $s );	
    $br = array( '<br>', '</br>', '<br/>' );
    $s = str_replace( $br, '', $s );
	$s = preg_replace('/scr1pt/i', 'script', $s );
	$s = preg_replace('/d0cument/i', 'document', $s );		
    $s = preg_replace('/．php/i', '.php', $s );
    $s = preg_replace('/asc11/i', 'ascii', $s );
	$s = preg_replace('/eva1/i' , 'eval', $s );
	$s = str_replace(' &nbsp; &nbsp; &nbsp; &nbsp;', "\t", $s );
	$s = strip_tags( $s );
    return $s;
}

function html_textarea( $s ){
    if ( !$s ) return $s;
    $br = array( '<br>', '</br>', '<br/>' );
    $s = str_replace( $br, PHP_EOL, $s );
	$s = preg_replace('/scr1pt/i', 'script', $s );
	$s = preg_replace('/d0cument/i', 'document', $s );	
    $s = preg_replace('/．php/i', '.php', $s );
    $s = preg_replace('/asc11/i', 'ascii', $s );
	$s = preg_replace('/eva1/i' , 'eval', $s );
	$s = str_replace(' &nbsp; &nbsp; &nbsp; &nbsp;', "\t", $s );
    return $s;
}

function html_info( $s ) {
	if ( !$s ) return $s;
	$s = decode( $s );
	$br = array( '<br>', '</br>', '<br/>' );
	$s = str_replace( $br, '[br]', $s );
	$s = str_replace( '</p>', "</p>[br]", $s );	
	$s = strip_tags( $s );
	$s = str_replace( "[br]", '<br/>', $s );
	$s = str_replace( "<br/><br/>", '<br/>', $s );
	return $s;
}

function html_wx( $s ) {
	if ( !$s ) return $s;
	$s = decode( $s );
	$s = str_replace( '<br/>', "[br]", $s );
	$s = str_replace( '</p>', "</p>[br]", $s );
	$s = strip_tags( $s );
	$s = str_replace( '[br]', PHP_EOL, $s );
	return $s;
}

function br_txt( $s ) {
	$br = array( '<br>', '</br>', '<br/>' );
	$s = str_replace( $br, PHP_EOL, $s );
	return $s;
}

function html_AI($html) {
    // 1. 预处理：将<br>标签替换为临时换行标记（避免被DOM解析器忽略）
    $html = str_ireplace(['<br>', '<br/>', '<br />'], '[br]', $html);

    // 2. 初始化DOM解析器
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<meta charset="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    // 3. 定义需要保留的媒体标签
    $preserveTags = ['img', 'video', 'audio', 'source'];
    $preserveTags = array_flip($preserveTags);

    // 4. 递归处理节点（明确换行逻辑）
    $processNode = function ($node) use (&$processNode, $preserveTags, $dom) {
        $result = [];

        if ($node->nodeType === XML_ELEMENT_NODE) {
            $tagName = strtolower($node->tagName);

            // 保留媒体标签
            if (isset($preserveTags[$tagName])) {
                $html = $dom->saveHTML($node);
                $result[] = $html;
                return $result;
            }

            // 块级标签：前后添加换行标记（确保分隔）
            $blockTags = ['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'blockquote', 'pre'];
            $isBlock = in_array($tagName, $blockTags);
            $childContent = [];

            foreach ($node->childNodes as $child) {
                $childContent = array_merge($childContent, $processNode($child));
            }

            // 块级标签前后添加换行（使用临时标记）
            if ($isBlock) {
                return array_merge(['[br]'], $childContent, ['[br]']);
            }
            return $childContent;
        }

        // 处理文本节点：保留原始换行
        if ($node->nodeType === XML_TEXT_NODE) {
            $text = $node->nodeValue;
            // 替换文本内的换行符为临时标记
            $text = preg_replace("/\r\n|\r|\n/", '[br]', $text);
            // 清理连续空格（不影响换行）
            $text = preg_replace('/[ \t]+/', ' ', $text);
            if (trim($text) !== '') {
                $result[] = $text;
            }
        }

        return $result;
    };

    // 5. 处理所有节点
    $rootNodes = $dom->childNodes;
    $allParts = [];
    foreach ($rootNodes as $node) {
        $allParts = array_merge($allParts, $processNode($node));
    }

    // 6. 替换临时标记为PHP_EOL并清理格式
    $content = implode('', $allParts); // 不添加空格，避免干扰换行
    $content = str_replace('[br]', PHP_EOL, $content);
    // 合并连续换行（最多保留2个）
    $content = preg_replace('/(' . preg_quote(PHP_EOL, '/') . '){3,}/', PHP_EOL . PHP_EOL, $content);
    // 清理首尾空白
    return trim($content);
}

function decode( $s ) {
	if ( !$s ) return '';
	if ( is_array( $s ) ) { // 数组处理
		foreach ( $s as $key => $value ) {
			$s[ $key ] = decode( $value );
		}
	} elseif ( is_object( $s ) ) { // 对象处理
		foreach ( $s as $key => $value ) {
			$s->$key = decode( $value );
		}
	} else { // 字符串处理		
       $s = htmlspecialchars_decode( trim( $s ) ,ENT_QUOTES);      
	}
	return $s;
}

function encode( $s ) {
	if ( !$s ) return $s;
	if ( is_array( $s ) ) { // 数组处理
		foreach ( $s as $key => $value ) {
			$s[ $key ] = encode( $value );
		}
	} elseif ( is_object( $s ) ) { // 对象处理
		foreach ( $s as $key => $value ) {
			$s->$key = encode( $value );
		}
	} else { // 字符串处
        $s = htmlspecialchars( trim( $s ),ENT_QUOTES,'UTF-8' );  		
		$s = str_replace('&lt;br/&gt;','<br/>',$s );
	}
	return $s;
}

function en_url( $s ) {
	$s = urlencode( $s );
	$s = str_replace( '_', '_5f', $s );
	$s = str_replace( '-', '_2d', $s );
	$s = str_replace( '.', '_2e', $s );
	$s = str_replace( '+', '_2b', $s );
	$s = str_replace( '=', '_3d', $s );
	$s = str_replace( '%', '_', $s );
	return $s;
}

function de_url( $s ) {
	$s = str_replace( '_', '%', $s );
	$s = urldecode( $s );
	return $s;
}

function replacestr($str,$str1,$str2=""){
	empty($str1) and die($str);
	$str= str_replace($str1,$str2,$str);
	return $str;
}

function tojson( $data, $pretty = FALSE, $level = 0 ) {
	$tab = $pretty ? str_repeat( "\t", $level ) : '';
	$tab2 = $pretty ? str_repeat( "\t", $level + 1 ) : '';
	$br = $pretty ? "\r\n" : '';
	switch ( $type = gettype( $data ) ) {
		case 'NULL':
			return 'null';
		case 'boolean':
			return ( $data ? 'true' : 'false' );
		case 'integer':
		case 'double':
		case 'float':
			return $data;
		case 'string':
			$data = '"' . str_replace( array( '\\', '"' ), array( '\\\\', '\\"' ), $data ) . '"';
			$data = str_replace( "\r", '\\r', $data );
			$data = str_replace( "\n", '\\n', $data );
			$data = str_replace( "\t", '\\t', $data );
			return $data;
		case 'object':
			$data = get_object_vars( $data );
		case 'array':
			$output_index_count = 0;
			$output_indexed = array();
			$output_associative = array();
			foreach ( $data as $key => $value ) {
				$output_indexed[] = tojson( $value, $pretty, $level + 1 );
				$output_associative[] = $tab2 . '"' . $key . '":' . tojson( $value, $pretty, $level + 1 );
				if ( $output_index_count !== NULL && $output_index_count++ !== $key ) {
					$output_index_count = NULL;
				}
			}
			if ( $output_index_count !== NULL ) {
				return '[' . implode( ",$br", $output_indexed ) . ']';
			} else {
				return "{{$br}" . implode( ",$br", $output_associative ) . "{$br}{$tab}}";
			}
		default:
			return ''; // Not supported
	}
}

function jsonto( $json,$s='' ) {  
	$json = trim( $json, "\xEF\xBB\xBF" );
	$json = trim( $json, "\xFE\xFF" );
	$lpos = strpos( $json, "(" );
    $rpos = strrpos( $json, ")" );
	$json =$rpos >0 ? substr( $json, $lpos + 1, $rpos - $lpos - 1 ) : $json;
	if(empty($s)){
		$json= json_decode( $json );
	}elseif($s){
		$json= json_decode( $json,true);
	}else{
		$arr=json_decode( $json, 1 );
		$json= isset($arr[$s]) ? $arr[$s] : '';
	}		
	return $json;	
}

function togbk( $data ) {
	if ( is_array( $data ) ) { // 数组处理
		foreach ( $data as $key => $value ) {
			$data[ $key ] = togbk( $value );
		}
	} elseif ( !empty( $data ) ) {
		if ( ifch( $data ) ) {
			$data =iconv( 'GBK','UTF-8//IGNORE', $data);
			//$data = mb_convert_encoding( $data, 'utf-8', 'GBK' );
		}
	}
	return $data;
}

function toutf( $data ) {
	if ( is_array( $data ) ) { // 数组处理
		foreach ( $data as $key => $value ) {
			$data[ $key ] = toutf( $value );
		}
	} elseif ( !empty( $data ) ) {
		if ( ifch( $data ) ) {
			$data =iconv( 'UTF-8','GBK//IGNORE', $data);
			//$data = mb_convert_encoding( $data, 'GBK', 'utf-8' );
		}
	}
	return $data;
}

function is_weixin() {
	if ( strpos( $_SERVER[ 'HTTP_USER_AGENT' ], 'MicroMessenger' ) !== false ) {
		return true;
	} else {
		return false;
	}
}
/*移动端判断*/
function is_mobile() {
	
	// 如果有HTTP_X_WAP_PROFILE则一定是移动设备  
	if ( isset( $_SERVER[ 'HTTP_X_WAP_PROFILE' ] ) ) return true;
	// 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息  
	if ( isset( $_SERVER[ 'HTTP_VIA' ] ) ) {
		if(stristr( $_SERVER[ 'HTTP_VIA' ], "wap" )) return true;
	}		
	// 野蛮方法，判断手机发送的客户端标志,兼容性有待提高  
	if ( isset( $_SERVER[ 'HTTP_USER_AGENT' ] ) ) {
		$clientkeywords = array( 'nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile' );
		// 从HTTP_USER_AGENT中查找手机浏览器的关键字  
		if ( preg_match( "/(" . implode( '|', $clientkeywords ) . ")/i", strtolower( $_SERVER[ 'HTTP_USER_AGENT' ] ) ) )
			return true;
	}
	// 协议法，因为有可能不准确，放到最后判断  
	if ( isset( $_SERVER[ 'HTTP_ACCEPT' ] ) ) {
		// 如果只支持wml并且不支持html那一定是移动设备  
		// 如果支持wml和html但是wml在html之前则是移动设备  
		if ( ( strpos( $_SERVER[ 'HTTP_ACCEPT' ], 'vnd.wap.wml' ) !== false ) && ( strpos( $_SERVER[ 'HTTP_ACCEPT' ], 'text/html' ) === false || ( strpos( $_SERVER[ 'HTTP_ACCEPT' ], 'vnd.wap.wml' ) < strpos( $_SERVER[ 'HTTP_ACCEPT' ], 'text/html' ) ) ) )
			return true;
	}	
	return false;
}

function pagination_tpl( $url, $page, $active = '',$style=1,$type='') {
	$g_pagination_tpl=check_file(PLUG_DIR.'pagesize/pagesize'.$style.'.tpl') ? load_file(PLUG_DIR.'pagesize/pagesize'.$style.'.tpl') : load_file(PLUG_DIR.'pagesize/pagesize1.tpl');
	if(empty( $g_pagination_tpl )){
		$g_pagination_tpl = '<li class="page-item"><a href="{url}" class="page-link {active}">{page}</a></li>';
	}else{
		$s_pagination_tpl=splits($g_pagination_tpl,';');
		switch ($type){
			case 'home'	: $g_pagination_tpl=str_replace('home=','',$s_pagination_tpl[0]);		break;
			case 'prev'	: $g_pagination_tpl=str_replace('prev=','',$s_pagination_tpl[1]);		break;	
			case 'page'	: $g_pagination_tpl=str_replace('page=','',$s_pagination_tpl[2]);		break; 
			case 'next'	: $g_pagination_tpl=str_replace('next=','',$s_pagination_tpl[3]);		break;
			case 'end'	: $g_pagination_tpl=str_replace('end=','',$s_pagination_tpl[4]);		break;
			case 'totalnum'		: $g_pagination_tpl=str_replace('totalnum=','',$s_pagination_tpl[5]);		break;
			case 'totalpage'	: $g_pagination_tpl=str_replace('totalpage=','',$s_pagination_tpl[6]);		break;
			case 'inputpage'	: $g_pagination_tpl=str_replace('inputpage=','',$s_pagination_tpl[7]);		break;	
			case 'more'	: $g_pagination_tpl=str_replace('more=','',$s_pagination_tpl[8]);		break;	
		}
	}
	$tpl= str_replace( array( '{url}', '{page}', '{active}' ), array( $url, $page, $active ), $g_pagination_tpl );
	if($page=='<'){
	   G('page')<3 and $tpl =  str_replace('_1','',$tpl);
	}else if($page=='>'){
	}else if($page<3){
	    $tpl =  str_replace('_1','',$tpl);
	}
    return $tpl;    
}

// bootstrap 翻页，命名与 bootstrap 保持一致
function pagination( $url, $totalnum, $page = 1, $pagesize = 20, $shownum=5,$style=1) {
	empty( $page )and $page = 1;
	//echop($url.',totalnum:'. $totalnum.',page:'. $page.',pagesize:'. $pagesize.',shownum:'.$shownum);
	$totalpage = ceil( $totalnum / $pagesize );
	if ( $totalpage < 2 ) return '';
	$page = intval(min( $totalpage, $page ));
	$start = max( 1, $page - intval($shownum/2) );
	$end = min( $totalpage, $page+intval($shownum/2) );
	// 不足 $shownum，补全左右两侧
	$right = $page + intval($shownum/2) - $totalpage;
	$right > 0 && $start = max( 1, $start -= $right );
	$left = $page - intval($shownum/2);
	$left < 0 && $end = min( $totalpage, $end -= $left );
	$s = '';
	$s .= $page == 1 ? pagination_tpl( 'javascript:void(0)', '<', 'disabled',$style,'prev' ):  pagination_tpl( str_replace( '{page}', intval($page) - 1, $url ), '<', '',$style,'prev' )  ;
	if ( $start > 1 ){		
		$s .= pagination_tpl( str_replace( '{page}', 1, $url ), '1 ' ,'',$style,'home' );
		if ($start > 2)   $s .= pagination_tpl( '','','',$style,'more' );
	}
	for ( $i = $start; $i <= $end; $i++ ) {
		$s .= pagination_tpl( str_replace( '{page}', $i, $url ), $i, $i == $page ? ' active' : '' ,$style,'page');
	}
	if ( $end != $totalpage ){
		if ($totalpage - $end > 1)  $s .= pagination_tpl( '','','',$style,'more' );
		$s .= pagination_tpl( str_replace( '{page}', $totalpage, $url ), $totalpage,'',$style,'end' );
	}
	if ( $page != $totalpage ) {
		$s .= pagination_tpl( str_replace( '{page}', intval($page) + 1, $url ), '>','',$style,'next' );
		conf('runmode')==1 and $GLOBALS[ 'page' ] = $page+1;
	} else {
		$s .= pagination_tpl('javascript:void(0)', '>','disabled',$style,'next' );
		conf('runmode')==1 and $GLOBALS[ 'page' ] = 0;
	}
	$s .= pagination_tpl( '', $totalnum,'',$style,'totalnum' );
	$s .= pagination_tpl( '', $totalpage,'',$style,'totalpage' );
	$s .= pagination_tpl( '', $page,'',$style,'inputpage' );
	return $s;
}

// 简单的上一页，下一页，比较省资源，不用count(), 推荐使用，命名与 bootstrap 保持一致
function pager( $url, $totalnum, $page, $pagesize = 20 ) {
	$totalpage = ceil( $totalnum / $pagesize );
	if ( $totalpage < 2 ) return '';
	$page = min( $totalpage, $page );

	$s = '';
	$page > 1 AND $s .= '<li><a href="' . str_replace( '{page}', $page - 1, $url ) . '">上一页</a></li>';
	$s .= " $page / $totalpage ";
	$totalnum >= $pagesize AND $page != $totalpage AND $s .= '<li><a href="' . str_replace( '{page}', $page + 1, $url ) . '">下一页</a></li>';
	$s = str_replace( '_1', '', $s );
	return $s;
}

function mid( $n, $min, $max ) {
	if ( $n < $min ) return $min;
	if ( $n > $max ) return $max;
	return $n;
}

function fromattime( $timestamp, $lan = array() ) {
	$time = $_SERVER[ 'time' ];
	$lang = $_SERVER[ 'lang' ];
    $seconds = $time - $timestamp;
	$lan = empty( $lang ) ? $lan : $lang;
	empty( $lan )AND $lan = array(
		'month_ago' => '月前',
		'day_ago' => '天前',
		'hour_ago' => '小时前',
		'minute_ago' => '分钟前',
		'second_ago' => '秒前',
	);
	if ( $seconds > 31536000 ) {
		return date( 'Y-n-j', $timestamp );
	} elseif ( $seconds > 2592000 ) {
		return floor( $seconds / 2592000 ) . $lan[ 'month_ago' ];
	} elseif ( $seconds > 86400 ) {
		return floor( $seconds / 86400 ) . $lan[ 'day_ago' ];
	} elseif ( $seconds > 3600 ) {
		return floor( $seconds / 3600 ) . $lan[ 'hour_ago' ];
	} elseif ( $seconds > 60 ) {
		return floor( $seconds / 60 ) . $lan[ 'minute_ago' ];
	} else {
		return $seconds . $lan[ 'second_ago' ];
	}
}


//格式化文件大小
function formatnum( $num ,$type) {
	$r='';
	switch($type){
		case 'time':
			if($num>60){
				$r= number_format( $num / 60, 2, '.', '' ) . '分钟';
			}elseif($num>1){
				$r= number_format( $num, 2, '.', '' ) . '秒';
			}elseif($num>0){
				$r= number_format( $num*1000, 2, '.', '' ) . '毫秒';
			}
		break;
		case 'money':			
			$r= $num > 100000 ?  ceil( $num / 10000 ) . '万元' : $num.'元';
		break;
		case 'size':
			if ( $num > 1073741824 ) {
				$r= number_format( $num / 1073741824, 2, '.', '' ) . 'GB';
			} elseif ( $num > 1048576 ) {
				$r= number_format( $num / 1048576, 2, '.', '' ) . 'MB';
			} elseif ( $num > 1024 ) {
				$r= number_format( $num / 1024, 2, '.', '' ) . 'KB';
			} else {
				$r= $num . 'B';
			}
		break;
	}	
	return $r;
}

function conf( $str ) {
	$conf = _SERVER( 'conf' );
	if(isset( $conf[ $str ] ) ){
		if(in_array($str,array('smsserver','regsendsms','forgetsendsms'))){
			return $conf['smsmark']==1 ? $conf[ $str ] : 0;
		}else if(in_array($str,array('gbooksendmail','evalsendmail','regsendmail','loginsendmail','forgetsendmail','shopcatsendmail','shopordersendmail','commentsendmail','submissionsendmail','diyformsendmail'))){
			return $conf['mailmark']==1 ? $conf[ $str ] : 0;
		}else{
			return $conf[ $str ];
		}
	}else{
		return '';
	}
}

// 不安全的获取 IP 方式，在开启 CDN 的时候，如果被人猜到真实 IP，则可以伪造。
function ip() {
	$ip = '127.0.0.1';
	if ( isset( $_SERVER[ 'HTTP_CDN_SRC_IP' ] ) ) {
		$ip = $_SERVER[ 'HTTP_CDN_SRC_IP' ];
	} elseif ( isset( $_SERVER[ 'HTTP_CLIENTIP' ] ) ) {
		$ip = $_SERVER[ 'HTTP_CLIENTIP' ];
	} elseif ( isset( $_SERVER[ 'HTTP_CLIENT_IP' ] ) ) {
		$ip = $_SERVER[ 'HTTP_CLIENT_IP' ];
	} elseif ( isset( $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) ) {
		$ip = $_SERVER[ 'HTTP_X_FORWARDED_FOR' ];
		$arr = array_filter( explode( ',', $ip ) );
		$ip = trim( end( $arr ) );
	} else {
		$ip = _SERVER( 'REMOTE_ADDR' );
	}
	$ip = $ip == '::1' ? '127.0.0.1' : $ip;
	return long2ip( ip2long( $ip ) );
}

function check_ip(){
	$ip=conf('safe_ip');
	$type=conf('safe_type');
	if($ip && $type){
		$ips=splits($ip,'<br/>');
		if($type==1){
			white_ip($ips);
		}else if($type==2){
			black_ip($ips);
		}else if($type==3){
			white_city($ips);
		}else if($type==4){
			black_city($ips);
		}else if($type==5){
			isIpInRange($ips);
		}else{
			return true;
		}
	}
}
function isIpInRange ($ips){
    $ip= ip();
    foreach ( $ips as $ip_rule){
        $arr=splits($ip_rule,'-');
        $start=$arr[0];
        $end=$arr[1];
        if (filter_var($ip, FILTER_VALIDATE_IP) && filter_var($start, FILTER_VALIDATE_IP) && filter_var($end, FILTER_VALIDATE_IP)) {
            $startLong = ip2long($start);
            $endLong = ip2long($end);
            $ipLong = ip2long($ip);
            if ($ipLong >= $startLong && $ipLong <= $endLong) {
                return true;
            }
        }
    }
    error( '501,禁止访问,您不允许访问本站，IP不在白名单范围内,'.$ip,'close' );
}


function ip_test( $ip, $iprule ) {
	$ipruleregexp = str_replace( '.*', 'ph', $iprule );
	$ipruleregexp = preg_quote( $ipruleregexp, '/' );
	$ipruleregexp = str_replace( 'ph', '\.[0-9]{1,3}', $ipruleregexp );

	if ( preg_match( '/^' . $ipruleregexp . '$/', $ip ) ) return true;
	else return false;
}
//IP白名单
function white_ip($ip) {
	$curr_ip =  ip();
	$white_list =$ip; //黑名单规则  
	$test_success = false;
	foreach ( $white_list as $iprule ) {
		if ( ip_test( $curr_ip, $iprule ) ) {
			$test_success = true;
			break;
		}
	}
	if ( !$test_success )error( '403,禁止访问,您不允许访问本站，IP不在白名单范围内,'.$curr_ip,'close' );
}
//IP黑名单
function black_ip($ip) {
	$curr_ip =ip();
	$black_list = $ip; //黑名单规则  
	foreach ( $black_list as $iprule ) {
		if ( ip_test( $curr_ip, $iprule ) ) {
			error( '403,禁止访问,您不允许访问本站，IP在黑名单范围内,'.$curr_ip,'close' );
		}
	}
}
//城市白名单
function white_city($citys){
	$ip = ip(); $test_success = false;	$city=iptocity($ip);
	foreach ( $citys as $v ) {
		if (ifstrin($city,$v)) $test_success = true;		
	}
	if ( !$test_success ) error( '403,禁止访问,城市不在白名单范围内,'.$city,'close' );
}
//城市黑名单
function black_city($citys) {
	$ip = ip();	$city=iptocity($ip);
	foreach ( $citys as $v ) {
		if (ifstrin($city,$v)) error( '403,禁止访问,城市在黑名单范围内,'.$city,'close' );
	}
}

function iptocity($ip){
    $json = http_get("http://opendata.baidu.com/api.php?query=$ip&co=&resource_id=6006&oe=utf8");
    $arr = jsonto($json, 1);
    if (isset($arr['data'][0]['location'])) {
        $str= $arr['data'][0]['location'];
        if(ifstrin(	$str,'省') || ifstrin(	$str,'自治区')  || ifstrin(	$str,'特别行政区') || ifstrin(	$str,'北京市') || ifstrin(	$str,'天津市') || ifstrin(	$str,'上海市') || ifstrin(	$str,'重庆市')){
             $r= '中国 '.$str;
        }else{
            $r= $str;
        }
    } else {
		$r= '';
    };
	return $r;
}

function get__browser() {
	// 默认为 chrome 标准浏览器
	$browser = array(
		'device' => 'pc', // pc|mobile|pad
		'name' => 'chrome', // chrome|firefox|ie|opera
		'version' => 30,
	);
	$agent = _SERVER( 'HTTP_USER_AGENT' );
	// 主要判断是否为垃圾 IE6789
	if ( strpos( $agent, 'msie' ) !== FALSE || stripos( $agent, 'trident' ) !== FALSE ) {
		$browser[ 'name' ] = 'ie';
		$browser[ 'version' ] = 8;
		preg_match( '#msie\s*([\d\.]+)#is', $agent, $m );
		if ( !empty( $m[ 1 ] ) ) {
			if ( strpos( $agent, 'compatible; msie 7.0;' ) !== FALSE ) {
				$browser[ 'version' ] = 8;
			} else {
				$browser[ 'version' ] = intval( $m[ 1 ] );
			}
		} else {
			// 匹配兼容模式 Trident/7.0，兼容模式下会有此标志 $trident = 7;
			preg_match( '#Trident/([\d\.]+)#is', $agent, $m );
			if ( !empty( $m[ 1 ] ) ) {
				$trident = intval( $m[ 1 ] );
				$trident == 4 AND $browser[ 'version' ] = 8;
				$trident == 5 AND $browser[ 'version' ] = 9;
				$trident > 5 AND $browser[ 'version' ] = 10;
			}
		}
	}

	if ( isset( $_SERVER[ 'HTTP_X_WAP_PROFILE' ] ) || ( isset( $_SERVER[ 'HTTP_VIA' ] ) && stristr( $_SERVER[ 'HTTP_VIA' ], "wap" ) || stripos( $agent, 'phone' ) || stripos( $agent, 'mobile' ) || strpos( $agent, 'ipod' ) ) ) {
		$browser[ 'device' ] = 'mobile';
	} elseif ( strpos( $agent, 'pad' ) !== FALSE ) {
		$browser[ 'device' ] = 'pad';
		$browser[ 'name' ] = '';
		$browser[ 'version' ] = '';
		/*
		} elseif(strpos($agent, 'miui') !== FALSE) {
			$browser['device'] = 'mobile';
			$browser['name'] = 'xiaomi';
			$browser['version'] = '';
		*/
	} else {
		$robots = array( 'bot', 'spider', 'slurp' );
		foreach ( $robots as $robot ) {
			if ( strpos( $agent, $robot ) !== FALSE ) {
				$browser[ 'name' ] = 'robot';
				return $browser;
			}
		}
	}
	return $browser;
}

function is_robot() {
	$agent = _SERVER( 'HTTP_USER_AGENT' );
	$robots = array( 'bot', 'spider', 'slurp' );
	foreach ( $robots as $robot ) {
		if ( strpos( $agent, $robot ) !== FALSE ) {
			return TRUE;
		}
	}
	return FALSE;
}

function browser_lang() {
	// return 'zh-cn';
	$accept = _SERVER( 'HTTP_ACCEPT_LANGUAGE' );
	$accept = substr( $accept, 0, strpos( $accept, ';' ) );
	if ( strpos( $accept, 'ko-kr' ) !== FALSE ) {
		return 'ko-kr';
		// } elseif(strpos($accept, 'en') !== FALSE) {
		// 	return 'en';
	} else {
		return 'zh-cn';
	}
}

// 安全请求一个 URL
// ini_set('default_socket_timeout', 60);
function http_get( $url, $cookie = '', $timeout = 30, $times = 3 ) {
	//return '';
	//	$arr = array(
	//			'ssl' => array (
	//			'verify_peer'   => TRUE,
	//			'cafile'        => './cacert.pem',
	//			'verify_depth'  => 5,
	//			'method'  	=> 'GET',
	//			'timeout'  	=> $timeout,
	//			'CN_match'      => 'secure.example.com'
	//		)
	//	);
	if ( substr( $url, 0, 8 ) == 'https://' ) {
		return https_get( $url, $cookie, $timeout, $times );
	}
	$arr = array(
		'http' => array(
			'method' => 'GET',
			'timeout' => $timeout
		)
	);
	$stream = stream_context_create( $arr );
	while ( $times-- > 0 ) {		
		$s = file_get_contents( $url, 0, $stream, 0, 4096000 );
		if ( $s !== FALSE ) return $s;
	}
	return FALSE;
}

function http_post( $url, $post = '', $cookie = '', $timeout = 30, $times = 3 ) {
	if ( substr( $url, 0, 8 ) == 'https://' ) {
		return https_post( $url, $post, $cookie, $timeout, $times );
	}
	is_array( $post )AND $post = http_build_query( $post );
	is_array( $cookie )AND $cookie = http_build_query( $cookie );
	$stream = stream_context_create( array( 'http' => array( 'header' => "Content-type: application/x-www-form-urlencoded\r\nx-requested-with: XMLHttpRequest\r\nCookie: $cookie\r\n", 'method' => 'POST', 'content' => $post, 'timeout' => $timeout ) ) );
	while ( $times-- > 0 ) {
		$s = file_get_contents( $url, 0, $stream, 0, 4096000 );
		if ( $s !== FALSE ) return $s;
	}
	return FALSE;
}

function https_get( $url, $cookie = '', $timeout = 30, $times = 1 ) {
	if ( substr( $url, 0, 7 ) == 'http://' ) {
		return http_get( $url, $cookie, $timeout, $times );
	}
	return https_post( $url, '', $cookie, $timeout, $times, 'GET' );
}

function https_post( $url, $post = '', $cookie = '', $timeout = 30, $times = 1, $method = 'POST' ) {
	if ( substr( $url, 0, 7 ) == 'http://' ) {
		return http_post( $url, $post, $cookie, $timeout, $times );
	}
	is_array( $post )AND $post = http_build_query( $post );
	is_array( $cookie )AND $cookie = http_build_query( $cookie );
	$w = stream_get_wrappers();
	$allow_url_fopen = strtolower( ini_get( 'allow_url_fopen' ) );
	$allow_url_fopen = ( empty( $allow_url_fopen ) || $allow_url_fopen == 'off' ) ? 0 : 1;
	/*
	if ( extension_loaded( 'openssl' ) && in_array( 'https', $w ) && $allow_url_fopen ) {
		$stream = stream_context_create( array( 'http' => array( 'header' => "Content-type: application/x-www-form-urlencoded\r\nx-requested-with: XMLHttpRequest\r\nCookie: $cookie\r\n", 'method' => $method, 'content' => $post, 'timeout' => $timeout ) ) );
		$s = @file_get_contents( $url, 0, $stream, 0, 4096000 );
		return $s;
	} elseif ( !function_exists( 'curl_init' ) ) {
		return xn_error( -1, 'server not installed curl.' );
	}
	*/
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_HEADER, 2 ); // 1/2
	curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-type: application/x-www-form-urlencoded', 'x-requested-with: XMLHttpRequest' ) );
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_USERAGENT, _SERVER( 'HTTP_USER_AGENT' ) );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 ); // 对认证证书来源的检查
	curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 ); // 从证书中检查SSL加密算法是否存在，默认可以省略
	if ( $method == 'POST' ) {
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $post );
	}
	$header = array( 'Content-type: application/x-www-form-urlencoded', 'X-Requested-With: XMLHttpRequest' );
	if ( $cookie ) {
		$header[] = "Cookie: $cookie";
	}
	curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );

	( !ini_get( 'safe_mode' ) && !ini_get( 'open_basedir' ) ) && curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 ); // 使用自动跳转, 安全模式不允许
	curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
	$data = curl_exec( $ch );
	if ( curl_errno( $ch ) ) {
		return xn_error( -1, 'Errno' . curl_error( $ch ) );
	}
	if ( !$data ) {
		curl_close( $ch );
		return '';
	}

	list( $header, $data ) = explode( "\r\n\r\n", $data );
	$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	if ( $http_code == 301 || $http_code == 302 ) {
		$matches = array();
		preg_match( '/Location:(.*?)\n/', $header, $matches );
		$url = trim( array_pop( $matches ) );
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_HEADER, false );
		$data = curl_exec( $ch );
	}
	curl_close( $ch );
	return $data;
}

// 多线程抓取数据，需要CURL支持，一般在命令行下执行，此函数收集于互联网，由 xiuno 整理，经过测试，会导致 CPU 100%。
function http_multi_get( $urls ) {
	// 如果不支持，则转为单线程顺序抓取
	$data = array();
	if ( !function_exists( 'curl_multi_init' ) ) {
		foreach ( $urls as $k => $url ) {
			$data[ $k ] = https_get( $url );
		}
		return $data;
	}

	$multi_handle = curl_multi_init();
	foreach ( $urls as $i => $url ) {
		$conn[ $i ] = curl_init( $url );
		curl_setopt( $conn[ $i ], CURLOPT_RETURNTRANSFER, 1 );
		$timeout = 3;
		curl_setopt( $conn[ $i ], CURLOPT_CONNECTTIMEOUT, $timeout ); // 超时 seconds
		curl_setopt( $conn[ $i ], CURLOPT_FOLLOWLOCATION, 1 );
		//curl_easy_setopt(curl, CURLOPT_NOSIGNAL, 1);
		curl_multi_add_handle( $multi_handle, $conn[ $i ] );
	}
	do {
		$mrc = curl_multi_exec( $multi_handle, $active );
	} while ( $mrc == CURLM_CALL_MULTI_PERFORM );

	while ( $active and $mrc == CURLM_OK ) {
		if ( curl_multi_select( $multi_handle ) != -1 ) {
			do {
				$mrc = curl_multi_exec( $multi_handle, $active );
			} while ( $mrc == CURLM_CALL_MULTI_PERFORM );
		}
	}
	foreach ( $urls as $i => $url ) {
		$data[ $i ] = curl_multi_getcontent( $conn[ $i ] );
		curl_multi_remove_handle( $multi_handle, $conn[ $i ] );
		curl_close( $conn[ $i ] );
	}
	return $data;
}


// 获取 http://xxx.com/path/
function http_url_path() {
	$port = _SERVER( 'SERVER_PORT' );
	//$portadd = ($port == 80 ? '' : ':'.$port);
	$host = _SERVER( 'HTTP_HOST' ); // host 里包含 port
	$https = strtolower( _SERVER( 'HTTPS', 'off' ) );
	$proto = strtolower( _SERVER( 'HTTP_X_FORWARDED_PROTO' ) );
	$path = substr( $_SERVER[ 'PHP_SELF' ], 0, strrpos( $_SERVER[ 'PHP_SELF' ], '/' ) );
	$http = ( ( $port == 443 ) || $proto == 'https' || ( $https && $https != 'off' ) ) ? 'https' : 'http';
	return "$http://$host$path/";
}

/**
 * URL format: http://www.domain.com/demo/?user-login.htm?a=b&c=d
 * URL format: http://www.domain.com/demo/?user-login.htm&a=b&c=d
 * URL format: http://www.domain.com/demo/user-login.htm?a=b&c=d
 * URL format: http://www.domain.com/demo/user-login.htm&a=b&c=d
 * array(
 *     0 => user,
 *     1 => login
 *     a => b
 *     c => d
 * )
 */

function getlocation() {
	$location = getform( 'location', 'get' );
	if ( $location ) {		
		return $location;
	}
	
	$cleanUrl = cleanUrlParams($_SERVER['REQUEST_URI']);
	$url = danger_key(str_replace(array(conf('siteext'), '.php'), '', $cleanUrl), 1);
	
	if (conf('runmode') == 2 && stripos($url, 'wap') !== FALSE) {
		$url = str_replace('wap/', '', $url);
	}
	
	$parsed = parse_url($url);
	$query = arr_value($parsed, 'query');
	$GLOBALS['page'] = substr(strrchr($query ?: '', '_'), 1) ?: '';
	$query = $query ? (strpos($query, '_') ? substr($query, 0, strpos($query, '_')) : $query) : '';
	
	if (defined('LOCATION')) {
		setRouteGlobals('-1', '-1', LOCATION);
		return LOCATION;
	}
	
	if (empty($query)) {
		setRouteGlobals(0, 0, 'index');
		return 'index';
	}
	
	if (strpos($query, '=') !== FALSE) {
		setRouteGlobals(0, 0, 'index');
		return 'index';
	}
	
	list($route, $params) = parseRouteQuery($query);
	
	$location = checklocationOptimized($route, $params);
	if ($location) {
		return $location;
	}
	
	if ($route === 'brand') {
		return handleBrandRoute($params);
	}
	
	return handleCustomRoute($query);
}

function cleanUrlParams($url) {
	$trackingParams = array(
		'from', 'tdsourcetag', 'isappinstalled', 'qz_gdt', 'gdt_vid',
		'tn', 'wd', 'gtm_debug', 'utm_source', 'utm_medium', 'utm_campaign',
		'utm_term', 'utm_content', 'fbclid', 'gclid', 'msclkid', 'mc_eid',
		'_ga', 'utm_referrer', 'ref', 'source', 'channel', 'campaign'
	);
	
	$parsed = parse_url($url);
	if (!isset($parsed['query'])) {
		return $url;
	}
	
	parse_str($parsed['query'], $queryParams);
	foreach ($trackingParams as $param) {
		unset($queryParams[$param]);
	}
	
	$newQuery = http_build_query($queryParams);
	$parsed['query'] = $newQuery ?: null;
	
	return rebuildUrl($parsed);
}

function rebuildUrl($parsed) {
	$url = '';
	if (isset($parsed['scheme'])) {
		$url .= $parsed['scheme'] . '://';
	}
	if (isset($parsed['host'])) {
		$url .= $parsed['host'];
	}
	if (isset($parsed['port'])) {
		$url .= ':' . $parsed['port'];
	}
	if (isset($parsed['path'])) {
		$url .= $parsed['path'];
	}
	if (isset($parsed['query'])) {
		$url .= '?' . $parsed['query'];
	}
	if (isset($parsed['fragment'])) {
		$url .= '#' . $parsed['fragment'];
	}
	return $url;
}

function parseRouteQuery($query) {
	$pos = strpos($query, '/');
	if ($pos === false) {
		return array($query, '');
	}
	return array(substr($query, 0, $pos), substr($query, $pos + 1));
}

function setRouteGlobals($sid, $cid, $cname) {
	$GLOBALS['sid'] = $sid;
	$GLOBALS['cid'] = $cid;
	$GLOBALS['cname'] = $cname;
}

function handleBrandRoute($params) {
	$GLOBALS['sid'] = '-1';
	if (!empty($params)) {
		if (db_count('brand', "b_filename='" . safe_word($params) . "'") > 0) {
			$GLOBALS['bname'] = $params;
		} else {
			$GLOBALS['bid'] = $params;
		}
	}
	return 'brand';
}

function handleCustomRoute($query) {
	$query = strpos($query, '=') ? substr($query, 0, strpos($query, '=')) : $query;
	$safeQuery = safe_word($query);
	
	if (db_count("sort", "s_filename='" . $safeQuery . "'") > 0) {
		$data = db_load_one("sort", "s_filename='" . $safeQuery . "'", "sid,s_type");
		setRouteGlobals($data['sid'], 0, $query);
		$models = load_model();
		return in_array($data['s_type'], $models) ? 'list' : $data['s_type'];
	}
	
	if (db_count("content", "c_pagename='" . $safeQuery . "'") > 0) {
		$data = db_load_one("content", "c_pagename='" . $safeQuery . "'", "cid,c_sid");
		setRouteGlobals($data['c_sid'], $data['cid'], $query);
		return 'content';
	}
	
	return $query;
}

function checklocation($q, $p = NULL) {
	return checklocationOptimized($q, $p);
}

function checklocationOptimized($q, $p = NULL) {
	$pageTypes = array(
		'static' => array('about', 'gbook', 'list', 'taglist', 'brandlist'),
		'special' => array('order', 'shopcart', 'shopconfirm', 'user', 'userorder', 'shoporder', 
						   'shopcomment', 'pointsorder', 'form', conf('wappath'), 'sitemap', 'sitexml'),
		'models' => load_model()
	);
	
	if (in_array($q, $pageTypes['static'])) {
		return handleStaticPage($q, $p);
	} elseif (in_array($q, $pageTypes['special'])) {
		return handleSpecialPage($q, $p);
	} elseif (in_array($q, $pageTypes['models'])) {
		return handleModelContent($q, $p);
	}
	
	return FALSE;
}

function handleStaticPage($q, $p) {
	$p = $p ? substr(strrchr('/' . $p, '/'), 1) : '';
	$sid = $p ? (strpos($p, '_') ? substr($p, 0, strpos($p, '_')) : $p) : '';
	
	if (ifnum($sid)) {
		setRouteGlobals($sid, 0, '');
	} else {
		$p = $p ? (strpos($p, '=') ? substr($p, 0, strpos($p, '=')) : $p) : '';
		$sid = $p ? (strpos($p, '&') ? substr($p, 0, strpos($p, '&')) : $p) : '';
		
		if (ifnum($sid)) {
			$GLOBALS['sid'] = $sid;
		} elseif ($q === 'brandlist') {
			handleBrandListRoute($sid);
		} elseif ($q === 'taglist') {
			handleTagListRoute($sid);
		} else {
			$GLOBALS['sid'] = 0;
		}
		$GLOBALS['cid'] = 0;
	}
	return $q;
}

function handleBrandListRoute($sid) {
	$safeSid = safe_word($sid);
	if (db_count("sort", "s_filename='" . $safeSid . "'") > 0) {
		$GLOBALS['sid'] = db_select("sort", "sid", "s_filename='" . $safeSid . "'");
		$GLOBALS['btype'] = $sid;
	} else {
		$GLOBALS['sid'] = 0;
	}
}

function handleTagListRoute($sid) {
	$safeSid = safe_word($sid);
	if (db_count("tag", "t_enname='" . $safeSid . "'") > 0) {
		$GLOBALS['sid'] = '-1';
		$GLOBALS['tag'] = $sid;
	} else {
		$GLOBALS['sid'] = 0;
	}
}

function handleSpecialPage($q, $p) {
	$p = $p ? (strpos($p, '=') ? substr($p, 0, strpos($p, '=')) : $p) : '';
	setRouteGlobals(0, 0, $p);
	return $q;
}

function handleModelContent($q, $p) {
	if (ifnum($p)) {
		$GLOBALS['cid'] = $p;
		return 'content';
	}
	
	$p = $p ? (strpos($p, '=') ? substr($p, 0, strpos($p, '=')) : $p) : '';
	$cid = $p ? (strpos($p, '&') ? substr($p, 0, strpos($p, '&')) : $p) : '';
	
	if ($cid > 0) {
		$GLOBALS['cid'] = safe_word($cid);
		return 'content';
	} elseif (!empty($p)) {
		$safeP = safe_word($p);
		if (db_count("content", "c_pagename='" . $safeP . "'") > 0) {
			$data = db_load_one("content", "c_pagename='" . $safeP . "'", "cid,c_sid");
			$GLOBALS['sid'] = $data['c_sid'];
			$GLOBALS['cid'] = $data['cid'];
			$GLOBALS['cname'] = $p;
			return 'content';
		}
	}
	
	return false;
}

function checklanip( $url = '' ) {
	$url = empty( $url ) ? $_SERVER[ 'SERVER_NAME' ] : $url;
	if ( $url == 'localhost' || $url == '127.0.0.1' ) return true;
	$get_ip_number = function ( $ip ) {
		$ip_segment = explode( '.', $ip );
		if ( !is_array( $ip_segment ) || count( $ip_segment ) != 4 ) {
			return false;
		} else {
			if ( isnum( $ip_segment[ 0 ] ) > 0 ) {
				$ip_num = $ip_segment[ 0 ] * 256 * 256 * 256 + $ip_segment[ 1 ] * 256 * 256 + $ip_segment[ 2 ] * 256 + $ip_segment[ 3 ];
				return $ip_num;
			}
		}
	};
	$process_ip = $get_ip_number( $url );
	$a_begin = $get_ip_number( "10.0.0.0" );
	$a_end = $get_ip_number( "10.255.255.255" );
	if ( $process_ip >= $a_begin && $process_ip <= $a_end )
		return true;

	$b_begin = $get_ip_number( "172.16.0.0" );
	$b_end = $get_ip_number( "172.31.255.255" );
	if ( $process_ip >= $b_begin && $process_ip <= $b_end )
		return true;

	$c_begin = $get_ip_number( "192.168.0.0" );
	$c_end = $get_ip_number( "192.168.255.255" );
	if ( $process_ip >= $c_begin && $process_ip <= $c_end )
		return true;

	$d_begin = $get_ip_number( "127.0.0.0" );
	$d_end = $get_ip_number( "127.255.255.255" );
	if ( $process_ip >= $d_begin && $process_ip <= $d_end )
		return true;

	return false;
}

function getrootdomain( $url = '' ) {
	if(empty( $url )){
		$url =$_SERVER["HTTP_HOST"] ?: $_SERVER[ 'SERVER_NAME' ];
	}	
	#列举域名中固定元素
	$state_domain = array(
		'ac','ad','ae','aero','af','ag','ai','al','am','an','ao','aq','ar','arpa','art','as','asia','at','au','auto','aw','ax','az','ba','bb','bd','be','bf','bg','bh','bi','biz','bj','bl','bm','bn','bo','bq','br','bs','bt','bv','bw','by','bz','ca','cat','cc','cd','center','cf','cg','ch','chat','ci','city','ck','cl','club','cm','cn','co','com.cn','com','company','cool','coop','cr','cu','cv','cw','cx','cy','cz','de','dj','dk','dm','do','dz','ec','edu','ee','eg','eh','email','er','es','et','eu','fi','fj','fk','fm','fo','fr','fun','fund','ga','gb','gd','ge','gf','gg','gh','gi','gl','gm','gn','gold','gov','gp','gq','gr','group','gs','gt','gu','gw','gy','hk','hm','hn','hr','ht','hu','icu','id','idv','ie','il','im','in','info','ink','int','io','iq','ir','is','it','je','jm','jo','jobs','jp','ke','kg','kh','ki','kim','km','kn','kp','kr','kw','ky','kz','la','lb','lc','li','life','link','live','lk','love','lr','ls','lt','ltd','lu','lv','ly','ma','mc','md','me','mf','mg','mh','mil','mk','ml','mm','mn','mo','mobi','mp','mq','mr','ms','mt','mu','museum','mv','mw','mx','my','mz','na','name','nc','ne','net.cn','net','nf','ng','ni','nl','no','np','nr','nu','nz','om','online','org.cn','org','pa','pe','pf','pg','ph','pk','pl','plus','pm','pn','pr','pro','ps','pt','pub','pw','py','qa','re','red','ren','ro','rs','ru','run','rw','sa','sb','sc','sd','se','sg','sh','shop','show','si','site','sj','sk','sl','sm','sn','so','social','sr','st','store','su','sv','sx','sy','sz','tc','td','team','tech','tel','tf','tg','th','tj','tk','tl','tm','tn','to','today','top','tp','tr','travel','tt','tv','tw','tz','ua','ug','uk','um','us','uy','uz','va','vc','ve','vg','vi','video','vip','vn','vu','wang','wf','wiki','work','world','ws','xin','xxx','xyz','ye','yr','yt','yu','za','zm','zone','zw'
	);
	if ( !preg_match( "/^http/is", $url ) )$url = "http://" . $url;
	$url_parse = parse_url( strtolower( $url ) );
	$urlarr = explode( ".", $url_parse[ 'host' ] );
	$count = count( $urlarr );
	$domain = '';
	if ( $count == 1 ) {
		$domain = '';
	} elseif ( $count <= 2 ) {
		$domain = $url_parse[ 'host' ];
	} elseif ( $count > 2 ) {
		$last = array_pop( $urlarr );
		$last_1 = array_pop( $urlarr );
		$last_2 = array_pop( $urlarr );
		if ( in_array( $last, $state_domain ) ) {
			$domain = $last_1 . '.' . $last;
		}
		if ( in_array( $last_1, $state_domain ) ) {
			$domain = $last_2 . '.' . $last_1 . '.' . $last;
		}
	}
	return $domain;
}

function geturl($name='') {
	$s = $_SERVER[ 'REQUEST_URI' ];
	$s = danger_key($s,1);
	$s = cright( $s, 1 ) == '/' ? rtrim( $s, '/' ) : $s;
	$get = array();
	$s = parse_url( $s ); //解析一个 URL 并返回一个关联数组
	$s = isset( $s[ 'query' ] ) ? $s[ 'query' ] : '';
	$arr = explode( '/', $s );
	$arr2 = array();
	$i = 0;
	$last = str_replace( '&amp;', '=', array_pop( $arr ) ); //删除数组中的最后一个元素
	if ( strpos( $last, '=' ) !== FALSE ) {
		$arr1 = explode( '=', $last );
        foreach ( $arr1 as $key => $value ) {            
            if ( $key < count( $arr1 ) - 1 ) $arr2[ $value ] = $arr1[ $key + 1 ];
        }          
        if( $name!=''){              
            if(isset($arr2[ $name ]))  return $arr2[ $name ];            
        }else{
            return $arr2;
        }
	}else{
        return '';
    }
}

function phpgopc() {
	$s = str_replace('//','/',str_replace(conf('wappath'),'',$_SERVER[ 'REQUEST_URI' ]));	
	if (conf('padautogo')==1){		
		header( 'location:' . str_replace('//','/',SITE_PATH . $s )); 
	}elseif(conf('padautogo')==2){
		$siteurl=db_select('language','siteurl',"l_alias='".LANGUAGE."'");
		if (ifstrin($siteurl,'http')){			
            $sitelink=str_replace(array('http','https','://'),'',$siteurl);
		}else{
			$sitelink=$siteurl;
			$siteurl ='//'.$siteurl;
		}
		if(_SERVER('HTTP_HOST')!=$sitelink &&  _SERVER('SERVER_NAME')!=$sitelink ){         
			 header( 'location:' . $siteurl . $s );	
		}
	}
}

function phpgowap() {
	$s = SITE_PATH!='/' ? str_replace(SITE_PATH,'',$_SERVER[ 'REQUEST_URI' ]) : $_SERVER[ 'REQUEST_URI' ];
	if (conf('wapautogo')==1){
		header( 'location:' .str_replace('//','/', SITE_PATH . conf('wappath') . $s ));
	}elseif(conf('wapautogo')==2){
		$wapurl=db_select('language','sitewapurl',"l_alias='".LANGUAGE."'");
		if (ifstrin($wapurl,'http')){
			$waplink=str_replace(array('http','https','://'),'',$wapurl);
		}else{
			$waplink=$wapurl;
			$wapurl ='//'.$wapurl;
		}	
		if(_SERVER('HTTP_HOST')==$waplink ||  _SERVER('SERVER_NAME')==$waplink ){           
			define('ISWAP',TRUE);	
		}else{            
            header( 'location:' . $wapurl . $s );			
		}
	}
}

function geturl_path( $s = NULL ) {
	$s = isset( $s ) ? : $_SERVER[ 'REQUEST_URI' ];
	$s = cright( $s, 1 ) == '/' ? $s : $s . '/';
	$get = array();
	substr( $s, 0, 1 ) == '/'
	AND $s = substr( $s, 1 );
	$arr = explode( '/', $s );
	$get = $arr;
	$last = array_pop( $arr );
	if ( strpos( $last, '?' ) !== FALSE ) {
		$get = $arr;
		$arr1 = explode( '?', $last );
		parse_str( $arr1[ 1 ], $arr2 );
		$get[] = $arr1[ 0 ];
		$get = array_merge( $get, $arr2 );
	}
	return $get;
}

function getModule( $url = NULL ) {
	$url = isset( $url ) ? : $_SERVER[ 'REQUEST_URI' ];
    $referer=_SERVER('HTTP_REFERER');
    if(!defined('WAPPATH')) {         
        if ( strpos( $referer, conf('wappath' )) !== FALSE ) {  
            define( 'WAPPATH', conf('wappath' ));		
        }else{
            define( 'WAPPATH', '');	
        }
    }
	$module = getform( 'module', 'get' );
	if ( isset( $module ) ) return $module;
	if ( strpos( $url, '?' ) !== FALSE ) {
		$url = explode( '?', $url );
		$url = $url[ 1 ];
		if ( strpos( $url, '/' ) !== FALSE ) {
			$str = explode( '/', $url );
			$url = $str[ 0 ];
			$GLOBALS[ 'ID' ] = safe_url($str[ 1 ]);
		} else {
			$GLOBALS[ 'ID' ] = 0;
		}
	} else {
		$url = str_replace( ADMIN_PATH, '', $url );
		if ( $url == 'index.php' )phpgo( ADMIN_PATH );
		$GLOBALS[ 'ID' ] = 0;
	}
	return $url;
}
// 解码客户端提交的 base64 数据
function base64_decode_file_data( $data ) {
	if ( substr( $data, 0, 5 ) == 'data:' ) {
		$data = substr( $data, strpos( $data, ',' ) + 1 ); // 去掉 data:image/png;base64,
	}
	$data = base64_decode( $data );
	return $data;
}

function str_push( $str, $v, $sep = '_' ) {
	if ( empty( $str ) ) return $v;
	if ( strpos( $str, $v . $sep ) === FALSE ) {
		return $str . $sep . $v;
	}
	return $str;
}

function y2f( $rmb ) {
	$rmb = floor( $rmb * 10 * 10 );
	return $rmb;
}

// $round: float round ceil floor
function f2y( $rmb, $round = 'float' ) {
	$rmb = floor( $rmb * 100 ) / 10000;
	if ( $round == 'float' ) {
		$rmb = number_format( $rmb, 2, '.', '' );
	} elseif ( $round == 'round' ) {
		$rmb = round( $rmb );
	} elseif ( $round == 'ceil' ) {
		$rmb = ceil( $rmb );
	} elseif ( $round == 'floor' ) {
		$rmb = floor( $rmb );
	}
	return $rmb;
}

function alertmail( $title, $content ) {
	$data = db_load_one( 'language', "l_alias='" . LANGUAGE . "'", 'sitetitle,siteurl' );
	$content .= '<br>时间:' . date( 'Y-m-d H:i:s' ) . '<br>IP:' . ip();
	send_mail( $data[ 'sitetitle' ] . $data[ 'siteurl' ] . $title, $content );
}

function send_mail( $title, $content, $to = '' ) {
	$conf = _SERVER( 'conf' );
	if ( $conf[ 'mailmark' ] == 0 ) return '';
	require_once( PLUG_DIR . "phpmailer/class.phpmailer.php" );
	require_once( PLUG_DIR . "phpmailer/class.smtp.php" );
	$mail = new PHPMailer(); //实例化PHPMailer核心类
	$mail->SMTPDebug = $conf[ 'smtp_debug' ]; //是否启用smtp的debug进行调试 开发环境建议开启 生产环境注释掉即可 默认关闭debug调试模式
	$mail->isSMTP(); //使用smtp鉴权方式发送邮件
	$mail->SMTPAuth = true; //smtp需要鉴权 这个必须是true
	$mail->Host = $conf[ 'smtp_server' ]; //链接qq域名邮箱的服务器地址
	$mail->SMTPSecure = $conf[ 'smtp_ssl' ]; //设置使用ssl加密方式登录鉴权
	$mail->Port = $conf[ 'smtp_port' ]; //设置ssl连接smtp服务器的远程服务器端口号，以前的默认是25，但是现在新的好像已经不可用了 可选465或587
	$mail->CharSet = $conf[ 'smtp_charset' ]; //设置发送的邮件的编码 可选GB2312 我喜欢utf-8 据说utf8在某些客户端收信下会乱码
	$mail->FromName = $conf[ 'smtp_name' ]; //设置发件人姓名（昵称） 任意内容，显示在收件人邮件的发件人邮箱地址前的发件人姓名
	$mail->Username = $conf[ 'smtp_user' ]; //smtp登录的账号 这里填入字符串格式的qq号即可
	$mail->Password = $conf[ 'smtp_pass' ]; //smtp登录的密码 使用生成的授权码（就刚才叫你保存的最新的授权码）【非常重要：在网页上登陆邮箱后在设置中去获取此授权码】
	$mail->From = $conf[ 'smtp_mail' ]; //设置发件人邮箱地址 这里填入上述提到的“发件人邮箱”
	$mail->isHTML( true ); //邮件正文是否为html编码 注意此处是一个方法 不再是属性 true或false
	$to == ''
	and $to = $conf[ 'receive_email' ];
	$to = splits( $to, ',' );
	foreach ( $to as $value ) {
		if ( checkstr( $value, 'email' ) === true ) {
			$mail->addAddress( $value ); // 收件人邮箱地址
		}
	}
	$mail->Subject = $title; //添加该邮件的主题
	$mail->Body = $content; //添加邮件正文 上方将isHTML设置成了true，则可以是完整的html字符串 如：使用file_get_contents函数读取本地的html文件
	//简单的判断与提示信息
	if ( $mail->send() ) {
		return true;
	} else {
		return false;
	}
}

function smsnum() {
	if ( conf( 'smsmark' ) == 0 ) return '0';
	if ( conf( 'smsserver' ) == 2 ) return '0';
	$smsid = conf( 'smsid' );
	$smspw = conf( 'smspw' );
	$smsurl = "http://sms.10690221.com:9011/hy/m?uid=" . $smsid . "&auth=" . $smspw;
	$data= http_get( $smsurl );
	return $data>=0 ? $data : '查询失败，请检查账号密码　';
}

function send_sms( $mobile, $content ) {
	$url = "http://sms.10690221.com:9011/hy/";
	if ( conf( 'smsmark' ) == 0 )exit;
	$t0 = time();
	$t1 = get_session( 'smstime' );
	$t2 = 59 - ( $t0 - $t1 );
	$ip = ip();
	if ( $t2 > 0 )die( '0请等待' . $t2 . '秒再尝试' );
	if ( empty( $_SESSION[ 'code' ] ) )die( '0警报：正在尝试刷短信，已通知管理员并将你加入黑名单。' );
	$prevtime = strtotime( db_select( 'sms', 'smsaddtime', array( 'smsip' => $ip ), array( 'id' => 'desc' ) ) );
	$t3 = $t0 - $prevtime;
	if ( $t3 < 60 )die( '0请不要尝试连续请求！' );
	$smsid = conf( 'smsid' );
	$smspw = conf( 'smspw' );
	$post_string = "uid=" . $smsid . "&auth=" . $smspw . "&mobile=" . $mobile . "&msg=" . $content . "&expid=0&encode=utf-8";

	$data = http_get( $url . '?' . $post_string);
	$sms_data = splits( $data, ',' );
	$code= $sms_data[ 0 ];
	//curl_close($ch);
	switch ( $code) {
		case "":
			$code=1;
			$msg = "1发送成功";
			break;
		case "-1":
			$msg = "签权失败";
			break;
		case "-2":
			$msg = "未检索到被叫号码";
			break;
		case "-3":
			$msg = "被叫号码过多";
			break;
		case "-4":
			$msg = "内容未签名";
			break;
		case "-5":
			$msg = "内容过长";
			break;
		case "-6":
			$msg = "余额不足";
			break;
		case "-7":
			$msg = "暂停发送";
			break;
		case "-8":
			$msg = "保留";
			break;
		case "-9":
           	$msg = "定时发送时间格式错误";
			break;
		case "-10":
			$msg = "下发内容为空";
			break;
		case "-11":
			$msg = "账户无效";
			break;
		case "-12":
			$msg = "Ip地址非法";
			break;
		case "-13":
			$msg = "操作频率快";
			break;
		case "-14":
			$msg = "操作失败";
			break;
		case "-15":
			$msg = "拓展码无效";
			break;
		case "-16":
			$msg = "取消定时,seqid错误";
			break;
		case "-17":
			$msg = "未开通报告";
			break;
		case "-18":
			$msg = "暂留";
			break;
		case "-19":
			$msg = "未开通上行";
			break;
		case "-20":
			$msg = "暂留";
			break;
		case "-21":
			$msg = "包含屏蔽词";
			break;
		default:
			$msg = "未知错误";
	}
	set_session( 'smstime', $t0 );
	db_exec( "insert into [dbpre]sms(smsmobile,smscontent,smsip,smsaddtime,smsbackinfo,smsonoff,smsid,smspw)values('" . $mobile . "','" . $content . "','" . $ip . "','" . date( 'Y-m-d H:i:s' ) . "','" . $msg . "'," . cleft( $msg, 0, 1 ) . ",'" . $smsid . "','" . hidestr( $smspw, 8 ) . "')" );
	return [$code,$msg];
}

function check_token(){	 
	$token		= safe_word(_POST('token'));
	$timestamp	= safe_word(_POST('timestamp'));
	if(empty($token))  die('token不能为空');
	if(empty($timestamp))  die('时间戳不能为空');
	if(time() - intval($timestamp) >1800) die('很抱歉，保存失败，时间戳已超时，请刷新重试');	
	if(md5(md5($timestamp.get_session("adminname")).get_session("adminrand"))!=$token) die('很抱歉，保存失败，token验证失败，请刷新重试');
	return true;
}

function parse_admin_tlp( $module) {
	$tpltype = G( 'ID' ) ? 'edit' : 'add';
	$tplfile = SITE_DIR . conf( 'adminpath' ) . 'template/' . $module . '.tpl';
	$cachefile = RUN_DIR . 'cache/' . conf( 'adminpath' ) . md5( $module . $tpltype ) . '.tpl';
	//echop ($tplfile);echop ($cachefile);
	//echop( template_parse(load_file($tplfile)));
	$timestamp=time();
    $token=md5($timestamp.get_session("adminname"));
	$GLOBALS['timestamp']=$timestamp;
    $GLOBALS['token']= md5($token.get_session("adminrand"));   
	//if ( !is_file( $cachefile ) || time_file( $tplfile ) > time_file( $cachefile ) || size_file( $tplfile ) == 0 ) {
		create_file( $cachefile, template_parse( load_file( $tplfile )));
	//}
	return $cachefile;
}

function template_parse( $htmlstr) {   
	$htmlstr = preg_replace( '/\{php\s+(.+?)\}/i', '<?php \\1?>', $htmlstr );
	$htmlstr = preg_replace( '/\{tpl\s+(.+)\}/i', '<?php include template(\'\\1\');?>', $htmlstr );
	$htmlstr = preg_replace( '/\{include\s+(.+)\}/i', '<?php include(\\1);?>', $htmlstr );
	$htmlstr = preg_replace( '/\{(\$[a-z0-9_\+\'\"\[\]\x7f-\xff\$]+)\}/i', '<?php echo isset(\\1)?\\1:\'\';?>', $htmlstr );
	$htmlstr = preg_replace( '/\{__([a-z0-9_]+)__\}/i', '<?php echo defined(\'\\1\')?\\1:\'{__\\1__}\';?>', $htmlstr );
	$htmlstr = preg_replace( '/\[G\s([a-z0-9_]+)\]/i', 'G(\'\1\')', $htmlstr );
	$htmlstr = preg_replace( '/\[G:([a-z0-9_]+)\]/i', '<?php echo G(\'\1\')?>', $htmlstr );
	$htmlstr = str_replace( '{language_title}', get_cookie( 'language_title' )?: '', $htmlstr );
	$htmlstr = str_replace( '{language}', get_cookie( 'language' )?: '', $htmlstr );
    $htmlstr = str_replace( 'id="contentform">', 'id="contentform">
    <input type="hidden" name="token" value="<?php echo G("token")?>">
	<input type="hidden" name="timestamp" value="<?php echo G("timestamp")?>">', $htmlstr );
	if ( G( 'r' ) ) {
		$htmlstr = str_replace( '[r:readonly]', 'readonly', $htmlstr );
		$htmlstr = str_replace( '{ways}', '修改', $htmlstr );
		$htmlstr = preg_replace( '/\[r:([a-z0-9_]+)\]/i', '<?php echo $r[\'\\1\'];?>', $htmlstr );
		$htmlstr = preg_replace( '/\[r\s([a-z0-9_]+)\]/i', '$r[\'\\1\']', $htmlstr );
	} else {
		$htmlstr = str_replace( '[r:readonly]', '', $htmlstr );
		$htmlstr = str_replace( '{ways}', '添加', $htmlstr );
		$htmlstr = preg_replace( '/\[r:([a-z0-9_]+)\]/i', '', $htmlstr );
		$htmlstr = preg_replace( '/\[r\s([a-z0-9_]+)\]/i', '\'\'', $htmlstr );
		$htmlstr = str_replace( '{$togbk }', '', $htmlstr );
	}
	$htmlstr = preg_replace( '/\[c:([a-z0-9_]+)\]/i', '<?php echo conf(\'\\1\');?>', $htmlstr );
	$htmlstr = preg_replace( '/\[c\s([a-z0-9_]+)\]/i', '$conf[\'\\1\']', $htmlstr );
	$htmlstr = preg_replace( '/\{if\s+([^\}]+)\}/i', '<?php if(\\1) { ?>', $htmlstr );
	$htmlstr = preg_replace( '/\{else\s*if\s+([^\}]+)\}/i', '<?php }elseif(\\1){ ?>', $htmlstr );
	$htmlstr = preg_replace( '/\{elseif\s+([^\}]+)\}/i', '<?php }elseif(\\1){ ?>', $htmlstr );
	$htmlstr = preg_replace( '/\{else\}/i', '<?php }else{ ?>', $htmlstr );
	$htmlstr = preg_replace( '/\{end if}/i', '<?php }?>', $htmlstr );
	$htmlstr = preg_replace( '/\{\/if}/i', '<?php }?>', $htmlstr );
	$htmlstr = preg_replace( '/\{loop\s+(\S+)\s+(\S+)\}/i', '<?php $no=1;if(is_array(\\1))foreach(\\1 as \\2){?>', $htmlstr );
	$htmlstr = preg_replace( '/\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}/i', '<?php $no=1;if(is_array(\\1))foreach(\\1 as \\2=>\\3){?>', $htmlstr );
	$htmlstr = preg_replace( '/\{\/loop}/i', '<?php $no++;}?>', $htmlstr );
	$htmlstr = preg_replace( '/\{\$([\w]+)\s+(.+?)\}/i', '<?php echo \1(\\2);?>', $htmlstr );
	$htmlstr = str_replace( '{zzz:table}', 'id="table" data-toggle="table" data-method="post" data-pagination="true" data-search="true" data-show-refresh= "true" data-side-pagination="server" data-id-table="advancedtable" data-page-list="[10,50,500]" data-page-size="' . conf( 'pagesize' ) . '" data-content-type="application/x-www-form-urlencoded; charset=UTF-8"', $htmlstr );
	$htmlstr = str_replace( '{zzz:table50}', 'id="table" data-toggle="table" data-method="post" data-pagination="true" data-search="true" data-show-refresh= "true" data-id-table="advancedtable" data-page-list="[10,50,500]" data-page-size="50" data-content-type="application/x-www-form-urlencoded; charset=UTF-8"', $htmlstr );
	return $htmlstr;
}

function formatdate( $string, $type = NULL ) {
	if ( !is_null( $type ) ) {
		$time = strtotime( $string );
		$s = $type;
	} else {
		$arr = splits( $string, ',' );
		$time = strtotime( $arr[ 0 ] );
		$s = isset( $arr[ 1 ] ) ? $arr[ 1 ] : $string;
	}
	switch ( $s ) {
		case '1':
		case 'yy/m/d':
			return date( 'Y/m/d', $time );
			break;
		case '2':
		case 'yy-m-d':
			return date( 'Y-m-d', $time );
			break;
		case '3':
			return date( 'A H:i:s', $time );
			break;
		case '4':
		case 'hh:mm:ss':
			return date( 'H:i:s', $time );
			break;
		case '5':
		case 'yyyy':
			return date( 'Y', $time );
			break;
		case '6':
		case 'mm':
			return date( 'm', $time );
			break;
		case '7':
		case 'dd':
			return date( 'd', $time );
			break;
		case '8':
		case 'm-d':
			return date( 'm-d', $time );
			break;
		case '9':
		case 'yyyymmddhhmnss':
			return date( 'YmdHis', $time );
			break;
		case '10':
		case 'hh':
			return date( 'H', $time );
			break;
		case '11':
		case 'mn':
			return date( 'i', $time );
			break;
		case '12':
		case "ss":
			return date( 's', $time );
			break;
		case '13':
		case 'yy-m':
		case 'y-m':
			return date( 'Y-m', $time );
			break;
		case '14':
		case "enm":
			return date( 'M', $time );
			break;
		case '15':
		case "enmm":
			return date( 'F', $time );
			break;
		case '16':
		case 'now':
			return DateToNow( $time );
			break;
		default:
			if(empty($s)){
				return date( 'Y-m-d H:i:s', $time );
			}else{
				return date( $s, $time );
			}
	}
}

function DateToNow( $time ) {
	$now = strtotime( date( 'Y-m-d H:i:s' ) ); //当前时间
	$iSeconds = ceil( $now - $time ); //x相隔秒
	$iMinutes = ceil( $iSeconds / 60 );
	$iHours = ceil( $iSeconds / 3600 );
	$iDays = ceil( $iSeconds / 86400 );
	if ( $iDays > 30 ) {
		return date( 'Y-m-d', $time );
	} elseif ( $iDays > 14 ) {
		return "2周前";
	} elseif ( $iDays > 7 ) {
		return "1周前";
	} elseif ( $iDays > 1 ) {
		return $iDays . "天前";
	} elseif ( $iHours > 1 ) {
		return $iHours . "小时前";
	} elseif ( $iMinutes > 1 ) {
		return $iMinutes . "分钟前";
	} else {
		return "刚刚";
	}
}

// 无 Notice 方式的获取超级全局变量中的 key
function _GET( $k, $def = NULL ) {
	return isset( $_GET[ $k ] ) ? $_GET[ $k ] : $def;
}

function _POST( $k, $def = NULL ) {
	return isset( $_POST[ $k ] ) ? $_POST[ $k ] : $def;
}

function _COOKIE( $k, $def = NULL ) {
	return isset( $_COOKIE[ $k ] ) ? $_COOKIE[ $k ] : $def;
}

function _REQUEST( $k, $def = NULL ) {
	return isset( $_REQUEST[ $k ] ) ? $_REQUEST[ $k ] : $def;
}

function _SERVER( $k, $def = NULL ) {
	return isset( $_SERVER[ $k ] ) ? $_SERVER[ $k ] : $def;
}

function GLOBALS( $k, $def = NULL ) {
	return isset( $GLOBALS[ $k ] ) ? $GLOBALS[ $k ] : $def;
}

function G( $k, $def = NULL ) {
	if($def ){
		if(isset( $GLOBALS[ $k ] ))  {
		    return $GLOBALS[ $k ] ?:  $def;
		}else{
		     return $def;
		}
	}else if(isset( $GLOBALS[ $k ] ))  {
		return GLOBALS($k);
	}else{
		return $def;
	}
}

function _SESSION( $k, $def = NULL ) {
	global $g_session;
	return isset( $_SESSION[ $k ] ) ? $_SESSION[ $k ] : ( isset( $g_session[ $k ] ) ? $g_session[ $k ] : $def );
}
?>