<?php

declare(strict_types=1);

namespace App\Service\Founder;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use App\Library\Support\AppUrl;
use App\Library\Tenant\DynamicTablePrefix;
use App\Library\Tenant\TenantInfo;
use App\Model\Enums\User\Status;
use App\Model\Enums\User\Type;
use App\Model\OpsTenant;
use App\Model\Permission\User;
use App\Service\PassportService;
use App\Service\Tenant\TenantProvisionService;

/**
 * 创始人业务租户 CRUD + 本地开通（不写订阅、不调 saas）.
 */
final class FounderTenantService
{
    public const STATUS_ACTIVE = 1;

    public const STATUS_DISABLED = 2;

    public const STATUS_PROVISIONING = 5;

    public const STATUS_PROVISION_FAILED = 6;

    public function __construct(
        private readonly TenantProvisionService $provision,
        private readonly DynamicTablePrefix $dynamicTablePrefix,
        private readonly FounderAppAssignService $appAssign,
        private readonly PassportService $passport,
    ) {}

    public function paginate(array $params): array
    {
        return $this->dynamicTablePrefix->withoutPrefix(function () use ($params) {
            $query = OpsTenant::query()->orderByDesc('id');
            if (! empty($params['name'])) {
                $query->where('name', 'like', '%' . $params['name'] . '%');
            }
            if (! empty($params['code'])) {
                $query->where('code', $params['code']);
            }
            if (isset($params['status']) && $params['status'] !== '') {
                $query->where('status', (int) $params['status']);
            }
            $page = max(1, (int) ($params['page'] ?? 1));
            $pageSize = max(1, min(100, (int) ($params['page_size'] ?? 15)));
            $paginator = $query->paginate($pageSize, ['*'], 'page', $page);

            return [
                'list' => array_map(fn (OpsTenant $t) => $this->toVo($t), $paginator->items()),
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'page_size' => $paginator->perPage(),
            ];
        });
    }

    /** @return array<string, mixed> */
    public function detail(int $id): array
    {
        return $this->dynamicTablePrefix->withoutPrefix(
            fn () => $this->toVo($this->requireTenant($id))
        );
    }

    /**
     * 域名标识是否可用（空白视为不可用）.
     */
    public function isDomainAvailable(string $domain, ?int $excludeId = null): bool
    {
        $domain = strtolower(trim($domain));
        if ($domain === '' || ! preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/', $domain)) {
            return false;
        }

        return $this->dynamicTablePrefix->withoutPrefix(function () use ($domain, $excludeId) {
            $query = OpsTenant::query()->where('domain', $domain);
            if ($excludeId !== null && $excludeId > 0) {
                $query->where('id', '<>', $excludeId);
            }

            return ! $query->exists();
        });
    }

    /**
     * 按域名标识解析可用租户（供 /login 区分租户）.
     *
     * @return array<string, mixed>
     */
    public function resolveByDomain(string $domain): array
    {
        $domain = strtolower(trim($domain));
        if ($domain === '' || ! preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/', $domain)) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '租户标识格式无效');
        }

        return $this->dynamicTablePrefix->withoutPrefix(function () use ($domain) {
            $tenant = OpsTenant::query()->where('domain', $domain)->first();
            if ($tenant === null) {
                throw new BusinessException(ResultCode::NOT_FOUND, '租户不存在');
            }
            $status = (int) ($tenant->status ?? 0);
            if ($status === self::STATUS_DISABLED || $status === 3) {
                throw new BusinessException(ResultCode::FORBIDDEN, '租户已停用');
            }
            if ($status === self::STATUS_PROVISIONING) {
                throw new BusinessException(ResultCode::FORBIDDEN, '租户开通中，请稍后再试');
            }
            if ($status === self::STATUS_PROVISION_FAILED) {
                throw new BusinessException(ResultCode::FORBIDDEN, '租户开通失败，请联系管理员');
            }
            if ($status !== self::STATUS_ACTIVE) {
                throw new BusinessException(ResultCode::FORBIDDEN, '租户不可用');
            }

            return $this->toVo($tenant);
        });
    }

    /**
     * 生成未被占用的随机域名标识（t + 7 位小写字母数字）.
     */
    public function suggestDomain(): string
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyz0123456789';
        for ($attempt = 0; $attempt < 32; ++$attempt) {
            $label = 't';
            for ($i = 0; $i < 7; ++$i) {
                $label .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            if ($this->isDomainAvailable($label)) {
                return $label;
            }
        }

        throw new BusinessException(ResultCode::FAIL, '无法生成可用域名标识，请稍后重试');
    }

    /**
     * 生成未被占用的随机租户编码（c + 9 位小写字母数字）.
     */
    public function generateUniqueCode(): string
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyz0123456789';

        return $this->dynamicTablePrefix->withoutPrefix(function () use ($alphabet) {
            for ($attempt = 0; $attempt < 32; ++$attempt) {
                $code = 'c';
                for ($i = 0; $i < 9; ++$i) {
                    $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
                }
                if (! OpsTenant::query()->where('code', $code)->exists()) {
                    return $code;
                }
            }

            throw new BusinessException(ResultCode::FAIL, '无法生成可用租户编码，请稍后重试');
        });
    }

    public function create(array $data): array
    {
        foreach (['name', 'domain', 'admin_user', 'admin_pass'] as $field) {
            if (empty($data[$field]) || ! is_string($data[$field])) {
                throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, "缺少必填字段: {$field}");
            }
        }

        $code = $this->generateUniqueCode();
        $name = trim($data['name']);
        $domain = strtolower(trim($data['domain']));
        $adminUser = trim($data['admin_user']);
        $adminPass = (string) $data['admin_pass'];

        if (! preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/', $domain)) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '域名标识格式无效（小写字母、数字、连字符）');
        }
        if (! $this->isDomainAvailable($domain)) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '域名标识已存在');
        }

        $tenant = $this->dynamicTablePrefix->withoutPrefix(function () use ($code, $name, $domain, $data) {
            $tenant = OpsTenant::query()->create([
                'code' => $code,
                'name' => $name,
                'domain' => $domain,
                'custom_domain' => self::normalizeCustomDomain($data['custom_domain'] ?? null),
                'contact_phone' => (string) ($data['contact_phone'] ?? ''),
                'contact_email' => (string) ($data['contact_email'] ?? ''),
                'remark' => (string) ($data['remark'] ?? ''),
                'status' => self::STATUS_PROVISIONING,
                'table_prefix' => '',
            ]);
            $tenant->table_prefix = TenantInfo::tablePrefixForId((int) $tenant->id);
            $tenant->save();

            return $tenant;
        });

        try {
            $this->provision->provision((int) $tenant->id, $adminUser, $adminPass);
            $vo = $this->dynamicTablePrefix->withoutPrefix(function () use ($tenant) {
                $tenant->status = self::STATUS_ACTIVE;
                $tenant->save();

                return $this->toVo($tenant->refresh());
            });
            try {
                $this->appAssign->assignAllLocalFilesystemApps((int) $tenant->id);
            } catch (\Throwable) {
                // 开通成功优先；附加应用失败不回滚租户
            }

            return $vo;
        } catch (\Throwable $e) {
            $this->dynamicTablePrefix->withoutPrefix(function () use ($tenant) {
                $tenant->status = self::STATUS_PROVISION_FAILED;
                $tenant->save();
            });
            throw $e;
        }
    }

    public function update(int $id, array $data): array
    {
        return $this->dynamicTablePrefix->withoutPrefix(function () use ($id, $data) {
            $tenant = $this->requireTenant($id);
            $allowed = array_intersect_key($data, array_flip([
                'name',
                'custom_domain',
                'contact_phone',
                'contact_email',
                'remark',
                'status',
            ]));
            if (array_key_exists('custom_domain', $allowed)) {
                $allowed['custom_domain'] = self::normalizeCustomDomain($allowed['custom_domain']);
            }
            $tenant->fill($allowed);
            if (isset($data['status'])) {
                $tenant->status = (int) $data['status'];
            }
            $tenant->save();

            return $this->toVo($tenant);
        });
    }

    /** 自定义域名：仅 trim；空则保持空字符串，不做任何拼装补全 */
    private static function normalizeCustomDomain(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return trim($value);
    }

    public function reprovision(int $id, ?string $adminUser = null, ?string $adminPass = null): array
    {
        $tenant = $this->dynamicTablePrefix->withoutPrefix(function () use ($id) {
            $tenant = $this->requireTenant($id);
            $tenant->status = self::STATUS_PROVISIONING;
            $tenant->save();

            return $tenant;
        });

        $user = $adminUser !== null && $adminUser !== '' ? $adminUser : 'admin';
        $pass = $adminPass !== null && $adminPass !== '' ? $adminPass : '123456';

        try {
            $this->provision->provision((int) $tenant->id, $user, $pass);

            return $this->dynamicTablePrefix->withoutPrefix(function () use ($tenant) {
                $tenant->status = self::STATUS_ACTIVE;
                $tenant->save();

                return $this->toVo($tenant->refresh());
            });
        } catch (\Throwable $e) {
            $this->dynamicTablePrefix->withoutPrefix(function () use ($tenant) {
                $tenant->status = self::STATUS_PROVISION_FAILED;
                $tenant->save();
            });
            throw $e;
        }
    }

    /**
     * 创始人免密进入租户：签发该租户超级管理员 JWT.
     *
     * @return array{access_token:string,refresh_token:string,expire_at:int,tenant:array<string,mixed>}
     */
    public function enterAsAdmin(int $tenantId): array
    {
        $tenant = $this->dynamicTablePrefix->withoutPrefix(function () use ($tenantId) {
            return $this->requireTenant($tenantId);
        });
        if ((int) $tenant->status !== self::STATUS_ACTIVE) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '请先开通或启用租户后再进入');
        }

        $prefix = (string) ($tenant->table_prefix ?: TenantInfo::tablePrefixForId($tenantId));
        $this->dynamicTablePrefix->apply($prefix);
        try {
            $user = User::query()
                ->where('user_type', Type::SYSTEM->value)
                ->where('status', Status::Normal->value)
                ->whereHas('roles', static function ($q) {
                    $q->where('code', 'SuperAdmin');
                })
                ->orderBy('id')
                ->first();
            if ($user === null) {
                $user = User::query()
                    ->where('user_type', Type::SYSTEM->value)
                    ->where('status', Status::Normal->value)
                    ->orderBy('id')
                    ->first();
            }
            if ($user === null) {
                throw new BusinessException(ResultCode::NOT_FOUND, '租户未找到可用管理员账号');
            }

            $tokens = $this->passport->issueTokens($user);

            return [
                ...$tokens,
                'tenant' => $this->toVo($tenant),
            ];
        } finally {
            $this->dynamicTablePrefix->reset();
        }
    }

    private function requireTenant(int $id): OpsTenant
    {
        $tenant = OpsTenant::query()->find($id);
        if (! $tenant) {
            throw new BusinessException(ResultCode::NOT_FOUND, '租户不存在');
        }

        return $tenant;
    }

    /** @return array<string, mixed> */
    private function toVo(OpsTenant $tenant): array
    {
        $data = $tenant->toArray();
        $data['access_url'] = AppUrl::tenantAccessUrl(
            (string) ($tenant->domain ?? ''),
            (string) ($tenant->custom_domain ?? ''),
        );

        return $data;
    }
}
