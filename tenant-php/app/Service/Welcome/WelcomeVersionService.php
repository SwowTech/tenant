<?php
declare(strict_types=1);

namespace App\Service\Welcome;

final class WelcomeVersionService
{
    public function current(): array
    {
        $current = (string) env('APP_VERSION', '1.0.0');
        return [
            'current' => $current,
            'latest' => null,
            'upgradable' => false,
            'message' => '当前版本非最新版本，无法升级',
        ];
    }

    public function check(): array
    {
        $base = $this->current();
        $url = trim((string) env('UPGRADE_CHECK_URL', ''));
        if ($url === '') {
            $base['message'] = '未配置升级源（UPGRADE_CHECK_URL），无法检测新版本';
            $base['upgradable'] = false;
            return $base;
        }
        try {
            $json = json_decode((string) file_get_contents($url), true);
            $latest = (string) ($json['version'] ?? $json['latest'] ?? '');
            $base['latest'] = $latest !== '' ? $latest : null;
            $base['upgradable'] = $latest !== '' && version_compare($latest, $base['current'], '>');
            $base['message'] = $base['upgradable']
                ? '发现新版本 ' . $latest
                : '当前版本即最新版本，无须升级';
            return $base;
        } catch (\Throwable $e) {
            $base['message'] = '检查失败: ' . $e->getMessage();
            return $base;
        }
    }
}
