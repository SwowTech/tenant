<?php

declare(strict_types=1);

namespace App\Http\Api\Controller\V1;

use App\Exception\BusinessException;
use App\Http\Common\Controller\AbstractController;
use App\Http\Common\Result;
use App\Http\Common\ResultCode;
use App\Library\Support\AppUrl;
use App\Service\Founder\FounderTenantService;
use App\Service\Setting\PasswordPolicy;
use App\Service\Setting\UserLoginSettingService;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Swagger\Annotation\Get;
use Hyperf\Swagger\Annotation\HyperfServer;
use Hyperf\Swagger\Annotation\Post;
use Mine\Swagger\Attributes\ResultResponse;

/**
 * Public tenant self-registration (same fields as founder「新增租户」).
 */
#[HyperfServer(name: 'http')]
final class TenantRegisterController extends AbstractController
{
    public function __construct(
        private readonly FounderTenantService $tenants,
        private readonly UserLoginSettingService $loginSetting,
        private readonly PasswordPolicy $passwordPolicy,
    ) {}

    #[Get(
        path: '/api/v1/tenant/domain-available',
        operationId: 'ApiV1TenantDomainAvailable',
        summary: '域名标识是否可用',
        tags: ['api'],
    )]
    #[ResultResponse(instance: new Result())]
    public function domainAvailable(RequestInterface $request): Result
    {
        $domain = strtolower(trim((string) $request->input('domain', '')));
        $available = $this->tenants->isDomainAvailable($domain);

        return $this->success([
            'domain' => $domain,
            'available' => $available,
            'root_host' => AppUrl::host(),
            'access_url' => $available ? AppUrl::tenantAccessUrl($domain) : '',
        ]);
    }

    #[Get(
        path: '/api/v1/tenant/suggest-domain',
        operationId: 'ApiV1TenantSuggestDomain',
        summary: '建议可用域名标识',
        tags: ['api'],
    )]
    #[ResultResponse(instance: new Result())]
    public function suggestDomain(): Result
    {
        $domain = $this->tenants->suggestDomain();

        return $this->success([
            'domain' => $domain,
            'available' => true,
            'root_host' => AppUrl::host(),
            'access_url' => AppUrl::tenantAccessUrl($domain),
        ]);
    }

    #[Get(
        path: '/api/v1/tenant/resolve',
        operationId: 'ApiV1TenantResolve',
        summary: '按域名标识解析租户',
        tags: ['api'],
    )]
    #[ResultResponse(instance: new Result())]
    public function resolve(RequestInterface $request): Result
    {
        $domain = strtolower(trim((string) $request->input('domain', '')));
        $vo = $this->tenants->resolveByDomain($domain);

        return $this->success([
            'id' => (int) ($vo['id'] ?? 0),
            'name' => (string) ($vo['name'] ?? ''),
            'domain' => (string) ($vo['domain'] ?? $domain),
            'access_url' => (string) ($vo['access_url'] ?? ''),
            'root_host' => AppUrl::host(),
        ]);
    }

    #[Post(
        path: '/api/v1/tenant/register',
        operationId: 'ApiV1TenantRegister',
        summary: '自助注册租户',
        tags: ['api'],
    )]
    #[ResultResponse(instance: new Result())]
    public function register(RequestInterface $request): Result
    {
        $cfg = $this->loginSetting->get();
        if (! ($cfg['register_enabled'] ?? false)) {
            throw new BusinessException(ResultCode::FORBIDDEN, '当前未开放注册');
        }

        $payload = $request->all();
        $adminPass = (string) ($payload['admin_pass'] ?? '');
        $this->passwordPolicy->assertValid($adminPass);

        $vo = $this->tenants->create($payload);

        return $this->success($vo);
    }
}
