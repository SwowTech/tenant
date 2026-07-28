<?php
declare (strict_types = 1);

namespace app;

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
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        if (method_exists($v, 'setTypeMsg')) {
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
                'length'      => ':attribute长度不符合要求 :rule',
                'max'         => ':attribute长度不能超过 :rule',
                'min'         => ':attribute长度不能小于 :rule',
                'in'          => ':attribute必须在 :rule 范围内',
                'unique'      => ':attribute已存在',
                'regex'       => ':attribute不符合指定规则',
            ]);
        }

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

}
