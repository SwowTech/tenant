<?php
require '../inc/zzz_admin.php';
check_admin('index');
$act=safe_word(getform('act','get'));
$time=time();
$ip=ip();
$success=false;
if (filter_var(	$_SERVER['REMOTE_ADDR'], 	FILTER_VALIDATE_IP, 	FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)) {
	if(conf('manage_login_type')==1){
		$info =conf('manage_login_info'); //白名单规则  	
		$infos=splits($info,'<br/>');
		foreach ( $infos as $iprule ) {
			if ( ip_test( $ip, $iprule ) ) {
				$success = true;
			}
		}
		if (!$success )error( '502,禁止访问,您IP不在指定IP范围内' );
	}else if(conf('manage_login_type')==2){
		$info =conf('manage_login_info'); //白名单规则
		$infos=splits($info,'<br/>');
		$city=iptocity($ip);
		foreach ( $infos as $name ) {
			if (ifstrin($city,$name)) $success = true;		
		}
		if (!$success )error( '502,禁止访问,您IP不在指定城市范围内,' .$city);
	}else{
		$success = true;
	}
}

switch ($act) {
	case 'loginesc':
		login_esc();
		break;
	case 'loginout':
		del_cookie("adminname");
		del_session("adminid");
		phpgo ("login.php");	
		break;	
	case 'loginon':
		
		$adminname=safe_word(getform('adminname','post'));
		$logintype=safe_word(getform('logintype','post'));
		$logintime=getform('time','post');
		$token=getform('token','post');
		

		if(md5($adminname.$logintime)!=$token){
			returnmsg('json',0,'登陆超时，请重新登录');
		}

		$logpath =RUN_DIR . ADMIN_PATH. '/login/'. date( 'Ymd').'/'. $adminname.date( 'H').".zzz";
		if ( is_file( $logpath ) ) {
			$s=load_file($logpath);
			$arr=splits($s,PHP_EOL);
			if(count($arr)>4){
				returnmsg('json',0,'很抱歉，登陆失败超过5次，请一小时后在尝试！');
			}
		}

		if($logintype=='sms'){
			$smscode=getform('smscode','post','*');
			if($smscode!=get_session("smscode") || $adminname!=get_session("smsphone")){
				$s='登录失败,短信验证码或手机号错误!';
				login_log($logpath,$adminname,$smscode,get_session("smsphone"),get_session('smscode'),$s);
				returnmsg('json',0,$s);
			}
			$adminname=get_session("adminname");
		}else if($logintype=='question'){
			$adminpass=getform('adminpass','post','*');
			$question=safe_word(getform('question','post','*'));			
			if(!$question){
				returnmsg('json',0,'请输入问题');
			}
			$answer=db_select('user','answer',['username'=>$adminname,'question'=>$question]);
			if($adminpass!=md5($answer)){
				$s='登录失败,问题和答案错误!';
				login_log($logpath,$adminname,$adminpass,$question,$answer,$s);
				returnmsg('json',0,$s);
			}			
		}else{
			if (conf('iscode')==1 && $logintype=='pass'){
				$code=getform('code','post','code','json');
			}
			$adminpass=getform('adminpass','post','*');
			if(lenstr($adminpass)!=32){
				returnmsg('json',0,'很抱歉，密码输入有误！');
			}else{
				$adminpass= substr($adminpass, 8, 16);
			}
			$lowpass=array('8ad9902aecba32e2','965eb72c92a549dd','60827015e5d605ed','757783e6cf17b26f','13955235245b2497','ff8aaa8a2dde9154','49ba59abbe56e057','83aa400af464c76d','1511b4f6020ec61d','c831b04de153469d','77804d2ba1922c33','098950fc58aad83c','92ca43ad26d44c7a','28cb38d5f2608536','6b4aee58e1d71b36','404507aa8c22714d','e14fb36680850768','8458ce06fbc5bb76','39ac3e7b2fc9396f','7a57a5a743894a0e','5aa765d61d8327de','3f66540e67349e0a','e6393254e72ffa4d','2690b780b2a14e14','16e0c197e42a6be3','d78cc6c2fea32b9f','c9e6d3e4cf2e8739','80f4189ca1c9d4d9','8458ce06fbc5bb76','28cb38d5f2608536','a9d9bd2a9516280e','7412b5da7be0cf42','f5bbe40cade3de5c','547a12215b173ff4','c5698829397d9776','b86edaa7fc805516','97624dd3a48fa681','2ac330a7455809c6','102fca279fce7559','6505d83bc7949dd1','5216c019988915ed','986ba87ed28fc1b5','13955235245b2497','1511b4f6020ec61d','8a65073a3bcf0eb2','730d9f35de7ac538','a14b3aa27deeb4cb','2900d5e94b8da524','469e80d32c0559f8',);
            in_array($adminpass,$lowpass) ? set_cookie('adminpass','1') :  set_cookie('adminpass','0');
			if(!db_count('user',"username = '". $adminname ."' and password='".$adminpass."'")){
				$s='登录失败,用户名或密码错误!';
				login_log($logpath,$adminname,$adminpass,'','',$s);
				returnmsg('json',0,$s);
			}
		}		
		login_in($adminname);
		break;
	default:
	include parse_admin_tlp('login');
}

function login_log($logpath,$adminname,$adminpass,$question,$answer,$msg){
	check_dir(dirname($logpath),true);
	$time=time();
	$s = "$adminname\t$adminpass\t$question\t$answer\t$time\t$msg\r\n";
	error_log( $s, 3, $logpath );
	returnmsg('json',0, $msg);	
}

function login_in($username){
    $value=db_load_one('user a,user_group b',array('username'=>$username,'u_gid'=>array('='=>'gid')),'uid,username,u_onoff,face,gid,g_onoff,g_name,g_menu,g_sort,g_mark,isadmin,logincount');
    if($value){
        $admintime=time();
        $adminrand=md5($admintime.$value['uid']);
		$value['isadmin']!=1 and	returnmsg('json',0,$username."对不起，你不是管理员");
		$value['g_onoff']!=1 and 	returnmsg('json',0,"对不起，您所在用户组已被禁用");
		$value['u_onoff']!=1 and 	returnmsg('json',0,"对不起，您的账号已被禁用");        
		set_cookie("adminname",$value['username'],90,false);	
		set_cookie("admintime",$admintime);
		set_session("admingroup",$value['g_name']);	
		set_session("adminid",$value['uid']);
		set_session("adminname",$value['username']);
		set_session("admingid",$value['gid']);
		set_session("adminmenu",$value['g_menu']);	
		set_session("adminsort",$value['g_sort']);	
		set_session("adminmark",$value['g_mark']);      
		set_session("adminrand",$adminrand);
		
		if (empty($value['face'])){
			set_cookie("adminface","../plugins/face/face01.png");
		}elseif(lenstr($value['face'])<11){
			set_cookie("adminface","../plugins/face/". $value['face']);
		}else{
			set_cookie("adminface", $value['face']);
		}
		
		try{
			if(db_update('user',['uid'=>$value['uid']],['lastlogintime'=>date('Y/m/d H:i:s'),'lastloginip'=>ip(),'adminrand'=>$adminrand,'logincount'=>$value['logincount']+1])){
				returnmsg('json',1,'登陆成功');	
			}else{
				returnmsg('json',0,'登陆失败，请确认数据库有写入权限');			
			}
			
		}catch(PDOException $e){
			returnmsg('json',0,'登陆失败，请确认数据库有写入权限');			
		} 
		
    }else{
        returnmsg('json',0,"对不起，登陆失败");
    }	
}