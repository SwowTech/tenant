<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Wechat;

use App\Service\WechatCallbackService;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class WechatCallbackServiceTest extends TestCase
{
    public function testCheckSignatureOk(): void
    {
        $token = 'mineToken';
        $timestamp = '1710000000';
        $nonce = 'nonce123';
        $tmp = [$token, $timestamp, $nonce];
        sort($tmp, SORT_STRING);
        $signature = sha1(implode($tmp));

        $svc = new WechatCallbackService();
        self::assertTrue($svc->checkSignature($token, $signature, $timestamp, $nonce));
    }

    public function testCheckSignatureFail(): void
    {
        $svc = new WechatCallbackService();
        self::assertFalse($svc->checkSignature('t', 'bad', '1', '2'));
    }
}
