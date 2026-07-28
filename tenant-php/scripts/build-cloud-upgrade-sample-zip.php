<?php

declare(strict_types=1);

/**
 * Pack scripts/fixtures/cloud-upgrade-sample into runtime/cloud-upgrade-sample.zip
 * Skips .gitkeep so empty overlay dirs do not pollute tenant root.
 */

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));

$fixtureRoot = BASE_PATH . '/scripts/fixtures/cloud-upgrade-sample';
$outZip = BASE_PATH . '/runtime/cloud-upgrade-sample.zip';

if (! is_dir($fixtureRoot)) {
    fwrite(STDERR, "FAIL: fixture root missing: {$fixtureRoot}\n");
    exit(1);
}

$runtimeDir = dirname($outZip);
if (! is_dir($runtimeDir) && ! mkdir($runtimeDir, 0775, true) && ! is_dir($runtimeDir)) {
    fwrite(STDERR, "FAIL: cannot create runtime dir\n");
    exit(1);
}

if (is_file($outZip)) {
    @unlink($outZip);
}

$zip = new ZipArchive();
if ($zip->open($outZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "FAIL: cannot create zip: {$outZip}\n");
    exit(1);
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($fixtureRoot, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$added = 0;
foreach ($iterator as $item) {
    /** @var SplFileInfo $item */
    if ($item->isDir()) {
        continue;
    }
    $base = $item->getBasename();
    if ($base === '.gitkeep' || $base === '.DS_Store') {
        continue;
    }
    $full = $item->getPathname();
    $rel = substr($full, strlen($fixtureRoot) + 1);
    $rel = str_replace('\\', '/', $rel);
    if ($rel === false || $rel === '') {
        continue;
    }
    if (! $zip->addFile($full, $rel)) {
        $zip->close();
        @unlink($outZip);
        fwrite(STDERR, "FAIL: addFile {$rel}\n");
        exit(1);
    }
    ++$added;
}

$zip->close();

if ($added < 1 || ! is_file($outZip)) {
    fwrite(STDERR, "FAIL: zip empty or missing\n");
    exit(1);
}

$sha = hash_file('sha256', $outZip);
echo "zip={$outZip}\n";
echo "files={$added}\n";
echo 'sha256=' . ($sha ?: '') . "\n";
echo "PASS\n";
exit(0);
