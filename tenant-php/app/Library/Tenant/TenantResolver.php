<?php

declare(strict_types=1);

namespace App\Library\Tenant;

use App\Model\OpsTenant;
use Psr\Http\Message\ServerRequestInterface;

final class TenantResolver
{
    public function __construct(
        private readonly DynamicTablePrefix $dynamicTablePrefix,
    ) {}

    public function resolve(ServerRequestInterface $request): ?TenantInfo
    {
        $headerId = $request->getHeaderLine('X-Tenant-Id');
        if ($headerId !== '' && ctype_digit($headerId)) {
            return $this->fromId((int) $headerId);
        }

        $query = $request->getQueryParams();
        $queryId = (string) ($query['tenant_id'] ?? $query['tenantId'] ?? '');
        if ($queryId !== '' && ctype_digit($queryId)) {
            return $this->fromId((int) $queryId);
        }

        $host = $request->getHeaderLine('Host');
        if ($host === '') {
            return null;
        }
        $host = explode(':', $host)[0];
        $byCustom = $this->fromCustomDomain($host);
        if ($byCustom !== null) {
            return $byCustom;
        }
        $parts = explode('.', $host);
        if (count($parts) < 2) {
            return null;
        }
        $subdomain = $parts[0];
        if (in_array($subdomain, ['www', '127', 'localhost'], true)) {
            return null;
        }

        return $this->fromDomain($subdomain);
    }

    public function fromId(int $id): ?TenantInfo
    {
        return $this->dynamicTablePrefix->withoutPrefix(function () use ($id) {
            $row = OpsTenant::query()->where('id', $id)->first();
            if ($row === null) {
                return null;
            }

            return $this->mapRow($row->toArray());
        });
    }

    public function fromDomain(string $domain): ?TenantInfo
    {
        return $this->dynamicTablePrefix->withoutPrefix(function () use ($domain) {
            $row = OpsTenant::query()->where('domain', $domain)->first();
            if ($row === null) {
                return null;
            }

            return $this->mapRow($row->toArray());
        });
    }

    public function fromCustomDomain(string $host): ?TenantInfo
    {
        $host = strtolower(trim($host));
        if ($host === '') {
            return null;
        }

        return $this->dynamicTablePrefix->withoutPrefix(function () use ($host) {
            $row = OpsTenant::query()->where('custom_domain', $host)->first();
            if ($row === null) {
                return null;
            }

            return $this->mapRow($row->toArray());
        });
    }

    public function fromCode(string $code): ?TenantInfo
    {
        if ($code === '') {
            return null;
        }

        return $this->dynamicTablePrefix->withoutPrefix(function () use ($code) {
            $row = OpsTenant::query()->where('code', $code)->first();
            if ($row === null) {
                return null;
            }

            return $this->mapRow($row->toArray());
        });
    }

    private function mapRow(array $row): TenantInfo
    {
        $id = (int) $row['id'];
        $prefix = (string) ($row['table_prefix'] ?: TenantInfo::tablePrefixForId($id));

        return new TenantInfo(
            id: $id,
            code: (string) $row['code'],
            domain: (string) $row['domain'],
            tablePrefix: $prefix,
            status: (int) ($row['status'] ?? 1),
        );
    }
}
