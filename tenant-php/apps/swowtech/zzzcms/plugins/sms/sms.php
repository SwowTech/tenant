<?php
include '../../inc/zzz_class.php';
$act = getform("act", "both");
switch ($act) {
	case "sendcode":
		sendcode();
		break;
	case "checkcode":
		checkcode();
		break;
	case "checkmobile":
		checkmobile();
		break;
	case "checkemail":
		checkemail();
		break;
	case "checkname":
		checkname();
		break;
	case 'trysms':
		echo	try_sms();
		break;
	case "getnum":
		echo	smsnum();
		break;
	case "getcode":
		echo $_SESSION['code'];
		break;
	default:
		echo get_session("smscode");
}

function try_sms()
{
	$mobile = getform("mobile", 'post');
	if (conf('smsserver') == 1) {
		$smsdata = send_sms($mobile, "验证码为1234,有效期10分钟，这条是测试信息。");
	} else if (conf('smsserver') == 2) {
		$smsdata = alysms($mobile, '1234');
	}
	return $smsdata;
}

function sendcode()
{
	$smsphone = getform("phonenum", "both");
	$smscode = randname(4, 'num');
	$type = getform("type", "post");
	if ($type == 'manage') {
		$user = db_load_sql_one("select username from [dbpre]user,[dbpre]user_group where gid=u_gid and mobile='$smsphone' and u_onoff=1 and isadmin=1");
		if (!$user) {
			die('0账号不存在，管理员可能未绑定手机号');
		} else {
			set_session("adminname", $user['username']);
		}
	} else if ($type == 'forget') {
		$user = db_load_sql_one("select username from [dbpre]user,[dbpre]user_group where gid=u_gid and mobile='$smsphone' and u_onoff=1 and isadmin=0");
		if (!$user) {
			die('0账号不存在，会员账号可能未绑定手机号');
		} else {
			set_session("username", $user['username']);
		}
	}

	if (conf('smsserver') == 1) {
		$smsdata = '1发送成功' . $smscode;
		//$smsdata=send_sms($smsphone,"验证码为".$smscode.",有效期10分钟，请不要把验证码泄露给其他人。");
	} else if (conf('smsserver') == 2) {
		$smsdata = alysms($smsphone, $smscode);
	}
	set_session("smscode", $smscode);
	set_session("smsphone", $smsphone);
	echo $smsdata;
}

function alysms($phone, $code)
{
	$conf = _SERVER('conf');
	$smsid = $conf['smsid'];
	$smspw = $conf['smspw'];
	$sign = $conf['smssign'];
	$templatecode = $conf['smscode'];
	$templateparam = "{'code':'" . $code . "'}";
	include_once('alysms.php');
	$r = sendsms($smsid, $smspw, $sign, $phone, $templatecode, $templateparam);
	$m = $r['Message'];
	if ($r['Code'] == 'OK') {
		$c = 1;
	} else {
		$c = 0;
	}
	db_exec("insert into [dbpre]sms(smsmobile,smscontent,smsip,smsaddtime,smsbackinfo,smsonoff,smsid,smspw)values('" . $phone . "','" . $code . "','" . ip() . "','" . date('Y-m-d H:i:s') . "','" . $m . "','" . $c . "','" . $smsid . "','" . hidestr($smspw, 8) . "')");
	return $c . $m;
}

function checkcode()
{
	$checkname = safe_word(getform("name", "post"));
	$checkparam = safe_word(getform("param", 'post'));
	$checktype = getform("checktype", 'get');
	if ($checkname == "smscode" || $checktype == "smscode") {
		$nowcode = get_session("smscode");
		if (empty($nowcode)) die('{"status":"n","info":"验证失败1"}');
	} elseif ($checkname = "code" || $checktype = "code") {
		$nowcode = $_SESSION['code'];
		if (empty($nowcode)) die('{"status":"n","info":"验证失败2"}');
	}
	if ($checkparam == $nowcode) {
		echo '{"info":"验证成功","status":"y"}';
	} else {
		echo '{"info":"验证失败","status":"n"}';
	}
}

function checkmobile()
{
	$checkparam = safe_word(getform("param", "post"));
	$checktype = getform("checktype", "get");
	if (empty($checktype)) $checktype = 0;
	if (checkstr($checkparam, "mobile")) {
		if ($checktype == 0) {
			if (check_used("user", "mobile", $checkparam)) {
				echo '{"info":"手机号已被使用，请更换!","status":"n"}';
			} else {
				echo '{"info":"通过验证","status":"y"}';
			}
		}
		if ($checktype == 1) {
			if (check_used("user", "mobile", $checkparam)) {
				echo '{"info":"通过验证","status":"y"}';
			} else {
				echo '{"info":"手机号不存在，请确认!","status":"n"}';
			}
		}
	} else {
		echo '{"info":"验证失败","status":"n"}';
	}
}

function checkemail()
{
	$checkparam = safe_url(getform("param", "post"));
	$checktype = getform("checktype", "get");
	if (empty($checktype)) $checktype = 0;
	if (checkstr($checkparam, "email")) {
		if ($checktype == 0) {
			if (check_used("user", "email", $checkparam)) {
				echo '{"info":"邮箱账号已被使用，请更换！","status":"n"}';
			} else {
				echo '{"info":"通过验证","status":"y"}';
			}
		}
		if ($checktype == 1) {
			if (check_used("user", "email", $checkparam)) {
				echo '{"info":"通过验证","status":"y"}';
			} else {
				echo '{"info":"邮箱账号不存在，请确认！","status":"n"}';
			}
		}
	} else {
		echo '{"info":"验证失败","status":"n"}';
	}
}

function checkname()
{
	$checkparam = safe_word(getform("param", "post"));
	$checktype = getform("checktype", "get");
	if (empty($checktype)) $checktype = 0;
	if (checkstr($checkparam, "username")) {
		if ($checktype == 0) {
			if (check_used("user", "username", $checkparam)) {
				echo '{"info":"账号已存在，<a  href=javascript:void(0) onclick=tologin()>登录</a>账号！","status":"n"}';
			} else {
				echo '{"info":"通过验证","status":"y"}';
			}
		}
		if ($checktype == 1) {
			if (check_used("user", "username", $checkparam) || check_used("user", "mobile", $checkparam)) {
				echo '{"info":"通过验证","status":"y"}';
			} else {
				echo '{"info":"账号不存在，请先<a href=javascript:void(0) onclick=toreg()>注册</a>","status":"n"}';
			}
		}
	} else {
		echo '{"info":"验证失败","status":"n"}';
	}
}
