<?php

namespace app\services;

use app\model\SysConfig as SysConfigModel;

class AuthService
{
    /**
     * 读取当前授权状态（仅本地校验，不自动拉码）
     */
    public static function current()
    {
        $row = SysConfigModel::where('key', 'author_code')->find();
        $host = self::currentHost();
        $code = $row ? trim((string) $row['value']) : '';
        $authorized = $code !== '' && self::verifyCode($code, $host);

        // 纠正脏数据：无码却标记已授权
        if ($row && $code === '' && ($row['desc'] ?? '') === '已授权') {
            SysConfigModel::where('key', 'author_code')->update(['desc' => '未授权']);
            $row = SysConfigModel::where('key', 'author_code')->find();
            $authorized = false;
        } elseif ($row && $code !== '' && $authorized && ($row['desc'] ?? '') !== '已授权') {
            SysConfigModel::where('key', 'author_code')->update(['desc' => '已授权']);
            $row = SysConfigModel::where('key', 'author_code')->find();
        } elseif ($row && $code !== '' && !$authorized) {
            // 有码但验签失败：保留码，状态显示未授权（由检查按钮处理清空）
            if (($row['desc'] ?? '') === '已授权') {
                SysConfigModel::where('key', 'author_code')->update(['desc' => '未授权']);
                $row = SysConfigModel::where('key', 'author_code')->find();
            }
        }

        return self::buildResult($authorized, $row, $host);
    }

    /**
     * 检查本地授权码是否有效
     */
    public static function check()
    {
        $row = SysConfigModel::where('key', 'author_code')->find();
        $host = self::currentHost();
        $code = $row ? trim((string) $row['value']) : '';

        if ($code === '') {
            SysConfigModel::where('key', 'author_code')->update(['value' => '', 'desc' => '未授权']);
            $row = SysConfigModel::where('key', 'author_code')->find();
            return array_merge(self::buildResult(false, $row, $host), [
                'ok' => false,
                'msg' => '尚未填写授权码，请先获取或手动填写',
            ]);
        }

        if (self::verifyCode($code, $host)) {
            self::markAuthorized($code);
            $row = SysConfigModel::where('key', 'author_code')->find();
            return array_merge(self::buildResult(true, $row, $host), [
                'ok' => true,
                'msg' => '授权有效（当前域名：' . $host . '）',
            ]);
        }

        SysConfigModel::where('key', 'author_code')->update(['desc' => '未授权']);
        $row = SysConfigModel::where('key', 'author_code')->find();
        return array_merge(self::buildResult(false, $row, $host), [
            'ok' => false,
            'msg' => '授权码无效或与当前域名不匹配（' . $host . '）',
        ]);
    }

    /**
     * 从授权服务器获取授权码并保存
     */
    public static function fetch()
    {
        $host = self::currentHost();
        if ($host === '') {
            return array_merge(self::buildResult(false, null, $host), [
                'ok' => false,
                'msg' => '无法识别当前站点域名',
            ]);
        }

        $remote = self::fetchRemoteCode($host);
        if ($remote) {
            self::markAuthorized($remote);
            $row = SysConfigModel::where('key', 'author_code')->find();
            $authorized = self::verifyCode(trim((string) $row['value']), $host) || trim((string) $row['value']) !== '';
            return array_merge(self::buildResult($authorized, $row, $host), [
                'ok' => true,
                'msg' => '已成功获取授权码',
            ]);
        }

        $row = SysConfigModel::where('key', 'author_code')->find();
        return array_merge(self::buildResult(false, $row, $host), [
            'ok' => false,
            'msg' => '获取授权码失败，请确认站点已在授权平台登记（' . $host . '）',
        ]);
    }

    /**
     * 兼容旧调用：先检查本地，失败再尝试获取
     */
    public static function auth()
    {
        $check = self::check();
        if (!empty($check['authorized'])) {
            return true;
        }
        $fetch = self::fetch();
        return !empty($fetch['ok']);
    }

    public static function status()
    {
        return self::current();
    }

    private static function buildResult($authorized, $row, $host)
    {
        return [
            'authorized' => (bool) $authorized,
            'desc' => $authorized ? '已授权' : '未授权',
            'has_code' => $row && trim((string) $row['value']) !== '',
            'host' => $host,
            'config' => $row ? $row->toArray() : null,
        ];
    }

    private static function currentHost()
    {
        return $_SERVER['HTTP_HOST'] ?? '';
    }

    private static function markAuthorized($code)
    {
        SysConfigModel::where('key', 'author_code')->update([
            'value' => $code,
            'desc' => '已授权',
        ]);
    }

    private static function verifyCode($code, $host)
    {
        if ($code === '' || $host === '') {
            return false;
        }
        $keyPath = app()->getAppPath() . 'rsa/public.key';
        if (!is_file($keyPath)) {
            return false;
        }
        $key = file_get_contents($keyPath);
        $public_key = openssl_pkey_get_public($key);
        if (!$public_key) {
            return false;
        }
        $decrypted = '';
        $ok = @openssl_public_decrypt(base64_decode($code), $decrypted, $public_key);
        return $ok && $decrypted === $host;
    }

    private static function fetchRemoteCode($host)
    {
        try {
            $client = new \GuzzleHttp\Client(['timeout' => 10]);
            $url = rtrim(VENDOR_URL, '/') . '/index.php/auth/soft_author?site_name=' . urlencode($host);
            $response = $client->get($url);
            $res = json_decode((string) $response->getBody());
            if ($res && isset($res->msg) && $res->msg === 'ok' && !empty($res->data)) {
                return $res->data;
            }
        } catch (\Throwable $e) {
        }
        return null;
    }
}
