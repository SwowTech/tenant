<?php

declare(strict_types=1);

namespace App\Service\Setting;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;

/**
 * Password strength rules from user_login.password_strength.
 */
final class PasswordPolicy
{
    public function __construct(private readonly UserLoginSettingService $loginSetting) {}

    public function assertValid(string $password): void
    {
        $strength = (string) ($this->loginSetting->get()['password_strength'] ?? 'medium');
        $len = strlen($password);
        if ($len < 6) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '密码长度至少 6 位');
        }

        if ($strength === 'weak') {
            return;
        }

        if ($strength === 'strong') {
            if ($len < 8
                || ! preg_match('/[A-Za-z]/', $password)
                || ! preg_match('/\d/', $password)
                || ! preg_match('/[^A-Za-z0-9]/', $password)
            ) {
                throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '密码须至少 8 位，且包含字母、数字和符号');
            }

            return;
        }

        // medium
        if ($len < 8 || ! preg_match('/[A-Za-z]/', $password) || ! preg_match('/\d/', $password)) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '密码须至少 8 位，且包含字母和数字');
        }
    }
}
