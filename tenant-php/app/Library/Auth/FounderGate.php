<?php

declare(strict_types=1);

namespace App\Library\Auth;

use App\Library\Tenant\TenantContext;
use App\Model\Permission\User;

/**
 * 平台创始人判定：租户上下文中 user_id=1 只是该租户管理员，不是平台创始人。
 */
final class FounderGate
{
    public static function allows(?int $userId, ?User $user): bool
    {
        if (TenantContext::get() !== null) {
            return false;
        }

        if ($userId === 1) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $user->roles()->where('code', 'founder')->exists();
    }
}
