<?php

declare(strict_types=1);

namespace App\Library\App;

use App\Exception\BusinessException;
use App\Http\Common\ResultCode;
use App\Library\Tenant\DynamicTablePrefix;
use App\Model\OpsAppDomain;
use App\Model\OpsTenant;

/**
 * 应用独立域名绑定（一应用可绑多个域名，按租户隔离）.
 */
final class AppDomainBinding
{
    public function __construct(
        private readonly DynamicTablePrefix $dynamicTablePrefix,
    ) {}

    public static function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain) ?? $domain;
        $domain = explode('/', $domain)[0];
        $domain = explode(':', $domain)[0];

        return rtrim($domain, '.');
    }

    /**
     * @return array{id: int, domain: string, tenant_id: int, identifier: string, scheme: string, is_primary: bool, public_base: string}|null
     */
    public function findByDomain(string $host): ?array
    {
        $host = self::normalizeDomain($host);
        if ($host === '') {
            return null;
        }

        return $this->dynamicTablePrefix->withoutPrefix(function () use ($host) {
            $row = OpsAppDomain::query()->where('domain', $host)->first();
            if ($row === null) {
                return null;
            }

            return $this->toArray($row);
        });
    }

    /**
     * 生成对外访问根地址（主域名优先），如 https://sy.example.com
     */
    public function publicBase(int $tenantId, string $identifier): ?string
    {
        $identifier = AppPath::assertSafeIdentifier($identifier);
        $row = $this->pickPrimaryRow($tenantId, $identifier);

        return $row === null ? null : $this->buildPublicBase($row);
    }

    /**
     * @return array{id: int, domain: string, tenant_id: int, identifier: string, scheme: string, is_primary: bool, public_base: string}|null
     */
    public function findById(int $id): ?array
    {
        return $this->dynamicTablePrefix->withoutPrefix(function () use ($id) {
            $row = OpsAppDomain::query()->where('id', $id)->first();

            return $row === null ? null : $this->toArray($row);
        });
    }

    /**
     * @return list<array{id: int, domain: string, tenant_id: int, identifier: string, scheme: string, is_primary: bool, public_base: string}>
     */
    public function list(?int $tenantId = null, ?string $identifier = null): array
    {
        if ($identifier !== null && $identifier !== '') {
            $identifier = AppPath::assertSafeIdentifier($identifier);
        }

        return $this->dynamicTablePrefix->withoutPrefix(function () use ($tenantId, $identifier) {
            $q = OpsAppDomain::query()->orderByDesc('is_primary')->orderBy('id');
            if ($tenantId !== null && $tenantId > 0) {
                $q->where('tenant_id', $tenantId);
            }
            if ($identifier !== null && $identifier !== '') {
                $q->where('identifier', $identifier);
            }

            return $q->get()->map(fn ($row) => $this->toArray($row))->all();
        });
    }

    /**
     * 新增一条域名绑定（同一应用可绑多条）.
     *
     * @return array{id: int, domain: string, tenant_id: int, identifier: string, scheme: string, is_primary: bool, public_base: string}
     */
    public function add(int $tenantId, string $identifier, string $domain, string $scheme = 'https', bool $asPrimary = false): array
    {
        $identifier = AppPath::assertSafeIdentifier($identifier);
        $domain = self::normalizeDomain($domain);
        if ($domain === '' || ! preg_match('/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/i', $domain)) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '域名格式无效');
        }
        $scheme = strtolower($scheme) === 'http' ? 'http' : 'https';

        return $this->dynamicTablePrefix->withoutPrefix(function () use ($tenantId, $identifier, $domain, $scheme, $asPrimary) {
            $tenant = OpsTenant::query()->find($tenantId);
            if (! $tenant) {
                throw new BusinessException(ResultCode::NOT_FOUND, '租户不存在');
            }
            $taken = OpsAppDomain::query()->where('domain', $domain)->exists();
            if ($taken) {
                throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '域名已被占用');
            }

            $count = OpsAppDomain::query()
                ->where('tenant_id', $tenantId)
                ->where('identifier', $identifier)
                ->count();
            $primary = $asPrimary || $count === 0;
            if ($primary) {
                OpsAppDomain::query()
                    ->where('tenant_id', $tenantId)
                    ->where('identifier', $identifier)
                    ->update(['is_primary' => 0]);
            }

            $row = OpsAppDomain::query()->create([
                'domain' => $domain,
                'tenant_id' => $tenantId,
                'identifier' => $identifier,
                'scheme' => $scheme,
                'is_primary' => $primary ? 1 : 0,
            ]);

            return $this->toArray($row);
        });
    }

    /**
     * 兼容旧调用：无则新增，有则更新首条主域名（一应用一域名语义）.
     */
    public function bind(int $tenantId, string $identifier, string $domain, string $scheme = 'https'): void
    {
        $identifier = AppPath::assertSafeIdentifier($identifier);
        $domain = self::normalizeDomain($domain);
        $scheme = strtolower($scheme) === 'http' ? 'http' : 'https';

        $existing = $this->pickPrimaryRow($tenantId, $identifier);
        if ($existing === null) {
            $this->add($tenantId, $identifier, $domain, $scheme, true);

            return;
        }
        $this->update((int) $existing->id, $tenantId, [
            'domain' => $domain,
            'scheme' => $scheme,
            'is_primary' => true,
        ]);
    }

    /**
     * @param array{domain?: string, scheme?: string, is_primary?: bool} $data
     * @return array{id: int, domain: string, tenant_id: int, identifier: string, scheme: string, is_primary: bool, public_base: string}
     */
    public function update(int $id, ?int $tenantId, array $data): array
    {
        return $this->dynamicTablePrefix->withoutPrefix(function () use ($id, $tenantId, $data) {
            $q = OpsAppDomain::query()->where('id', $id);
            if ($tenantId !== null && $tenantId > 0) {
                $q->where('tenant_id', $tenantId);
            }
            /** @var OpsAppDomain|null $row */
            $row = $q->first();
            if ($row === null) {
                throw new BusinessException(ResultCode::NOT_FOUND, '域名绑定不存在');
            }

            if (isset($data['domain'])) {
                $domain = self::normalizeDomain((string) $data['domain']);
                if ($domain === '' || ! preg_match('/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/i', $domain)) {
                    throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '域名格式无效');
                }
                $taken = OpsAppDomain::query()
                    ->where('domain', $domain)
                    ->where('id', '<>', $id)
                    ->exists();
                if ($taken) {
                    throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '域名已被占用');
                }
                $row->domain = $domain;
            }
            if (isset($data['scheme'])) {
                $row->scheme = strtolower((string) $data['scheme']) === 'http' ? 'http' : 'https';
            }
            if (array_key_exists('is_primary', $data) && $data['is_primary']) {
                OpsAppDomain::query()
                    ->where('tenant_id', $row->tenant_id)
                    ->where('identifier', $row->identifier)
                    ->where('id', '<>', $id)
                    ->update(['is_primary' => 0]);
                $row->is_primary = 1;
            }

            $row->save();

            return $this->toArray($row->fresh() ?? $row);
        });
    }

    public function removeById(int $id, ?int $tenantId = null): void
    {
        $this->dynamicTablePrefix->withoutPrefix(function () use ($id, $tenantId) {
            $q = OpsAppDomain::query()->where('id', $id);
            if ($tenantId !== null && $tenantId > 0) {
                $q->where('tenant_id', $tenantId);
            }
            /** @var OpsAppDomain|null $row */
            $row = $q->first();
            if ($row === null) {
                throw new BusinessException(ResultCode::NOT_FOUND, '域名绑定不存在');
            }
            $tenantIdVal = (int) $row->tenant_id;
            $identifier = (string) $row->identifier;
            $wasPrimary = (int) $row->is_primary === 1;
            $row->delete();

            if ($wasPrimary) {
                $next = OpsAppDomain::query()
                    ->where('tenant_id', $tenantIdVal)
                    ->where('identifier', $identifier)
                    ->orderBy('id')
                    ->first();
                if ($next) {
                    $next->is_primary = 1;
                    $next->save();
                }
            }
        });
    }

    public function unbind(int $tenantId, string $identifier): void
    {
        $identifier = AppPath::assertSafeIdentifier($identifier);
        $this->dynamicTablePrefix->withoutPrefix(function () use ($tenantId, $identifier) {
            OpsAppDomain::query()
                ->where('tenant_id', $tenantId)
                ->where('identifier', $identifier)
                ->delete();
        });
    }

    private function pickPrimaryRow(int $tenantId, string $identifier): ?OpsAppDomain
    {
        return $this->dynamicTablePrefix->withoutPrefix(function () use ($tenantId, $identifier) {
            $row = OpsAppDomain::query()
                ->where('tenant_id', $tenantId)
                ->where('identifier', $identifier)
                ->where('is_primary', 1)
                ->first();
            if ($row !== null) {
                return $row;
            }

            return OpsAppDomain::query()
                ->where('tenant_id', $tenantId)
                ->where('identifier', $identifier)
                ->orderBy('id')
                ->first();
        });
    }

    private function buildPublicBase(OpsAppDomain $row): string
    {
        $scheme = (string) ($row->scheme ?: 'https');
        $base = $scheme . '://' . $row->domain;

        return \App\Library\Support\AppUrl::withLocalPort($base);
    }

    /**
     * @return array{id: int, domain: string, tenant_id: int, identifier: string, scheme: string, is_primary: bool, public_base: string}
     */
    private function toArray(OpsAppDomain $row): array
    {
        return [
            'id' => (int) $row->id,
            'domain' => (string) $row->domain,
            'tenant_id' => (int) $row->tenant_id,
            'identifier' => (string) $row->identifier,
            'scheme' => (string) ($row->scheme ?: 'https'),
            'is_primary' => (int) ($row->is_primary ?? 0) === 1,
            'public_base' => $this->buildPublicBase($row),
        ];
    }
}
