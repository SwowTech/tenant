<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace Plugin\MineAdmin\Crontab\Http;

use Hyperf\Validation\Request\FormRequest as HyperfFormRequest;
use Hyperf\Validation\Rule;
use Plugin\MineAdmin\Crontab\Enums\CrontabType;

class FormRequest extends HyperfFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:crontab,name',
            ],
            'rule' => 'required|string',
            'memo' => 'sometimes|string',
            'status' => 'sometimes|boolean',
            'is_singleton' => 'sometimes|boolean',
            'is_on_one_server' => 'sometimes|boolean',
            'type' => [
                'required',
                Rule::enum(CrontabType::class),
            ],
            'value' => 'required|string',
        ];
        if ($this->isMethod('put')) {
            $id = $this->route('id');
            $rules['name'][3] = Rule::unique('crontab', 'name')->ignore($id);
        }
        return $rules;
    }

    public function attributes(): array
    {
        return [
            'name' => '名称',
            'rule' => '规则',
            'memo' => '备注',
            'status' => '状态',
            'is_singleton' => '是否单例',
            'is_on_one_server' => '是否只在一台服务器上运行',
            'type' => '任务类型',
            'value' => '值',
        ];
    }
}
