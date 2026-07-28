<?php

declare(strict_types=1);

namespace App\Http\Api\Request\V1;

use App\Http\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\Validation\Request\FormRequest;

class RegisterRequest extends FormRequest
{
    use NoAuthorizeTrait;

    public function rules(): array
    {
        return [
            'username' => 'required|string|min:3|max:20|regex:/^[A-Za-z0-9_]+$/',
            'password' => 'required|string|min:6|max:64',
            'nickname' => 'sometimes|string|max:60',
        ];
    }

    public function attributes(): array
    {
        return [
            'username' => '用户名',
            'password' => '密码',
            'nickname' => '昵称',
        ];
    }
}
