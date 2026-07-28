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

namespace App\Service;

use App\Exception\BusinessException;
use App\Exception\JwtInBlackException;
use App\Http\Common\ResultCode;
use App\Model\Enums\User\Status;
use App\Model\Enums\User\Type;
use App\Model\Permission\Department;
use App\Model\Permission\Position;
use App\Model\Permission\Role;
use App\Model\Permission\User;
use App\Repository\Permission\UserRepository;
use App\Service\Setting\PasswordPolicy;
use App\Service\Setting\UserLoginSettingService;
use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\UnencryptedToken;
use Mine\Jwt\Factory;
use Mine\Jwt\JwtInterface;
use Mine\JwtAuth\Event\UserLoginEvent;
use Mine\JwtAuth\Interfaces\CheckTokenInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

final class PassportService extends IService implements CheckTokenInterface
{
    /**
     * @var string jwt场景
     */
    private string $jwt = 'default';

    public function __construct(
        protected readonly UserRepository $repository,
        protected readonly Factory $jwtFactory,
        protected readonly EventDispatcherInterface $dispatcher,
        protected readonly UserLoginSettingService $loginSetting,
        protected readonly PasswordPolicy $passwordPolicy,
    ) {}

    /**
     * @return array<string,int|string>
     */
    public function login(string $username, string $password, Type $userType = Type::SYSTEM, string $ip = '0.0.0.0', string $browser = 'unknown', string $os = 'unknown'): array
    {
        $user = $this->repository->findByUnameType($username, $userType);
        if (! $user->verifyPassword($password)) {
            $this->dispatcher->dispatch(new UserLoginEvent($user, $ip, $os, $browser, false));
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, trans('auth.password_error'));
        }
        if ($user->status->isDisable()) {
            throw new BusinessException(ResultCode::DISABLED, '账号已停用或待审核');
        }
        $this->dispatcher->dispatch(new UserLoginEvent($user, $ip, $os, $browser));

        return $this->issueTokens($user);
    }

    /**
     * 签发 token（不做密码校验；供创始人免密进入租户等场景）.
     *
     * @return array{access_token:string,refresh_token:string,expire_at:int}
     */
    public function issueTokens(User $user): array
    {
        if ($user->status->isDisable()) {
            throw new BusinessException(ResultCode::DISABLED, '账号已停用或待审核');
        }
        $jwt = $this->getJwt();

        return [
            'access_token' => $jwt->builderAccessToken((string) $user->id)->toString(),
            'refresh_token' => $jwt->builderRefreshToken((string) $user->id)->toString(),
            'expire_at' => (int) $jwt->getConfig('ttl', 0),
        ];
    }

    public function register(string $username, string $password, string $nickname = ''): User
    {
        $cfg = $this->loginSetting->get();
        if (! ($cfg['register_enabled'] ?? false)) {
            throw new BusinessException(ResultCode::FORBIDDEN, '当前未开放用户注册');
        }

        $this->passwordPolicy->assertValid($password);

        if ($this->repository->getQuery()
            ->where('username', $username)
            ->where('user_type', Type::USER->value)
            ->exists()
        ) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '用户名已存在');
        }

        $status = ($cfg['review_new_user'] ?? false) ? Status::DISABLE : Status::Normal;
        $roleId = (int) ($cfg['default_user_group'] ?? 0);

        /** @var User $user */
        $user = $this->repository->create([
            'username' => $username,
            'password' => $password,
            'user_type' => Type::USER->value,
            'nickname' => $nickname !== '' ? $nickname : $username,
            'status' => $status->value,
            'created_by' => 0,
            'updated_by' => 0,
            'remark' => 'tenant-self-register',
        ]);

        if ($roleId > 0) {
            try {
                $user->roles()->sync([$roleId]);
            } catch (\Throwable) {
                // ignore invalid default role
            }
        }

        return $user;
    }

    /**
     * @return array{register_enabled:bool,captcha_login:bool,captcha_register:bool,password_strength:string,user_agreement:string,login_time_limit:int}
     */
    public function tenantAuthConfig(): array
    {
        $login = $this->loginSetting->get();

        return [
            'register_enabled' => (bool) ($login['register_enabled'] ?? false),
            'captcha_login' => (bool) ($login['captcha_login'] ?? false),
            'captcha_register' => (bool) ($login['captcha_register'] ?? false),
            'password_strength' => (string) ($login['password_strength'] ?? 'medium'),
            'user_agreement' => (string) ($login['user_agreement'] ?? ''),
            'login_time_limit' => (int) ($login['login_time_limit'] ?? 0),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function formatUserInfo(?User $user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        return [
            'id' => $user->id,
            'username' => $user->username,
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'signed' => $user->signed,
            'backend_setting' => $user->backend_setting,
            'phone' => $user->phone,
            'email' => $user->email,
            'departments' => $this->getUserDepartments($user),
            'positions' => $this->getUserPositions($user),
            'roles' => $this->getUserRoles($user),
        ];
    }

    /**
     * @return array<int,array{id:int,name:string}>
     */
    private function getUserDepartments(User $user): array
    {
        return $user->department()
            ->select(['department.id', 'department.name'])
            ->orderBy('department.id')
            ->get()
            ->map(static fn (Department $department): array => [
                'id' => $department->id,
                'name' => $department->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int,array{id:int,dept_id:int,name:string}>
     */
    private function getUserPositions(User $user): array
    {
        return $user->position()
            ->select(['position.id', 'position.dept_id', 'position.name'])
            ->orderBy('position.id')
            ->get()
            ->map(static fn (Position $position): array => [
                'id' => $position->id,
                'dept_id' => $position->dept_id,
                'name' => $position->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int,array{id:int,code:string,name:string}>
     */
    private function getUserRoles(User $user): array
    {
        return $user->roles()
            ->where('role.status', Status::Normal->value)
            ->select(['role.id', 'role.code', 'role.name'])
            ->orderBy('role.sort')
            ->get()
            ->map(static fn (Role $role): array => [
                'id' => $role->id,
                'code' => $role->code,
                'name' => $role->name,
            ])
            ->values()
            ->all();
    }

    public function checkJwt(UnencryptedToken $token): void
    {
        $this->getJwt()->hasBlackList($token) && throw new JwtInBlackException();
    }

    public function logout(UnencryptedToken $token): void
    {
        $this->getJwt()->addBlackList($token);
    }

    public function getJwt(): JwtInterface
    {
        return $this->jwtFactory->get($this->jwt);
    }

    /**
     * @return array<string,int|string>
     */
    public function refreshToken(UnencryptedToken $token): array
    {
        return value(static function (JwtInterface $jwt) use ($token) {
            $jwt->addBlackList($token);
            return [
                'access_token' => $jwt->builderAccessToken($token->claims()->get(RegisteredClaims::ID))->toString(),
                'refresh_token' => $jwt->builderRefreshToken($token->claims()->get(RegisteredClaims::ID))->toString(),
                'expire_at' => (int) $jwt->getConfig('ttl', 0),
            ];
        }, $this->getJwt());
    }
}