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

namespace App\Http\Api\Controller\V1;

use App\Http\Api\Request\V1\RegisterRequest;
use App\Http\Api\Request\V1\UserRequest;
use App\Http\Common\Controller\AbstractController;
use App\Http\Common\Result;
use App\Model\Enums\User\Type;
use App\Service\PassportService;
use Hyperf\Swagger\Annotation\Get;
use Hyperf\Swagger\Annotation\HyperfServer;
use Hyperf\Swagger\Annotation\Post;
use Mine\Swagger\Attributes\ResultResponse;

/**
 * Tenant member auth (not admin console).
 */
#[HyperfServer(name: 'http')]
final class UserController extends AbstractController
{
    public function __construct(private readonly PassportService $passportService) {}

    #[Get(
        path: '/api/v1/auth-config',
        operationId: 'ApiV1AuthConfig',
        summary: '租户成员登录/注册公开配置',
        tags: ['api'],
    )]
    #[ResultResponse(instance: new Result())]
    public function authConfig(): Result
    {
        return $this->success($this->passportService->tenantAuthConfig());
    }

    #[Post(
        path: '/api/v1/login',
        operationId: 'ApiV1Login',
        summary: '租户成员登录',
        tags: ['api'],
    )]
    #[ResultResponse(instance: new Result())]
    public function login(UserRequest $request): Result
    {
        $validated = $request->validated();
        $ip = (string) ($request->getServerParams()['remote_addr'] ?? '0.0.0.0');
        $ua = (string) ($request->getHeaderLine('User-Agent') ?: 'unknown');
        $tokens = $this->passportService->login(
            (string) $validated['username'],
            (string) $validated['password'],
            Type::USER,
            $ip,
            $ua,
            'unknown',
        );
        $cfg = $this->passportService->tenantAuthConfig();
        $tokens['auth'] = [
            'login_time_limit' => $cfg['login_time_limit'],
        ];

        return $this->success($tokens);
    }

    #[Post(
        path: '/api/v1/register',
        operationId: 'ApiV1Register',
        summary: '租户成员注册',
        tags: ['api'],
    )]
    #[ResultResponse(instance: new Result())]
    public function register(RegisterRequest $request): Result
    {
        $data = $request->validated();
        $user = $this->passportService->register(
            (string) $data['username'],
            (string) $data['password'],
            (string) ($data['nickname'] ?? ''),
        );

        return $this->success([
            'id' => $user->id,
            'username' => $user->username,
            'status' => $user->status->value,
            'need_review' => $user->status->isDisable(),
        ]);
    }
}
