<?php

declare(strict_types=1);

/**
 * Smoke: AppInstallService::installFromZip
 */
! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

use App\Library\App\AppManifest;
use App\Library\App\AppPath;
use App\Service\App\AppInstallService;

$src = BASE_PATH . '/apps/mineadmin/demo';
$zipPath = BASE_PATH . '/runtime/tmp/test-demo-app.zip';
$altId = 'mineadmin/demozip';
$altDir = AppPath::appDir($altId);

@mkdir(dirname($zipPath), 0755, true);
@unlink($zipPath);

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "FAIL create zip\n");
    exit(1);
}

$manifest = json_decode((string) file_get_contents($src . '/app.json'), true);
$manifest['name'] = $altId;
$manifest['title'] = 'Demo Zip';
$zip->addFromString('pack/app.json', json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
    /** @var SplFileInfo $file */
    if (! $file->isFile()) {
        continue;
    }
    $full = str_replace('\\', '/', $file->getPathname());
    $rel = substr($full, strlen(str_replace('\\', '/', $src)) + 1);
    if ($rel === 'app.json') {
        continue;
    }
    if (str_contains($rel, '/vendor/') || str_starts_with($rel, 'vendor/')) {
        continue;
    }
    if (str_contains($rel, '/storage/') || str_starts_with($rel, 'storage/')) {
        continue;
    }
    $zip->addFile($file->getPathname(), 'pack/' . $rel);
}
$zip->close();

$ref = new ReflectionClass(AppInstallService::class);
/** @var AppInstallService $svc */
$svc = $ref->newInstanceWithoutConstructor();

if (is_dir($altDir)) {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($altDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
    }
    @rmdir($altDir);
}

if (! $svc->zipLooksLikeApp($zipPath)) {
    fwrite(STDERR, "FAIL zipLooksLikeApp\n");
    exit(1);
}
echo "OK zipLooksLikeApp\n";

$id = $svc->installFromZip($zipPath, $altId);
if ($id !== $altId) {
    fwrite(STDERR, "FAIL identifier {$id}\n");
    exit(1);
}
$loaded = AppManifest::load($altId);
if (($loaded['name'] ?? '') !== $altId) {
    fwrite(STDERR, "FAIL load after install\n");
    exit(1);
}
echo "OK installFromZip\n";

// cleanup alt app
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($altDir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);
foreach ($it as $f) {
    $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
}
@rmdir($altDir);
@unlink($zipPath);

echo "PASS app install zip\n";
