<?php

namespace bases;

use exceptions\BaseException;
use think\facade\Log;
use think\facade\Request;
use think\Validate;

class BaseValidate extends Validate
{
    protected $typeMsg = [
        'require'     => ':attribute不能为空',
        'must'        => ':attribute必须填写',
        'number'      => ':attribute必须是数字',
        'integer'     => ':attribute必须是整数',
        'float'       => ':attribute必须是浮点数',
        'boolean'     => ':attribute必须是布尔值',
        'email'       => ':attribute格式不正确',
        'mobile'      => ':attribute格式不正确',
        'array'       => ':attribute必须是数组',
        'accepted'    => ':attribute必须是yes、on或者1',
        'date'        => ':attribute不是有效的日期',
        'file'        => ':attribute不是有效的文件',
        'image'       => ':attribute不是有效的图片',
        'alpha'       => ':attribute只能是字母',
        'alphaNum'    => ':attribute只能是字母和数字',
        'alphaDash'   => ':attribute只能是字母、数字和下划线_及破折号-',
        'activeUrl'   => ':attribute不是有效的域名或者IP',
        'chs'         => ':attribute只能是汉字',
        'chsAlpha'    => ':attribute只能是汉字、字母',
        'chsAlphaNum' => ':attribute只能是汉字、字母和数字',
        'chsDash'     => ':attribute只能是汉字、字母、数字和下划线_及破折号-',
        'url'         => ':attribute不是有效的URL地址',
        'ip'          => ':attribute不是有效的IP地址',
        'dateFormat'  => ':attribute必须使用日期格式 :rule',
        'in'          => ':attribute必须在 :rule 范围内',
        'notIn'       => ':attribute不能在 :rule 范围内',
        'between'     => ':attribute只能在 :1 - :2 之间',
        'notBetween'  => ':attribute不能在 :1 - :2 之间',
        'length'      => ':attribute长度不符合要求 :rule',
        'max'         => ':attribute长度不能超过 :rule',
        'min'         => ':attribute长度不能小于 :rule',
        'after'       => ':attribute日期不能小于 :rule',
        'before'      => ':attribute日期不能超过 :rule',
        'expire'      => ':attribute不在有效期内 :rule',
        'allowIp'     => '不允许的IP访问',
        'denyIp'      => '禁止的IP访问',
        'confirm'     => ':attribute和确认字段不一致',
        'different'   => ':attribute和比较字段不能相同',
        'egt'         => ':attribute必须大于等于 :rule',
        'gt'          => ':attribute必须大于 :rule',
        'elt'         => ':attribute必须小于等于 :rule',
        'lt'          => ':attribute必须小于 :rule',
        'eq'          => ':attribute必须等于 :rule',
        'unique'      => ':attribute已存在',
        'regex'       => ':attribute不符合指定规则',
        'method'      => '请求方法无效',
        'token'       => '令牌数据无效',
        'fileSize'    => '上传文件大小不符',
        'fileExt'     => '上传文件后缀不符',
        'fileMime'    => '上传文件类型不符',
        'isPositiveInteger' => ':attribute必须是正整数',
        'isNotEmpty'  => ':attribute不能为空',
        'isMobile'    => ':attribute格式不正确',
        'isBatch'     => ':attribute格式不正确',
    ];

    //获取所有请求数据并做批量验证
    public function goCheck()
    {

        $params = Request::param();
        $result = $this->check($params);
        if (!$result){
            //var_dump($this->error);
            //Log::error(var_export($this->error));
            throw new BaseException(['msg' => $this->error]);
        }else{
            return true;
        }
    }

    //验证是否正整数
    protected function isPositiveInteger($value)
    {
        if (is_numeric($value) && is_int($value + 0) && ($value + 0) > 0)
        {
            return true;
        } else {
            return false;
        }
    }

    //验证批次是否含名称
    protected function isBatch($value)
    {
        if ($value['name'])
        {
            return true;
        } else {
            return false;
        }
    }

    protected function isMobile($value)
    {
        $rule = '^1(3|4|5|7|8)[0-9]\d{8}$^';
        $result = preg_match($rule, $value);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
    //验证是否为空
    protected function isNotEmpty($value)
    {
        if (empty($value))
        {
            return false;
        } else {
            return true;
        }
    }
    //验证提交的数组中不含user_id与uid。并且过滤非验证器规则的字段
    public function getDataByRule($arrays)
    {
        if (array_key_exists('user_id', $arrays) |array_key_exists('uid', $arrays)){
            // 不允许包含user_id或者uid，防止恶意覆盖user_id外键

            throw new BaseException(['msg' => '参数中包含有非法的参数名user_id或者uid']);
        }
        $newArray = [];
        foreach ($this->rule as $key => $value){
            $newArray[$key] = $arrays[$key];
        }
        return $arrays;
    }
}
