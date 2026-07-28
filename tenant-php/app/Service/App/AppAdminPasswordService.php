<?php

declare(strict_types=1);

namespace App\Service\App;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use App\Library\App\AppPath;
use App\Library\Tenant\TenantContext;
use App\Library\Tenant\TenantInfo;
use Hyperf\DbConnection\Db;
use PDO;

/**
 * 租户侧重置已安装应用的管理员密码（按应用类型适配）.
 */
final class AppAdminPasswordService
{
    private const XUNLIAN_IDENTIFIER = 'swowtech/xunlian-trace-tp6';

    /** 与 TP6 config/setting.php psw_code 保持一致 */
    private const XUNLIAN_PSW_CODE = 'rus5w2fr168';

    /**
     * @return array{identifier: string, username: string, supported: bool}
     */
    public function info(string $identifier): array
    {
        $identifier = AppPath::assertSafeIdentifier($identifier);
        $this->assertInstalled($identifier);

        return [
            'identifier' => $identifier,
            'username' => $this->defaultUsername($identifier),
            'supported' => $this->isSupported($identifier),
        ];
    }

    public function change(
        string $identifier,
        string $newPassword,
        ?string $username = null,
    ): void {
        $identifier = AppPath::assertSafeIdentifier($identifier);
        $this->assertInstalled($identifier);
        $newPassword = trim($newPassword);
        if (strlen($newPassword) < 6) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '密码至少 6 位');
        }
        if (! $this->isSupported($identifier)) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '该应用暂不支持在此修改管理员密码');
        }

        $user = trim((string) ($username ?? '')) ?: $this->defaultUsername($identifier);
        if ($identifier === self::XUNLIAN_IDENTIFIER) {
            $this->changeXunlianAdmin($user, $newPassword);

            return;
        }

        throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '该应用暂不支持在此修改管理员密码');
    }

    private function isSupported(string $identifier): bool
    {
        return $identifier === self::XUNLIAN_IDENTIFIER;
    }

    private function defaultUsername(string $identifier): string
    {
        return 'admin';
    }

    private function assertInstalled(string $identifier): void
    {
        $row = Db::table('tenant_installed_app')
            ->where('identifier', $identifier)
            ->where('status', 1)
            ->first(['id']);
        if ($row === null) {
            throw new BusinessException(ResultCode::NOT_FOUND, '应用未安装或未启用');
        }
    }

    private function changeXunlianAdmin(string $username, string $newPassword): void
    {
        $tenantId = TenantContext::id();
        if ($tenantId === null || $tenantId <= 0) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '请先选择租户');
        }
        $prefix = TenantContext::tablePrefix()
            ?: TenantInfo::tablePrefixForId($tenantId);
        $table = rtrim($prefix, '_') . '_xlsy_admin';
        // 安全：仅允许当前租户前缀表
        if (! preg_match('/^cy_\d+_xlsy_admin$/', $table)) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '无效的管理员表');
        }

        $hash = $this->xunlianHash($newPassword);
        /** @var PDO $pdo */
        $pdo = Db::connection()->getPdo();
        $exists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetch(PDO::FETCH_NUM);
        if ($exists === false) {
            throw new BusinessException(ResultCode::NOT_FOUND, '应用管理员表不存在，请先完成应用初始化');
        }

        $stmt = $pdo->prepare("UPDATE `{$table}` SET `password` = ? WHERE `username` = ? LIMIT 1");
        $stmt->execute([$hash, $username]);
        if ($stmt->rowCount() < 1) {
            // 无该用户则尝试更新 id=1 的主管理员
            $stmt2 = $pdo->prepare("UPDATE `{$table}` SET `password` = ?, `username` = ? WHERE `id` = 1 LIMIT 1");
            $stmt2->execute([$hash, $username]);
            if ($stmt2->rowCount() < 1) {
                throw new BusinessException(ResultCode::NOT_FOUND, '未找到应用管理员账号');
            }
        }
    }

    private function xunlianHash(string $password): string
    {
        return md5(md5($password) . md5(self::XUNLIAN_PSW_CODE));
    }
}
