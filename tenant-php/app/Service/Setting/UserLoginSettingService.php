<?php

declare(strict_types=1);

namespace App\Service\Setting;

use App\Repository\SystemSettingRepository;

final class UserLoginSettingService
{
    public const KEY = 'user_login';

    public function __construct(private readonly SystemSettingRepository $repo) {}

    public function get(): array
    {
        return array_replace_recursive($this->defaults(), (array) $this->repo->get(self::KEY, []));
    }

    public function save(array $data): array
    {
        $cur = $this->get();

        foreach ([
            'register_enabled',
            'mobile_register_enabled',
            'review_new_user',
            'captcha_register',
            'captcha_login',
            'third_party_entry',
            'review_app_operator',
            'operator_force_bind',
        ] as $b) {
            if (array_key_exists($b, $data)) {
                $cur[$b] = (bool) $data[$b];
            }
        }

        if (array_key_exists('user_agreement', $data)) {
            $cur['user_agreement'] = (string) $data['user_agreement'];
        }
        if (array_key_exists('password_strength', $data)) {
            $cur['password_strength'] = (string) $data['password_strength'];
        }
        if (array_key_exists('default_user_group', $data)) {
            $cur['default_user_group'] = max(0, (int) $data['default_user_group']);
        }
        if (array_key_exists('login_time_limit', $data)) {
            $cur['login_time_limit'] = max(0, (int) $data['login_time_limit']);
        }
        if (array_key_exists('force_bind', $data)) {
            $bind = (string) $data['force_bind'];
            $cur['force_bind'] = in_array($bind, ['', 'qq', 'wechat', 'mobile'], true) ? $bind : '';
        }

        foreach (['qq', 'wechat'] as $provider) {
            if (isset($data[$provider]) && is_array($data[$provider])) {
                foreach (['app_id', 'app_secret', 'callback_domain'] as $field) {
                    if (array_key_exists($field, $data[$provider])) {
                        $cur[$provider][$field] = (string) $data[$provider][$field];
                    }
                }
            }
        }

        $this->repo->set(self::KEY, $cur);

        return $cur;
    }

    private function defaults(): array
    {
        return [
            'register_enabled' => true,
            'mobile_register_enabled' => false,
            'review_new_user' => false,
            'user_agreement' => '',
            'captcha_register' => false,
            'captcha_login' => false,
            'password_strength' => 'medium',
            'default_user_group' => 0,
            'login_time_limit' => 0,
            'force_bind' => '',
            'third_party_entry' => false,
            'qq' => [
                'app_id' => '',
                'app_secret' => '',
                'callback_domain' => '',
            ],
            'wechat' => [
                'app_id' => '',
                'app_secret' => '',
                'callback_domain' => '',
            ],
            'review_app_operator' => false,
            'operator_force_bind' => false,
        ];
    }
}
