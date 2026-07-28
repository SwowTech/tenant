<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/Service/WechatCallbackService.php';

use App\Service\WechatCallbackService;

$svc = new WechatCallbackService();
$token = 'mineToken';
$timestamp = '1710000000';
$nonce = 'nonce123';
$tmp = [$token, $timestamp, $nonce];
sort($tmp, SORT_STRING);
$signature = sha1(implode($tmp));

assert($svc->checkSignature($token, $signature, $timestamp, $nonce) === true);
assert($svc->checkSignature('t', 'bad', '1', '2') === false);
echo "WechatCallbackService OK\n";
