<?php
namespace app\controller;


use bases\BaseController;
use utils\Captcha;


class Index extends BaseController
{
    /**
     * 验证码
     * @return $this|\think\Response
     */
    public function captcha()
    {
        ob_end_clean();
        return app()->make(Captcha::class)->create();
    }
}
