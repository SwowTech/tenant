<?php
declare (strict_types = 1);

namespace bases;


use exceptions\BaseException;
use think\App;
use think\exception\ValidateException;
use think\Validate;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {}

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                list($validate, $scene) = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }
        // 默认中文校验提示（字段级 message 优先）
        $v->setTypeMsg([
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
        ]);
        $v->message($message);


        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }
        $result=$v->check($data);
        if (!$result){
            throw new BaseException(['msg' => $v->getError()]);
        }else{
            return true;
        }
    }

}
