<?php

declare(strict_types=1);

namespace App\Http\Admin\Request;

use App\Http\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\Validation\Request\FormRequest;

class WechatAccountRequest extends FormRequest
{
    use NoAuthorizeTrait;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'app_id' => 'required|string|max:64',
            'app_secret' => 'nullable|string|max:128',
            'token' => 'required|string|max:64',
            'encoding_aes_key' => 'nullable|string|max:64',
            'level' => 'integer|in:1,2,3,4',
            'status' => 'integer|in:0,1',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '公众号名称',
            'app_id' => 'AppID',
            'app_secret' => 'AppSecret',
            'token' => 'Token',
            'encoding_aes_key' => 'EncodingAESKey',
            'level' => '账号类型',
            'status' => '状态',
        ];
    }
}
