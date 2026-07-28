<?php

declare(strict_types=1);

/**
 * Unit-style smoke for CloudUpgradeService (safeJoin + version read/write).
 */

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

use App\Service\Cloud\CloudUpgradeService;
use Hyperf\Context\ApplicationContext;

function upgFail(string $msg): never
{
    echo "FAIL: {$msg}\n";
    exit(1);
}

try {
    ! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', 0);
    require BASE_PATH . '/tests/bootstrap.php';
} catch (Throwable $e) {
    echo 'FAIL: Hyperf bootstrap unavailable (' . $e->getMessage() . ")\n";
    exit(1);
}

/** @var CloudUpgradeService $svc */
$svc = ApplicationContext::getContainer()->get(CloudUpgradeService::class);

$tmpRoot = BASE_PATH . '/runtime/cloud-upgrade/safejoin-test-' . bin2hex(random_bytes(4));
if (! mkdir($tmpRoot, 0775, true) && ! is_dir($tmpRoot)) {
    upgFail('cannot create tmp root');
}

// --- safeJoin rejects .. ---
$rejected = false;
try {
    $svc->safeJoin($tmpRoot, '../etc/passwd');
} catch (RuntimeException $e) {
    $rejected = str_contains($e->getMessage(), '非法');
}
if (! $rejected) {
    upgFail('safeJoin should reject ../');
}
echo "safeJoin_reject_dotdot=ok\n";

$rejectedAbs = false;
try {
    $svc->safeJoin($tmpRoot, '/etc/passwd');
} catch (RuntimeException $e) {
    $rejectedAbs = str_contains($e->getMessage(), '非法');
}
if (! $rejectedAbs) {
    upgFail('safeJoin should reject absolute path');
}
echo "safeJoin_reject_abs=ok\n";

$okPath = $svc->safeJoin($tmpRoot, 'a/b/c.txt');
$expectedPrefix = str_replace('\\', '/', $tmpRoot) . '/';
$okNorm = str_replace('\\', '/', $okPath);
if (! str_starts_with($okNorm, $expectedPrefix) || ! str_ends_with($okNorm, 'a/b/c.txt')) {
    upgFail('safeJoin ok path unexpected: ' . $okPath);
}
echo "safeJoin_ok=ok\n";

// --- strict agreed parsing ---
$agreedCases = [
    [true, true],
    [1, true],
    ['true', true],
    ['1', true],
    [false, false],
    [0, false],
    ['false', false],
    ['0', false],
    ['', false],
    [null, false],
    ['yes', false],
];
foreach ($agreedCases as [$input, $expected]) {
    if (CloudUpgradeService::parseAgreed($input) !== $expected) {
        upgFail('parseAgreed unexpected for ' . var_export($input, true));
    }
}
echo "parseAgreed=ok\n";

$rejectedAgreed = false;
try {
    $svc->start('false');
} catch (InvalidArgumentException $e) {
    $rejectedAgreed = str_contains($e->getMessage(), '协议');
}
if (! $rejectedAgreed) {
    upgFail('start should reject agreed="false"');
}
echo "agreed_reject_false=ok\n";

// --- version read/write ---
$marker = '9.9.9-upgrade-smoke-' . time();
$before = $svc->currentVersion();
echo 'current_before=' . $before . "\n";

$svc->writeVersion($marker);
$after = $svc->currentVersion();
if ($after !== $marker) {
    upgFail("expected currentVersion={$marker}, got {$after}");
}
echo "version_write_read=ok version={$after}\n";

// restore previous if it looked like a real version; otherwise leave marker is fine for smoke
if ($before !== '' && ! str_contains($before, 'upgrade-smoke')) {
    $svc->writeVersion($before);
    echo 'version_restored=' . $svc->currentVersion() . "\n";
}

// cleanup tmp
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($tmpRoot, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);
foreach ($it as $item) {
    if ($item->isDir()) {
        @rmdir($item->getPathname());
    } else {
        @unlink($item->getPathname());
    }
}
@rmdir($tmpRoot);

echo "PASS\n";
exit(0);
