<?php

declare(strict_types=1);

namespace App\Http\Admin\Request;

use App\Http\Common\Request\Traits\NoAuthorizeTrait;
use Hyperf\Validation\Request\FormRequest;

class AppDataMigrateRequest extends FormRequest
{
    use NoAuthorizeTrait;

    public function rules(): array
    {
        return [
            'from' => ['required', 'string', 'regex:/\A[A-Za-z0-9_-]+\/[A-Za-z0-9_-]+\z/'],
            'to' => ['required', 'string', 'regex:/\A[A-Za-z0-9_-]+\/[A-Za-z0-9_-]+\z/'],
        ];
    }

    public function attributes(): array
    {
        return [
            'from' => '源应用',
            'to' => '目标应用',
        ];
    }
}
