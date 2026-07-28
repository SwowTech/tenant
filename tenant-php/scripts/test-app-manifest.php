<?php

declare(strict_types=1);

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

use App\Library\App\AppManifest;
use App\Library\App\AppPath;

$caught = false;
try {
    AppManifest::load('nope/missing');
} catch (Throwable) {
    $caught = true;
}
if (! $caught) {
    fwrite(STDERR, "FAIL expected missing app to throw\n");
    exit(1);
}
echo "OK missing throws\n";

$id = AppPath::assertSafeIdentifier('mineadmin/demo');
if ($id !== 'mineadmin/demo') {
    fwrite(STDERR, "FAIL identifier\n");
    exit(1);
}
echo "OK identifier\n";

$m = AppManifest::load('mineadmin/demo');
if (($m['name'] ?? '') !== 'mineadmin/demo') {
    fwrite(STDERR, "FAIL name\n");
    exit(1);
}
echo "OK load demo\n";

echo "PASS app manifest smoke\n";
