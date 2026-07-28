<?php

declare(strict_types=1);

namespace App\Service;

final class WechatCallbackService
{
    public function checkSignature(string $token, string $signature, string $timestamp, string $nonce): bool
    {
        if ($token === '' || $signature === '' || $timestamp === '' || $nonce === '') {
            return false;
        }
        $tmp = [$token, $timestamp, $nonce];
        sort($tmp, SORT_STRING);
        return hash_equals(sha1(implode($tmp)), $signature);
    }
}
