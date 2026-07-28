<?php

declare(strict_types=1);

/**
 * Smoke: app process manager start/stop.
 */
! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

use App\Library\App\AppProcessManager;
use App\Library\App\AppProcessRegistry;

$manager = new AppProcessManager();
$row = $manager->ensureRunning('mineadmin/demo');
$listen = (string) ($row['listen'] ?? '');
if ($listen === '') {
    fwrite(STDERR, "FAIL no listen\n");
    exit(1);
}
$body = @file_get_contents('http://' . $listen . '/health');
if (! is_string($body) || ! str_contains($body, 'ok')) {
    fwrite(STDERR, "FAIL health\n");
    exit(1);
}
echo "OK health {$listen}\n";
$manager->stop('mineadmin/demo');
if (AppProcessRegistry::get('mineadmin/demo') !== null) {
    fwrite(STDERR, "FAIL registry not cleared\n");
    exit(1);
}
echo "OK stopped\n";
echo "PASS app process manager\n";
