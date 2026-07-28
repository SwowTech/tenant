<?php

declare(strict_types=1);

namespace App\Service;

use App\Library\Tenant\TenantContext;
use App\Repository\WechatAccountRepository;

use function Hyperf\Support\env;

final class WechatAccountService
{
    public function __construct(
        private readonly WechatAccountRepository $repository,
    ) {}

    public function get(): ?array
    {
        $row = $this->repository->first();
        if ($row === null) {
            return null;
        }

        return $this->toView($row->toArray());
    }

    public function save(array $input): array
    {
        $row = $this->repository->first();
        $secret = (string) ($input['app_secret'] ?? '');
        if ($secret === '' && $row !== null) {
            $secret = (string) $row->app_secret;
        }

        $data = [
            'name' => (string) ($input['name'] ?? ''),
            'app_id' => (string) ($input['app_id'] ?? ''),
            'app_secret' => $secret,
            'token' => (string) ($input['token'] ?? ''),
            'encoding_aes_key' => (string) ($input['encoding_aes_key'] ?? ''),
            'level' => (int) ($input['level'] ?? 1),
            'status' => (int) ($input['status'] ?? 1),
        ];

        $saved = $this->repository->upsert($data);

        return $this->toView($saved->toArray());
    }

    public function callbackUrl(): string
    {
        $base = rtrim((string) env('APP_URL', 'http://127.0.0.1:9501'), '/');
        $code = TenantContext::get()?->code ?: 'default';

        return $base . '/wechat/callback/' . rawurlencode($code);
    }

    public function emptyView(): array
    {
        return [
            'name' => '',
            'app_id' => '',
            'app_secret' => '',
            'app_secret_set' => false,
            'token' => '',
            'encoding_aes_key' => '',
            'level' => 1,
            'status' => 1,
            'callback_url' => $this->callbackUrl(),
        ];
    }

    private function toView(array $row): array
    {
        $secret = (string) ($row['app_secret'] ?? '');
        $masked = $secret === ''
            ? ''
            : str_repeat('*', max(strlen($secret) - 4, 0)) . substr($secret, -4);

        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'app_id' => (string) ($row['app_id'] ?? ''),
            'app_secret' => $masked,
            'app_secret_set' => $secret !== '',
            'token' => (string) ($row['token'] ?? ''),
            'encoding_aes_key' => (string) ($row['encoding_aes_key'] ?? ''),
            'level' => (int) ($row['level'] ?? 1),
            'status' => (int) ($row['status'] ?? 1),
            'callback_url' => $this->callbackUrl(),
        ];
    }
}
